--TEST--
validate() INT edge branches - "values" without GMP, signs, AS_STRING range, overflow, RAW
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

echo "**** values option (int keys, no GMP required) ****\n";
$spec = array(VALIDATE_INT, VALIDATE_FLAG_NONE, array('values'=>array(1=>true, 5=>true)));
foreach (array(1, 2, '1', 'abc') as $var) {
    try {
        $copy = $var;
        var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** bool / null inputs ****\n";
$spec = array(VALIDATE_INT, VALIDATE_FLAG_NONE, array('min'=>0, 'max'=>100));
try {
    $copy = true;
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}
$spec = array(VALIDATE_INT, VALIDATE_FLAG_NULL, array('min'=>0, 'max'=>100));
try {
    $copy = null;
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** VALIDATE_INT_POSITIVE_SIGN ****\n";
$spec = array(VALIDATE_INT, VALIDATE_INT_POSITIVE_SIGN, array('min'=>0, 'max'=>100));
$copy = '+42';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** VALIDATE_FLAG_EMPTY ****\n";
$spec = array(VALIDATE_INT, VALIDATE_FLAG_EMPTY, array('min'=>0, 'max'=>100));
$copy = '';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
$spec = array(VALIDATE_INT, VALIDATE_INT_AS_STRING | VALIDATE_FLAG_EMPTY, array('min'=>0, 'max'=>100));
$copy = '';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** AS_STRING: big int range ****\n";
$spec = array(VALIDATE_INT, VALIDATE_INT_AS_STRING,
         array('min'=>0, 'max'=>'99999999999999999999999999'));
foreach (array('12345678901234567890123', '199999999999999999999999999') as $var) {
    try {
        $copy = $var;
        var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** AS_STRING + RAW ****\n";
$spec = array(VALIDATE_INT, VALIDATE_INT_AS_STRING | VALIDATE_FLAG_RAW, array('min'=>0, 'max'=>100));
$copy = '42';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** int overflow / float input ****\n";
$spec = array(VALIDATE_INT, VALIDATE_FLAG_NONE, array('min'=>0, 'max'=>PHP_INT_MAX));
foreach (array('9223372036854775808', 1.5) as $var) {
    try {
        $copy = $var;
        var_dump(validate($ctx, $copy, $spec, $func_opts));
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** out of range ****\n";
$spec = array(VALIDATE_INT, VALIDATE_FLAG_NONE, array('min'=>0, 'max'=>100));
try {
    $copy = '500';
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** RAW returns original string ****\n";
$spec = array(VALIDATE_INT, VALIDATE_FLAG_RAW, array('min'=>0, 'max'=>100));
$copy = '42';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

?>
--EXPECT--
**** values option (int keys, no GMP required) ****
int(1)
bool(true)
string(86) "param: 'ROOT' error: 'VALIDATE_INT: Failed to match defined option "values".' val: '2'"
bool(false)
int(1)
bool(true)
string(99) "param: 'ROOT' error: 'VALIDATE_INT: Failed option "values" match. Value is not integer.' val: 'abc'"
bool(false)

**** bool / null inputs ****
string(109) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.' val: '1'"
bool(false)
NULL

**** VALIDATE_INT_POSITIVE_SIGN ****
int(42)
bool(true)

**** VALIDATE_FLAG_EMPTY ****
NULL
bool(true)
string(0) ""
bool(true)

**** AS_STRING: big int range ****
string(23) "12345678901234567890123"
bool(true)
string(136) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "0" max: "99999999999999999999999999"' val: '199999999999999999999999999'"
bool(false)

**** AS_STRING + RAW ****
string(2) "42"
bool(true)

**** int overflow / float input ****
string(128) "param: 'ROOT' error: 'VALIDATE_INT: Overflow or malformed input. min: "0" max: "9223372036854775807"' val: '9223372036854775808'"
bool(false)
string(67) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '1.5'"
bool(false)

**** out of range ****
string(89) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "0" max: "100"' val: '500'"
bool(false)

**** RAW returns original string ****
string(2) "42"
bool(true)
