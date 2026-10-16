--TEST--
Validate::validate() OO entry - broken spec throws ValidateInvalidSpecException (same as validate())
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

$v = new Validate();
$in = 5;
// 'min'>'max' is a spec error; unknown option 'foo' is a spec warning.
$spec = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>5, 'max'=>1, 'foo'=>1]];
try {
    $v->validate($in, $spec, VALIDATE_OPT_CHECK_SPEC);
    echo "not reached\n";
} catch (ValidateInvalidSpecException $e) {
    var_dump($e instanceof InvalidArgumentException);
    echo $e->getMessage(), "\n";
    // The spec-check context carries every spec error / warning.
    $errors = validate_get_system_errors($e->getContext());
    var_dump(count($errors['error']), count($errors['warning']));
    // Spec problems are not mixed into the value-validation context.
    var_dump($v->getSystemErrors()['error'], $v->getStatus());
}

echo "\n**** procedural validate() throws the same exception ****\n";
try {
    validate($ctx, $in, $spec);
} catch (ValidateInvalidSpecException $e) {
    echo get_class($e), "\n";
}

echo "\n**** validate_spec() itself never throws for a bad spec ****\n";
var_dump(validate_spec($spec, $unvalidated, $sctx));
var_dump($sctx->getSystemErrors()['error'][0]['message']);

?>
--EXPECT--
bool(true)
Invalid validation spec detected. Fix spec errors first. Errors: 1 Warnings: 1. First error: param: 'ROOT' message: 'VALIDATE_INT must have valid "min" and "max" options. min: "5" max:"1"'. Hint: validate_spec($spec, $unvalidated, $ctx); validate_get_system_errors($ctx);
int(1)
int(1)
array(0) {
}
NULL

**** procedural validate() throws the same exception ****
ValidateInvalidSpecException

**** validate_spec() itself never throws for a bad spec ****
bool(false)
string(70) "VALIDATE_INT must have valid "min" and "max" options. min: "5" max:"1""
