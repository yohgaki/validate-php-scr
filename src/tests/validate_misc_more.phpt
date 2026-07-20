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
// Note: with a plain VALIDATE_ARRAY spec, leftover keys stay in $inputs
// without an error; the "Unvalidated value remains" check only fires when
// the top-level spec also carries VALIDATE_FLAG_ARRAY.
$specs = [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10], ['a'=>$int_spec]];
$ctx = validate_init();
$copy = ['a'=>5, 'extra'=>'x'];
var_dump(validate($ctx, $copy, $specs, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
var_dump($copy); // leftover input remains silently
$specs = [VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY, ['min'=>0, 'max'=>10], ['a'=>$int_spec]];
$ctx = validate_init();
$copy = ['a'=>5, 'extra'=>'x'];
var_dump(validate($ctx, $copy, $specs, VALIDATE_OPT_DISABLE_EXCEPTION), $ctx->getStatus());
var_dump(validate_get_system_errors($ctx)['error']['ROOT'][0]['message']);
$ctx = validate_init();
$copy = ['a'=>5, 'extra'=>'x'];
var_dump(validate($ctx, $copy, $specs, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_UNVALIDATED),
         $ctx->getStatus());

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

echo "\n**** reportWarning / reportNotice (internal, via reflection) ****\n";
$ctx = validate_init();
$ctx->setContext($ctx, 'p', true, 'v', [VALIDATE_INT, VALIDATE_FLAG_NONE, []],
                 VALIDATE_OPT_DISABLE_EXCEPTION);
$ctx->setContextErrorValue('v');
foreach (['reportWarning'=>'warning', 'reportNotice'=>'notice'] as $method => $bucket) {
    $m = new ReflectionMethod('Validate', $method);
    if (PHP_VERSION_ID < 80100) {
    $m->setAccessible(true); // required on PHP 8.0, deprecated no-op on 8.5+
}
    $m->invoke($ctx, ['message'=>"internal $bucket", 'value'=>'v'],
               [VALIDATE_INT, VALIDATE_FLAG_NONE, []], VALIDATE_OPT_DISABLE_EXCEPTION);
    var_dump(validate_get_system_errors($ctx)[$bucket]['ROOT'][0]['message']);
}

echo "\n**** validateInvalid (internal, via reflection) ****\n";
$ctx = validate_init();
$ctx->setContext($ctx, 'p', true, 'v', [VALIDATE_INT, VALIDATE_FLAG_NONE, []],
                 VALIDATE_OPT_DISABLE_EXCEPTION);
$m = new ReflectionMethod('Validate', 'validateInvalid');
if (PHP_VERSION_ID < 80100) {
    $m->setAccessible(true); // required on PHP 8.0, deprecated no-op on 8.5+
}
$validated = null;
$args = [&$validated, 'v', VALIDATE_INVALID, 0, [], VALIDATE_OPT_DISABLE_EXCEPTION];
var_dump($m->invokeArgs($ctx, $args));
var_dump(validate_get_system_errors($ctx)['error']['ROOT'][0]['message']);

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
array(1) {
  ["a"]=>
  int(5)
}
bool(true)
array(1) {
  ["extra"]=>
  string(1) "x"
}
NULL
bool(false)
string(36) "Validate: Unvalidated value remains."
array(1) {
  ["a"]=>
  int(5)
}
bool(true)

**** OO validate() with spec warnings only ****
int(5)

**** procedural wrapper argument errors ****
string(40) "1st parameter is not a Validate context."
string(19) "Spec must be array."
string(28) "Function option must be int."

**** reportWarning / reportNotice (internal, via reflection) ****
string(16) "internal warning"
string(15) "internal notice"

**** validateInvalid (internal, via reflection) ****
bool(false)
string(55) "VALIDATE_INVALID: This validator should not be called. "
