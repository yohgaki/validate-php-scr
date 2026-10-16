--TEST--
basicTypes format specs: sessid, base64, base64url, urle, time, datetime, domain, AS_STRING ints
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting=-1
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/../lib/basic_types.php';

// Table-driven test: [$basicTypes key, input value, expected outcome].
// Passing cases also print the result (type matters for the int specs).
$cases = [
    // sessid: session.sid_length accepts 22-256.
    ['sessid', str_repeat('a', 21), 'fail'],
    ['sessid', str_repeat('a', 22), 'pass'],
    ['sessid', 'abcdefghijklmnopqrstuvwxyz-,0123', 'pass'],
    ['sessid', str_repeat('a', 256), 'pass'],
    ['sessid', str_repeat('a', 257), 'fail'],

    // base64: '=' only as trailing padding of the last group; padding optional.
    ['base64_32', 'YWJj', 'pass'],
    ['base64_32', 'YQ==', 'pass'],
    ['base64_32', 'YQ', 'pass'],
    ['base64_32', 'YWI=', 'pass'],
    ['base64_32', 'ab+/', 'pass'],
    ['base64_32', '====', 'fail'],
    ['base64_32', 'a=b', 'fail'],
    ['base64_32', 'YQ=', 'fail'],
    ['base64_32', 'abcde', 'fail'],
    ['base64_32', 'ab-_', 'fail'],

    // base64url: '-' and '_' instead of '+' and '/'.
    ['base64url_32', 'ab-_', 'pass'],
    ['base64url_32', 'YQ', 'pass'],
    ['base64url_32', 'YQ==', 'pass'],
    ['base64url_32', '====', 'fail'],
    ['base64url_32', 'ab+/', 'fail'],

    // urle: '%' only as a "%HH" escape.
    ['urle32', 'a%20b', 'pass'],
    ['urle32', 'A%2Fb+c-_.~', 'pass'],
    ['urle32', 'a%zz', 'fail'],
    ['urle32', '%', 'fail'],
    ['urle32', '%2', 'fail'],
    ['urle32', 'a b', 'fail'],

    // time: optional seconds, optional 1-9 digit fraction after seconds.
    ['time', '12:34', 'pass'],
    ['time', '12:34:56', 'pass'],
    ['time', '12:34:56.789', 'pass'],
    ['time', '12:34:56.123456789', 'pass'],
    ['time', '12:34.5', 'fail'],
    ['time', '12:34:56.', 'fail'],
    ['time', '12:34:56.1234567890', 'fail'],
    ['time', '24:00', 'fail'],

    // datetime: JavaScript toISOString() output and RFC 3339 offsets.
    ['datetime', '2025-01-02T03:04:05.678Z', 'pass'],
    ['datetime', '2025-01-02T03:04:05.123456789+09:00', 'pass'],
    ['datetime', '2025-01-02 03:04', 'pass'],
    ['datetime', '2025-01-02T03:04:05-05:00', 'pass'],
    ['datetime', '2025-01-02T03:04.5Z', 'fail'],
    ['datetime', '2025-01-02T03:04:05.Z', 'fail'],
    ['datetime', '2025-13-02T03:04', 'fail'],

    // domain: alphabetic TLD or IDNA A-label TLD.
    ['domain', 'example.com', 'pass'],
    ['domain', 'sub.example.co.jp', 'pass'],
    ['domain', 'example.xn--p1ai', 'pass'],
    ['domain', 'xn--80ak6aa92e.xn--p1ai', 'pass'],
    ['domain', 'example.XN--P1AI', 'pass'],
    ['domain', 'example.xn--', 'fail'],
    ['domain', 'example.xn--a-', 'fail'],
    ['domain', 'example.123', 'fail'],
    ['domain', 'example.c', 'fail'],
    ['domain', 'localhost', 'fail'],

    // VALIDATE_INT_AS_STRING returns a string for int input too.
    ['uint64', 5, 'pass'],
    ['uint64', PHP_INT_MAX, 'pass'],
    ['uint64', '18446744073709551615', 'pass'],
    ['uint64', '18446744073709551616', 'fail'],
    ['uint64', -1, 'fail'],
    ['uint128', 7, 'pass'],
    ['int64', 5, 'pass'], // native int spec on 64-bit builds
];

foreach ($cases as [$type, $input, $expected]) {
    $ctx = null;
    $r = validate($ctx, $input, $basicTypes[$type], VALIDATE_OPT_DISABLE_EXCEPTION);
    $actual = ($ctx instanceof Validate && $ctx->getStatus()) ? 'pass' : 'fail';
    $mark = ($actual === $expected) ? 'OK' : 'NG';
    $shown = is_string($input) && strlen($input) > 20 && strspn($input, 'a') === strlen($input)
             ? 'str_repeat(\''.$input[0].'\', '.strlen($input).')' : var_export($input, true);
    printf("[%s] %s(%s) => %s (expected %s)%s\n", $mark, $type, $shown, $actual, $expected,
           $actual === 'pass' ? ' '.gettype($r) : '');
}

