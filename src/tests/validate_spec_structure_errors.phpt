--TEST--
validate_spec() structural errors - IDs, flags, options, MULTI and ARRAY shapes
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

function check($title, $spec)
{
    echo "== $title ==\n";
    $ctx = null;
    $un = null;
    var_dump(validate_spec($spec, $un, $ctx));
    $errs = validate_get_system_errors($ctx);
    foreach (['error', 'warning', 'notice'] as $t) {
        foreach ($errs[$t] as $e) {
            echo strtoupper($t), ': ', $e['message'], "\n";
        }
    }
    echo "\n";
}

$sub = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10]];

check('ID missing', []);
check('ID is not int', ['x', 0, []]);
check('ID out of range', [999, 0, []]);
check('FLAGS missing (options present)', [0=>VALIDATE_INT, 2=>['min'=>0, 'max'=>1]]);
check('OPTIONS missing', [VALIDATE_INT, VALIDATE_FLAG_NONE]);
check('OPTIONS is not array', [VALIDATE_INT, VALIDATE_FLAG_NONE, 'x']);
check('VALIDATE_UNVALIDATED passes', [VALIDATE_UNVALIDATED, 0, []]);

check('MULTI needs 4 elements', [VALIDATE_MULTI, VALIDATE_MULTI_AND, []]);
check('MULTI params slot missing', [0=>VALIDATE_MULTI, 1=>VALIDATE_MULTI_AND, 2=>[], 5=>[]]);
check('MULTI both AND and OR', [VALIDATE_MULTI, VALIDATE_MULTI_AND | VALIDATE_MULTI_OR, [], [$sub]]);
check('MULTI missing AND/OR', [VALIDATE_MULTI, 0, [], [$sub]]);
check('MULTI extra flag bits', [VALIDATE_MULTI, VALIDATE_MULTI_AND | (1 << 5), [], [$sub]]);
check('MULTI useless options', [VALIDATE_MULTI, VALIDATE_MULTI_AND, ['x'=>1], [$sub]]);
check('MULTI with VALIDATE_FLAG_ARRAY', [VALIDATE_MULTI, VALIDATE_MULTI_AND | VALIDATE_FLAG_ARRAY, [], [$sub]]);
check('MULTI with broken sub-spec', [VALIDATE_MULTI, VALIDATE_MULTI_AND, [], ['broken']]);

check('ARRAY params missing', [VALIDATE_ARRAY, 0, ['min'=>0, 'max'=>10]]);
check('ARRAY params not array', [VALIDATE_ARRAY, 0, ['min'=>0, 'max'=>10], 'x']);
check('ARRAY min/max missing', [VALIDATE_ARRAY, 0, [], ['p'=>$sub]]);
check('ARRAY invalid min/max', [VALIDATE_ARRAY, 0, ['min'=>-1, 'max'=>-2], ['p'=>$sub]]);
check('ARRAY sub-spec is not array', [VALIDATE_ARRAY, 0, ['min'=>0, 'max'=>10], ['p'=>'broken']]);
check('ARRAY valid nested', [VALIDATE_ARRAY, 0, ['min'=>0, 'max'=>10], ['p'=>$sub]]);

echo "== exceptions from procedural wrappers ==\n";
try {
    validate_spec('not array');
} catch (Exception $e) {
    var_dump($e->getMessage());
}
try {
    $bad = 5;
    $un = null;
    validate_spec($sub, $un, $bad);
} catch (Exception $e) {
    var_dump($e->getMessage());
}

?>
--EXPECT--
== ID missing ==
bool(false)
ERROR: Validator ID(int) is missing.

== ID is not int ==
bool(false)
ERROR: Validator ID must be valid int.

== ID out of range ==
bool(false)
ERROR: Validator ID must be valid int.

== FLAGS missing (options present) ==
bool(false)
ERROR: Validator Flag(int) is missing.
ERROR: VALIDATE_INT: Too few elements. Spec must have 3 elements.

== OPTIONS missing ==
bool(false)
ERROR: Validator Options(array) is missing.

== OPTIONS is not array ==
bool(false)
ERROR: Validation option must be array.

== VALIDATE_UNVALIDATED passes ==
bool(true)

== MULTI needs 4 elements ==
bool(false)
ERROR: VALIDATE_MULTI requires 4 elements.

== MULTI params slot missing ==
bool(false)
ERROR: VALIDATE_MULTI spec parameters ($spec[VALIDATE_PARAMS]) is missing.

== MULTI both AND and OR ==
bool(false)
ERROR: VALIDATE_MULTI requires either VALIDATE_MULTI_AND or VALIDATE_MULTI_OR flag. Both flags are set.

== MULTI missing AND/OR ==
bool(false)
ERROR: VALIDATE_MULTI requires either VALIDATE_MULTI_AND or VALIDATE_MULTI_OR flag. Flag is missing.

== MULTI extra flag bits ==
bool(false)
ERROR: VALIDATE_MULTI Flags other than VALIDATE_MULTI_AND and VALIDATE_MULTI_OR are set.

== MULTI useless options ==
bool(true)
WARNING: VALIDATE_MULTI has options that has no effect.

== MULTI with VALIDATE_FLAG_ARRAY ==
bool(false)
ERROR: VALIDATE_MULTI Flags other than VALIDATE_MULTI_AND and VALIDATE_MULTI_OR are set.
ERROR: VALIDATE_MULTI cannot have VALIDATE_FLAG_ARRAY flag.

== MULTI with broken sub-spec ==
bool(false)
ERROR: Validation spec must be array.

== ARRAY params missing ==
bool(false)
ERROR: Array parameters ($spec[VALIDATE_PARAMS]) is missing.

== ARRAY params not array ==
bool(false)
ERROR: Array parameters ($spec[VALIDATE_PARAMS]) must be array.

== ARRAY min/max missing ==
bool(false)
ERROR: Array parameter must have "min" and "max" options.

== ARRAY invalid min/max ==
bool(false)
ERROR: Array must have valid min and max. min: "-1" max:"-2"

== ARRAY sub-spec is not array ==
bool(false)
ERROR: Validation spec must be array.

== ARRAY valid nested ==
bool(true)

== exceptions from procedural wrappers ==
string(44) "1st parameter must be validation spec array."
string(50) "3rd parameter must be instance of Validate object."
