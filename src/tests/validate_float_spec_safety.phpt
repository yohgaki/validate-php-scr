--TEST--
validate() VALIDATE_FLOAT — INF options, AS_STRING input type, 'default' handling, separator safety
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
precision=14
error_reporting=-1
zend.assertions=-1
--FILE--
<?php
// zend.assertions=-1 (see --INI--) simulates production: the separator
// safety net below must not depend on assert() or VALIDATE_OPT_CHECK_SPEC.
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

echo "**** 'INF'/'-INF' options accept infinities (min/max do not apply) ****\n";
$spec = [VALIDATE_FLOAT, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 100, 'INF' => true]];
tryone('INF allowed',   INF,   $spec); // ok — not clipped by max=100
$spec = [VALIDATE_FLOAT, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 100]];
tryone('INF rejected',  INF,   $spec); // fail — option not set
$spec = [VALIDATE_FLOAT, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 100, '-INF' => true]];
tryone('-INF allowed',  -INF,  $spec); // ok
tryone('range ok',      50.5,  $spec); // ok — finite values still range-checked
tryone('range fail',    200.5, $spec); // fail

echo "\n**** AS_STRING rejects native float input ****\n";
$spec = [VALIDATE_FLOAT, VALIDATE_FLOAT_AS_STRING, ['min' => 0, 'max' => 10]];
tryone('double in',     1.5,   $spec); // fail — numeric string required
tryone('string in',     '1.5', $spec); // ok
tryone('int in',        2,     $spec); // ok — int is validated as string '2'

echo "\n**** 'default' is validated like normal input ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_EMPTY_TO_DEFAULT,
    ['min' => 0, 'max' => 10, 'decimal' => ',', 'default' => 1.5],
];
tryone('float default + decimal', '', $spec); // ok — default takes the native-float path
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_FRACTION | VALIDATE_FLAG_EMPTY_TO_DEFAULT,
    ['min' => 0, 'max' => 10, 'default' => 2.0],
];
tryone('float default + FRACTION', '', $spec); // ok — native float satisfies FRACTION
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_EMPTY_TO_DEFAULT,
    ['min' => 0, 'max' => 1.0e40, 'length' => 5, 'default' => '123456789012345678901234567890'],
];
tryone('long default rejected', '', $spec); // fail — default exceeds 'length'

echo "\n**** Broken separator specs fail safely in production ****\n";
// Assertions and spec check are both off here; these used to validate
// successfully with corrupted values (15.0, 0.1 and 1.5 respectively).
$spec = [VALIDATE_FLOAT, VALIDATE_FLOAT_THOUSAND, ['min' => 0, 'max' => 100, 'decimal' => ',']];
tryone('decimal==thousand', '1,5', $spec); // fail — ',' collides with default thousand
$spec = [VALIDATE_FLOAT, VALIDATE_FLAG_NONE, ['min' => -10, 'max' => 10, 'decimal' => '-']];
tryone('sign as decimal', '-1-5', $spec); // fail — '-' collides with float grammar
$spec = [VALIDATE_FLOAT, VALIDATE_FLOAT_SCIENTIFIC, ['min' => 0, 'max' => 1000000, 'decimal' => 'e']];
tryone('exponent as decimal', '1e5e3', $spec); // fail — 'e' collides with float grammar

echo "\n**** Grammar-overlapping separators are spec errors ****\n";
$spec = [VALIDATE_FLOAT, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 10, 'decimal' => 'e']];
$ctx = validate_init();
var_dump(validate_spec($spec, $unvalidated, $ctx));
foreach (validate_get_system_errors($ctx)['error'] as $e) {
    echo 'spec error: ', $e['message'], "\n";
}
$spec = [VALIDATE_FLOAT, VALIDATE_FLOAT_THOUSAND, ['min' => 0, 'max' => 10000, 'thousand' => '5']];
$ctx = validate_init();
var_dump(validate_spec($spec, $unvalidated, $ctx));
foreach (validate_get_system_errors($ctx)['error'] as $e) {
    echo 'spec error: ', $e['message'], "\n";
}

?>
--EXPECT--
**** 'INF'/'-INF' options accept infinities (min/max do not apply) ****
[INF allowed] in=INF => out=INF status=true
[INF rejected] in=INF => out=NULL status=false
[-INF allowed] in=-INF => out=-INF status=true
[range ok] in=50.5 => out=50.5 status=true
[range fail] in=200.5 => out=NULL status=false

**** AS_STRING rejects native float input ****
[double in] in=1.5 => out=NULL status=false
[string in] in='1.5' => out='1.5' status=true
[int in] in=2 => out='2' status=true

**** 'default' is validated like normal input ****
[float default + decimal] in='' => out=1.5 status=true
[float default + FRACTION] in='' => out=2.0 status=true
[long default rejected] in='' => out=NULL status=false

**** Broken separator specs fail safely in production ****
[decimal==thousand] in='1,5' => out=NULL status=false
[sign as decimal] in='-1-5' => out=NULL status=false
[exponent as decimal] in='1e5e3' => out=NULL status=false

**** Grammar-overlapping separators are spec errors ****
bool(false)
spec error: VALIDATE_FLOAT "decimal" option must be a single character other than digits, "+", "-", "e" and "E".
bool(false)
spec error: VALIDATE_FLOAT "thousand" option must be a single character other than digits, "+", "-", "e" and "E".
