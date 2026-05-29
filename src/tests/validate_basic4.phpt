--TEST--
validate() VALIDATE_ARRAY — missing required key + un-whitelisted chars; should fail
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
	[ // 'min'/'max' bound the element count for VALIDATE_ARRAY (not byte length).
		'min' => 0,
		'max' => 10,
	],
	[ // VALIDATE_PARAMS slot — sub-specs keyed by the expected input keys.
		0 => [
			VALIDATE_STRING,
			VALIDATE_FLAG_NONE,
			[
				'min' => 0,
				'max' => 10,
			],
		],
		'key' => [
			VALIDATE_STRING,
			VALIDATE_FLAG_NONE,
			[
				'min' => 0,
				'max' => 30,
			],
		],
		'nested_arr' => [ // Nested VALIDATE_ARRAY sub-specs are allowed to any depth.
			VALIDATE_ARRAY,
			VALIDATE_FLAG_NONE,
			[
				'min' => 0,
				'max' => 10,
			],
			[
				'el1' => [
					VALIDATE_STRING,
					VALIDATE_FLAG_NONE,
					['min' => 0, 'max' => 10],
				],
				'el2' => [
					VALIDATE_STRING,
					VALIDATE_FLAG_NONE,
					['min' => 0, 'max' => 10],
				],
			],
		],
		'missing' => [ // Required by spec but absent in $input below — triggers the failure.
			VALIDATE_STRING,
			VALIDATE_FLAG_NONE,
			[
				'min' => 0,
				'max' => 10,
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
var_dump(validate($ctx, $input, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus(), $ctx->getValidated(), $input);
?>
--EXPECT--
NULL
bool(false)
array(1) {
  ["nested_arr"]=>
  array(0) {
  }
}
array(3) {
  ["key"]=>
  string(12) "abc日本語"
  [0]=>
  string(5) "qwert"
  ["nested_arr"]=>
  array(2) {
    ["el1"]=>
    string(4) "sadf"
    ["el2"]=>
    string(4) "uiop"
  }
}
