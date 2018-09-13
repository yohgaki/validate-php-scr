--TEST--
validate() and FLOAT
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
precision=14
error_reporting=-1
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

$floats = array(
	'1.234   ',
	'   1.234',
	'1.234'	,
	'-1.234'	,
	'+1.234'	,
	'1234'	,
	'-1234'	,
	'+1.2e3',
	'7E3',
	'-7E3',
	'+7E3',
	'7E3     ',
	'  7E3     ',
	'  7E-3     ',
	'  7E+3     ',
);

echo "By default, validate() will not convert inputs.\n";
foreach ($floats as $float) {
	try {
		$result = validate($ctx, $float,
			[
				VALIDATE_FLOAT,
				VALIDATE_FLOAT_SCIENTIFIC,
				['min'=>-10000, 'max'=>10000]
			]
		);
		var_dump($result, $ctx->getStatus());
	} catch (Exception $e) {
		var_dump($e->getMessage(), $ctx->getStatus());
	}
}

echo "\nApply filter trims strings.\n";
foreach ($floats as $float) {
	try {
		$result = validate($ctx, $float,
			[
				VALIDATE_FLOAT,
				VALIDATE_FLOAT_SCIENTIFIC,
				['min'=>-10000, 'max'=>10000, 'filter'=>
				function($ctx, $input, &$error) {
					if (!is_scalar($input)) {
						// Use validate_error()
						validate_error($ctx, 'Filter error: $input is not a scalar.');
					}
					return trim($input);
				}]
			]
		);
		var_dump($result, $ctx->getStatus());
	} catch (Exception $e) {
		var_dump($e->getMessage(), $ctx->getStatus());
	}
}

echo "\nSmaller range between -1000 and 1000.\n";
foreach ($floats as $float) {
	try {
		$result = validate($ctx, $float,
			[
				VALIDATE_FLOAT,
				VALIDATE_FLOAT_SCIENTIFIC,
				['min'=>-1000, 'max'=>1000, 'filter'=>
				function($ctx, $input, &$error) {
					return trim($input);
				}]
			]
		);
		var_dump($result, $ctx->getStatus());
	} catch (Exception $e) {
		var_dump($e->getMessage(), $ctx->getStatus());
	}
}

echo "\nNo scientific notation.\n";
foreach ($floats as $float) {
	try {
		$result = validate($ctx, $float,
			[
				VALIDATE_FLOAT,
				VALIDATE_FLOAT_POSITIVE_SIGN,
				['min'=>-1000, 'max'=>1000, 'filter'=>
				function($ctx, $input, &$error) {
					return trim($input);
				}]
			]
		);
		var_dump($result, $ctx->getStatus());
	} catch (Exception $e) {
		var_dump($e->getMessage(), $ctx->getStatus());
	}
}

/*
 * "decimal" is not supported, yet.
$floats = array(
	'1.234   '	=> ',',
	'1,234'		=> ',',
	'   1.234'	=> '.',
	'1.234'		=> '..',
	'1.2e3'		=> ','
);

echo "\ncustom decimal:\n";
foreach ($floats as $float => $dec) {
	try {
		$status = validate($ctx, $result, $float,
			[
				VALIDATE_FLOAT,
				VALIDATE_FLAG_NONE,
				['min'=>-10000, 'max'=>10000, 'decimal' => $dec]
			]
		);
		var_dump($result);
	} catch (Exception $e) {
		var_dump($e->getMessage());
	}
}
*/

?>
--EXPECTF--
By default, validate() will not convert inputs.
string(76) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '1.234   '"
bool(false)
string(76) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '   1.234'"
bool(false)
float(1.234)
bool(true)
float(-1.234)
bool(true)
string(74) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+1.234'"
bool(false)
float(1234)
bool(true)
float(-1234)
bool(true)
string(74) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+1.2e3'"
bool(false)
float(7000)
bool(true)
float(-7000)
bool(true)
string(72) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+7E3'"
bool(false)
string(76) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '7E3     '"
bool(false)
string(78) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '  7E3     '"
bool(false)
string(79) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '  7E-3     '"
bool(false)
string(79) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '  7E+3     '"
bool(false)

Apply filter trims strings.
float(1.234)
bool(true)
float(1.234)
bool(true)
float(1.234)
bool(true)
float(-1.234)
bool(true)
string(74) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+1.234'"
bool(false)
float(1234)
bool(true)
float(-1234)
bool(true)
string(74) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+1.2e3'"
bool(false)
float(7000)
bool(true)
float(-7000)
bool(true)
string(72) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+7E3'"
bool(false)
float(7000)
bool(true)
float(7000)
bool(true)
float(0.007)
bool(true)
float(7000)
bool(true)

Smaller range between -1000 and 1000.
float(1.234)
bool(true)
float(1.234)
bool(true)
float(1.234)
bool(true)
float(-1.234)
bool(true)
string(74) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+1.234'"
bool(false)
string(98) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '1234'"
bool(false)
string(99) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '-1234'"
bool(false)
string(74) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+1.2e3'"
bool(false)
string(98) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '7000'"
bool(false)
string(99) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '-7000'"
bool(false)
string(72) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+7E3'"
bool(false)
string(98) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '7000'"
bool(false)
string(98) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '7000'"
bool(false)
float(0.007)
bool(true)
string(98) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '7000'"
bool(false)

No scientific notation.
float(1.234)
bool(true)
float(1.234)
bool(true)
float(1.234)
bool(true)
float(-1.234)
bool(true)
float(1.234)
bool(true)
string(98) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '1234'"
bool(false)
string(99) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '-1234'"
bool(false)
string(74) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+1.2e3'"
bool(false)
string(71) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '7E3'"
bool(false)
string(72) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '-7E3'"
bool(false)
string(72) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '+7E3'"
bool(false)
string(71) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '7E3'"
bool(false)
string(71) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '7E3'"
bool(false)
string(72) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '7E-3'"
bool(false)
string(72) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float format.' val: '7E+3'"
bool(false)
