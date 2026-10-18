--TEST--
validate() and INT validation
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

$vals = array(
	"123",
	123,
	123.0, // treats integer float as int
	"+123",
	+123,
	+123.0,
	"-123",
	-123,
	-123.0,
	"1234",
	1234,
	1234.0,
	"123.4",
	123.4,
	123.40,
	"123.4.5",
);

$spec = array(
	VALIDATE_INT,
	VALIDATE_FLAG_NONE,
	array('min'=>-150, 'max'=>150)
);

$func_opts = VALIDATE_OPT_CHECK_SPEC;
foreach ($vals as $var) {
    try {
        var_dump(validate($ctx, $var, $spec, $func_opts), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

?>
--EXPECT--
int(123)
bool(true)
int(123)
bool(true)
int(123)
bool(true)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
int(123)
bool(true)
int(123)
bool(true)
int(-123)
bool(true)
int(-123)
bool(true)
int(-123)
bool(true)
string(81) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-150" max: "150"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-150" max: "150"'"
bool(false)
string(81) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-150" max: "150"'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
string(67) "param: 'ROOT' error: 'VALIDATE_INT: Float value is not an integer.'"
bool(false)
string(67) "param: 'ROOT' error: 'VALIDATE_INT: Float value is not an integer.'"
bool(false)
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
bool(false)
