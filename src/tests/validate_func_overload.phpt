--TEST--
Validate refuses mbstring.func_overload string functions (strlen() would count chars)
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
if (!extension_loaded('mbstring')) die("skip no mbstring");
?>
--INI--
error_reporting=-1
mbstring.func_overload=2
mbstring.internal_encoding=UTF-8
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

// strlen("\xE3\x81\x82\xE3\x81\x82\xE3\x81\x82\xE3\x81\x82<x>") is 7 here, so
// the char check would stop before "<x>".
var_dump(strlen("\xE3\x81\x82"));
try {
    $ctx = validate_init();
    echo "no exception\n";
} catch (RuntimeException $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}
?>
--EXPECTF--
%Aint(1)
RuntimeException: Validate does not work with mbstring.func_overload string functions (2).
