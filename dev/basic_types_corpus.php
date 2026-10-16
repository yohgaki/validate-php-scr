<?php
/**
 * Branch comparison corpus: validates tricky inputs with every $basicTypes
 * spec and prints one line per (spec, input) with the result, the status
 * and the error messages.
 *
 * All branches (master, php-8.x, php-7.x, php-5.x) provide the same features.
 * Run this with each branch's src/ on each PHP version and compare the output
 * with master's; any difference is a porting bug (or a PHP difference to
 * document):
 *
 *   php dev/basic_types_corpus.php src > corpus-$(php -r 'echo PHP_VERSION;').txt
 *
 * The second argument limits the specs by a regex, e.g. '/^int/'. Specs whose
 * results depend on DNS or on the tzdata of the PHP build are skipped.
 *
 * Written in PHP 5.3 syntax so that the same file runs on every branch.
 */
error_reporting(-1);
ini_set('display_errors', '1');
if ($argc < 2) {
    fwrite(STDERR, "Usage: php basic_types_corpus.php <src dir> [spec name regex]\n");
    exit(2);
}
$src = $argv[1];
require_once $src.'/validate_func.php';
require_once $src.'/lib/basic_types.php';
$filter = isset($argv[2]) ? $argv[2] : null;

// Depend on DNS or the tzdata version of the PHP build.
$skip = array('active_url', 'email_dns', 'fqdn', 'timezone', 'timezone_all_with_bc');

$inputs = array(
    '', ' ', '0', '1', '-1', '+1', '01', '-0', '00', '1.5', '-1.5', '.5', '5.', '1e3', '1E-3', '0x1A', '0b11', '007',
    '127', '128', '-128', '-129', '255', '256', '32767', '32768', '65535', '65536',
    '2147483647', '2147483648', '-2147483648', '-2147483649', '4294967295', '4294967296',
    '9007199254740991', '9007199254740992', '9223372036854775807', '9223372036854775808',
    '-9223372036854775808', '-9223372036854775809', '18446744073709551615', '18446744073709551616',
    '170141183460469231731687303715884105727', '170141183460469231731687303715884105728',
    '340282366920938463463374607431768211455', '340282366920938463463374607431768211456',
    ' 1', '1 ', "1\n", "\t1", '1,000', '1_000', 'INF', 'NAN', '-INF',
    'abc', 'ABC', 'aBc_-', 'a b', 'a  b', "a\tb", "a\nb", "a\r\nb", "a\rb", "a\x00b", "a\x7fb", "a\x1bb",
    'true', 'TRUE', 'True', 'false', 'on', 'off', 'yes', 'no', 'y', 'n', 'null', 't', 'f',
    '2024-02-29', '2023-02-29', '2024-13-01', '2024-1-1', '2024-01-01T00:00:00Z', '2024-01-01 00:00:00',
    '2024-01-01T00:00:00.123+09:00', '2024-01-01T00:00:00.1234567890Z', '2024-01-01T24:00:00Z',
    '12:34', '12:34:56', '25:00', '12:60', '12:34:56.789', '23:59:60',
    '127.0.0.1', '256.1.1.1', '01.1.1.1', '1.2.3', '1.2.3.4.5', '0.0.0.0', '255.255.255.255',
    '::1', '::', '2001:db8::1', '2001:DB8::1', '::ffff:1.2.3.4', 'fe80::1%eth0', '1:2:3:4:5:6:7:8', '1:2:3:4:5:6:7:8:9', ':::1', '2001:db8::g',
    '192.168.0.0/16', '10.0.0.0/33', '10.0.0.0/0', '10.0.0.0/08', '10.0.0.0',
    'example.com', 'EXAMPLE.COM', 'example.com.', 'xn--p1ai', 'example.xn--p1ai', 'a.b-c.d', '-a.com', 'a-.com', 'a..b', 'a.b.c.d.e.f',
    str_repeat('a', 63).'.com', str_repeat('a', 64).'.com', 'localhost', 'localhost:8080', 'example.com:80', 'example.com:080',
    'example.com:65535', 'example.com:65536', 'example.com:0', '[::1]', '[::1]:80', '[::1', '::1:80', '1.2.3.4:443', '999.1.1.1',
    'user@example.com', 'a@b', 'user@@example.com', '"quoted"@example.com', 'user@[127.0.0.1]', 'user.name+tag@example.co.jp', '@example.com', 'user@',
    'http://example.com', 'https://example.com/a?b=c#d', 'HTTP://EXAMPLE.COM', 'ftp://example.com', 'http://', 'javascript:alert(1)',
    'http://exa mple.com', 'http://[::1]/', 'http://user:pass@example.com:8080/p', 'https://example.com/%7E', '//example.com',
    'YWJj', 'YQ==', 'YQ', 'YWI=', '====', 'a=b', 'YQ=', 'abcde', 'ab+/', 'ab-_', 'a%20b', 'a%zz', '%', '%2', 'a+b',
    '&amp;', '&lt;a&gt;', '&#x41;', '&', '<a>',
    '550e8400-e29b-41d4-a716-446655440000', '550E8400-E29B-41D4-A716-446655440000', '550e8400e29b41d4a716446655440000', '550e8400-e29b-41d4-a716-44665544000z',
    '01ARZ3NDEKTSV4RRFFQ69G5FAV', '7ZZZZZZZZZZZZZZZZZZZZZZZZZ', '8ZZZZZZZZZZZZZZZZZZZZZZZZZ', '01ARZ3NDEKTSV4RRFFQ69G5FAU',
    'deadbeef', 'DEADBEEF', 'xyz', str_repeat('a', 64), str_repeat('a', 65), str_repeat('f', 32),
    '{"a":1}', '[1,2]', '07', '[1.]', '-.1', '{"\u0000a":1}', '{"":1}', ' [1] ', "[\"a\tb\"]", '["\ud800"]', '["😀"]',
    '{"a":1', '""', 'null', ' true', '[1e999]', '[-0]', '{"a":1,"a":2}', "[1]\n", '[01]', '["\x7f"]',
    '00:1A:2B:3C:4D:5E', '00-1a-2b-3c-4d-5e', '001A.2B3C.4D5E', '00:1A-2B:3C:4D:5E', '#fff', '#FFFF', '#ffffff', '#GGG', 'fff',
    'my-slug', 'My-Slug', 'my_slug', '-slug', 'slug-', 'my--slug',
    'text/html', 'text/html; charset=UTF-8', 'application/json', 'Mozilla/5.0 (X11; Linux x86_64)', 'PHPSESSID=abc; lang=ja',
    str_repeat('a', 21), str_repeat('a', 22), str_repeat('a', 256), str_repeat('a', 257), str_repeat('a', 1025), str_repeat('x', 70000),
    "\xc3\xa9", "caf\xc3\xa9", "\xff", "\xc3", "\xed\xa0\x80", "\xf0\x9f\x98\x80", "\xe2\x80\x8b", "\xc0\xaf", "\xe0\x80\xaf", "\xf4\x90\x80\x80",
    "\xef\xbb\xbfabc", "\xe3\x81\x82\xe3\x81\x84", "\xc2\x85", "\xe2\x80\xae", "\xef\xbf\xbe",
    'password1', 'P@ssw0rd!', 'pass word', 'select', 'a1_b2', '1abc', str_repeat('i', 63), str_repeat('i', 64),
    'accepted', '1.0', '0.0', '-0.0', '1e-7', '123456789012345678901234567890.5', '0.1', '3,14', '3:14',
);
$nonStrings = array(
    'int(0)' => 0, 'int(1)' => 1, 'int(-1)' => -1, 'int(max)' => PHP_INT_MAX, 'float(1.5)' => 1.5, 'float(-0.0)' => 0.0 * -1, // PHP 5 compiles the literal -0.0 as +0.0
    'float(1e20)' => 1e20, 'bool(true)' => true, 'bool(false)' => false, 'null' => null, 'array()' => array(), "array('a')" => array('a'),
    'object' => new stdClass(),
);

