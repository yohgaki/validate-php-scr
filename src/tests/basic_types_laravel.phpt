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

$cases = [
    // mac_address
    ['mac_address', '00:1A:2B:3C:4D:5E', 'pass'],
    ['mac_address', '00-1A-2B-3C-4D-5E', 'pass'],
    ['mac_address', 'aa:bb:cc:dd:ee:ff', 'pass'],
    ['mac_address', 'gg:1A:2B:3C:4D:5E', 'fail'],
    ['mac_address', '00:1A:2B:3C:4D', 'fail'],

    // ulid
    ['ulid', '01ARZ3NDEKTSV4RRFFQ69G5FAV', 'pass'],
    ['ulid', '01arz3ndektsv4rrffq69g5fav', 'pass'],
    ['ulid', '01ARZ3NDEKTSV4RRFFQ69G5FAU', 'fail'],
    ['ulid', '01ARZ3NDEKTSV4RRFFQ69G5FA', 'fail'],

    // hex_color
    ['hex_color', '#fff', 'pass'],
    ['hex_color', '#FFFF', 'pass'],
    ['hex_color', '#FFFFFF', 'pass'],
    ['hex_color', '#FFFFFFFF', 'pass'],
    ['hex_color', '#FF', 'fail'],
    ['hex_color', 'FFFFFF', 'fail'],
    ['hex_color', '#GGG', 'fail'],

    // url (format only)
    ['url', 'https://example.com/path?q=1', 'pass'],
    ['url', 'http://example.com', 'pass'],
    ['url', 'not-a-url', 'fail'],

    // alpha_dash
    ['alpha_dash32', 'foo-bar_baz123', 'pass'],
    ['alpha_dash32', 'abc', 'pass'],
    ['alpha_dash32', 'foo bar', 'fail'],
    ['alpha_dash32', 'foo.bar', 'fail'],

    // ascii
    ['ascii64', 'Hello, World!', 'pass'],
    ['ascii64', 'abc XYZ 123 _-+', 'pass'],
    ['ascii64', "\xe3\x81\x82", 'fail'],

    // lowercase
    ['lowercase32', 'hello', 'pass'],
    ['lowercase32', 'Hello', 'fail'],
    ['lowercase32', 'hello1', 'fail'],

    // uppercase
    ['uppercase32', 'HELLO', 'pass'],
    ['uppercase32', 'Hello', 'fail'],
    ['uppercase32', 'HELLO1', 'fail'],

    // digits
    ['digits5', '12345', 'pass'],
    ['digits5', '00000', 'pass'],
    ['digits5', '1234', 'fail'],
    ['digits5', '123456', 'fail'],
    ['digits5', 'abcde', 'fail'],
    ['digits_between_1_10', '1', 'pass'],
    ['digits_between_1_10', '1234567890', 'pass'],
    ['digits_between_1_10', '', 'fail'],
    ['digits_between_1_10', '12345678901', 'fail'],

    // accepted
    ['accepted', 'yes', 'pass'],
    ['accepted', 'on', 'pass'],
    ['accepted', 'true', 'pass'],
    ['accepted', '1', 'pass'],
    ['accepted', 'no', 'fail'],
    ['accepted', 'maybe', 'fail'],

    // declined
    ['declined', 'no', 'pass'],
    ['declined', 'off', 'pass'],
    ['declined', 'false', 'pass'],
    ['declined', '0', 'pass'],
    ['declined', 'yes', 'fail'],

    // timezone
    ['timezone', 'UTC', 'pass'],
    ['timezone', 'Asia/Tokyo', 'pass'],
    ['timezone', 'America/New_York', 'pass'],
    ['timezone', 'Not/A/Zone', 'fail'],
    ['timezone', '', 'fail'],
];

foreach ($cases as [$type, $input, $expected]) {
    $ctx = null;
    $r = validate($ctx, $input, $basicTypes[$type], VALIDATE_OPT_DISABLE_EXCEPTION);
    $actual = ($ctx instanceof Validate && $ctx->getStatus()) ? 'pass' : 'fail';
    $mark = ($actual === $expected) ? 'OK' : 'NG';
    printf("[%s] %s(%s) => %s (expected %s)\n", $mark, $type, var_export($input, true), $actual, $expected);
}
echo "Done.\n";
?>
--EXPECTF--
[OK] mac_address('00:1A:2B:3C:4D:5E') => pass (expected pass)
[OK] mac_address('00-1A-2B-3C-4D-5E') => pass (expected pass)
[OK] mac_address('aa:bb:cc:dd:ee:ff') => pass (expected pass)
[OK] mac_address('gg:1A:2B:3C:4D:5E') => fail (expected fail)
[OK] mac_address('00:1A:2B:3C:4D') => fail (expected fail)
[OK] ulid('01ARZ3NDEKTSV4RRFFQ69G5FAV') => pass (expected pass)
[OK] ulid('01arz3ndektsv4rrffq69g5fav') => pass (expected pass)
[OK] ulid('01ARZ3NDEKTSV4RRFFQ69G5FAU') => fail (expected fail)
[OK] ulid('01ARZ3NDEKTSV4RRFFQ69G5FA') => fail (expected fail)
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
Done.
