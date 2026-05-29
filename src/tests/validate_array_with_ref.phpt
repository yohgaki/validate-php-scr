--TEST--
validate() VALIDATE_ARRAY — nested reference + VALIDATE_FLAG_ARRAY_RECURSIVE
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

// 'alimit' caps the total number of elements traversed when descending into a
// nested input, which is the safety net against circular references like the
// $array2 below. If 'alimit' is absent, 'amax' is used as the cap.
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
