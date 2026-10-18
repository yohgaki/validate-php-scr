--TEST--
validate() STRING edge branches - "values" option, RAW, strict CRLF, encoding, empty/default handling
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

$func_opts = VALIDATE_OPT_NONE;

echo "**** values option ****\n";
$spec = [VALIDATE_STRING, VALIDATE_FLAG_NONE, ['values'=>['red'=>true, 'blue'=>true]]];
foreach (['red', 'green', ['red']] as $var) {
    try {
        $copy = $var;
        var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** bool input is rejected ****\n";
$spec = [VALIDATE_STRING, VALIDATE_STRING_ALPHA, ['min'=>0, 'max'=>10]];
try {
    $copy = true;
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** VALIDATE_FLAG_EMPTY_TO_DEFAULT ****\n";
$spec = [VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_FLAG_EMPTY_TO_DEFAULT,
         ['min'=>0, 'max'=>10, 'default'=>'fallback']];
$copy = '';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

// Broken spec: missing "default". Spec check is off (VALIDATE_OPT_NONE).
$spec = [VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_FLAG_EMPTY_TO_DEFAULT,
         ['min'=>0, 'max'=>10]];
try {
    $copy = '';
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** invalid UTF-8 encoding with VALIDATE_STRING_MB ****\n";
$spec = [VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB, ['min'=>0, 'max'=>10]];
try {
    $copy = "a\xFF\xFEb"; // broken UTF-8
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** strict CRLF (CR and LF flags without CRLF_MIXED) ****\n";
$spec = [VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_STRING_CRLF, ['min'=>0, 'max'=>10]];
foreach (["a\r\nb", "a\nb", "a\rb"] as $var) {
    try {
        $copy = $var;
        var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** VALIDATE_FLAG_RAW returns original input ****\n";
$spec = [VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_FLAG_RAW, ['min'=>0, 'max'=>10]];
$copy = 'abc';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

?>
--EXPECT--
**** values option ****
string(3) "red"
bool(true)
string(80) "param: 'ROOT' error: 'VALIDATE_STRING: Failed to match defined option "values".'"
bool(false)
string(87) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.'"
bool(false)

**** bool input is rejected ****
string(103) "param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.'"
bool(false)

**** VALIDATE_FLAG_EMPTY_TO_DEFAULT ****
string(8) "fallback"
bool(true)
string(106) "param: 'ROOT' error: 'Invalid validation spec: empty value cannot be used without proper "default" value.'"
bool(false)

**** invalid UTF-8 encoding with VALIDATE_STRING_MB ****
string(63) "param: 'ROOT' error: 'VALIDATE_STRING: Invalid UTF-8 encoding.'"
bool(false)

**** strict CRLF (CR and LF flags without CRLF_MIXED) ****
string(4) "a
b"
bool(true)
string(60) "param: 'ROOT' error: 'VALIDATE_STRING: Invalid LF detected.'"
bool(false)
string(63) "param: 'ROOT' error: 'VALIDATE_STRING: Invalid CR/LF detected.'"
bool(false)

**** VALIDATE_FLAG_RAW returns original input ****
string(3) "abc"
bool(true)
