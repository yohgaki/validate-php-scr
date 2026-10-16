--TEST--
basicTypes format specs: sessid, base64, base64url, urle, time, datetime, domain, http_host, AS_STRING ints
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
$cases = array(
    // sessid: session.sid_length accepts 22-256.
    array('sessid', str_repeat('a', 21), 'fail'),
    array('sessid', str_repeat('a', 22), 'pass'),
    array('sessid', 'abcdefghijklmnopqrstuvwxyz-,0123', 'pass'),
    array('sessid', str_repeat('a', 256), 'pass'),
    array('sessid', str_repeat('a', 257), 'fail'),

    // base64: '=' only as trailing padding of the last group; padding optional.
    array('base64_32', 'YWJj', 'pass'),
    array('base64_32', 'YQ==', 'pass'),
    array('base64_32', 'YQ', 'pass'),
    array('base64_32', 'YWI=', 'pass'),
    array('base64_32', 'ab+/', 'pass'),
    array('base64_32', '====', 'fail'),
    array('base64_32', 'a=b', 'fail'),
    array('base64_32', 'YQ=', 'fail'),
    array('base64_32', 'abcde', 'fail'),
    array('base64_32', 'ab-_', 'fail'),

    // base64url: '-' and '_' instead of '+' and '/'.
    array('base64url_32', 'ab-_', 'pass'),
    array('base64url_32', 'YQ', 'pass'),
    array('base64url_32', 'YQ==', 'pass'),
    array('base64url_32', '====', 'fail'),
    array('base64url_32', 'ab+/', 'fail'),

    // urle: '%' only as a "%HH" escape.
    array('urle32', 'a%20b', 'pass'),
    array('urle32', 'A%2Fb+c-_.~', 'pass'),
    array('urle32', 'a%zz', 'fail'),
    array('urle32', '%', 'fail'),
    array('urle32', '%2', 'fail'),
    array('urle32', 'a b', 'fail'),

    // time: optional seconds, optional 1-9 digit fraction after seconds.
    array('time', '12:34', 'pass'),
    array('time', '12:34:56', 'pass'),
    array('time', '12:34:56.789', 'pass'),
    array('time', '12:34:56.123456789', 'pass'),
    array('time', '12:34.5', 'fail'),
    array('time', '12:34:56.', 'fail'),
    array('time', '12:34:56.1234567890', 'fail'),
    array('time', '24:00', 'fail'),

    // datetime: JavaScript toISOString() output and RFC 3339 offsets.
    array('datetime', '2025-01-02T03:04:05.678Z', 'pass'),
    array('datetime', '2025-01-02T03:04:05.123456789+09:00', 'pass'),
    array('datetime', '2025-01-02 03:04', 'pass'),
    array('datetime', '2025-01-02T03:04:05-05:00', 'pass'),
    array('datetime', '2025-01-02T03:04.5Z', 'fail'),
    array('datetime', '2025-01-02T03:04:05.Z', 'fail'),
    array('datetime', '2025-13-02T03:04', 'fail'),

    // domain: alphabetic TLD or IDNA A-label TLD.
    array('domain', 'example.com', 'pass'),
    array('domain', 'sub.example.co.jp', 'pass'),
    array('domain', 'example.xn--p1ai', 'pass'),
    array('domain', 'xn--80ak6aa92e.xn--p1ai', 'pass'),
    array('domain', 'example.XN--P1AI', 'pass'),
    array('domain', 'example.xn--', 'fail'),
    array('domain', 'example.xn--a-', 'fail'),
    array('domain', 'example.123', 'fail'),
    array('domain', 'example.c', 'fail'),
    array('domain', 'localhost', 'fail'),

    // http_host: host name or IP address with an optional port.
    array('http_host', 'example.com', 'pass'),
    array('http_host', 'example.com:8080', 'pass'),
    array('http_host', 'localhost', 'pass'),
    array('http_host', '127.0.0.1:8080', 'pass'),
    array('http_host', '[::1]', 'pass'),
    array('http_host', '[2001:db8::1]:443', 'pass'),
    array('http_host', 'example.com:0', 'fail'),
    array('http_host', 'example.com:65536', 'fail'),
    array('http_host', 'example.com:080', 'fail'),
    array('http_host', 'example.com:', 'fail'),
    array('http_host', 'example.com.', 'fail'),
    array('http_host', '999.1.1.1', 'fail'),
    array('http_host', '010.0.0.1', 'fail'),
    array('http_host', '[::1', 'fail'),
    array('http_host', '::1', 'fail'),
    array('http_host', 'a_b.com', 'fail'),
    array('http_host', 'user@example.com', 'fail'),

    // VALIDATE_INT_AS_STRING returns a string for int input too.
    array('uint64', 5, 'pass'),
    array('uint64', PHP_INT_MAX, 'pass'),
    array('uint64', '18446744073709551615', 'pass'),
    array('uint64', '18446744073709551616', 'fail'),
    array('uint64', -1, 'fail'),
    array('uint128', 7, 'pass'),
    array('int64', 5, 'pass'), // native int spec on 64-bit builds
    // url: the PHP 8.2+ FILTER_VALIDATE_URL rules on every PHP version
    array('url', 'http://example.com:0/', 'pass'),
    array('url', 'http://[2001:db8::1]:8080/', 'pass'),
    array('url', 'http://user@[::1]/', 'pass'),
    array('url', 'http://a@b@[::1]/', 'fail'),
    array('url', 'http://a@b@example.com/', 'fail'),
    array('url', 'http://example.com\\@evil.com/', 'fail'),
    array('url', 'http://%41b:c@example.com/', 'pass'),
    array('url', 'http://example-/', 'fail'),
    array('url', 'http://example..com/', 'fail'),
    array('url', 'http://example.com./', 'pass'),
    array('url', 'http://example.com:65536/', 'fail'),
    array('url', 'http://[1.2.3.4]/', 'fail'),
);

