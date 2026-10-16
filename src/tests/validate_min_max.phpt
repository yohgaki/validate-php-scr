--TEST--
validate() min and max - all tests should succeed
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
$spec = array(
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    array('min' => -100, 'max' => 100),
);
$values = array(-100, 100, 0, '-100', '100', '0');
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() FLOAT ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_AS_STRING,
    array('min' => -100, 'max' => 100),
);
$values = array(-100, 100, 0, '-100', '100', '0');
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() STRING ****\n";
$spec = array(
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    array('min' => 0, 'max' => 2),
);
$values = array(0, '', 'a', 'ab');
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() ARRAY ****\n";
$spec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 2, 'max' => 3),
    array(
        0 => array(VALIDATE_INT, 0, array('min'=>-100, 'max'=>100)),
        1 => array(VALIDATE_INT, 0, array('min'=>-100, 'max'=>100)),
        2 => array(VALIDATE_INT, VALIDATE_FLAG_UNDEFINED, array('min'=>-100, 'max'=>100)),
     )
);
$values = array(array(1,2), array(1,2,3));
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
