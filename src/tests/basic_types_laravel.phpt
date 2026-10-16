--TEST--
Laravel-compatible basicTypes validators (no DNS)
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting=E_ALL & ~E_DEPRECATED
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/../lib/basic_types.php';

// Table-driven test: [$basicTypes key, input value, expected outcome].
// Covers the Laravel-compatible single-value validators that don't need
// network access. DNS-bound rules ('active_url', 'email_dns') live in
// basic_types_laravel_dns.phpt.
$cases = array(
    // mac_address
    array('mac_address', '00:1A:2B:3C:4D:5E', 'pass'),
    array('mac_address', '00-1A-2B-3C-4D-5E', 'pass'),
    array('mac_address', 'aa:bb:cc:dd:ee:ff', 'pass'),
    array('mac_address', 'gg:1A:2B:3C:4D:5E', 'fail'),
    array('mac_address', '00:1A:2B:3C:4D', 'fail'),
    array('mac_address', '00:1A-2B:3C:4D:5E', 'fail'), // mixed separators
    array('mac_address', '00-1A-2B-3C-4D:5E', 'fail'),

    // ulid
    array('ulid', '01ARZ3NDEKTSV4RRFFQ69G5FAV', 'pass'),
    array('ulid', '01arz3ndektsv4rrffq69g5fav', 'pass'),
    array('ulid', '01ARZ3NDEKTSV4RRFFQ69G5FAU', 'fail'),
    array('ulid', '01ARZ3NDEKTSV4RRFFQ69G5FA', 'fail'),
    array('ulid', '7ZZZZZZZZZZZZZZZZZZZZZZZZZ', 'pass'), // 2^128-1
    array('ulid', '8ZZZZZZZZZZZZZZZZZZZZZZZZZ', 'fail'), // > 128 bits

    // hex_color
    array('hex_color', '#fff', 'pass'),
    array('hex_color', '#FFFF', 'pass'),
    array('hex_color', '#FFFFFF', 'pass'),
    array('hex_color', '#FFFFFFFF', 'pass'),
    array('hex_color', '#FF', 'fail'),
    array('hex_color', 'FFFFFF', 'fail'),
    array('hex_color', '#GGG', 'fail'),

    // url (format only)
    array('url', 'https://example.com/path?q=1', 'pass'),
    array('url', 'http://example.com', 'pass'),
    array('url', 'not-a-url', 'fail'),

    // alpha_dash
    array('alpha_dash32', 'foo-bar_baz123', 'pass'),
    array('alpha_dash32', 'abc', 'pass'),
    array('alpha_dash32', 'foo bar', 'fail'),
    array('alpha_dash32', 'foo.bar', 'fail'),

    // ascii
    array('ascii64', 'Hello, World!', 'pass'),
    array('ascii64', 'abc XYZ 123 _-+', 'pass'),
    array('ascii64', "\xe3\x81\x82", 'fail'),

    // lowercase
    array('lowercase32', 'hello', 'pass'),
    array('lowercase32', 'Hello', 'fail'),
    array('lowercase32', 'hello1', 'fail'),

    // uppercase
    array('uppercase32', 'HELLO', 'pass'),
    array('uppercase32', 'Hello', 'fail'),
    array('uppercase32', 'HELLO1', 'fail'),

    // digits
    array('digits5', '12345', 'pass'),
    array('digits5', '00000', 'pass'),
    array('digits5', '1234', 'fail'),
    array('digits5', '123456', 'fail'),
    array('digits5', 'abcde', 'fail'),
    array('digits_between_1_10', '1', 'pass'),
    array('digits_between_1_10', '1234567890', 'pass'),
    array('digits_between_1_10', '', 'fail'),
    array('digits_between_1_10', '12345678901', 'fail'),

    // accepted
    array('accepted', 'yes', 'pass'),
    array('accepted', 'on', 'pass'),
    array('accepted', 'true', 'pass'),
    array('accepted', '1', 'pass'),
    array('accepted', 'no', 'fail'),
    array('accepted', 'maybe', 'fail'),

    // declined
    array('declined', 'no', 'pass'),
    array('declined', 'off', 'pass'),
    array('declined', 'false', 'pass'),
    array('declined', '0', 'pass'),
    array('declined', 'yes', 'fail'),

    // timezone
    array('timezone', 'UTC', 'pass'),
    array('timezone', 'Asia/Tokyo', 'pass'),
    array('timezone', 'America/New_York', 'pass'),
    array('timezone', 'Not/A/Zone', 'fail'),
    array('timezone', '', 'fail'),
    array('timezone', 'US/Eastern', 'fail'),    // backward-compatible link
    array('timezone', 'Asia/Calcutta', 'fail'), // renamed (Asia/Kolkata)
    array('timezone', array('UTC'), 'fail'),

    // timezone_all_with_bc (Laravel 'timezone:all_with_bc')
    array('timezone_all_with_bc', 'Asia/Tokyo', 'pass'),
    array('timezone_all_with_bc', 'US/Eastern', 'pass'),
    array('timezone_all_with_bc', 'Asia/Calcutta', 'pass'),
    array('timezone_all_with_bc', 'Etc/GMT+5', 'pass'),
    array('timezone_all_with_bc', 'Not/A/Zone', 'fail'),
);

