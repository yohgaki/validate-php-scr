--TEST--
validate() and VALIDATE_OBJECT
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

class GoodObject {
    private $var = null;
    public function validate($ctx) {
        if ($this->var !== null) {
            validate_error($ctx, '$var is not null.');
            return false;
        }
        return true;
    }
}

class BadObject {
    private $var = 1;
    public function validate($ctx) {
        if ($this->var !== null) {
            validate_error($ctx, '$var is not null.');
            return false;
        }
        return true;
    }
    public function badCallback($ctx) {
        return 'foo';
    }
}


echo "Good object\n";
$spec = [
    VALIDATE_OBJECT,
    VALIDATE_FLAG_NONE,
    ['callback' => 'validate']
];

$val = new GoodObject;

try {
    $result = validate($ctx, $val, $spec);
    var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo "\nBad object\n";
$val = new BadObject;

try {
    $result = validate($ctx, $val, $spec);
    var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo "\nBad callback\n";
$spec = [
    VALIDATE_OBJECT,
    VALIDATE_FLAG_NONE,
    ['callback' => 'foo']
];

$val = new GoodObject;

try {
    $result = validate($ctx, $val, $spec);
    var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo "\nBad callback\n";
$spec = [
    VALIDATE_OBJECT,
    VALIDATE_FLAG_NONE,
    ['callback' => 'badCallback']
];

$val = new BadObject;

try {
    $result = validate($ctx, $val, $spec);
    var_dump($result, $ctx->getStatus());
} catch (Exception $e) {
    var_dump($e->getMessage());
}

?>
--EXPECTF--
Good object
object(GoodObject)#1 (1) {
  ["var":"GoodObject":private]=>
  NULL
}
bool(true)

Bad object
string(94) "param: 'ROOT' error: '$var is not null.' val: 'O:9:"BadObject":1:{s:14:" BadObject var";i:1;}'"

Bad callback
string(184) "param: 'ROOT' error: 'VALIDATE_OBJECT: "GoodObject" class validation callback is not callable or does not exist. Callback: 'foo'' val: 'O:10:"GoodObject":1:{s:15:" GoodObject var";N;}'"

Bad callback

Fatal error: Uncaught AssertionError: assert(is_bool($ret)) in %s/Validate.php:%d
Stack trace:
#0 %s/Validate.php(3056): assert(false, 'assert(is_bool(...')
#1 %s/Validate.php(1206): Validate->validateObject(NULL, Object(BadObject), 0, Array, 1)
#2 %s/Validate.php(785): Validate->validateScalar(NULL, Object(BadObject), 10, 0, Array, 1)
#3 %s/Validate.php(345): Validate->validateImpl(NULL, Object(BadObject), Array, 1)
#4 %s/validate_func.php(79): Validate->validate(Object(BadObject), Array, 1)
#5 %s/tests/validate_object.php(82): validate(Object(Validate), Object(BadObject), Array)
#6 {main}
  thrown in %s/Validate.php on line %d