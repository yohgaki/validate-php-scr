--TEST--
validate() STRING validation with "values" option.
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

$vals = array(1,2,10,11, 'abc', 'X', 'Z', '');

$spec = array(
	VALIDATE_STRING,
	VALIDATE_FLAG_NONE,
    array(
        'values' => array(
            1 => true,
            2 => true,
            10 => false,
            'abc' => true,
            'Z' => false,
            '' => true,
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

$var = array('scalar'); // Array. Error
try {
    var_dump(validate($ctx, $var, $spec, $func_opts), $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

?>
--EXPECT--
string(1) "1"
bool(true)
string(1) "2"
bool(true)
string(80) "param: 'ROOT' error: 'VALIDATE_STRING: Failed to match defined option "values".'"
bool(false)
string(80) "param: 'ROOT' error: 'VALIDATE_STRING: Failed to match defined option "values".'"
bool(false)
string(3) "abc"
bool(true)
string(80) "param: 'ROOT' error: 'VALIDATE_STRING: Failed to match defined option "values".'"
bool(false)
string(80) "param: 'ROOT' error: 'VALIDATE_STRING: Failed to match defined option "values".'"
bool(false)
string(0) ""
bool(true)
string(87) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.'"
bool(false)
