--TEST--
validate() and VALIDATE_RESOURCE
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

echo "Stream resource\n";
$spec = [
    VALIDATE_RESOURCE,
    VALIDATE_FLAG_NONE,
    ['resource' => 'stream']
];

$fp = fopen(__FILE__, 'r');

try {
    $result = validate($ctx, $fp, $spec);
    var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo "\nBad resource\n";
$spec = [
    VALIDATE_RESOURCE,
    VALIDATE_FLAG_NONE,
    ['resource' => 'does not exist']
];

$fp = fopen(__FILE__, 'r');

try {
    $result = validate($ctx, $fp, $spec);
    var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo "\nNull\n";
$spec = [
    VALIDATE_RESOURCE,
    VALIDATE_FLAG_NONE,
    ['resource' => 'does not exist']
];

$fp = null;

try {
    $result = validate($ctx, $fp, $spec);
    var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo "\nObject\n";
$spec = [
    VALIDATE_RESOURCE,
    VALIDATE_FLAG_NONE,
    ['resource' => 'does not exist']
];

$fp = new StdClass;

try {
    $result = validate($ctx, $fp, $spec);
    var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage());
}
?>
--EXPECTF--
Stream resource
resource(%d) of type (stream)
bool(true)

Bad resource
string(117) "param: 'ROOT' error: 'VALIDATE_RESOURCE: Resource type does not match. Returned: 'stream' Expected: 'does not exist''"

Null
string(76) "param: 'ROOT' error: 'VALIDATE_RESOURCE: NULL input is rejected by default.'"

Object
string(71) "param: 'ROOT' error: 'VALIDATE_RESOURCE: Not a resource. Type: object '"
