--TEST--
validate() and scalar array
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting = -1
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

$func_opts = VALIDATE_OPT_CHECK_SPEC;

echo "Simple string\n";
$var = 12;
$spec = [
	VALIDATE_INT,
	VALIDATE_INT_AS_STRING,
	['min'=>0, 'max'=>20]
];
$result = validate($ctx, $var, $spec, $func_opts);
var_dump($result, $ctx->getStatus());


echo "\Simple array\n";
try {
	$var = array(12);
	$spec = [
		VALIDATE_INT,
		VALIDATE_FLAG_ARRAY,
		['min'=>0, 'max'=>20, 'amin'=>0, 'amax'=>20]
	];
	$result = validate($ctx, $var, $spec, $func_opts);
	var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
	var_dump($e->getMessage());
}

echo "\nScalar to array\n";
try {
	$var = 12;
	$spec = [
		VALIDATE_INT,
		VALIDATE_FLAG_ARRAY,
		['min'=>0, 'max'=>20, 'amin'=>0, 'amax'=>20]
	];
	$result = validate($ctx, $var, $spec, $func_opts);
	var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
	var_dump($e->getMessage());
}


echo "\nNested array\n";
try {
	$var = array(array(12));
	$spec = [
		VALIDATE_INT,
		VALIDATE_FLAG_ARRAY,
		['min'=>0, 'max'=>20, 'amin'=>0, 'amax'=>20]
	];
	$result = validate($ctx, $var, $spec, $func_opts);
	var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
	var_dump($e->getMessage());
}


echo "\nNested array (should pass)\n";
try {
	$var = array(array(12));
	$spec = [
		VALIDATE_INT,
		VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_RECURSIVE,
		['min'=>0, 'max'=>20, 'amin'=>0, 'amax'=>20]
	];
	$result = validate($ctx, $var, $spec, $func_opts);
	var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
	var_dump($e->getMessage());
}

echo "\nArray of scalars\n";
try {
	$var = array(12,13,20);
	$spec = [
		VALIDATE_INT,
		VALIDATE_FLAG_ARRAY,
		['min'=>0, 'max'=>20, 'amin'=>0, 'amax'=>20]
	];
	$result = validate($ctx, $var, $spec, $func_opts);
	var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
	var_dump($e->getMessage());
}
?>
--EXPECT--
Simple string
int(12)
bool(true)
\Simple array
array(1) {
  [0]=>
  int(12)
}
bool(true)

Scalar to array
string(104) "param: 'ROOT' error: 'VALIDATE_INT: Array of scalars validation. Scalar value is not allowed.' val: '12'"

Nested array
string(129) "param: 'ROOT' error: 'VALIDATE_INT: Array validation. Nested array is not allowed by VALIDATE_FLAG_ARRAY_RECURSIVE.' val: 'key:0'"

Nested array (should pass)
array(1) {
  [0]=>
  array(1) {
    [0]=>
    int(12)
  }
}
bool(true)

Array of scalars
array(3) {
  [0]=>
  int(12)
  [1]=>
  int(13)
  [2]=>
  int(20)
}
bool(true)
