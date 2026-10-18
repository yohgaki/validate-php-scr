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
$spec = array(VALIDATE_OBJECT, VALIDATE_FLAG_NONE, array('callback'=>'check'));
$vals = array(new ObjOk, new ObjNg, 42);
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
$spec = array(VALIDATE_OBJECT, VALIDATE_FLAG_NONE, array('callback'=>'no_such_method'));
try {
    $copy = new ObjOk;
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}

echo "\n**** VALIDATE_RESOURCE ****\n";
$spec = array(VALIDATE_RESOURCE, VALIDATE_FLAG_NONE, array('resource'=>'stream'));
// Not a resource at all
try {
    $copy = 42;
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}
// Type mismatch: stream passed but "curl" expected
$spec = array(VALIDATE_RESOURCE, VALIDATE_FLAG_NONE, array('resource'=>'curl'));
try {
    $copy = fopen('php://memory', 'r');
    var_dump(validate($ctx, $copy, $spec, $func_opts));
} catch (Exception $e) {
    var_dump($e->getMessage(), $ctx->getStatus());
}
// Success
$spec = array(VALIDATE_RESOURCE, VALIDATE_FLAG_NONE, array('resource'=>'stream'));
$copy = fopen('php://memory', 'r');
var_dump(is_resource(validate($ctx, $copy, $spec, $func_opts)), $ctx->getStatus());

?>
--EXPECT--
**** VALIDATE_OBJECT ****
string(5) "ObjOk"
bool(true)
string(65) "param: 'ROOT' error: 'VALIDATE_OBJECT: Object validation failed.'"
bool(false)
string(79) "param: 'ROOT' error: 'VALIDATE_OBJECT: Value is not an object. Type: 'integer''"
bool(false)
string(135) "param: 'ROOT' error: 'VALIDATE_OBJECT: "ObjOk" class validation callback is not callable or does not exist. Callback: 'no_such_method''"
bool(false)

**** VALIDATE_RESOURCE ****
string(72) "param: 'ROOT' error: 'VALIDATE_RESOURCE: Not a resource. Type: integer '"
bool(false)
string(107) "param: 'ROOT' error: 'VALIDATE_RESOURCE: Resource type does not match. Returned: 'stream' Expected: 'curl''"
bool(false)
bool(true)
bool(true)