foreach ($cases as $case) {
    list($type, $input, $expected) = $case; // foreach (... as list()) is PHP 5.5+
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
$spec = array(VALIDATE_INT, VALIDATE_INT_AS_STRING, array('values' => array(1 => true, 3 => true)));
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
[OK] http_host('example.com') => pass (expected pass) string
[OK] http_host('example.com:8080') => pass (expected pass) string
[OK] http_host('localhost') => pass (expected pass) string
[OK] http_host('127.0.0.1:8080') => pass (expected pass) string
[OK] http_host('[::1]') => pass (expected pass) string
[OK] http_host('[2001:db8::1]:443') => pass (expected pass) string
[OK] http_host('example.com:0') => fail (expected fail)
[OK] http_host('example.com:65536') => fail (expected fail)
[OK] http_host('example.com:080') => fail (expected fail)
[OK] http_host('example.com:') => fail (expected fail)
[OK] http_host('example.com.') => fail (expected fail)
[OK] http_host('999.1.1.1') => fail (expected fail)
[OK] http_host('010.0.0.1') => fail (expected fail)
[OK] http_host('[::1') => fail (expected fail)
[OK] http_host('::1') => fail (expected fail)
[OK] http_host('a_b.com') => fail (expected fail)
[OK] http_host('user@example.com') => fail (expected fail)
[OK] uint64(5) => pass (expected pass) string
[OK] uint64(%d) => pass (expected pass) string
[OK] uint64('18446744073709551615') => pass (expected pass) string
[OK] uint64('18446744073709551616') => fail (expected fail)
[OK] uint64(-1) => fail (expected fail)
[OK] uint128(7) => pass (expected pass) string
[OK] int64(5) => pass (expected pass) %s
[OK] url('http://example.com:0/') => pass (expected pass) string
[OK] url('http://[2001:db8::1]:8080/') => pass (expected pass) string
[OK] url('http://user@[::1]/') => pass (expected pass) string
[OK] url('http://a@b@[::1]/') => fail (expected fail)
[OK] url('http://a@b@example.com/') => fail (expected fail)
[OK] url('http://example.com\\@evil.com/') => fail (expected fail)
[OK] url('http://%41b:c@example.com/') => pass (expected pass) string
[OK] url('http://example-/') => fail (expected fail)
[OK] url('http://example..com/') => fail (expected fail)
[OK] url('http://example.com./') => pass (expected pass) string
[OK] url('http://example.com:65536/') => fail (expected fail)
[OK] url('http://[1.2.3.4]/') => fail (expected fail)

**** VALIDATE_INT_AS_STRING with 'values' ****
string(1) "3"
string(1) "1"
int(3)
Done.
