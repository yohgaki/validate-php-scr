--TEST--
validate() and VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
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

$values = array(
	'foo' => 1234,
);

echo "\n** Successful case**\n";
$spec = [
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	['min'=>0, 'max'=>10],
	[
		'foo' => [
			VALIDATE_INT,
			VALIDATE_FLAG_NONE,
			['min'=>1000, 'max'=>10000],
		],
		'bar' => [ // Undefined in $values
			VALIDATE_STRING,
			VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
			 | VALIDATE_STRING_SPACE | VALIDATE_STRING_ALNUM,
			['min'=>0, 'max'=>100, 'default'=>'value was not defined']
		],
		'baz' => [ // Undefined in $values
			VALIDATE_INT,
			VALIDATE_FLAG_UNDEFINED_TO_DEFAULT,
			['min'=>0, 'max'=>100, 'default'=>1 ]
		],
		'hoge' => [ // Undefined in $values
			VALIDATE_STRING,
			VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
			 | VALIDATE_STRING_SPACE | VALIDATE_STRING_UPPER_ALPHA,
			['min'=>0, 'max'=>100, 'default'=>'TEXT']
		],
	],
];

try {
	var_dump(validate($ctx, $values, $spec), $ctx->getStatus(), $ctx->getValidated());
} catch (Exception $e) {
	var_dump($e->getMessage());
}



$values = array(
	'foo' => 1234,
);

echo "\n** Failure case**\n";
$spec = [
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	['min'=>0, 'max'=>10],
	[
		'bar' => [ // Undefined in $values
			VALIDATE_STRING,
			VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
			 | VALIDATE_STRING_SPACE | VALIDATE_STRING_LOWER_ALPHA,
			['min'=>0, 'max'=>100, 'default'=>'value was not defined']
		],
		'baz' => [ // Undefined in $values
			VALIDATE_INT,
			VALIDATE_FLAG_UNDEFINED_TO_DEFAULT,
			['min'=>0, 'max'=>100, 'default'=>-1 ] // Invalid default is error
		],
		'hoge' => [ // Undefined in $values
			VALIDATE_STRING,
			VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
			 | VALIDATE_STRING_SPACE | VALIDATE_STRING_UPPER_ALPHA,
			['min'=>0, 'max'=>100, 'default'=>'text'] // Lower case is illegal.
		],
	],
];

try {
	$ctx = validate_init();
	validate_set_error_level($ctx, E_USER_WARNING);
	var_dump(validate($ctx, $values, $spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR),
			 $ctx->getStatus(), $ctx->getValidated());
} catch (Exception $e) {
	var_dump($e->getMessage());
}


echo "Done\n";
?>
--EXPECTF--
** Successful case**
array(4) {
  ["foo"]=>
  int(1234)
  ["bar"]=>
  string(21) "value was not defined"
  ["baz"]=>
  int(1)
  ["hoge"]=>
  string(4) "TEXT"
}
bool(true)
array(4) {
  ["foo"]=>
  int(1234)
  ["bar"]=>
  string(21) "value was not defined"
  ["baz"]=>
  int(1)
  ["hoge"]=>
  string(4) "TEXT"
}

** Failure case**

Warning: param: 'ROOT=>baz' error: 'VALIDATE_INT: Invalid int string.' val: '-1' in %s/src/Validate.php on line %d

Warning: param: 'ROOT=>hoge' error: 'VALIDATE_STRING: Illegal char detected. ord: "116" chr: "t"' val: 'text' in %s/src/Validate.php on line %d
NULL
bool(false)
array(1) {
  ["bar"]=>
  string(21) "value was not defined"
}
Done
