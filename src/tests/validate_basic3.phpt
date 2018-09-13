--TEST--
Test basic validate module features
	All Test cases should pass
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

$spec = [
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	[ // min, max is allowed number of elements for the array
		'min' => 0,
		'max' => 10,
	],
	[ // 4th element is the VALIDATE_ARRAY's spec. Key is required.
		0 => [
			VALIDATE_STRING,
			VALIDATE_STRING_LOWER_ALPHA,
			[
				'min' => 0,
				'max' => 10,
			],
		],
		'key' => [
			VALIDATE_STRING,
			VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_MB,
			[
				'min' => 0,
				'max' => 20,
			],
		],
		'nested_arr' => [ // Nested array is OK
			VALIDATE_ARRAY,
			VALIDATE_FLAG_NONE,
			[
				'min' => 0,
				'max' => 10,
			],
			[
				'el1' => [
					VALIDATE_STRING,
					VALIDATE_STRING_LOWER_ALPHA,
					['min' => 0, 'max' => 10],
				],
				'el2' => [
					VALIDATE_STRING,
					VALIDATE_STRING_LOWER_ALPHA,
					['min' => 0, 'max' => 10],
				],
			],
		],
	],
];

$input = [
	"key" => "abc日本語",
	0 => "qwert",
	"nested_arr"=> [
		"el1" => "sadf",
		"el2" => "uiop",
	],
];
var_dump(validate($ctx, $input, $spec, 0 /*VALIDATE_OPT_DISABLE_EXCEPTION*/), $ctx->getStatus());
?>
--EXPECT--
array(3) {
  [0]=>
  string(5) "qwert"
  ["key"]=>
  string(12) "abc日本語"
  ["nested_arr"]=>
  array(2) {
    ["el1"]=>
    string(4) "sadf"
    ["el2"]=>
    string(4) "uiop"
  }
}
bool(true)
