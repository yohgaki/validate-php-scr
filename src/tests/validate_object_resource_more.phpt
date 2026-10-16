--TEST--
validate() OBJECT / RESOURCE edge branches - bad callback, failed validation, type mismatch
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

class ObjOk
{
    public function check($ctx)
    {
        return true;
    }
}

class ObjNg
{
    public function check($ctx)
    {
        return false;
    }
}

$func_opts = VALIDATE_OPT_NONE;

echo "**** VALIDATE_OBJECT ****\n";
$spec = [VALIDATE_OBJECT, VALIDATE_FLAG_NONE, ['callback'=>'check']];
$vals = [new ObjOk, new ObjNg, 42];
foreach ($vals as $var) {
    try {
        $copy = $var;
        $result = validate($ctx, $copy, $spec, $func_opts);
        var_dump(is_object($result) ? get_class($result) : $result, $ctx->getStatus());
    } catch (Exception $e) {
        var_dump($e->getMessage(), $ctx->getStatus());
    }
}

// Callback method that does not exist
$spec = [VALIDATE_OBJECT, VALIDATE_FLAG_NONE, ['callback'=>'no_such_method']];
try {
    $copy = new ObjOk;
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** VALIDATE_RESOURCE ****\n";
$spec = [VALIDATE_RESOURCE, VALIDATE_FLAG_NONE, ['resource'=>'stream']];
// Not a resource at all
try {
    $copy = 42;
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}
// Type mismatch: stream passed but "curl" expected
$spec = [VALIDATE_RESOURCE, VALIDATE_FLAG_NONE, ['resource'=>'curl']];
try {
    $copy = fopen('php://memory', 'r');
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}
// Success
$spec = [VALIDATE_RESOURCE, VALIDATE_FLAG_NONE, ['resource'=>'stream']];
$copy = fopen('php://memory', 'r');
var_dump(is_resource(validate($ctx, $copy, $spec, $func_opts)), $ctx->getStatus());

?>
--EXPECT--
**** VALIDATE_OBJECT ****
string(5) "ObjOk"
bool(true)
string(89) "param: 'ROOT' error: 'VALIDATE_OBJECT: Object validation failed.' val: 'O:5:"ObjNg":0:{}'"
bool(false)
string(89) "param: 'ROOT' error: 'VALIDATE_OBJECT: Value is not an object. Type: 'integer'' val: '42'"
bool(false)
string(159) "param: 'ROOT' error: 'VALIDATE_OBJECT: "ObjOk" class validation callback is not callable or does not exist. Callback: 'no_such_method'' val: 'O:5:"ObjOk":0:{}'"
bool(false)

**** VALIDATE_RESOURCE ****
string(82) "param: 'ROOT' error: 'VALIDATE_RESOURCE: Not a resource. Type: integer ' val: '42'"
bool(false)
string(131) "param: 'ROOT' error: 'VALIDATE_RESOURCE: Resource type does not match. Returned: 'stream' Expected: 'curl'' val: 'resource(stream)'"
bool(false)
bool(true)
bool(true)
