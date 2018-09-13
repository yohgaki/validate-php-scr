--TEST--
validate() and array with reference
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

// In case of circular reference, "alimit" option protects against infinite recursion.
// If "alimit" is not set, "amax" is used as "alimit"
$array = ["12"];
$array2 = [&$array];

$spec = [
	VALIDATE_INT,
	VALIDATE_FLAG_ARRAY,
	['min'=>-100,'max'=>100, 'amin'=>0, 'amax'=>10, 'alimit'=>100]
];

var_dump('Without VALIDATE_FLAG_ARRAY_RECURSION');
var_dump(validate($ctx, $array2, $spec, VALIDATE_OPT_DISABLE_EXCEPTION));
var_dump($ctx->getStatus(), $array, $array2);


$spec = [
	VALIDATE_INT,
	VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_RECURSIVE,
	['min'=>-100,'max'=>100, 'amin'=>0, 'amax'=>10, 'alimit'=>100]
];

var_dump('With VALIDATE_FLAG_ARRAY_RECURSIVE');
var_dump(validate($ctx, $array2, $spec, VALIDATE_OPT_DISABLE_EXCEPTION));
var_dump($ctx->getStatus(), $array, $array2);

?>
--EXPECTF--
string(37) "Without VALIDATE_FLAG_ARRAY_RECURSION"
NULL
bool(false)
array(0) {
}
array(1) {
  [0]=>
  &array(0) {
  }
}
string(34) "With VALIDATE_FLAG_ARRAY_RECURSIVE"
array(1) {
  [0]=>
  NULL
}
bool(true)
array(0) {
}
array(1) {
  [0]=>
  &array(0) {
  }
}
