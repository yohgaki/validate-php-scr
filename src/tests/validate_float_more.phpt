--TEST--
validate() FLOAT edge branches - NAN/INF, EMPTY, cast overflow, RAW, empty string
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

$func_opts = VALIDATE_OPT_NONE;

echo "**** array input is rejected ****\n";
$spec = [VALIDATE_FLOAT, VALIDATE_FLAG_NONE, ['min'=>0.0, 'max'=>100.0]];
try {
    $copy = [1.0];
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** NAN / INF / -INF native doubles ****\n";
foreach ([NAN, INF, -INF] as $var) {
    try {
        $copy = $var;
        var_dump(validate($ctx, $copy, $spec, $func_opts));
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

echo "\n**** INF allowed by option ****\n";
$spec_inf = [VALIDATE_FLOAT, VALIDATE_FLAG_NONE,
             ['min'=>0.0, 'max'=>100.0, 'INF'=>true, '-INF'=>true]];
foreach ([INF, -INF] as $var) {
    $copy = $var;
    var_dump(validate($ctx, $copy, $spec_inf, $func_opts), $ctx->getStatus());
}

echo "\n**** VALIDATE_FLAG_EMPTY ****\n";
$spec_e = [VALIDATE_FLOAT, VALIDATE_FLAG_EMPTY, ['min'=>0.0, 'max'=>100.0]];
$copy = '';
var_dump(validate($ctx, $copy, $spec_e, $func_opts), $ctx->getStatus());
$spec_e = [VALIDATE_FLOAT, VALIDATE_FLOAT_AS_STRING | VALIDATE_FLAG_EMPTY, ['min'=>0.0, 'max'=>100.0]];
$copy = '';
var_dump(validate($ctx, $copy, $spec_e, $func_opts), $ctx->getStatus());

echo "\n**** empty string without EMPTY flag ****\n";
try {
    $copy = '';
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** EMPTY_TO_DEFAULT without default (broken spec, check off) ****\n";
$spec_d = [VALIDATE_FLOAT, VALIDATE_FLAG_EMPTY_TO_DEFAULT, ['min'=>0.0, 'max'=>100.0]];
try {
    $copy = '';
    var_dump(validate($ctx, $copy, $spec_d, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** string form overflows to INF after cast ****\n";
$spec_s = [VALIDATE_FLOAT, VALIDATE_FLOAT_SCIENTIFIC, ['min'=>0.0, 'max'=>INF]];
try {
    $copy = '9e999';
    var_dump(validate($ctx, $copy, $spec_s, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** RAW returns original string ****\n";
$spec_r = [VALIDATE_FLOAT, VALIDATE_FLAG_RAW, ['min'=>0.0, 'max'=>100.0]];
$copy = '1.5';
var_dump(validate($ctx, $copy, $spec_r, $func_opts), $ctx->getStatus());

?>
--EXPECT--
**** array input is rejected ****
string(86) "param: 'ROOT' error: 'VALIDATE_FLOAT: Array or object parameter is passed for scalar.'"
bool(false)

**** NAN / INF / -INF native doubles ****
string(64) "param: 'ROOT' error: 'VALIDATE_FLOAT: NAN value is not allowed.'"
bool(false)
string(64) "param: 'ROOT' error: 'VALIDATE_FLOAT: INF value is not allowed.'"
bool(false)
string(65) "param: 'ROOT' error: 'VALIDATE_FLOAT: -INF value is not allowed.'"
bool(false)

**** INF allowed by option ****
float(INF)
bool(true)
float(-INF)
bool(true)

**** VALIDATE_FLAG_EMPTY ****
NULL
bool(true)
string(0) ""
bool(true)

**** empty string without EMPTY flag ****
string(51) "param: 'ROOT' error: 'VALIDATE_FLOAT: Empty input.'"
bool(false)

**** EMPTY_TO_DEFAULT without default (broken spec, check off) ****
string(106) "param: 'ROOT' error: 'Invalid validation spec: empty value cannot be used without proper "default" value.'"
bool(false)

**** string form overflows to INF after cast ****
string(59) "param: 'ROOT' error: 'VALIDATE_FLOAT: Invalid float value.'"
bool(false)

**** RAW returns original string ****
string(3) "1.5"
bool(true)
