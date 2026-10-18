--TEST--
validate() min and max - all tests should fail
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

echo "\n**** validate() INT ****\n";
$spec = [
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    ['min' => -100, 'max' => 100],
];
$values = [null, true, false, [], new StdClass, function() {return true;},  '', -101, 101, '-100.5', '101.5'];
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
    ['min' => -100, 'max' => 100],
];
$values = [null, true, false, [], new StdClass, function() {return true;}, '', -101, 101, '-100.5', '100.5'];
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
    ['min' => 2, 'max' => 3],
];
$values = [null, true, false, [], new StdClass, function() {return true;}, 0, '', 'abcd'];
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** validate() ARRAY ****\n";
$spec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 2, 'max' => 3],
    [
        0 => [VALIDATE_INT, 0, ['min'=>-100, 'max'=>100]],
        1 => [VALIDATE_INT, 0, ['min'=>-100, 'max'=>100]],
        2 => [VALIDATE_INT, 0, ['min'=>-100, 'max'=>100]],
        3 => [VALIDATE_INT, 0, ['min'=>-100, 'max'=>100]],
    ]
];
$values = [null, true, false, [], new StdClass, function() {return true;}, 0, '', 'abcd', [1], [1,2,3,4]];
foreach ($values as $val) {
    try {
        var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

?>
--EXPECT--
**** validate() INT ****
string(71) "param: 'ROOT' error: 'VALIDATE_INT: NULL input is rejected by default.'"
bool(false)
string(100) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(100) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-100" max: "100"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-100" max: "100"'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)

**** validate() FLOAT ****
string(73) "param: 'ROOT' error: 'VALIDATE_FLOAT: NULL input is rejected by default.'"
bool(false)
string(102) "param: 'ROOT' error: 'VALIDATE_FLOAT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(102) "param: 'ROOT' error: 'VALIDATE_FLOAT: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(86) "param: 'ROOT' error: 'VALIDATE_FLOAT: Array or object parameter is passed for scalar.'"
bool(false)
string(86) "param: 'ROOT' error: 'VALIDATE_FLOAT: Array or object parameter is passed for scalar.'"
bool(false)
string(86) "param: 'ROOT' error: 'VALIDATE_FLOAT: Array or object parameter is passed for scalar.'"
bool(false)
string(51) "param: 'ROOT' error: 'VALIDATE_FLOAT: Empty input.'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-100" max: "100"'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-100" max: "100"'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-100" max: "100"'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-100" max: "100"'"
bool(false)

**** validate() STRING ****
string(74) "param: 'ROOT' error: 'VALIDATE_STRING: NULL input is rejected by default.'"
bool(false)
string(103) "param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(103) "param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.'"
bool(false)
string(87) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.'"
bool(false)
string(87) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.'"
bool(false)
string(87) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "3"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "3"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "3"'"
bool(false)

**** validate() ARRAY ****
string(65) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. '"
bool(false)
string(65) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. '"
bool(false)
string(65) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. '"
bool(false)
string(80) "param: 'ROOT' error: 'VALIDATE_ARRAY: Count out of range. min: 2 max: 3 count 0'"
bool(false)
string(65) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. '"
bool(false)
string(65) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. '"
bool(false)
string(65) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. '"
bool(false)
string(65) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. '"
bool(false)
string(65) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. '"
bool(false)
string(80) "param: 'ROOT' error: 'VALIDATE_ARRAY: Count out of range. min: 2 max: 3 count 1'"
bool(false)
string(80) "param: 'ROOT' error: 'VALIDATE_ARRAY: Count out of range. min: 2 max: 3 count 4'"
bool(false)
