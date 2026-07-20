--TEST--
Validate::validate() OO entry - broken spec raises E_USER_WARNING then E_USER_ERROR
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
$v->validate($in, $spec, VALIDATE_OPT_CHECK_SPEC);
echo "not reached\n";

?>
--EXPECTF--
Warning: Validation spec problem detected. Check spec errors. Hint: validate_get_errors($ctx); Warnings: 1 in %s on line %d
%A
Fatal error: Invalid validation spec detected. Fix spec errors first. Hint: validate_get_errors($ctx);Errors: 1 in %s on line %d%A
