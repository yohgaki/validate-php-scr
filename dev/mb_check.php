<?php
/**
 * Measure what mbstring of this PHP version accepts, for README "Character
 * Encoding Validation".
 *
 *   php dev/mb_check.php
 *
 * 1. UTF-8: mb_check_encoding($s, 'UTF-8') against the RFC 3629 grammar on
 *    300,000 strings of 1 - 6 bytes (length cycles 1, 2, ..., 6). Each byte
 *    is one of 31 boundary values, picked by a linear congruential generator
 *    with a fixed seed, so every PHP version checks the same strings. Prints
 *    the number of disagreements and up to 5 of them.
 * 2. Results known to differ between PHP versions: 1 = valid, 0 = invalid,
 *    - = unknown encoding.
 *
 * Written in PHP 5.3 syntax so that the same file runs on every branch.
 */
error_reporting(-1);

$utf8 = '/\A(?:[\x00-\x7F]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'
    .'|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})*\z/';
$bytes = array(0x00, 0x41, 0x7F, 0x80, 0x8F, 0x90, 0x9F, 0xA0, 0xBF, 0xC0, 0xC1, 0xC2, 0xDF, 0xE0, 0xE1, 0xEC, 0xED,
               0xEE, 0xEF, 0xF0, 0xF1, 0xF3, 0xF4, 0xF5, 0xF7, 0xF8, 0xFB, 0xFC, 0xFD, 0xFE, 0xFF);
$n = count($bytes);
$seed = 12345;
$valid = 0;
$diff = array();
for ($i = 0; $i < 300000; $i++) {
    $s = '';
    for ($j = 0; $j <= $i % 6; $j++) {
        $seed = ($seed * 1103515245 + 12345) & 0x7FFFFFFF;
        $s .= chr($bytes[$seed % $n]);
    }
    $ref = (preg_match($utf8, $s) === 1);
    $valid += $ref ? 1 : 0;
    if ($ref !== mb_check_encoding($s, 'UTF-8')) {
        $diff[] = bin2hex($s).($ref ? ' (RFC 3629 valid)' : ' (RFC 3629 invalid)');
    }
}
echo 'PHP ', PHP_VERSION, "\n";
echo 'UTF-8: 300000 strings, ', $valid, ' valid, ', count($diff), " disagreements\n";
foreach (array_slice($diff, 0, 5) as $d) {
    echo '  ', $d, "\n";
}

$GLOBALS['unknown'] = false;
set_error_handler(function ($no, $str) {
    $GLOBALS['unknown'] = true;
    return true;
});
$cases = array(
    array('Windows-1252', "\x81"),
    array('Windows-1252', "\x8D"),
    array('Windows-1252', "\x8F"),
    array('Windows-1252', "\x90"),
    array('Windows-1252', "\x9D"),
    array('UTF-16', "\xFE\xFF\x30\x42"),
    array('UTF-16', "\x30\x42"),
    array('GB18030', "\x81\x30\x81\x30"),
);
foreach ($cases as $c) {
    $GLOBALS['unknown'] = false;
    $ok = false;
    try {
        $ok = mb_check_encoding($c[1], $c[0]);
    } catch (Throwable $e) {
        $GLOBALS['unknown'] = true; // ValueError (PHP 8); a warning before
    }
    printf("%-12s %-10s %s\n", $c[0], bin2hex($c[1]), $GLOBALS['unknown'] ? '-' : ($ok ? '1' : '0'));
}
