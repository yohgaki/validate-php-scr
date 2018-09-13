--TEST--
validate() and VALIDATE_FLAG_EMPTY_TO_DEFAULT
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
	'bar' => '',
	'baz' => '',
	'hoge' => '',
);

echo "\n** Successful case**\n";
$spec = [
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	['min'=>0, 'max'=>10],
	[
		'bar' => [ // Empty in $values
			VALIDATE_STRING,
			VALIDATE_FLAG_EMPTY_TO_DEFAULT
			| VALIDATE_STRING_SPACE | VALIDATE_STRING_LOWER_ALPHA,
			['min'=>0, 'max'=>100, 'default'=>'value was not defined']
		],
		'baz' => [ // Empty in $values
			VALIDATE_INT,
			VALIDATE_FLAG_EMPTY_TO_DEFAULT,
			['min'=>0, 'max'=>100, 'default'=>1 ]
		],
		'hoge' => [ // Empty in $values
			VALIDATE_FLOAT,
			VALIDATE_FLAG_EMPTY_TO_DEFAULT
			| VALIDATE_FLAG_ARRAY,
			['min'=>0, 'max'=>100, 'amin'=>2, 'amax'=>4, 'default'=>[12,34,56] ] // Abuse, but works
		],
	],
];

try {
	var_dump(validate($ctx, $values, $spec), $ctx->getStatus());
} catch (Exception $e) {
	var_dump($e->getMessage());
}


$values = array(
	'foo' => '', // Not in the validation SPEC
	'bar' => '',
	'baz' => '',
	'hoge' => '',
);


echo "\n** Successful case**\n";
try {
	var_dump(validate($ctx, $values, $spec), $ctx->getStatus());
} catch (Exception $e) {
	var_dump($e->getMessage());
}



echo "Done\n";
?>
--EXPECT--
** Successful case**
array(3) {
  ["bar"]=>
  string(21) "value was not defined"
  ["baz"]=>
  int(1)
  ["hoge"]=>
  array(3) {
    [0]=>
    float(12)
    [1]=>
    float(34)
    [2]=>
    float(56)
  }
}
bool(true)

** Successful case**
array(3) {
  ["bar"]=>
  string(21) "value was not defined"
  ["baz"]=>
  int(1)
  ["hoge"]=>
  array(3) {
    [0]=>
    float(12)
    [1]=>
    float(34)
    [2]=>
    float(56)
  }
}
bool(true)
Done
