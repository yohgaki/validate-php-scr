--TEST--
validate() min and max - all tests should success
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
$values = [-100, 100, 0, '-100', '100', '0'];
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() FLOAT ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_AS_STRING,
    ['min' => -100, 'max' => 100],
];
$values = [-100, 100, 0, '-100', '100', '0'];
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() STRING ****\n";
$spec = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 0, 'max' => 2],
];
$values = [0, '', 'a', 'ab'];
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() ARRAY ****\n";
$spec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 2, 'max' => 3],
    [
        0 => [VALIDATE_INT, 0, ['min'=>-100, 'max'=>100]],
        1 => [VALIDATE_INT, 0, ['min'=>-100, 'max'=>100]],
        2 => [VALIDATE_INT, VALIDATE_FLAG_UNDEFINED, ['min'=>-100, 'max'=>100]],
     ]
];
$values = [[1,2], [1,2,3]];
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
int(-100)
bool(true)
int(100)
bool(true)
int(0)
bool(true)
string(4) "-100"
bool(true)
string(3) "100"
bool(true)
string(1) "0"
bool(true)

**** validate() FLOAT ****
string(4) "-100"
bool(true)
string(3) "100"
bool(true)
string(1) "0"
bool(true)
string(4) "-100"
bool(true)
string(3) "100"
bool(true)
string(1) "0"
bool(true)

**** validate() STRING ****
string(1) "0"
bool(true)
string(0) ""
bool(true)
string(1) "a"
bool(true)
string(2) "ab"
bool(true)

**** validate() ARRAY ****
array(2) {
  [0]=>
  int(1)
  [1]=>
  int(2)
}
bool(true)
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
bool(true)
