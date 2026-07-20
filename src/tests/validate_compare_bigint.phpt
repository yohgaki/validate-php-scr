--TEST--
Validate::compareBigInt() pure PHP fallback (bccomp disabled, no GMP)
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
if (extension_loaded('gmp')) die("skip GMP loaded - fallback not reachable");
?>
--INI--
error_reporting=-1
disable_functions=bccomp
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

var_dump(function_exists('bccomp')); // must be false for the fallback path

$m = new ReflectionMethod('Validate', 'compareBigInt');
if (PHP_VERSION_ID < 80100) {
    $m->setAccessible(true); // required on PHP 8.0, deprecated no-op on 8.5+
}
$cmp = function ($a, $b) use ($m) {
    return $m->invoke(null, $a, $b);
};

echo "**** int fast path ****\n";
var_dump($cmp(1, 2), $cmp(2, 1), $cmp(2, 2));

echo "\n**** mixed signs ****\n";
var_dump($cmp('-1', '99999999999999999999'), $cmp('99999999999999999999', '-1'));

echo "\n**** both positive ****\n";
var_dump(
    $cmp('99999999999999999999', '100000000000000000000'), // shorter < longer
    $cmp('100000000000000000000', '99999999999999999999'),
    $cmp('12345678901234567890', '12345678901234567891'),  // same length
    $cmp('12345678901234567891', '12345678901234567890'),
    $cmp('0012345678901234567890', '12345678901234567890') // leading zeros
);

echo "\n**** both negative ****\n";
var_dump(
    $cmp('-99999999999999999999', '-100000000000000000000'), // shorter > longer
    $cmp('-100000000000000000000', '-99999999999999999999'),
    $cmp('-12345678901234567890', '-12345678901234567891'),
    $cmp('-12345678901234567891', '-12345678901234567890'),
    $cmp('-12345678901234567890', '-12345678901234567890')
);

echo "\n**** zero forms ****\n";
var_dump($cmp('000', '0'), $cmp('-0', '0'));

?>
--EXPECT--
bool(false)
**** int fast path ****
int(-1)
int(1)
int(0)

**** mixed signs ****
int(-1)
int(1)

**** both positive ****
int(-1)
int(1)
int(-1)
int(1)
int(0)

**** both negative ****
int(1)
int(-1)
int(1)
int(-1)
int(0)

**** zero forms ****
int(0)
int(0)
