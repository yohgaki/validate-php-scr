--TEST--
validate() simple scalars - all tests should success
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

echo "\n**** validate() NULL ****\n";
$spec = [
    VALIDATE_NULL,
    VALIDATE_FLAG_NULL,
    [],
];
$values = [null, ''];
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() BOOL ****\n";
$spec = [
    VALIDATE_BOOL,
    VALIDATE_BOOL_TF | VALIDATE_BOOL_ON_OFF | VALIDATE_BOOL_TRUE_FALSE | VALIDATE_BOOL_YES_NO | VALIDATE_BOOL_01,
    [],
];
$values = [true, 't', 'T', 'on', 'oN', 'true', 'tRue', 1, '1'];
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() INT ****\n";
$spec = [
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    ['min' => 0, 'max' => '99999999999999999999999999999999999999999999999'],
];
$values = [1234, '1234',  '99999999999999999999999999999999999999999999999'];
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() FLOAT ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_AS_STRING,
    ['min' => 0, 'max' => '99999999999999999999999999999999999999999999999'],
];
$values = [1234, '1234',  '99999999999999999999999999999999999999999999999'];
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}

echo "\n**** validate() STRING ****\n";
$spec = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NULL | VALIDATE_STRING_BINARY,
    ['min' => 0, 'max' => PHP_INT_MAX],
];
$values = [NULL, 12.34, 1234, '1234',  '99999999999999999999999999999999999999999999999', "<\t\n\r>"];
foreach ($values as $val) {
    var_dump(validate($ctx, $val, $spec), $ctx->getStatus());
}


?>
--EXPECT--
**** validate() NULL ****
NULL
bool(true)
NULL
bool(true)

**** validate() BOOL ****
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)

**** validate() INT ****
int(1234)
bool(true)
string(4) "1234"
bool(true)
string(47) "99999999999999999999999999999999999999999999999"
bool(true)

**** validate() FLOAT ****
string(4) "1234"
bool(true)
string(4) "1234"
bool(true)
string(47) "99999999999999999999999999999999999999999999999"
bool(true)

**** validate() STRING ****
string(0) ""
bool(true)
float(12.34)
bool(true)
int(1234)
bool(true)
string(4) "1234"
bool(true)
string(47) "99999999999999999999999999999999999999999999999"
bool(true)
string(5) "<	
>"
bool(true)
