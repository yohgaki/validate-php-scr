--TEST--
validate() simple scalars - all tests should fail
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

echo "\n**** validate() NULL ****\n";
$spec = array(
    VALIDATE_NULL,
    VALIDATE_FLAG_NULL,
    array(),
);

$values = array(1, 0, true, false, 'a', 'null', new StdClass);
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() BOOL ****\n";
$spec = array(
    VALIDATE_BOOL,
    VALIDATE_BOOL_TF | VALIDATE_BOOL_ON_OFF | VALIDATE_BOOL_TRUE_FALSE | VALIDATE_BOOL_YES_NO | VALIDATE_BOOL_01,
    array(),
);
$values = array(null, new StdClass, '', 't ', ' T', ' on', 'oN ', ' true', 'tRue ', 2, '2', -1);
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() INT ****\n";
$spec = array(
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    array('min' => 0, 'max' => '99999999999999999999999999999999999999999999999'),
);
$values = array(-1, '', null, true, false, new StdClass, '1234abc',  '99999999999999999999999999999999999999999999999e99999');
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() INT2 ****\n";
$spec = array(
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    array('min' => 0, 'max' => '99999999999999999999999999999999999999999999999'),
);
$values = array(-1, '', null, true, false, new StdClass, '1234abc',  '99999999999999999999999999999999999999999999999e99999');
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() FLOAT ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    // 'length' raised so the long invalid-format inputs reach the syntax
    // check instead of being rejected up-front for length.
    array('min' => -0.1, 'max' => '99999999999999999999999999999999999999999999999', 'length' => 100),
);
$values = array(-1, '', null, true, false, new StdClass, '1234abc', 'abc1234',  'e9999999999999999999999999999999999999999999999999');
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() STRING ****\n";
$spec = array(
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    array('min' => 2, 'max' => 5),
);
$values = array(0, '', null, true, false, new StdClass, '', 12.3456789, 123456789, '123456',  '99999999999999999999999999999999999999999999999', "<\b\t\n\r>");
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}


?>
--EXPECT--
**** validate() NULL ****
string(53) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.'"
bool(false)
string(53) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.'"
bool(false)
string(53) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.'"
bool(false)
string(53) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.'"
bool(false)
string(53) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.'"
bool(false)
string(53) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.'"
bool(false)
string(85) "param: 'ROOT' error: 'VALIDATE_NULL: Array or object parameter is passed for scalar.'"
bool(false)

**** validate() BOOL ****
string(72) "param: 'ROOT' error: 'VALIDATE_BOOL: NULL input is rejected by default.'"
bool(false)
string(85) "param: 'ROOT' error: 'VALIDATE_BOOL: Array or object parameter is passed for scalar.'"
bool(false)
string(50) "param: 'ROOT' error: 'VALIDATE_BOOL: Empty input.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
bool(false)

**** validate() INT ****
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
string(71) "param: 'ROOT' error: 'VALIDATE_INT: NULL input is rejected by default.'"
bool(false)
string(100) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(100) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)

**** validate() INT2 ****
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
string(71) "param: 'ROOT' error: 'VALIDATE_INT: NULL input is rejected by default.'"
bool(false)
string(100) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(100) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)

**** validate() FLOAT ****
string(128) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-0.1" max: "99999999999999999999999999999999999999999999999"'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_FLOAT: Empty input.'"
bool(false)
string(73) "param: 'ROOT' error: 'VALIDATE_FLOAT: NULL input is rejected by default.'"
bool(false)
string(102) "param: 'ROOT' error: 'VALIDATE_FLOAT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(102) "param: 'ROOT' error: 'VALIDATE_FLOAT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(86) "param: 'ROOT' error: 'VALIDATE_FLOAT: Array or object parameter is passed for scalar.'"
bool(false)
string(60) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.'"
bool(false)
string(60) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.'"
bool(false)
string(60) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.'"
bool(false)

**** validate() STRING ****
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"'"
bool(false)
string(74) "param: 'ROOT' error: 'VALIDATE_STRING: NULL input is rejected by default.'"
bool(false)
string(103) "param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(103) "param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(87) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"'"
bool(false)
