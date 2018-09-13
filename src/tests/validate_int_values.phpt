--TEST--
validate() INT validation with "values" option.
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

$vals = [1,2,10,11];

$spec = array(
	VALIDATE_INT,
	VALIDATE_FLAG_NONE,
    [
        'values' => [
            1 => true,
            2 => true,
            10 => false
        ]
    ]
);

$func_opts = VALIDATE_OPT_CHECK_SPEC;
foreach ($vals as $var) {
    try {
        var_dump(validate($ctx, $var, $spec, $func_opts), $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

$var = [123]; // Array. Error
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
string(87) "param: 'ROOT' error: 'VALIDATE_INT: Failed to match defined option "values".' val: '10'"
bool(false)
string(87) "param: 'ROOT' error: 'VALIDATE_INT: Failed to match defined option "values".' val: '11'"
bool(false)
string(112) "param: 'ROOT' error: 'VALIDATE_INT: Failed option "values" match. Value is not integer.' val: 'a:1:{i:0;i:123;}'"
bool(false)