foreach ($cases as $case) {
    list($type, $input, $expected) = $case; // foreach (... as list()) is PHP 5.5+
    $ctx = null;
    $r = validate($ctx, $input, $basicTypes[$type], VALIDATE_OPT_DISABLE_EXCEPTION);
    $actual = ($ctx instanceof Validate && $ctx->getStatus()) ? 'pass' : 'fail';
    $mark = ($actual === $expected) ? 'OK' : 'NG';
    printf("[%s] %s(%s) => %s (expected %s)\n", $mark, $type, str_replace("\n", '', var_export($input, true)), $actual, $expected);
}
echo "Done.\n";
?>
--EXPECTF--
[OK] mac_address('00:1A:2B:3C:4D:5E') => pass (expected pass)
[OK] mac_address('00-1A-2B-3C-4D-5E') => pass (expected pass)
[OK] mac_address('aa:bb:cc:dd:ee:ff') => pass (expected pass)
[OK] mac_address('gg:1A:2B:3C:4D:5E') => fail (expected fail)
[OK] mac_address('00:1A:2B:3C:4D') => fail (expected fail)
[OK] mac_address('00:1A-2B:3C:4D:5E') => fail (expected fail)
[OK] mac_address('00-1A-2B-3C-4D:5E') => fail (expected fail)
[OK] ulid('01ARZ3NDEKTSV4RRFFQ69G5FAV') => pass (expected pass)
[OK] ulid('01arz3ndektsv4rrffq69g5fav') => pass (expected pass)
[OK] ulid('01ARZ3NDEKTSV4RRFFQ69G5FAU') => fail (expected fail)
[OK] ulid('01ARZ3NDEKTSV4RRFFQ69G5FA') => fail (expected fail)
[OK] ulid('7ZZZZZZZZZZZZZZZZZZZZZZZZZ') => pass (expected pass)
[OK] ulid('8ZZZZZZZZZZZZZZZZZZZZZZZZZ') => fail (expected fail)
[OK] hex_color('#fff') => pass (expected pass)
[OK] hex_color('#FFFF') => pass (expected pass)
[OK] hex_color('#FFFFFF') => pass (expected pass)
[OK] hex_color('#FFFFFFFF') => pass (expected pass)
[OK] hex_color('#FF') => fail (expected fail)
[OK] hex_color('FFFFFF') => fail (expected fail)
[OK] hex_color('#GGG') => fail (expected fail)
[OK] url('https://example.com/path?q=1') => pass (expected pass)
[OK] url('http://example.com') => pass (expected pass)
[OK] url('not-a-url') => fail (expected fail)
[OK] alpha_dash32('foo-bar_baz123') => pass (expected pass)
[OK] alpha_dash32('abc') => pass (expected pass)
[OK] alpha_dash32('foo bar') => fail (expected fail)
[OK] alpha_dash32('foo.bar') => fail (expected fail)
[OK] ascii64('Hello, World!') => pass (expected pass)
[OK] ascii64('abc XYZ 123 _-+') => pass (expected pass)
[OK] ascii64('%s') => fail (expected fail)
[OK] lowercase32('hello') => pass (expected pass)
[OK] lowercase32('Hello') => fail (expected fail)
[OK] lowercase32('hello1') => fail (expected fail)
[OK] uppercase32('HELLO') => pass (expected pass)
[OK] uppercase32('Hello') => fail (expected fail)
[OK] uppercase32('HELLO1') => fail (expected fail)
[OK] digits5('12345') => pass (expected pass)
[OK] digits5('00000') => pass (expected pass)
[OK] digits5('1234') => fail (expected fail)
[OK] digits5('123456') => fail (expected fail)
[OK] digits5('abcde') => fail (expected fail)
[OK] digits_between_1_10('1') => pass (expected pass)
[OK] digits_between_1_10('1234567890') => pass (expected pass)
[OK] digits_between_1_10('') => fail (expected fail)
[OK] digits_between_1_10('12345678901') => fail (expected fail)
[OK] accepted('yes') => pass (expected pass)
[OK] accepted('on') => pass (expected pass)
[OK] accepted('true') => pass (expected pass)
[OK] accepted('1') => pass (expected pass)
[OK] accepted('no') => fail (expected fail)
[OK] accepted('maybe') => fail (expected fail)
[OK] declined('no') => pass (expected pass)
[OK] declined('off') => pass (expected pass)
[OK] declined('false') => pass (expected pass)
[OK] declined('0') => pass (expected pass)
[OK] declined('yes') => fail (expected fail)
[OK] timezone('UTC') => pass (expected pass)
[OK] timezone('Asia/Tokyo') => pass (expected pass)
[OK] timezone('America/New_York') => pass (expected pass)
[OK] timezone('Not/A/Zone') => fail (expected fail)
[OK] timezone('') => fail (expected fail)
[OK] timezone('US/Eastern') => fail (expected fail)
[OK] timezone('Asia/Calcutta') => fail (expected fail)
[OK] timezone(array (  0 => 'UTC',)) => fail (expected fail)
[OK] timezone_all_with_bc('Asia/Tokyo') => pass (expected pass)
[OK] timezone_all_with_bc('US/Eastern') => pass (expected pass)
[OK] timezone_all_with_bc('Asia/Calcutta') => pass (expected pass)
[OK] timezone_all_with_bc('Etc/GMT+5') => pass (expected pass)
[OK] timezone_all_with_bc('Not/A/Zone') => fail (expected fail)
Done.
