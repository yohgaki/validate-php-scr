--TEST--
validate() VALIDATE_MULTI edge branches - OR success/failure, AND failure, null input, filter
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

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION;

$int_spec = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>100]];
$str_spec = [VALIDATE_STRING, VALIDATE_STRING_ALPHA, ['min'=>1, 'max'=>10]];

echo "**** OR: second spec matches ****\n";
$spec = [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$int_spec, $str_spec]];
$ctx = validate_init();
$copy = 'abc';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** OR: all specs fail ****\n";
$ctx = validate_init();
$copy = '###';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** AND: all pass ****\n";
$digit_spec = [VALIDATE_STRING, VALIDATE_STRING_DIGIT, ['min'=>1, 'max'=>10]];
$spec = [VALIDATE_MULTI, VALIDATE_MULTI_AND, [], [$int_spec, $digit_spec]];
$ctx = validate_init();
$copy = '42';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** AND: first spec fails ****\n";
$ctx = validate_init();
$copy = 'abc';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** null input is rejected ****\n";
$ctx = validate_init();
$copy = null;
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** filter applied before sub-specs ****\n";
$spec = [VALIDATE_MULTI, VALIDATE_MULTI_AND,
         ['filter'=>function ($ctx, $input, &$error) { return trim($input); }],
         [$int_spec]];
$ctx = validate_init();
$copy = '  42  ';
var_dump(validate($ctx, $copy, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());

echo "\n**** filter error ****\n";
$spec = [VALIDATE_MULTI, VALIDATE_MULTI_AND,
         ['filter'=>function ($ctx, $input, &$error) { $error = 'multi filter rejected'; return null; }],
         [$int_spec]];
$ctx = validate_init();
$copy = '42';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
var_dump($ctx->getSystemErrors()['error']['ROOT'][0]['message']);

?>
--EXPECT--
**** OR: second spec matches ****
string(3) "abc"
bool(true)

**** OR: all specs fail ****
NULL
bool(false)

**** AND: all pass ****
string(2) "42"
bool(true)

**** AND: first spec fails ****
NULL
bool(false)

**** null input is rejected ****
NULL
bool(false)

**** filter applied before sub-specs ****
int(42)
bool(true)

**** filter error ****
NULL
bool(false)
string(50) "VALIDATE_MULTI filter error: multi filter rejected"
