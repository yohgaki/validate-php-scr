--TEST--
$basicTypes specs - uuid filter, email/json format callbacks, HTTP header trim filter
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

$opts = VALIDATE_OPT_DISABLE_EXCEPTION;

function try_type($name, $value)
{
    global $basicTypes, $opts;
    $ctx = validate_init();
    $copy = $value;
    $result = validate($ctx, $copy, $basicTypes[$name], $opts);
    $user = validate_get_user_errors($ctx)['error'];
    printf("%s(%s): ", $name, is_scalar($value) ? var_export($value, true) : gettype($value));
    var_dump($result);
    foreach ($user as $errs) {
        foreach ((array)$errs as $msg) {
            echo "  user error: $msg\n";
        }
    }
    return $ctx;
}

echo "**** uuid (filter lowercases; non-string input hits filter error) ****\n";
try_type('uuid', '550E8400-E29B-41D4-A716-446655440000');
try_type('uuid', 550);

echo "\n**** email format callback ****\n";
try_type('email', 'user@example.com');
try_type('email', 'not-an-email');

echo "\n**** json callback ****\n";
try_type('json', '{"a":1}');
try_type('json', '{broken');
// Fixed messages per json_last_error() code, so they do not depend on the PHP
// version. Objects decode to arrays: '\u0000' starting a key is valid JSON.
$jsonCases = [
    'nesting 511'    => str_repeat('[', 511).str_repeat(']', 511),
    'nesting 512'    => str_repeat('[', 512).str_repeat(']', 512),
    'tab in string'  => "[\"a\tb\"]",
    'lone surrogate' => '["\ud800"]',
    'NUL key'        => '{"\u0000a":1}',
    'empty key'      => '{"":1}',
    'spaces'         => " [1] ",
    'TRUE'           => 'TRUE',
    'leading zero'   => '07',
    'trailing dot'   => '[1.]',
    'leading dot'    => '-.1',
];
foreach ($jsonCases as $label => $json) {
    $ctx = validate_init();
    $r = validate($ctx, $json, $basicTypes['json'], $opts);
    echo "json($label): ", $r === null ? 'NULL' : 'valid', "\n";
    foreach (validate_get_user_errors($ctx)['error'] as $errs) {
        foreach ((array)$errs as $msg) {
            echo "  user error: $msg\n";
        }
    }
}

echo "\n**** header64 (vtrim filter) ****\n";
try_type('header64', "  text/html  ");
$ctx = validate_init();
$copy = ['x'];
var_dump(validate($ctx, $copy, $basicTypes['header64'], $opts));

echo "\n**** url (format-only failure path) ****\n";
try_type('url', 'notaurl!');

?>
--EXPECT--
**** uuid (filter lowercases; non-string input hits filter error) ****
uuid('550E8400-E29B-41D4-A716-446655440000'): string(36) "550e8400-e29b-41d4-a716-446655440000"
uuid(550): NULL

**** email format callback ****
email('user@example.com'): string(16) "user@example.com"
email('not-an-email'): NULL
  user error: Invalid email address format.

**** json callback ****
json('{"a":1}'): string(7) "{"a":1}"
json('{broken'): NULL
  user error: Invalid JSON: Syntax error
json(nesting 511): valid
json(nesting 512): NULL
  user error: Invalid JSON: Maximum stack depth exceeded
json(tab in string): NULL
  user error: Invalid JSON: Control character error, possibly incorrectly encoded
json(lone surrogate): NULL
  user error: Invalid JSON: Single unpaired UTF-16 surrogate in unicode escape
json(NUL key): valid
json(empty key): valid
json(spaces): valid
json(TRUE): NULL
  user error: Invalid JSON: Syntax error
json(leading zero): NULL
  user error: Invalid JSON: Syntax error
json(trailing dot): NULL
  user error: Invalid JSON: Syntax error
json(leading dot): NULL
  user error: Invalid JSON: Syntax error

**** header64 (vtrim filter) ****
header64('  text/html  '): string(9) "text/html"
NULL

**** url (format-only failure path) ****
url('notaurl!'): NULL
  user error: Invalid URL format.
