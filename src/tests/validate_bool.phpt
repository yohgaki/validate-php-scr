--TEST--
validate() and VALIDATE_BOOL
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

$booleans = array(
	TRUE => true,
	1 => true,
	'1' => true,
	'on' => true,
	'On' => true,
	'True' => true,
	'TrUe' => true,
	'oN' => true,

	FALSE => true,
	0 => true,
	'0' => true,
	'off' => true,
	'Off' => true,
	'false' => true,
	'faLsE' => true,
	'oFf' => true,

	'' => false // This fails by design
);

foreach($booleans as $val=>$exp) {
	$spec = [
		VALIDATE_BOOL,
		VALIDATE_BOOL_01|VALIDATE_BOOL_TF|VALIDATE_BOOL_TRUE_FALSE|VALIDATE_BOOL_ON_OFF,
		 []
	];
	try {
		$result = validate($ctx, $val, $spec, VALIDATE_OPT_NONE);
		var_dump($result, $ctx->getStatus());
	} catch (Exception $e) {
		var_dump($e->getMessage());
	}
}
echo "Ok.";
?>
--EXPECTF--
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
string(58) "param: 'ROOT' error: 'VALIDATE_BOOL: Empty input.' val: ''"
Ok.
