<?php
/**
 * Fuzz inputs for the json and url specs of basic_types.php, to compare
 * branches and PHP versions (see also dev/basic_types_corpus.php).
 *
 *   php dev/fuzz_inputs.php gen json|url <seed> <count> > in.txt
 *   php dev/fuzz_inputs.php run <src dir> json|url in.txt > out.txt
 *
 * "gen" prints one base64 encoded input per line. Run it on PHP 7.1+: the
 * mt_rand() sequence of older PHP differs. "run" validates each input with
 * $basicTypes['json'] or $basicTypes['url'] and prints "ok", or "NG" and the
 * error messages. Compare the "run" output of a branch on an old PHP with
 * that of master on a current PHP.
 *
 * Written in PHP 5.3 syntax so that the same file runs on every branch.
 */
if ($argc < 5 || !in_array($argv[1], array('gen', 'run'), true)) {
    fwrite(STDERR, "Usage: php fuzz_inputs.php gen json|url <seed> <count>\n"
        ."       php fuzz_inputs.php run <src dir> json|url <input file>\n");
    exit(2);
}

if ($argv[1] === 'run') {
    error_reporting(-1);
    require $argv[2].'/validate_func.php';
    require $argv[2].'/lib/basic_types.php';
    foreach (file($argv[4], FILE_IGNORE_NEW_LINES) as $line) {
        $in = base64_decode($line);
        $ctx = null;
        $r = validate($ctx, $in, $basicTypes[$argv[3]], VALIDATE_OPT_DISABLE_EXCEPTION);
        $msgs = array();
        $errors = validate_get_user_errors($ctx);
        foreach ($errors['error'] as $list) {
            foreach ((array)$list as $m) {
                $msgs[] = $m;
            }
        }
        if (!$msgs) {
            $errors = validate_get_system_errors($ctx);
            foreach ($errors['error'] as $list) {
                foreach ((array)$list as $m) {
                    $msgs[] = is_array($m) ? $m['message'] : $m;
                }
            }
        }
        echo $r === null ? 'NG '.implode(' | ', $msgs) : 'ok', "\n";
    }
    exit(0);
}

mt_srand((int)$argv[3]);
if ($argv[2] === 'json') {
    gen_json_inputs((int)$argv[4]);
} else {
    gen_url_inputs((int)$argv[4]);
}

// ---- json: random JSON-like values, some of them mutated --------------------

function gen_json_value($d)
{
    $r = mt_rand(0, 99);
    if ($d < 6 && $r < 18) {
        $k = mt_rand(0, 4);
        $a = array();
        for ($i = 0; $i < $k; $i++) {
            $a[] = gen_json_value($d + 1);
        }
        return '['.implode(mt_rand(0, 5) ? ',' : ' , ', $a).']';
    }
    if ($d < 6 && $r < 33) {
        $k = mt_rand(0, 3);
        $a = array();
        for ($i = 0; $i < $k; $i++) {
            $a[] = gen_json_string().(mt_rand(0, 4) ? ':' : ' : ').gen_json_value($d + 1);
        }
        return '{'.implode(',', $a).'}';
    }
    if ($r < 55) {
        return gen_json_string();
    }
    if ($r < 80) {
        return gen_json_number();
    }
    $l = array('true', 'false', 'null', 'TRUE', 'True', 'nul', 'tru', 'falsey');
    return $l[mt_rand(0, mt_rand(0, 9) ? 2 : 7)];
}

function gen_json_number()
{
    $s = mt_rand(0, 3) ? '' : '-';
    $p = array('0', '7', '12', '007', '1', '999999999999999999999', '');
    $s .= $p[mt_rand(0, count($p) - 1)];
    if (!mt_rand(0, 3)) {
        $f = array('.5', '.', '.0', '.25');
        $s .= $f[mt_rand(0, 3)];
    }
    if (!mt_rand(0, 4)) {
        $e = array('e5', 'E+2', 'e-3', 'e', 'e+', 'E400');
        $s .= $e[mt_rand(0, 5)];
    }
    if (!mt_rand(0, 15)) {
        $s = '+'.$s;
    }
    if (!mt_rand(0, 15)) {
        $s = '.'.$s;
    }
    return $s === '' || $s === '-' ? '0' : $s;
}

function gen_json_string()
{
    $parts = array('a', 'b', ' ', 'xyz', "\xc3\xa9", "\xf0\x9f\x98\x80", '\\n', '\\"', '\\\\', '\\/', '\\u0041', '\\u00e9',
        '\\ud83d\\ude00', '\\ud800', '\\udc00', '\\ud800\\u0041', '\\uD800\\uDBFF', '\\uZZZZ', '\\u12', '\\x',
        "\t", "\n", "\r", '\\', '\\u0000');
    $k = mt_rand(0, 4);
    $s = '"';
    for ($i = 0; $i < $k; $i++) {
        $s .= $parts[mt_rand(0, mt_rand(0, 3) ? 10 : count($parts) - 1)];
    }
    return $s.(mt_rand(0, 30) ? '"' : '');
}

