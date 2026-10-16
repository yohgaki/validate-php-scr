--TEST--
validate() VALIDATE_ARRAY — VALIDATE_FLAG_UNDEFINED + VALIDATE_OPT_UNVALIDATED tolerate missing/extra keys
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
		'missing' => array( // Absent in $input — VALIDATE_FLAG_UNDEFINED makes that OK.
			VALIDATE_STRING,
			VALIDATE_FLAG_UNDEFINED,
			array(
				'min' => 0,
				'max' => 10,
			),
		),
		0 => array(
			VALIDATE_STRING,
			VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB,
			array(
				'min' => 0,
				'max' => 10,
			),
		),
		'key' => array(
			VALIDATE_STRING,
			VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB,
			array(
				'min' => 0,
				'max' => 30,
			),
		),
		'nested_arr' => array( // Nested VALIDATE_ARRAY sub-specs are allowed to any depth.
			VALIDATE_ARRAY,
			VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB,
			array(
				'min' => 0,
				'max' => 10,
			),
			array(
				'el1' => array(
					VALIDATE_STRING,
					VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB,
					array('min' => 0, 'max' => 10),
				),
				'el2' => array(
					VALIDATE_STRING,
					VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB,
					array('min' => 0, 'max' => 10),
				),
			),
		),
	),
);

// $input has no 'missing' key, plus an 'extra' key not declared in $spec.
// VALIDATE_OPT_UNVALIDATED tells validate() to leave undeclared keys in
// $input rather than raising "Unvalidated value remains."
$input = array(
	"key" => "abc日本語",
	0 => "qwert",
	"nested_arr"=> array(
		"el1" => "sadf",
		"el2" => "uiop",
	),
	"extra" => 1234,
);
var_dump(validate($ctx, $input, $spec, VALIDATE_OPT_UNVALIDATED), $ctx->getStatus(), $input);
var_dump($ctx->getSystemErrors());
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
array(1) {
  ["extra"]=>
  int(1234)
}
array(3) {
  ["error"]=>
  array(0) {
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(0) {
  }
}