echo "\n**** VALIDATE_INT_AS_STRING with 'values' ****\n";
$spec = [VALIDATE_INT, VALIDATE_INT_AS_STRING, ['values' => [1 => true, 3 => true]]];
$int = 3;
$str = '1';
var_dump(validate($ctx, $int, $spec), validate($ctx, $str, $spec));
$spec[VALIDATE_FLAGS] |= VALIDATE_FLAG_RAW;
var_dump(validate($ctx, $int, $spec));
echo "Done.\n";
?>
--EXPECTF--
[OK] sessid(str_repeat('a', 21)) => fail (expected fail)
[OK] sessid(str_repeat('a', 22)) => pass (expected pass) string
[OK] sessid('abcdefghijklmnopqrstuvwxyz-,0123') => pass (expected pass) string
[OK] sessid(str_repeat('a', 256)) => pass (expected pass) string
[OK] sessid(str_repeat('a', 257)) => fail (expected fail)
[OK] base64_32('YWJj') => pass (expected pass) string
[OK] base64_32('YQ==') => pass (expected pass) string
[OK] base64_32('YQ') => pass (expected pass) string
[OK] base64_32('YWI=') => pass (expected pass) string
[OK] base64_32('ab+/') => pass (expected pass) string
[OK] base64_32('====') => fail (expected fail)
[OK] base64_32('a=b') => fail (expected fail)
[OK] base64_32('YQ=') => fail (expected fail)
[OK] base64_32('abcde') => fail (expected fail)
[OK] base64_32('ab-_') => fail (expected fail)
[OK] base64url_32('ab-_') => pass (expected pass) string
[OK] base64url_32('YQ') => pass (expected pass) string
[OK] base64url_32('YQ==') => pass (expected pass) string
[OK] base64url_32('====') => fail (expected fail)
[OK] base64url_32('ab+/') => fail (expected fail)
[OK] urle32('a%20b') => pass (expected pass) string
[OK] urle32('A%2Fb+c-_.~') => pass (expected pass) string
[OK] urle32('a%zz') => fail (expected fail)
[OK] urle32('%') => fail (expected fail)
[OK] urle32('%2') => fail (expected fail)
[OK] urle32('a b') => fail (expected fail)
[OK] time('12:34') => pass (expected pass) string
[OK] time('12:34:56') => pass (expected pass) string
[OK] time('12:34:56.789') => pass (expected pass) string
[OK] time('12:34:56.123456789') => pass (expected pass) string
[OK] time('12:34.5') => fail (expected fail)
[OK] time('12:34:56.') => fail (expected fail)
[OK] time('12:34:56.1234567890') => fail (expected fail)
[OK] time('24:00') => fail (expected fail)
[OK] datetime('2025-01-02T03:04:05.678Z') => pass (expected pass) string
[OK] datetime('2025-01-02T03:04:05.123456789+09:00') => pass (expected pass) string
[OK] datetime('2025-01-02 03:04') => pass (expected pass) string
[OK] datetime('2025-01-02T03:04:05-05:00') => pass (expected pass) string
[OK] datetime('2025-01-02T03:04.5Z') => fail (expected fail)
[OK] datetime('2025-01-02T03:04:05.Z') => fail (expected fail)
[OK] datetime('2025-13-02T03:04') => fail (expected fail)
[OK] domain('example.com') => pass (expected pass) string
[OK] domain('sub.example.co.jp') => pass (expected pass) string
[OK] domain('example.xn--p1ai') => pass (expected pass) string
[OK] domain('xn--80ak6aa92e.xn--p1ai') => pass (expected pass) string
[OK] domain('example.XN--P1AI') => pass (expected pass) string
[OK] domain('example.xn--') => fail (expected fail)
[OK] domain('example.xn--a-') => fail (expected fail)
[OK] domain('example.123') => fail (expected fail)
[OK] domain('example.c') => fail (expected fail)
[OK] domain('localhost') => fail (expected fail)
[OK] uint64(5) => pass (expected pass) string
[OK] uint64(%d) => pass (expected pass) string
[OK] uint64('18446744073709551615') => pass (expected pass) string
[OK] uint64('18446744073709551616') => fail (expected fail)
[OK] uint64(-1) => fail (expected fail)
[OK] uint128(7) => pass (expected pass) string
[OK] int64(5) => pass (expected pass) %s

**** VALIDATE_INT_AS_STRING with 'values' ****
string(1) "3"
string(1) "1"
int(3)
Done.
