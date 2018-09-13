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
string(81) "param: 'ROOT' error: 'VALIDATE_INT: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(109) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.' val: '1'"
bool(false)
string(108) "param: 'ROOT' error: 'VALIDATE_INT: Bool value cannot be treated as valid value for this validator.' val: ''"
bool(false)
string(98) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.' val: 'a:0:{}'"
bool(false)
string(111) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)
string(41) "Serialization of 'Closure' is not allowed"
bool(false)
string(64) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: ''"
bool(false)
string(93) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-100" max: "100"' val: '-101'"
bool(false)
string(92) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-100" max: "100"' val: '101'"
bool(false)
string(70) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '-100.5'"
bool(false)
string(69) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '101.5'"
bool(false)

**** validate() FLOAT ****
string(83) "param: 'ROOT' error: 'VALIDATE_FLOAT: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(111) "param: 'ROOT' error: 'VALIDATE_FLOAT: Bool value cannot be treated as valid value for this validator.' val: '1'"
bool(false)
string(110) "param: 'ROOT' error: 'VALIDATE_FLOAT: Bool value cannot be treated as valid value for this validator.' val: ''"
bool(false)
string(100) "param: 'ROOT' error: 'VALIDATE_FLOAT: Array or object parameter is passed for scalar.' val: 'a:0:{}'"
bool(false)
string(113) "param: 'ROOT' error: 'VALIDATE_FLOAT: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)
string(41) "Serialization of 'Closure' is not allowed"
bool(false)
string(61) "param: 'ROOT' error: 'VALIDATE_FLOAT: Empty input.' val: 'N;'"
bool(false)
string(96) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-100" max: "100"' val: '-101'"
bool(false)
string(95) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-100" max: "100"' val: '101'"
bool(false)
string(98) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-100" max: "100"' val: '-100.5'"
bool(false)
string(97) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-100" max: "100"' val: '100.5'"
bool(false)

**** validate() STRING ****
string(84) "param: 'ROOT' error: 'VALIDATE_STRING: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(112) "param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'"
bool(false)
string(111) "param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: ''"
bool(false)
string(101) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.' val: 'a:0:{}'"
bool(false)
string(114) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)
string(41) "Serialization of 'Closure' is not allowed"
bool(false)
string(90) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "3"' val: '0'"
bool(false)
string(89) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "3"' val: ''"
bool(false)
string(93) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "3"' val: 'abcd'"
bool(false)

**** validate() ARRAY ****
string(75) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. ' val: 'N;'"
bool(false)
string(74) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. ' val: '1'"
bool(false)
string(73) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. ' val: ''"
bool(false)
string(89) "param: 'ROOT' error: 'VALIDATE_ARRAY: Count out of rage. min: 2 max: 3 count 0' val: 'N;'"
bool(false)
string(92) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. ' val: 'O:8:"stdClass":0:{}'"
bool(false)
string(41) "Serialization of 'Closure' is not allowed"
bool(false)
string(74) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. ' val: '0'"
bool(false)
string(73) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. ' val: ''"
bool(false)
string(77) "param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. ' val: 'abcd'"
bool(false)
string(89) "param: 'ROOT' error: 'VALIDATE_ARRAY: Count out of rage. min: 2 max: 3 count 1' val: 'N;'"
bool(false)
string(89) "param: 'ROOT' error: 'VALIDATE_ARRAY: Count out of rage. min: 2 max: 3 count 4' val: 'N;'"
bool(false)
