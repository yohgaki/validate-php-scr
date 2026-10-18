--TEST--
validate() misc branches - context accessors, logger, error modes, wrapper argument checks
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

$int_spec = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>100]];

echo "**** setRootName / getCurrentParam / getContext ****\n";
$ctx = validate_init('MYROOT');
$copy = 'x';
validate($ctx, $copy, $int_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump(array_keys(validate_get_system_errors($ctx)['error']));
$ctx->setRootName('RENAMED');
var_dump($ctx->getCurrentParam());
var_dump($ctx->getContext() instanceof Validate);
var_dump(validate_get_status($ctx));

echo "\n**** logger function with VALIDATE_OPT_LOG_ERROR ****\n";
$ctx = validate_init();
validate_set_logger_function($ctx, function ($ctx, $error) {
    echo 'LOGGED: ', $error['message'], "\n";
});
$copy = 'x';
validate($ctx, $copy, $int_spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_LOG_ERROR);

echo "\n**** VALIDATE_OPT_LOG_ERROR without logger warns ****\n";
$ctx = validate_init();
$copy = 'x';
validate($ctx, $copy, $int_spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_LOG_ERROR);

echo "\n**** error storage modes ****\n";
$specs = [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10],
          ['a'=>[VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10],
                ['b'=>$int_spec]]]];
foreach (['FULL'=>VALIDATE_OPT_ERROR_FULL, 'PARAM'=>VALIDATE_OPT_ERROR_PARAM,
          'SQUASH'=>VALIDATE_OPT_ERROR_SQUASH] as $mode => $opt) {
    $ctx = validate_init();
    $copy = ['a'=>['b'=>'bad']];
    validate($ctx, $copy, $specs, VALIDATE_OPT_DISABLE_EXCEPTION | $opt);
    $err = validate_get_system_errors($ctx)['error'];
    echo "$mode:\n";
    if ($mode === 'FULL') {
        var_dump(isset($err['ROOT']['a']['b'][0]['message']) ? $err['ROOT']['a']['b'][0]['message'] : null);
    } else {
        var_dump(array_keys($err));
    }
}

echo "\n**** unvalidated input remains ****\n";
// An input key the VALIDATE_ARRAY spec does not declare is an error by
// default; it stays in $inputs either way.
$specs = [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10], ['a'=>$int_spec]];
$ctx = validate_init();
$copy = ['a'=>5, 'extra'=>'x'];
var_dump(validate($ctx, $copy, $specs, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
var_dump($copy);
var_dump(validate_get_system_errors($ctx)['error']['ROOT'][0]['message']);
// VALIDATE_OPT_UNVALIDATED allows leftovers (multi-stage validation).
$ctx = validate_init();
$copy = ['a'=>5, 'extra'=>'x'];
var_dump(validate($ctx, $copy, $specs, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_UNVALIDATED),
         $ctx->getStatus());
var_dump($copy);

echo "\n**** OO validate() with spec warnings only ****\n";
$v = new Validate();
$in = 5;
$bad_spec = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>100, 'foo'=>1]];
var_dump(@$v->validate($in, $bad_spec, VALIDATE_OPT_CHECK_SPEC));

echo "\n**** procedural wrapper argument errors ****\n";
try {
    $bad = 5;
    $in = 1;
    validate($bad, $in, $int_spec, VALIDATE_OPT_NONE);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
try {
    $ctx = validate_init();
    $in = 1;
    validate($ctx, $in, 'not array', VALIDATE_OPT_NONE);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
try {
    $ctx = validate_init();
    $in = 1;
    validate($ctx, $in, $int_spec, 2.0); // float, integral - passes bit check, fails is_int
} catch (Exception $e) {
    var_dump($e->getMessage());
}

?>
--EXPECTF--
**** setRootName / getCurrentParam / getContext ****
array(1) {
  [0]=>
  string(6) "MYROOT"
}
string(7) "RENAMED"
bool(true)
NULL

**** logger function with VALIDATE_OPT_LOG_ERROR ****
LOGGED: VALIDATE_INT: Invalid int string.

**** VALIDATE_OPT_LOG_ERROR without logger warns ****

Warning: Validate logger function is not registered in %s on line %d

**** error storage modes ****
FULL:
string(33) "VALIDATE_INT: Invalid int string."
PARAM:
array(1) {
  [0]=>
  string(1) "b"
}
SQUASH:
array(1) {
  [0]=>
  string(10) "ROOT=>a=>b"
}

**** unvalidated input remains ****
NULL
bool(false)
array(1) {
  ["extra"]=>
  string(1) "x"
}
string(56) "Validate: Unvalidated value remains. Undeclared keys: 1."
array(1) {
  ["a"]=>
  int(5)
}
bool(true)
array(1) {
  ["extra"]=>
  string(1) "x"
}

**** OO validate() with spec warnings only ****
int(5)

**** procedural wrapper argument errors ****
string(40) "1st parameter is not a Validate context."
string(19) "Spec must be array."
string(56) "Function option must be int made of VALIDATE_OPT_* bits."
