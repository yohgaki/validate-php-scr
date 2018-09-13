--TEST--
validate() STRING validation with "values" option.
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!extension_loaded('GMP')) die('skip no GMP');
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting=-1
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

$vals = [1,2,10,11, 'abc', 'X', 'Z', ''];

$spec = array(
	VALIDATE_STRING,
	VALIDATE_FLAG_NONE,
    [
        'values' => [
            1 => true,
            2 => true,
            10 => false,
            'abc' => true,
            'Z' => false,
            '' => true,
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

$var = ['scalar']; // Array. Error
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
string(90) "param: 'ROOT' error: 'VALIDATE_STRING: Failed to match defined option "values".' val: '10'"
bool(false)
string(90) "param: 'ROOT' error: 'VALIDATE_STRING: Failed to match defined option "values".' val: '11'"
bool(false)
string(3) "abc"
bool(true)
string(89) "param: 'ROOT' error: 'VALIDATE_STRING: Failed to match defined option "values".' val: 'X'"
bool(false)
string(89) "param: 'ROOT' error: 'VALIDATE_STRING: Failed to match defined option "values".' val: 'Z'"
bool(false)
string(0) ""
bool(true)
string(118) "param: 'ROOT' error: 'VALIDATE_STRING: Array is passed for option "values" validation.' val: 'a:1:{i:0;s:6:"scalar";}'"
bool(false)
