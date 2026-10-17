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
    foreach (array('error', 'warning', 'notice') as $t) {
        foreach ($errs[$t] as $e) {
            echo strtoupper($t), ': ', $e['message'], "\n";
        }
    }
    echo "\n";
}

$sub = array(VALIDATE_INT, VALIDATE_FLAG_NONE, array('min'=>0, 'max'=>10));

check('ID missing', array());
check('ID is not int', array('x', 0, array()));
check('ID out of range', array(999, 0, array()));
check('FLAGS missing (options present)', array(0=>VALIDATE_INT, 2=>array('min'=>0, 'max'=>1)));
check('OPTIONS missing', array(VALIDATE_INT, VALIDATE_FLAG_NONE));
check('OPTIONS is not array', array(VALIDATE_INT, VALIDATE_FLAG_NONE, 'x'));
check('VALIDATE_UNVALIDATED passes', array(VALIDATE_UNVALIDATED, 0, array()));

check('MULTI needs 4 elements', array(VALIDATE_MULTI, VALIDATE_MULTI_AND, array()));
check('MULTI params slot missing', array(0=>VALIDATE_MULTI, 1=>VALIDATE_MULTI_AND, 2=>array(), 5=>array()));
check('MULTI both AND and OR', array(VALIDATE_MULTI, VALIDATE_MULTI_AND | VALIDATE_MULTI_OR, array(), array($sub)));
check('MULTI missing AND/OR', array(VALIDATE_MULTI, 0, array(), array($sub)));
check('MULTI extra flag bits', array(VALIDATE_MULTI, VALIDATE_MULTI_AND | (1 << 5), array(), array($sub)));
check('MULTI useless options', array(VALIDATE_MULTI, VALIDATE_MULTI_AND, array('x'=>1), array($sub)));
check('MULTI with VALIDATE_FLAG_ARRAY', array(VALIDATE_MULTI, VALIDATE_MULTI_AND | VALIDATE_FLAG_ARRAY, array(), array($sub)));
check('MULTI with broken sub-spec', array(VALIDATE_MULTI, VALIDATE_MULTI_AND, array(), array('broken')));

check('ARRAY params missing', array(VALIDATE_ARRAY, 0, array('min'=>0, 'max'=>10)));
check('ARRAY params not array', array(VALIDATE_ARRAY, 0, array('min'=>0, 'max'=>10), 'x'));
check('ARRAY min/max missing', array(VALIDATE_ARRAY, 0, array(), array('p'=>$sub)));
check('ARRAY invalid min/max', array(VALIDATE_ARRAY, 0, array('min'=>-1, 'max'=>-2), array('p'=>$sub)));
check('ARRAY sub-spec is not array', array(VALIDATE_ARRAY, 0, array('min'=>0, 'max'=>10), array('p'=>'broken')));
check('ARRAY valid nested', array(VALIDATE_ARRAY, 0, array('min'=>0, 'max'=>10), array('p'=>$sub)));

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

== OPTIONS missing ==
bool(false)
ERROR: Validator Options(array) is missing.

== OPTIONS is not array ==
bool(false)
ERROR: Validation option must be array.

== VALIDATE_UNVALIDATED passes ==
bool(false)
ERROR: Validator ID must be valid int.

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
WARNING: VALIDATE_MULTI has option "x" that has no effect. Only "filter" and "_" prefixed user options are allowed.

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
