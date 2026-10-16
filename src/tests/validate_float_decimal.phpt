--TEST--
validate() VALIDATE_FLOAT — 'decimal' option (custom decimal separator)
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
precision=14
serialize_precision=14
error_reporting=-1
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

// var_export() as in PHP 7.0.2+: a float keeps ".0" (PHP 5 prints 1500.0 as 1500).
function vexp($v)
{
    $s = var_export($v, true);
    if (is_float($v) && strpbrk($s, '.eEN') === false) {
        $s .= '.0';
    }
    return $s;
}

function tryone($label, $value, $spec) {
    $ctx = null;
    $r = validate($ctx, $value, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
    printf("[%s] in=%s => out=%s status=%s\n",
        $label,
        vexp($value),
        vexp($r),
        var_export($ctx instanceof Validate ? $ctx->getStatus() : null, true));
}

echo "**** decimal=',' (European locale) ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    array('min' => -10, 'max' => 10, 'decimal' => ','),
);
tryone('1,5',           '1,5',           $spec); // ok
tryone('0,0',           '0,0',           $spec); // ok
tryone('0,123',         '0,123',         $spec); // ok
tryone('-3,14',         '-3,14',         $spec); // ok (min < 0 implies neg sign allowed)
tryone('1.5',           '1.5',           $spec); // fail — '.' is no longer decimal
tryone('1',             '1',             $spec); // ok — fraction optional
tryone('1,',            '1,',            $spec); // fail — needs digits after
tryone(',5',            ',5',            $spec); // fail — needs digits before
tryone('1,5,5',         '1,5,5',         $spec); // fail — only one decimal sep allowed

echo "\n**** decimal=':' (arbitrary single char) ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    array('min' => -10, 'max' => 10, 'decimal' => ':'),
);
tryone('3:14',          '3:14',          $spec); // ok
tryone('3.14',          '3.14',          $spec); // fail
tryone('0:5',           '0:5',           $spec); // ok

echo "\n**** decimal=',' + VALIDATE_FLOAT_FRACTION ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_FRACTION,
    array('min' => -10, 'max' => 10, 'decimal' => ','),
);
tryone('1,5',           '1,5',           $spec); // ok
tryone('1',             '1',             $spec); // fail — fraction required
tryone('1,',            '1,',            $spec); // fail — no digits after
tryone('1.5',           '1.5',           $spec); // fail — '.' is not decimal

echo "\n**** decimal=',' + VALIDATE_FLOAT_SCIENTIFIC ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_SCIENTIFIC,
    array('min' => 0, 'max' => 1.0e6, 'decimal' => ','),
);
tryone('1,5e3',         '1,5e3',         $spec); // ok — 1.5e3 = 1500
tryone('1,5E3',         '1,5E3',         $spec); // ok
tryone('1.5e3',         '1.5e3',         $spec); // fail

echo "\n**** decimal='.' is the default (explicit equals implicit) ****\n";
$specA = array(VALIDATE_FLOAT, VALIDATE_FLAG_NONE, array('min' => 0, 'max' => 10));
$specB = array(VALIDATE_FLOAT, VALIDATE_FLAG_NONE, array('min' => 0, 'max' => 10, 'decimal' => '.'));
tryone('1.5 default',   '1.5', $specA); // ok
tryone('1.5 explicit',  '1.5', $specB); // ok

echo "\n**** Native double inputs unaffected by decimal option ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    array('min' => -10, 'max' => 10, 'decimal' => ','),
);
tryone('1.5 (dbl)',     1.5,             $spec); // ok — PHP scalar
tryone('-3.14 (dbl)',   -3.14,           $spec); // ok

echo "\n**** AS_STRING returns the canonical numeric string ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_AS_STRING,
    array('min' => -10, 'max' => 10, 'decimal' => ','),
);
tryone('"1,5"',         '1,5',           $spec); // string out, normalized to '.'

echo "\n**** Spec error: decimal not a string ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    array('min' => 0, 'max' => 10, 'decimal' => 44),
);
$ctx = validate_init();
var_dump(validate_spec($spec, $unvalidated, $ctx));
$_tmp = validate_get_system_errors($ctx); // PHP 5.3 cannot index a call result
foreach ($_tmp['error'] as $e) {
    echo 'spec error: ', $e['message'], "\n";
}

echo "\n**** Spec error: decimal more than 1 char ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    array('min' => 0, 'max' => 10, 'decimal' => '..'),
);
$ctx = validate_init();
var_dump(validate_spec($spec, $unvalidated, $ctx));
$_tmp = validate_get_system_errors($ctx); // PHP 5.3 cannot index a call result
foreach ($_tmp['error'] as $e) {
    echo 'spec error: ', $e['message'], "\n";
}

?>
--EXPECT--
**** decimal=',' (European locale) ****
[1,5] in='1,5' => out=1.5 status=true
[0,0] in='0,0' => out=0.0 status=true
[0,123] in='0,123' => out=0.123 status=true
[-3,14] in='-3,14' => out=-3.14 status=true
[1.5] in='1.5' => out=NULL status=false
[1] in='1' => out=1.0 status=true
[1,] in='1,' => out=NULL status=false
[,5] in=',5' => out=NULL status=false
[1,5,5] in='1,5,5' => out=NULL status=false

**** decimal=':' (arbitrary single char) ****
[3:14] in='3:14' => out=3.14 status=true
[3.14] in='3.14' => out=NULL status=false
[0:5] in='0:5' => out=0.5 status=true

**** decimal=',' + VALIDATE_FLOAT_FRACTION ****
[1,5] in='1,5' => out=1.5 status=true
[1] in='1' => out=NULL status=false
[1,] in='1,' => out=NULL status=false
[1.5] in='1.5' => out=NULL status=false

**** decimal=',' + VALIDATE_FLOAT_SCIENTIFIC ****
[1,5e3] in='1,5e3' => out=1500.0 status=true
[1,5E3] in='1,5E3' => out=1500.0 status=true
[1.5e3] in='1.5e3' => out=NULL status=false

**** decimal='.' is the default (explicit equals implicit) ****
[1.5 default] in='1.5' => out=1.5 status=true
[1.5 explicit] in='1.5' => out=1.5 status=true

**** Native double inputs unaffected by decimal option ****
[1.5 (dbl)] in=1.5 => out=1.5 status=true
[-3.14 (dbl)] in=-3.14 => out=-3.14 status=true

**** AS_STRING returns the canonical numeric string ****
["1,5"] in='1,5' => out='1.5' status=true

**** Spec error: decimal not a string ****
bool(false)
spec error: VALIDATE_FLOAT "decimal" option must be a single character other than digits, "+", "-", "e" and "E".

**** Spec error: decimal more than 1 char ****
bool(false)
spec error: VALIDATE_FLOAT "decimal" option must be a single character other than digits, "+", "-", "e" and "E".
