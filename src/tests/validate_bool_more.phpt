--TEST--
validate() BOOL edge branches - yes/no, AS_STRING, RAW, EMPTY, invalid inputs
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

echo "**** yes/no ****\n";
$spec = array(VALIDATE_BOOL, VALIDATE_BOOL_YES_NO, array());
foreach (array('yes', 'no', 'maybe') as $var) {
    try {
        $copy = $var;
        var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** on/off, true/false, t/f as string ****\n";
$spec = array(VALIDATE_BOOL, VALIDATE_BOOL_ON_OFF | VALIDATE_BOOL_AS_STRING, array());
foreach (array('on', 'off') as $var) {
    $copy = $var;
    var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
}
$spec = array(VALIDATE_BOOL, VALIDATE_BOOL_TF, array());
foreach (array('T', 'f') as $var) {
    $copy = $var;
    var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
}

echo "\n**** RAW returns original ****\n";
$spec = array(VALIDATE_BOOL, VALIDATE_BOOL_TRUE_FALSE | VALIDATE_FLAG_RAW, array());
$copy = 'TRUE';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** VALIDATE_FLAG_EMPTY ****\n";
$spec = array(VALIDATE_BOOL, VALIDATE_BOOL_01 | VALIDATE_FLAG_EMPTY, array());
$copy = '';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
$spec = array(VALIDATE_BOOL, VALIDATE_BOOL_01 | VALIDATE_BOOL_AS_STRING | VALIDATE_FLAG_EMPTY, array());
$copy = '';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** invalid inputs ****\n";
$spec = array(VALIDATE_BOOL, VALIDATE_BOOL_01, array());
foreach (array('toolong', array(1)) as $var) {
    try {
        $copy = $var;
        var_dump(validate($ctx, $copy, $spec, $func_opts));
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

?>
--EXPECT--
**** yes/no ****
bool(true)
bool(true)
bool(false)
bool(true)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)

**** on/off, true/false, t/f as string ****
string(2) "on"
bool(true)
string(3) "off"
bool(true)
bool(true)
bool(true)
bool(false)
bool(true)

**** RAW returns original ****
string(4) "TRUE"
bool(true)

**** VALIDATE_FLAG_EMPTY ****
NULL
bool(true)
string(0) ""
bool(true)

**** invalid inputs ****
string(52) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid input.'"
bool(false)
string(85) "param: 'ROOT' error: 'VALIDATE_BOOL: Array or object parameter is passed for scalar.'"
bool(false)
