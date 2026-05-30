--TEST--
validate() VALIDATE_FLOAT_FRACTION — require a fractional part
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

function tryone($label, $value, $spec) {
    $ctx = null;
    $r = validate($ctx, $value, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
    printf("[%s] in=%s => out=%s status=%s\n",
        $label,
        var_export($value, true),
        var_export($r, true),
        var_export($ctx instanceof Validate ? $ctx->getStatus() : null, true));
}

echo "**** String inputs ****\n";
$spec = [VALIDATE_FLOAT, VALIDATE_FLOAT_FRACTION, ['min' => -1000, 'max' => 1000]];
tryone('1.5',       '1.5',   $spec); // ok
tryone('1.0',       '1.0',   $spec); // ok — notation has fraction
tryone('0.0',       '0.0',   $spec); // ok
tryone('0.123',     '0.123', $spec); // ok
tryone('999.999',   '999.999', $spec); // ok
tryone('1',         '1',     $spec); // fail — no decimal point
tryone('1.',        '1.',    $spec); // fail — trailing dot, no digits
tryone('.5',        '.5',    $spec); // fail — needs integer part
tryone('0',         '0',     $spec); // fail
tryone('-3.14',     '-3.14', $spec); // ok — negative implicit (min < 0)

echo "\n**** Native double inputs ****\n";
tryone('1.5 (dbl)', 1.5,   $spec); // ok
tryone('1.0 (dbl)', 1.0,   $spec); // fail — value is integral
tryone('0.0 (dbl)', 0.0,   $spec); // fail
tryone('-3.14',    -3.14, $spec); // ok
tryone('5 (int)',  5,     $spec); // fail — int promotes to 5.0, integral

echo "\n**** Combined with VALIDATE_FLOAT_SCIENTIFIC ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_FRACTION | VALIDATE_FLOAT_SCIENTIFIC,
    ['min' => 0, 'max' => 1e9],
];
tryone('1.5e3',     '1.5e3', $spec); // ok — has fraction
tryone('1e3',       '1e3',   $spec); // fail — no fraction even though scientific
tryone('1.0e3',     '1.0e3', $spec); // ok — has fraction notation
tryone('1.5E2',     '1.5E2', $spec); // ok

echo "\n**** Combined with VALIDATE_FLOAT_POSITIVE_SIGN ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_FRACTION | VALIDATE_FLOAT_POSITIVE_SIGN,
    ['min' => 0, 'max' => 100],
];
tryone('+1.5',      '+1.5',  $spec); // ok
tryone('+1',        '+1',    $spec); // fail — no fraction
tryone('1.5',       '1.5',   $spec); // ok — sign is optional

echo "\n**** AS_STRING preserves textual form ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_FRACTION | VALIDATE_FLOAT_AS_STRING,
    ['min' => -100, 'max' => 100],
];
tryone('"3.140"',   '3.140', $spec); // string out, trailing zeros kept
tryone('"3"',       '3',     $spec); // fail

echo "\n**** Range still enforced ****\n";
$spec = [VALIDATE_FLOAT, VALIDATE_FLOAT_FRACTION, ['min' => -1, 'max' => 1]];
tryone('1.5',       '1.5', $spec); // fail — over max
tryone('-1.5',      '-1.5', $spec); // fail — under min
tryone('0.5',       '0.5', $spec); // ok

?>
--EXPECT--
**** String inputs ****
[1.5] in='1.5' => out=1.5 status=true
[1.0] in='1.0' => out=1.0 status=true
[0.0] in='0.0' => out=0.0 status=true
[0.123] in='0.123' => out=0.123 status=true
[999.999] in='999.999' => out=999.999 status=true
[1] in='1' => out=NULL status=false
[1.] in='1.' => out=NULL status=false
[.5] in='.5' => out=NULL status=false
[0] in='0' => out=NULL status=false
[-3.14] in='-3.14' => out=-3.14 status=true

**** Native double inputs ****
[1.5 (dbl)] in=1.5 => out=1.5 status=true
[1.0 (dbl)] in=1.0 => out=NULL status=false
[0.0 (dbl)] in=0.0 => out=NULL status=false
[-3.14] in=-3.14 => out=-3.14 status=true
[5 (int)] in=5 => out=NULL status=false

**** Combined with VALIDATE_FLOAT_SCIENTIFIC ****
[1.5e3] in='1.5e3' => out=1500.0 status=true
[1e3] in='1e3' => out=NULL status=false
[1.0e3] in='1.0e3' => out=1000.0 status=true
[1.5E2] in='1.5E2' => out=150.0 status=true

**** Combined with VALIDATE_FLOAT_POSITIVE_SIGN ****
[+1.5] in='+1.5' => out=1.5 status=true
[+1] in='+1' => out=NULL status=false
[1.5] in='1.5' => out=1.5 status=true

**** AS_STRING preserves textual form ****
["3.140"] in='3.140' => out='3.140' status=true
["3"] in='3' => out=NULL status=false

**** Range still enforced ****
[1.5] in='1.5' => out=NULL status=false
[-1.5] in='-1.5' => out=NULL status=false
[0.5] in='0.5' => out=0.5 status=true
