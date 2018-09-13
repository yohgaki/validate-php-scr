--TEST--
validate()  INT validation with GMP
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!extension_loaded('GMP')) die('skip no GMP');
if (!class_exists("Validate")) die("skip");
?>
--INI--
precision=14
error_reporting=-1
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

$vals = array(
	gmp_init(150),
	gmp_init(-150),
	gmp_init('999999999999999999999999999999999999'),
	gmp_init('-999999999999999999999999999999999999'),
);

$spec = array(
	VALIDATE_INT,
	VALIDATE_INT_AS_STRING | VALIDATE_INT_NEGATIVE_SIGN,
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
string(3) "150"
bool(true)
string(4) "-150"
bool(true)
string(125) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-150" max: "150"' val: '999999999999999999999999999999999999'"
bool(false)
string(126) "param: 'ROOT' error: 'VALIDATE_INT: Out of defined range. min: "-150" max: "150"' val: '-999999999999999999999999999999999999'"
bool(false)
