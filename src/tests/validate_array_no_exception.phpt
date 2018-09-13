--TEST--
Simple validate() no exception tests
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

$data = array(
	'product_id'	=> 'libgd<script>',
	'component'		=> '10',
	'versions'		=> '2.0.33',
	'test_float'	=> array('2', '23', '10', '12'),
	'extra'		    => '2', // Extra value remains. It can be addressed by 2nd validation.
);

$spec =
	array(
		VALIDATE_ARRAY,
		VALIDATE_FLAG_NONE,
		array('min'=>1, 'max'=>100),
		array(
			'product_id'   => array(
				VALIDATE_STRING,
				VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL,
				array('min' => 1, 'max' => 100)
			),
			'component'    => array(
				VALIDATE_INT,
				VALIDATE_FLAG_NONE,
				array('min' => 1, 'max' => 10)
			),
			'versions'     => array(
				VALIDATE_STRING,
				VALIDATE_FLAG_NONE,
				array('min' => 1, 'max' => 30, 'ascii' => '1234567890.'),
			),
			'test_float'     => array(
				VALIDATE_FLOAT,
				VALIDATE_FLAG_ARRAY,
				array('min' => 0, 'max' => 30, 'amin'=>1, 'amax'=>10),
			),
			'bool'    => array( // Input data has not "bool", so this raises error/exception.
				VALIDATE_BOOL,
				VALIDATE_BOOL_01,
				array(),
			),
		),
	);


try {
	$copy = $data; // Validated values are removed from inputs(array). Make copy.
	var_dump(validate($ctx, $data, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getValidated()); // Error
	var_dump($ctx->getStatus());
	var_dump(validate($ctx, $copy, $spec)); // Exception
} catch (Exception $e) {
	var_dump($e->getMessage());
}
?>
--EXPECTF--
NULL
array(4) {
  ["product_id"]=>
  string(13) "libgd<script>"
  ["component"]=>
  int(10)
  ["versions"]=>
  string(6) "2.0.33"
  ["test_float"]=>
  array(4) {
    [0]=>
    float(2)
    [1]=>
    float(23)
    [2]=>
    float(10)
    [3]=>
    float(12)
  }
}
bool(false)
string(94) "param: 'ROOT=>bool' error: 'Undefined parameter: Required parameter is not defined.' val: 'N;'"