function gen_json_inputs($n)
{
    $alpha = str_split("[]{}:,\" \t\n\r\\0123456789.eE+-tfnrulsaxu");
    for ($i = 0; $i < $n; $i++) {
        $j = gen_json_value(0);
        if (!mt_rand(0, 3)) {
            $j = (mt_rand(0, 1) ? ' ' : "\n").$j.(mt_rand(0, 1) ? ' ' : "\t");
        }
        $m = mt_rand(0, 2) ? 0 : mt_rand(1, 3);
        for ($k = 0; $k < $m; $k++) {
            $pos = mt_rand(0, strlen($j));
            $op = mt_rand(0, 2);
            $c = $alpha[mt_rand(0, count($alpha) - 1)];
            if ($op === 0) {
                $j = substr($j, 0, $pos).$c.substr($j, $pos);
            } elseif ($op === 1 && $pos < strlen($j)) {
                $j = substr($j, 0, $pos).substr($j, $pos + 1);
            } elseif ($pos < strlen($j)) {
                $j = substr($j, 0, $pos).$c.substr($j, $pos + 1);
            }
        }
        if (!mt_rand(0, 200)) { // nesting around the depth limit
            $d = mt_rand(509, 513);
            $j = str_repeat('[', $d).(mt_rand(0, 1) ? '' : '1').str_repeat(']', $d - mt_rand(0, 1));
        }
        if (!mt_rand(0, 300)) {
            $d = mt_rand(509, 513);
            $j = str_repeat('{"a":', $d).'1'.str_repeat('}', $d).(mt_rand(0, 1) ? '' : 'x');
        }
        if (strlen($j) < 2) {
            $j .= ' ';
        }
        echo base64_encode($j), "\n";
    }
}

// ---- url: random URL-like strings, some of them mutated ---------------------

function url_pick($a)
{
    return $a[mt_rand(0, count($a) - 1)];
}

function url_chars($chars, $max)
{
    $s = '';
    $k = mt_rand(0, $max);
    for ($i = 0; $i < $k; $i++) {
        $s .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $s;
}

function gen_url_inputs($n)
{
    $sym = '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
    for ($i = 0; $i < $n; $i++) {
        $u = url_pick(array('http', 'https', 'HTTP', 'Https', 'http', 'https', 'ftp', 'javascript', 'h_ttp', 'http+x', ''))
            .url_pick(array('://', '://', '://', '://', ':/', ':', ':///', '//'));
        if (mt_rand(0, 3) === 0) { // userinfo
            $u .= url_chars('abAB01-._~!$&\'()*+,;=:%@[]\\#/?', 8).url_pick(array('@', '@', ':@', '@@'));
        }
        $r = mt_rand(0, 9);
        if ($r < 5) {
            $labels = array();
            $k = mt_rand(1, 4);
            for ($j = 0; $j < $k; $j++) {
                $labels[] = mt_rand(0, 20) ? url_chars('abcz019-_', mt_rand(0, 1) ? 6 : 66) : '';
            }
            $h = implode('.', $labels);
            if (!mt_rand(0, 5)) {
                $h .= '.';
            }
            if (!mt_rand(0, 30)) {
                $h = str_repeat('a.', mt_rand(120, 130)).'b';
            }
        } elseif ($r < 7) {
            $h = url_pick(array('127.0.0.1', '256.1.1.1', '1.2.3', '01.1.1.1', '0.0.0.0', '1.2.3.4.5'));
        } else {
            $h = '['.url_pick(array('::1', '::', '2001:db8::1', '::ffff:1.2.3.4', 'fe80::1%25eth0', '1:2:3:4:5:6:7:8',
                                    '1:2:3:4:5:6:7:8:9', 'g::1', '', '1.2.3.4', 'v1.x', ':::'))
                .url_pick(array(']', ']', ']', '', ']]'));
        }
        $u .= $h;
        if (!mt_rand(0, 2)) {
            $u .= url_pick(array(':', ':0', ':00', ':80', ':8080', ':65535', ':65536', ':+80', ':-1', ':8a', ':123456',
                                 ':0x1', '::80', ':80:80'));
        }
        if (mt_rand(0, 1)) {
            $u .= '/'.url_chars("abc019-._~%/?#@:[]{}|\\^`<>\"'()*+,;=&$!", 12);
        }
        if (!mt_rand(0, 4)) {
            $u .= '?'.url_chars("ab=&%#?/:@[]{}|\\^`<>\"", 8);
        }
        if (!mt_rand(0, 5)) {
            $u .= '#'.url_chars("ab#?/:@[]{}|\\^`<>\"", 8);
        }
        $m = mt_rand(0, 3) ? 0 : mt_rand(1, 2);
        for ($k = 0; $k < $m; $k++) {
            $pos = mt_rand(0, strlen($u));
            $c = $sym[mt_rand(0, strlen($sym) - 1)];
            $u = mt_rand(0, 1) ? substr($u, 0, $pos).$c.substr($u, $pos) : substr($u, 0, $pos).substr($u, $pos + 1);
        }
        echo base64_encode($u), "\n";
    }
}
