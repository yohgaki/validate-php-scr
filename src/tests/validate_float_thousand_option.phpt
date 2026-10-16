--TEST--
validate() VALIDATE_FLOAT — 'thousand' option (custom thousand separator)
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

echo "**** European: thousand='.' decimal=',' ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_THOUSAND,
    array('min' => 0, 'max' => 1.0e10, 'thousand' => '.', 'decimal' => ','),
);
tryone('1.234',         '1.234',         $spec); // ok — 1234 grouped
tryone('12.345',        '12.345',        $spec); // ok
tryone('1.234.567',     '1.234.567',     $spec); // ok — multi group
tryone('1.234,5',       '1.234,5',       $spec); // ok — frac
tryone('1.234,567',     '1.234,567',     $spec); // ok
tryone('0,5',           '0,5',           $spec); // ok — pure frac
tryone('1,234',         '1,234',         $spec); // ok — ',' is decimal here -> 1.234
tryone('1.23',          '1.23',          $spec); // fail — '.' is thousand, needs 3 digits
tryone('12.34',         '12.34',         $spec); // fail — group not 3 digits
tryone('1.234.56',      '1.234.56',      $spec); // fail — last group not 3 digits

echo "\n**** Underscore separator (programmer-style) ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_THOUSAND,
    array('min' => 0, 'max' => 1.0e10, 'thousand' => '_'),
);
tryone('1_234',         '1_234',         $spec); // ok
tryone('1_234_567',     '1_234_567',     $spec); // ok
tryone('1_234.5',       '1_234.5',       $spec); // ok — '.' still decimal
tryone('1_23',          '1_23',          $spec); // fail
tryone('_123',          '_123',          $spec); // fail

echo "\n**** Space separator (French SI style) ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_THOUSAND,
    array('min' => 0, 'max' => 1.0e10, 'thousand' => ' '),
);
tryone('"1 234"',       '1 234',         $spec); // ok
tryone('"12 345 678"',  '12 345 678',    $spec); // ok
tryone('"1 23"',        '1 23',          $spec); // fail
tryone('"1234"',        '1234',          $spec); // ok — ungrouped still accepted

echo "\n**** Negative + custom separator ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_THOUSAND,
    array('min' => -1.0e6, 'max' => 1.0e6, 'thousand' => '.', 'decimal' => ','),
);
tryone('-1.234,5',      '-1.234,5',      $spec); // ok

echo "\n**** Spec error: 'thousand' option not a single char ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_THOUSAND,
    array('min' => 0, 'max' => 10000, 'thousand' => '__'),
);
$ctx = validate_init();
var_dump(validate_spec($spec, $unvalidated, $ctx));
$_tmp = validate_get_system_errors($ctx); // PHP 5.3 cannot index a call result
foreach ($_tmp['error'] as $e) {
    echo 'spec error: ', $e['message'], "\n";
}

echo "\n**** Spec error: decimal == thousand ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_THOUSAND,
    array('min' => 0, 'max' => 10000, 'decimal' => '.', 'thousand' => '.'),
);
$ctx = validate_init();
var_dump(validate_spec($spec, $unvalidated, $ctx));
$_tmp = validate_get_system_errors($ctx); // PHP 5.3 cannot index a call result
foreach ($_tmp['error'] as $e) {
    echo 'spec error: ', $e['message'], "\n";
}

echo "\n**** Spec warning: 'thousand' option without VALIDATE_FLOAT_THOUSAND flag ****\n";
$spec = array(
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    array('min' => 0, 'max' => 10000, 'thousand' => ','),
);
$ctx = validate_init();
var_dump(validate_spec($spec, $unvalidated, $ctx));
$_tmp = validate_get_system_errors($ctx); // PHP 5.3 cannot index a call result
foreach ($_tmp['warning'] as $w) {
    echo 'spec warning: ', $w['message'], "\n";
}

?>
--EXPECT--
**** European: thousand='.' decimal=',' ****
[1.234] in='1.234' => out=1234.0 status=true
[12.345] in='12.345' => out=12345.0 status=true
[1.234.567] in='1.234.567' => out=1234567.0 status=true
[1.234,5] in='1.234,5' => out=1234.5 status=true
[1.234,567] in='1.234,567' => out=1234.567 status=true
[0,5] in='0,5' => out=0.5 status=true
[1,234] in='1,234' => out=1.234 status=true
[1.23] in='1.23' => out=NULL status=false
[12.34] in='12.34' => out=NULL status=false
[1.234.56] in='1.234.56' => out=NULL status=false

**** Underscore separator (programmer-style) ****
[1_234] in='1_234' => out=1234.0 status=true
[1_234_567] in='1_234_567' => out=1234567.0 status=true
[1_234.5] in='1_234.5' => out=1234.5 status=true
[1_23] in='1_23' => out=NULL status=false
[_123] in='_123' => out=NULL status=false

**** Space separator (French SI style) ****
["1 234"] in='1 234' => out=1234.0 status=true
["12 345 678"] in='12 345 678' => out=12345678.0 status=true
["1 23"] in='1 23' => out=NULL status=false
["1234"] in='1234' => out=1234.0 status=true

**** Negative + custom separator ****
[-1.234,5] in='-1.234,5' => out=-1234.5 status=true

**** Spec error: 'thousand' option not a single char ****
bool(false)
spec error: VALIDATE_FLOAT "thousand" option must be a single character other than digits, "+", "-", "e" and "E".

**** Spec error: decimal == thousand ****
bool(false)
spec error: VALIDATE_FLOAT "decimal" and "thousand" options must differ. Both are ".".

**** Spec warning: 'thousand' option without VALIDATE_FLOAT_THOUSAND flag ****
bool(true)
spec warning: VALIDATE_FLOAT has "thousand" option but VALIDATE_FLOAT_THOUSAND flag is not set.
