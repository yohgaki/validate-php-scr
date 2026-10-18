--TEST--
validate() INT validation with "values" option.
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

$vals = array(1,2,10,11);

$spec = array(
	VALIDATE_INT,
	VALIDATE_FLAG_NONE,
    array(
        'values' => array(
            1 => true,
            2 => true,
            10 => false
        )
    )
);

$func_opts = VALIDATE_OPT_CHECK_SPEC;
foreach ($vals as $var) {
    try {
        var_dump(validate($ctx, $var, $spec, $func_opts), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

$var = array(123); // Array. Error
try {
    var_dump(validate($ctx, $var, $spec, $func_opts), $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}
?>
--EXPECT--
int(1)
bool(true)
int(2)
bool(true)
string(77) "param: 'ROOT' error: 'VALIDATE_INT: Failed to match defined option "values".'"
bool(false)
string(77) "param: 'ROOT' error: 'VALIDATE_INT: Failed to match defined option "values".'"
bool(false)
string(84) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.'"
bool(false)
