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
$spec = [
    VALIDATE_NULL,
    VALIDATE_FLAG_NULL,
    [],
];

$values = [1, 0, true, false, 'a', 'null', new StdClass];
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() BOOL ****\n";
$spec = [
    VALIDATE_BOOL,
    VALIDATE_BOOL_TF | VALIDATE_BOOL_ON_OFF | VALIDATE_BOOL_TRUE_FALSE | VALIDATE_BOOL_YES_NO | VALIDATE_BOOL_01,
    [],
];
$values = [null, new StdClass, '', 't ', ' T', ' on', 'oN ', ' true', 'tRue ', 2, '2', -1];
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() INT ****\n";
$spec = [
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    ['min' => 0, 'max' => '99999999999999999999999999999999999999999999999'],
];
$values = [-1, '', null, true, false, new StdClass, '1234abc',  '99999999999999999999999999999999999999999999999e99999'];
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() INT2 ****\n";
$spec = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => '99999999999999999999999999999999999999999999999'],
];
$values = [-1, '', null, true, false, new StdClass, '1234abc',  '99999999999999999999999999999999999999999999999e99999'];
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() FLOAT ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => -0.1, 'max' => '99999999999999999999999999999999999999999999999'],
];
$values = [-1, '', null, true, false, new StdClass, '1234abc', 'abc1234',  'e9999999999999999999999999999999999999999999999999'];
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() STRING ****\n";
$spec = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 2, 'max' => 5],
];
$values = [0, '', null, true, false, new StdClass, '', 12.3456789, 123456789, '123456',  '99999999999999999999999999999999999999999999999', "<\b\t\n\r>"];
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
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: '1'"
bool(false)
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: '0'"
bool(false)
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: '1'"
bool(false)
string(61) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: ''"
bool(false)
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: 'a'"
bool(false)
string(65) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: 'null'"
bool(false)
string(112) "param: 'ROOT' error: 'VALIDATE_NULL: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)

**** validate() BOOL ****
string(82) "param: 'ROOT' error: 'VALIDATE_BOOL: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(112) "param: 'ROOT' error: 'VALIDATE_BOOL: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)
string(58) "param: 'ROOT' error: 'VALIDATE_BOOL: Empty input.' val: ''"
bool(false)
string(61) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: 't '"
bool(false)
string(61) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: ' T'"
bool(false)
string(62) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: ' on'"
bool(false)
string(62) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: 'oN '"
bool(false)
string(64) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: ' true'"
bool(false)
string(64) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: 'tRue '"
bool(false)
string(60) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: '2'"
bool(false)
string(60) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: '2'"
bool(false)
string(61) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: '-1'"
bool(false)

**** validate() INT ****
string(66) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '-1'"
bool(false)
string(64) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: ''"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_INT: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(109) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.' val: '1'"
bool(false)
string(108) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.' val: ''"
bool(false)
string(111) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)
string(71) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '1234abc'"
bool(false)
string(117) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '99999999999999999999999999999999999999999999999e99999'"
bool(false)

**** validate() INT2 ****
string(66) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '-1'"
bool(false)
string(64) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: ''"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_INT: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(109) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.' val: '1'"
bool(false)
string(108) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.' val: ''"
bool(false)
string(111) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)
string(71) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '1234abc'"
bool(false)
string(117) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '99999999999999999999999999999999999999999999999e99999'"
bool(false)

**** validate() FLOAT ****
string(138) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-0.1" max: "99999999999999999999999999999999999999999999999"' val: '-1'"
bool(false)
string(61) "param: 'ROOT' error: 'VALIDATE_FLOAT: Empty input.' val: 'N;'"
bool(false)
string(83) "param: 'ROOT' error: 'VALIDATE_FLOAT: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(111) "param: 'ROOT' error: 'VALIDATE_FLOAT: Bool value cannot be treated as valid value for this validator.' val: '1'"
bool(false)
string(110) "param: 'ROOT' error: 'VALIDATE_FLOAT: Bool value cannot be treated as valid value for this validator.' val: ''"
bool(false)
string(113) "param: 'ROOT' error: 'VALIDATE_FLOAT: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)
string(75) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '1234abc'"
bool(false)
string(75) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: 'abc1234'"
bool(false)
string(118) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: 'e9999999999999999999999999999999999999999999999999'"
bool(false)

**** validate() STRING ****
string(90) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"' val: '0'"
bool(false)
string(89) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"' val: ''"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_STRING: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(112) "param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'"
bool(false)
string(111) "param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: ''"
bool(false)
string(114) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)
string(89) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"' val: ''"
bool(false)
string(99) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"' val: '12.3456789'"
bool(false)
string(98) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"' val: '123456789'"
bool(false)
string(95) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"' val: '123456'"
bool(false)
string(136) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"' val: '99999999999999999999999999999999999999999999999'"
bool(false)
string(96) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "5"' val: '<\b	
>'"
bool(false)
