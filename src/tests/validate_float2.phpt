--TEST--
validate() and VALIDATE_FLOAT
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

$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min'=>-1000, 'max'=>1000, 'filter'=>
    function($ctx, $input, &$error) {
        return trim($input);
    }]
];

$floats = [0, -1000, 1000, -1000.1, 1000.1];

foreach ($floats as $float) {
    try {
        $result = validate($ctx, $float, $spec);
        var_dump($result, $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}
?>
--EXPECT--
float(0)
bool(true)
float(-1000)
bool(true)
float(1000)
bool(true)
string(101) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '-1000.1'"
bool(false)
string(100) "param: 'ROOT' error: 'VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000"' val: '1000.1'"
bool(false)
