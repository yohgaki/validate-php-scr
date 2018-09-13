--TEST--
Simple validate() and array tests
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
	'product_id'	=> 'libgd',
	'component'		=> '10',
	'versions'		=> '2.0.33',
	'test_int'		 => array('2', '23', '10', '12'),
	'large_int'		=> '999999999',
);

$spec =
	array(
		VALIDATE_ARRAY,
		VALIDATE_FLAG_NONE,
		array('min'=>1, 'max'=>100),
		array(
			'product_id'   => array(
				VALIDATE_STRING,
				VALIDATE_STRING_LOWER_ALPHA,
				array('min' => 1, 'max' => 10)
			),
			'component'    => array(
				VALIDATE_INT,
				VALIDATE_FLAG_NONE,
				array('min' => 1, 'max' => 10)
			),
			'versions'     => array(
				VALIDATE_STRING,
				VALIDATE_FLAG_NONE,
				array('min' => 1, 'max' => 10, 'ascii' => '1234567890.'),
			),
			'test_int'     => array(
				VALIDATE_INT,
				VALIDATE_FLAG_ARRAY,
				array('min' => 0, 'max' => 30, 'amin'=>1, 'amax'=>10),
			),
			'large_int'    => array(
				VALIDATE_INT,
				FILTER_FLAG_NONE,
				array('min' => 1, 'max' => PHP_INT_MAX),
			),
		),
	);

try {
  // Input data is modified. i.e. Validated array elements are removed
  // to validate "not validated values" if it is required.
	$data3 = $data2 = $data;
  // Needs distinct Validate objects. i.e. $v1, $v2, $v3 must be distinct.
  var_dump('** 1st call **', validate($v1, $data, $spec)); // Should pass
  var_dump($v1->getStatus(), $data);
	var_dump('** 2nd call **', validate($v2, $data2, $spec)); // Should pass again
  var_dump($v2->getStatus(), $data2);
	var_dump('** 3rd call **', validate($v3, $data3, $spec)); // Should pass again
  var_dump($v3->getStatus(), $data3);
} catch (ValidException $e) {
	var_dump($e->getMessage());
} catch (Throwable $e) {
	var_dump('Throwable: '.$e->getMessage());
} catch (Exception $e) {
	var_dump('Exception: '.$e->getMessage());
} finally {
	var_dump('Finally');
}
?>
--EXPECT--
string(14) "** 1st call **"
array(5) {
  ["product_id"]=>
  string(5) "libgd"
  ["component"]=>
  int(10)
  ["versions"]=>
  string(6) "2.0.33"
  ["test_int"]=>
  array(4) {
    [0]=>
    int(2)
    [1]=>
    int(23)
    [2]=>
    int(10)
    [3]=>
    int(12)
  }
  ["large_int"]=>
  int(999999999)
}
bool(true)
array(0) {
}
string(14) "** 2nd call **"
array(5) {
  ["product_id"]=>
  string(5) "libgd"
  ["component"]=>
  int(10)
  ["versions"]=>
  string(6) "2.0.33"
  ["test_int"]=>
  array(4) {
    [0]=>
    int(2)
    [1]=>
    int(23)
    [2]=>
    int(10)
    [3]=>
    int(12)
  }
  ["large_int"]=>
  int(999999999)
}
bool(true)
array(0) {
}
string(14) "** 3rd call **"
array(5) {
  ["product_id"]=>
  string(5) "libgd"
  ["component"]=>
  int(10)
  ["versions"]=>
  string(6) "2.0.33"
  ["test_int"]=>
  array(4) {
    [0]=>
    int(2)
    [1]=>
    int(23)
    [2]=>
    int(10)
    [3]=>
    int(12)
  }
  ["large_int"]=>
  int(999999999)
}
bool(true)
array(0) {
}
string(7) "Finally"
