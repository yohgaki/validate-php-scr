--TEST--
validate() and VALIDATE_CALLBACK
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

// NOTE: When VALIDATE_CALLBAK is used with validate(), it is usres' responsibility
//      to raise proper exceptions when something goes wrong.
//
// WARNING: This test code uses validate() as 'filter', but 'filtering' is NOT validation.
//      Filtering is used only for testing purpose. Do not abuse.


echo "\n/* Simple callback function - closure*/\n";
$f = function($ctx, &$result, $value) {
	assert($ctx instanceof Validate);
	$result = strtoupper($value);
	return true;
};

$spec = [
	VALIDATE_CALLBACK,
	VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_ALPHA,
	["min"=>0, "max"=>99999, "callback"=>$f]
];

$str = ["data", "~!@#$%^&*()_<>?\"}{:", "AbCd", "abcd"];

foreach($str as $s) {
	var_dump(validate($ctx, $s, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
}


echo "\n/* Simple callback function - closure*/\n";
$f = function($ctx, &$result, $value) {
	assert($ctx instanceof Validate);
	$result = strtoupper($value);
	return false;
};


$spec = [
	VALIDATE_CALLBACK,
	VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_ALPHA,
	["min"=>0, "max"=>99999, "callback"=>$f]
];

$str = ["data", "~!@#$%^&*()_<>?\"}{:", "AbCd", "abcd"];

foreach($str as $s) {
	var_dump(validate($ctx, $s, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
}



echo "\n/* Simple callback function */\n";
function test($ctx, &$result, $value) {
	assert($ctx instanceof Validate);
	$result = strtoupper($value);
	return true;
};


$spec = [
	VALIDATE_CALLBACK,
	VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_ALPHA,
	["min"=>0, "max"=>99999, "callback"=>"test"]
];

$str = ["data", "~!@#$%^&*()_<>?\"}{:", "AbCd", "abcd"];

foreach($str as $s) {
	var_dump(validate($ctx, $s, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
}


echo "\n/* Simple class method callback */\n";
class test_class {
	static function test($ctx, &$result, $value) {
		assert($ctx instanceof Validate);
		$result = strtoupper($value);
		return true;
	}
}

$spec = [
	VALIDATE_CALLBACK,
	VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_ALPHA,
	["min"=>0, "max"=>99999, "callback"=>["test_class", "test"]]
];

$str = ["data", "~!@#$%^&*()_<>?\"}{:", "AbCd", "abcd"];

foreach($str as $s) {
	var_dump(validate($ctx, $s, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
}


echo "\n/* empty function without return value */\n";
function test1($ctx, &$result, $value) {
	$result = strtoupper($value);
}

$spec = [
	VALIDATE_CALLBACK,
	VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_ALPHA,
	["min"=>0, "max"=>99999, "callback"=>"test1"]
];

$str = ["data", "~!@#$%^&*()_<>?\"}{:", "AbCd", "abcd"];

foreach($str as $s) {
	var_dump(validate($ctx, $s, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
}


echo "\n/* function raise error */\n";
function test2($ctx, &$result, $value) {
	$result = strtoupper($value);
	trigger_error("Error");
	return false;
};

$spec = [
	VALIDATE_CALLBACK,
	VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_ALPHA,
	["min"=>0, "max"=>99999, "callback"=>"test2"]
];

$str = ["data", "~!@#$%^&*()_<>?\"}{:", "AbCd", "abcd"];

foreach($str as $s) {
	var_dump(validate($ctx, $s, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
}



echo "\n/* unsetting data */\n";
function test3($ctx, &$result, $value) {
	unset($value);
	return true;
};

$spec = [
	VALIDATE_CALLBACK,
	VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_ALPHA,
	["min"=>0, "max"=>99999, "callback"=>"test3"]
];

$str = ["data", "~!@#$%^&*()_<>?\"}{:", "AbCd", "abcd"];

foreach($str as $s) {
	var_dump(validate($ctx, $s, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
}


echo "\n/* unset data and return value */\n";
function test4($ctx, &$result, $value) {
	unset($value);
	$result = 1;
	return true;
};

$spec = [
	VALIDATE_CALLBACK,
	VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_ALPHA,
	["min"=>0, "max"=>99999, "callback"=>"test4"]
];

$str = ["data", "~!@#$%^&*()_<>?\"}{:", "AbCd", "abcd"];

foreach($str as $s) {
	var_dump(validate($ctx, $s, $spec, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
}

// Dump validate object
// echo "\n/* dump Validate object */\n";
// var_dump($ctx);

echo "Done\n";
?>
--EXPECTF--
/* Simple callback function - closure*/
string(4) "DATA"
bool(true)
string(19) "~!@#$%^&*()_<>?"}{:"
bool(true)
string(4) "ABCD"
bool(true)
string(4) "ABCD"
bool(true)

/* Simple callback function - closure*/
NULL
bool(false)
NULL
bool(false)
NULL
bool(false)
NULL
bool(false)

/* Simple callback function */
string(4) "DATA"
bool(true)
string(19) "~!@#$%^&*()_<>?"}{:"
bool(true)
string(4) "ABCD"
bool(true)
string(4) "ABCD"
bool(true)

/* Simple class method callback */
string(4) "DATA"
bool(true)
string(19) "~!@#$%^&*()_<>?"}{:"
bool(true)
string(4) "ABCD"
bool(true)
string(4) "ABCD"
bool(true)

/* empty function without return value */
NULL
bool(false)
NULL
bool(false)
NULL
bool(false)
NULL
bool(false)

/* function raise error */

Notice: Error in %s/src/tests/validate_callback.php on line %d
NULL
bool(false)

Notice: Error in %s/src/tests/validate_callback.php on line %d
NULL
bool(false)

Notice: Error in %s/src/tests/validate_callback.php on line %d
NULL
bool(false)

Notice: Error in %s/src/tests/validate_callback.php on line %d
NULL
bool(false)

/* unsetting data */
string(4) "data"
bool(true)
string(19) "~!@#$%^&*()_<>?"}{:"
bool(true)
string(4) "AbCd"
bool(true)
string(4) "abcd"
bool(true)

/* unset data and return value */
int(1)
bool(true)
int(1)
bool(true)
int(1)
bool(true)
int(1)
bool(true)
Done