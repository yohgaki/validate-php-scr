--TEST--
validate() VALIDATE_ARRAY — nested array spec, mixed int/string keys
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

$spec = array(
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	array( // 'min'/'max' bound the element count for VALIDATE_ARRAY (not byte length).
		'min' => 0,
		'max' => 10,
	),
	array( // VALIDATE_PARAMS slot — sub-specs keyed by the expected input keys.
		0 => array(
			VALIDATE_STRING,
			VALIDATE_STRING_LOWER_ALPHA,
			array(
				'min' => 0,
				'max' => 10,
			),
		),
		'key' => array(
			VALIDATE_STRING,
			VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_MB,
			array(
				'min' => 0,
				'max' => 20,
			),
		),
		'nested_arr' => array( // Nested VALIDATE_ARRAY sub-specs are allowed to any depth.
			VALIDATE_ARRAY,
			VALIDATE_FLAG_NONE,
			array(
				'min' => 0,
				'max' => 10,
			),
			array(
				'el1' => array(
					VALIDATE_STRING,
					VALIDATE_STRING_LOWER_ALPHA,
					array('min' => 0, 'max' => 10),
				),
				'el2' => array(
					VALIDATE_STRING,
					VALIDATE_STRING_LOWER_ALPHA,
					array('min' => 0, 'max' => 10),
				),
			),
		),
	),
);

$input = array(
	"key" => "abc日本語",
	0 => "qwert",
	"nested_arr"=> array(
		"el1" => "sadf",
		"el2" => "uiop",
	),
);
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
