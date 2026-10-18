--TEST--
validate() and INT validation with spaces
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
	" 123",
	" 123.01 ",
	"	
   ",
	" ",
	"1234 ",
	1234,
	"       1234           ",
);

$spec = array(
	VALIDATE_INT,
	VALIDATE_FLAG_NONE,
	['min'=>-100, 'max'=>150]
);

foreach ($vals as $var) {
    try {
        var_dump(validate($ctx, $var, $spec, VALIDATE_OPT_CHECK_SPEC), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage());
    }
}

?>
--EXPECT--
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
string(81) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-100" max: "150"'"
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
