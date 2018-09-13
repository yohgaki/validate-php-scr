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
	['min'=>-150, 'max'=>150]
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
string(68) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '+123'"
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
string(93) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-150" max: "150"' val: '1234'"
bool(false)
string(93) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-150" max: "150"' val: '1234'"
bool(false)
string(93) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-150" max: "150"' val: '1234'"
bool(false)
string(69) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '123.4'"
bool(false)
string(69) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '123.4'"
bool(false)
string(69) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '123.4'"
bool(false)
string(71) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: '123.4.5'"
bool(false)