function repr($v)
{
    if (is_string($v)) {
        if (strlen($v) > 40) {
            return 'str('.strlen($v).':'.md5($v).')';
        }
        return '"'.addcslashes($v, "\0..\37\42\134\177..\377").'"';
    }
    if (is_float($v)) {
        return 'float('.(is_nan($v) ? 'NAN' : sprintf('%.17g', $v)).')';
    }
    if (is_int($v)) {
        return (string)$v; // var_export(PHP_INT_MIN) differs between PHP versions
    }
    if (is_object($v)) {
        return 'object('.get_class($v).')';
    }
    if (is_array($v)) {
        $out = array();
        foreach ($v as $k => $e) {
            $out[] = repr($k).'=>'.repr($e);
        }
        return 'array('.implode(',', $out).')';
    }
    return var_export($v, true);
}

$types = array_keys($basicTypes);
sort($types);
foreach ($types as $type) {
    if (in_array($type, $skip, true) || ($filter !== null && !preg_match($filter, $type))) {
        continue;
    }
    $all = array();
    foreach ($inputs as $in) {
        $all[] = array(repr($in), $in);
    }
    foreach ($nonStrings as $label => $in) {
        $all[] = array($label, $in);
    }
    foreach ($all as $pair) {
        $label = $pair[0];
        $copy = $pair[1];
        $ctx = null;
        ob_start();
        try {
            $r = validate($ctx, $copy, $basicTypes[$type], VALIDATE_OPT_DISABLE_EXCEPTION);
            $status = validate_get_status($ctx) ? 'ok' : 'NG';
            $res = repr($r);
            $errs = validate_get_user_errors($ctx);
            $msgs = array();
            foreach ($errs['error'] as $p => $list) {
                foreach ((array)$list as $m) {
                    $msgs[] = $m;
                }
            }
            $sys = validate_get_system_errors($ctx);
            foreach (array('error', 'warning', 'notice') as $kind) {
                foreach ($sys[$kind] as $p => $list) {
                    foreach ((array)$list as $m) {
                        $msgs[] = $kind.':'.(is_array($m) ? (isset($m['message']) ? $m['message'] : repr($m)) : $m);
                    }
                }
            }
            $line = "$status $res ".implode(' || ', $msgs);
        } catch (Exception $e) {
            $line = 'EXCEPTION '.get_class($e).': '.$e->getMessage();
        } catch (Throwable $e) { // PHP 7+ Error; never matches on PHP 5
            $line = 'EXCEPTION '.get_class($e).': '.$e->getMessage();
        }
        $noise = ob_get_clean();
        if ($noise !== '') {
            $line .= ' OUTPUT['.trim(str_replace(array("\r", "\n"), ' ', $noise)).']';
        }
        echo "$type | $label => $line\n";
    }
}
