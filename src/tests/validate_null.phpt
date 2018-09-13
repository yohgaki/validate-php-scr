--TEST--
validate() null values
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
    VALIDATE_NULL,
    VALIDATE_FLAG_NONE,
    ['min'=>-100, 'max'=>100]
];

$vals = [null, '', '0', 0, 1, new StdClass];

$func_opts = VALIDATE_OPT_NONE;

$flags = [
    'VALIDATE_FLAG_NONE' => VALIDATE_FLAG_NONE,
    'VALIDATE_NULL_AS_STRING' => VALIDATE_NULL_AS_STRING
];

foreach ($flags as $k => $f) {
    echo "\n**** $k ****\n";
    $spec[VALIDATE_FLAGS] = $f;
    foreach ($vals as $var) {
        try {
            $copy = $var;
            var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
        } catch (Exception $e) {
            var_dump($e->getMessage(), $ctx->getStatus());
        }
    }
}

$validators = [
    'VALIDATE_BOOL' => VALIDATE_BOOL,
    'VALIDATE_INT' => VALIDATE_INT,
    'VALIDATE_FLOAT' => VALIDATE_FLOAT,
    'VALIDATE_STRING' => VALIDATE_STRING
];

foreach ($validators as $k => $v) {
    echo "\n**** $k ****\n";
    foreach ($vals as $var) {
        try {
            $copy = $var;
            $spec[VALIDATE_ID] = $v;
            var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
        } catch (Exception $e) {
            var_dump($e->getMessage(), $ctx->getStatus());
        }
    }
}

?>
--EXPECT--
**** VALIDATE_FLAG_NONE ****
NULL
bool(true)
NULL
bool(true)
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: '0'"
bool(false)
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: '0'"
bool(false)
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: '1'"
bool(false)
string(112) "param: 'ROOT' error: 'VALIDATE_NULL: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)

**** VALIDATE_NULL_AS_STRING ****
string(0) ""
bool(true)
string(0) ""
bool(true)
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: '0'"
bool(false)
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: '0'"
bool(false)
string(62) "param: 'ROOT' error: 'VALIDATE_NULL: Non null value.' val: '1'"
bool(false)
string(112) "param: 'ROOT' error: 'VALIDATE_NULL: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)

**** VALIDATE_BOOL ****
string(82) "param: 'ROOT' error: 'VALIDATE_BOOL: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(58) "param: 'ROOT' error: 'VALIDATE_BOOL: Empty input.' val: ''"
bool(false)
string(60) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: '0'"
bool(false)
string(60) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: '0'"
bool(false)
string(60) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.' val: '1'"
bool(false)
string(112) "param: 'ROOT' error: 'VALIDATE_BOOL: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)

**** VALIDATE_INT ****
string(81) "param: 'ROOT' error: 'VALIDATE_INT: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(64) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.' val: ''"
bool(false)
string(1) "0"
bool(true)
int(0)
bool(true)
int(1)
bool(true)
string(111) "param: 'ROOT' error: 'VALIDATE_INT: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)

**** VALIDATE_FLOAT ****
string(83) "param: 'ROOT' error: 'VALIDATE_FLOAT: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(61) "param: 'ROOT' error: 'VALIDATE_FLOAT: Empty input.' val: 'N;'"
bool(false)
string(1) "0"
bool(true)
string(1) "0"
bool(true)
string(1) "1"
bool(true)
string(113) "param: 'ROOT' error: 'VALIDATE_FLOAT: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)

**** VALIDATE_STRING ****
string(84) "param: 'ROOT' error: 'VALIDATE_STRING: NULL input is rejected by default.' val: 'N;'"
bool(false)
string(0) ""
bool(true)
string(90) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "48" chr: "0"' val: '0'"
bool(false)
string(90) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "48" chr: "0"' val: '0'"
bool(false)
string(90) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '1'"
bool(false)
string(114) "param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'"
bool(false)
