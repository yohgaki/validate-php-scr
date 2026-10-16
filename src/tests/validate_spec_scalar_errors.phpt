--TEST--
validate_spec() scalar spec errors - flags, options, min/max, callbacks, per-type checks
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
            echo strtoupper($t), ': ', $e['message'],
                 ($e['flags'] !== '' ? ' ['.$e['flags'].']' : ''), "\n";
        }
    }
    echo "\n";
}

$mm = array('min'=>0, 'max'=>10);

check('too few elements', array(0=>VALIDATE_INT, 2=>$mm));
check('too many elements', array(VALIDATE_INT, 0, $mm, 'extra'));

check('STRING all flags incl. dangerous ones',
      array(VALIDATE_STRING,
       VALIDATE_STRING_SPACE | VALIDATE_STRING_DIGIT | VALIDATE_STRING_TAB
       | VALIDATE_STRING_CRLF | VALIDATE_STRING_CRLF_MIXED
       | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB
       | VALIDATE_STRING_BINARY | VALIDATE_STRING_RFC3454_D,
       $mm));
check('STRING invalid type-flag bit', array(VALIDATE_STRING, 1 << 13, $mm));
check('invalid common-flag bit', array(VALIDATE_INT, 1 << 30, $mm));
check('INT type flags', array(VALIDATE_INT,
      VALIDATE_INT_AS_STRING | VALIDATE_INT_POSITIVE_SIGN | VALIDATE_INT_NEGATIVE_SIGN, $mm));
check('INT invalid type-flag bit', array(VALIDATE_INT, 1 << 6, $mm));
check('FLOAT type flags', array(VALIDATE_FLOAT,
      VALIDATE_FLOAT_AS_STRING | VALIDATE_FLOAT_FRACTION | VALIDATE_FLOAT_THOUSAND
      | VALIDATE_FLOAT_SCIENTIFIC | VALIDATE_FLOAT_POSITIVE_SIGN | VALIDATE_FLOAT_NEGATIVE_SIGN,
      $mm));
check('NULL AS_STRING flag', array(VALIDATE_NULL, VALIDATE_NULL_AS_STRING, array()));
check('BOOL all format flags', array(VALIDATE_BOOL,
      VALIDATE_BOOL_AS_STRING | VALIDATE_BOOL_01 | VALIDATE_BOOL_TF
      | VALIDATE_BOOL_TRUE_FALSE | VALIDATE_BOOL_ON_OFF | VALIDATE_BOOL_YES_NO, array()));
check('BOOL without format flags', array(VALIDATE_BOOL, VALIDATE_BOOL_AS_STRING, array()));

check('default: FLAG_ARRAY needs array default',
      array(VALIDATE_INT, VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_UNDEFINED_TO_DEFAULT,
       $mm + array('amin'=>0, 'amax'=>3, 'default'=>5)));
check('default: OBJECT object default is accepted',
      array(VALIDATE_OBJECT, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT,
       array('callback'=>'check', 'default'=>new stdClass)));
check('default: OBJECT scalar default is rejected',
      array(VALIDATE_OBJECT, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT,
       array('callback'=>'check', 'default'=>'x')));
check('default: RESOURCE cannot have default',
      array(VALIDATE_RESOURCE, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT,
       array('resource'=>'stream', 'default'=>'x')));
check('default without *_TO_DEFAULT flag', array(VALIDATE_INT, 0, $mm + array('default'=>1)));
check('UNDEFINED_TO_DEFAULT without default',
      array(VALIDATE_INT, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT, $mm));
check('EMPTY_TO_DEFAULT without default',
      array(VALIDATE_INT, VALIDATE_FLAG_EMPTY_TO_DEFAULT, $mm));

check('non-scalar option value', array(VALIDATE_INT, 0, array('min'=>array(), 'max'=>10)));
check('unknown option', array(VALIDATE_INT, 0, $mm + array('foo'=>1)));
check('user option with underscore prefix', array(VALIDATE_INT, 0, $mm + array('_custom'=>1)));

check('STRING min/max not int', array(VALIDATE_STRING, 0, array('min'=>'a', 'max'=>'b')));
check('STRING min/max missing', array(VALIDATE_STRING, 0, array('max'=>5)));
check('STRING min>max', array(VALIDATE_STRING, 0, array('min'=>5, 'max'=>1)));
check('INT min/max missing', array(VALIDATE_INT, 0, array()));
check('INT min>max (bigint)', array(VALIDATE_INT, VALIDATE_INT_AS_STRING,
      array('min'=>'99999999999999999999999999', 'max'=>0)));
check('FLOAT min/max missing', array(VALIDATE_FLOAT, 0, array()));
check('FLOAT min>max', array(VALIDATE_FLOAT, 0, array('min'=>5.0, 'max'=>1.0)));
check('BOOL with max only warns (no effect)', array(VALIDATE_BOOL, VALIDATE_BOOL_01, array('max'=>5)));

check('STRING encoding without MB flag', array(VALIDATE_STRING, 0, $mm + array('encoding'=>'UTF-8')));
check('INT with encoding / float-only options', array(VALIDATE_INT, 0,
      $mm + array('encoding'=>'UTF-8', 'length'=>10)));
check('FLOAT with encoding', array(VALIDATE_FLOAT, 0, $mm + array('encoding'=>'UTF-8')));
check('FLOAT invalid decimal separator', array(VALIDATE_FLOAT, 0, $mm + array('decimal'=>'5')));
check('FLOAT thousand option without flag', array(VALIDATE_FLOAT, 0, $mm + array('thousand'=>' ')));
check('FLOAT decimal == thousand', array(VALIDATE_FLOAT, VALIDATE_FLOAT_THOUSAND,
      $mm + array('decimal'=>',', 'thousand'=>',')));
check('FLOAT invalid length', array(VALIDATE_FLOAT, 0, $mm + array('length'=>0)));

check('CALLBACK without callback', array(VALIDATE_CALLBACK, 0, $mm));
check('CALLBACK not callable', array(VALIDATE_CALLBACK, 0, $mm + array('callback'=>'no_such_func')));
check('OBJECT without callback', array(VALIDATE_OBJECT, 0, array()));
check('INT with useless callback', array(VALIDATE_INT, 0, $mm + array('callback'=>'strlen')));

check('FLAG_ARRAY without amin/amax', array(VALIDATE_INT, VALIDATE_FLAG_ARRAY, $mm));
check('amin/amax not int', array(VALIDATE_INT, VALIDATE_FLAG_ARRAY,
      $mm + array('amin'=>'a', 'amax'=>3)));
check('amin > amax', array(VALIDATE_INT, VALIDATE_FLAG_ARRAY,
      $mm + array('amin'=>5, 'amax'=>3)));
check('key_callback without FLAG_ARRAY', array(VALIDATE_INT, 0,
      $mm + array('key_callback'=>'strlen')));
check('KEY_ALNUM without FLAG_ARRAY', array(VALIDATE_INT, VALIDATE_FLAG_ARRAY_KEY_ALNUM, $mm));
check('alimit without FLAG_ARRAY', array(VALIDATE_INT, 0, $mm + array('alimit'=>10)));
check('RECURSIVE without FLAG_ARRAY', array(VALIDATE_INT, VALIDATE_FLAG_ARRAY_RECURSIVE, $mm));
check('filter not callable', array(VALIDATE_INT, 0, $mm + array('filter'=>'no_such_func')));
check('key_callback not callable', array(VALIDATE_INT,
      VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM,
      $mm + array('amin'=>0, 'amax'=>3, 'key_callback'=>'no_such_func')));

check('INT with BINARY / RAW flags', array(VALIDATE_INT,
      VALIDATE_STRING_BINARY | VALIDATE_FLAG_RAW, $mm));
check('STRING ascii option not a string', array(VALIDATE_STRING, 0, $mm + array('ascii'=>5)));
check('STRING ascii option with non-ascii char', array(VALIDATE_STRING, 0, $mm + array('ascii'=>"\x80")));
check('STRING values not an array', array(VALIDATE_STRING, 0, array('values'=>'x')));
check('STRING values with non-bool value', array(VALIDATE_STRING, 0, array('values'=>array('a'=>1))));

check('REGEXP ascii not a string', array(VALIDATE_REGEXP, 0,
      $mm + array('regexp'=>'/a/', 'ascii'=>5)));
check('REGEXP ascii with non-ascii char', array(VALIDATE_REGEXP, 0,
      $mm + array('regexp'=>'/a/', 'ascii'=>"\x80")));
check('REGEXP without regexp option', array(VALIDATE_REGEXP, 0, $mm));
check('REGEXP with broken pattern', array(VALIDATE_REGEXP, 0, $mm + array('regexp'=>'/[/')));

check('CALLBACK ascii not a string', array(VALIDATE_CALLBACK, 0,
      $mm + array('callback'=>'is_string', 'ascii'=>5)));
check('CALLBACK ascii with non-ascii char', array(VALIDATE_CALLBACK, 0,
      $mm + array('callback'=>'is_string', 'ascii'=>"\x80")));
check('INT values not an array', array(VALIDATE_INT, 0, array('values'=>'x')));
check('INT values with non-bool value', array(VALIDATE_INT, 0, array('values'=>array(1=>'x'))));
check('INT values with non-int key', array(VALIDATE_INT, 0, array('values'=>array('abc'=>true))));

check('RESOURCE without resource option', array(VALIDATE_RESOURCE, 0, array()));
check('RESOURCE resource option not string', array(VALIDATE_RESOURCE, 0, array('resource'=>5)));

?>
--EXPECT--
== too few elements ==
bool(false)
ERROR: Validator Flag(int) is missing.

== too many elements ==
bool(true)
WARNING: VALIDATE_INT: Too many elements. Spec must have 3 elements.

== STRING all flags incl. dangerous ones ==
bool(true)
NOTICE: VALIDATE_STRING has dangerous VALIDATE_STRING_BINARY flag. [VALIDATE_STRING_SPACE | VALIDATE_STRING_DIGIT | VALIDATE_STRING_TAB | VALIDATE_STRING_LF | VALIDATE_STRING_CR | VALIDATE_STRING_CRLF_MIXED | VALIDATE_STRING_CRLF | VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA | VALIDATE_STRING_ALPHA | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB | VALIDATE_STRING_BINARY | VALIDATE_STRING_RFC3454_D]
NOTICE: VALIDATE_STRING has dangerous VALIDATE_STRING_SYMBOL flag. [VALIDATE_STRING_SPACE | VALIDATE_STRING_DIGIT | VALIDATE_STRING_TAB | VALIDATE_STRING_LF | VALIDATE_STRING_CR | VALIDATE_STRING_CRLF_MIXED | VALIDATE_STRING_CRLF | VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA | VALIDATE_STRING_ALPHA | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB | VALIDATE_STRING_BINARY | VALIDATE_STRING_RFC3454_D]
NOTICE: VALIDATE_STRING has dangerous VALIDATE_STRING_CRLF_MIXED flag. [VALIDATE_STRING_SPACE | VALIDATE_STRING_DIGIT | VALIDATE_STRING_TAB | VALIDATE_STRING_LF | VALIDATE_STRING_CR | VALIDATE_STRING_CRLF_MIXED | VALIDATE_STRING_CRLF | VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA | VALIDATE_STRING_ALPHA | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB | VALIDATE_STRING_BINARY | VALIDATE_STRING_RFC3454_D]

== STRING invalid type-flag bit ==
bool(true)
WARNING: VALIDATE_STRING has invalid flag for this validator. [__INVALID_BIT__(13)]

== invalid common-flag bit ==
bool(true)
WARNING: VALIDATE_INT has invalid flag in common flags. [__INVALID_BIT__(30)]

== INT type flags ==
bool(true)

== INT invalid type-flag bit ==
bool(true)
WARNING: VALIDATE_INT has invalid flag for this validator. [__INVALID_BIT__(6)]

== FLOAT type flags ==
bool(true)

== NULL AS_STRING flag ==
bool(true)

== BOOL all format flags ==
bool(true)

== BOOL without format flags ==
bool(true)
WARNING: VALIDATE_BOOL has no boolean format flags. No value can be accepted. [VALIDATE_BOOL_AS_STRING]

== default: FLAG_ARRAY needs array default ==
bool(false)
ERROR: VALIDATE_INT has invalid "default" option. "default" value must be array with VALIDATE_FLAG_ARRAY. [VALIDATE_FLAG_UNDEFINED_TO_DEFAULT | VALIDATE_FLAG_ARRAY]

== default: OBJECT object default is accepted ==
bool(true)

== default: OBJECT scalar default is rejected ==
bool(false)
ERROR: VALIDATE_OBJECT has invalid "default" option. "default" value must be object for VALIDATE_OBJECT. [VALIDATE_FLAG_UNDEFINED_TO_DEFAULT]

== default: RESOURCE cannot have default ==
bool(false)
ERROR: VALIDATE_RESOURCE has invalid "default" option. "default" is not allowed for VALIDATE_RESOURCE. [VALIDATE_FLAG_UNDEFINED_TO_DEFAULT]

== default without *_TO_DEFAULT flag ==
bool(true)
WARNING: VALIDATE_INT has "default" option, but VALIDATE_FLAG_EMPTY_TO_DEFAULT and/or VALIDATE_FLAG_UNDEFINED_TO_DEFAULT is not set.

== UNDEFINED_TO_DEFAULT without default ==
bool(false)
ERROR: VALIDATE_INT has VALIDATE_FLAG_UNDEFINED_TO_DEFAULT but "default" option is missing. [VALIDATE_FLAG_UNDEFINED_TO_DEFAULT]

== EMPTY_TO_DEFAULT without default ==
bool(false)
ERROR: VALIDATE_INT has VALIDATE_FLAG_EMPTY_TO_DEFAULT but "default" option is missing. [VALIDATE_FLAG_EMPTY_TO_DEFAULT]

== non-scalar option value ==
bool(false)
ERROR: VALIDATE_INT has invalid "min" option. Option value must be scalar or callable.
ERROR: VALIDATE_INT "min" and "max" options must be int or integer string.

== unknown option ==
bool(true)
WARNING: VALIDATE_INT has unknown spec option and/or invalid spec option.

== user option with underscore prefix ==
bool(true)

== STRING min/max not int ==
bool(false)
ERROR: VALIDATE_STRING "min" option must be int.
ERROR: VALIDATE_STRING "max" option must be int.

== STRING min/max missing ==
bool(false)
ERROR: VALIDATE_STRING "min" and/or "max" is missing.

== STRING min>max ==
bool(false)
ERROR: VALIDATE_STRING must have valid "min" and "max" options. min: "5" max:"1"

== INT min/max missing ==
bool(false)
ERROR: VALIDATE_INT "min" and/or "max" is missing.

== INT min>max (bigint) ==
bool(false)
ERROR: VALIDATE_INT must have valid "min" and "max" options. min: "99999999999999999999999999" max:"0" [VALIDATE_INT_AS_STRING]

== FLOAT min/max missing ==
bool(false)
ERROR: VALIDATE_FLOAT "min" and/or "max" is missing.

== FLOAT min>max ==
bool(false)
ERROR: VALIDATE_FLOAT must have valid "min" and "max" options. min: "5" max:"1"

== BOOL with max only warns (no effect) ==
bool(true)
WARNING: VALIDATE_BOOL has "min" and/or "max" options that have no effect. [VALIDATE_BOOL_01]

== STRING encoding without MB flag ==
bool(true)
WARNING: VALIDATE_STRING has "encoding" option, but VALIDATE_STRING_MB flag is not set.

== INT with encoding / float-only options ==
bool(true)
WARNING: VALIDATE_INT has "encoding" option that has no effect
WARNING: VALIDATE_INT has "INF"/"-INF"/"length"/"decimal"/"thousand" options that have no effect.

== FLOAT with encoding ==
bool(true)
WARNING: VALIDATE_FLOAT has "encoding" option that has no effect

== FLOAT invalid decimal separator ==
bool(false)
ERROR: VALIDATE_FLOAT "decimal" option must be a single character other than digits, "+", "-", "e" and "E".

== FLOAT thousand option without flag ==
bool(true)
WARNING: VALIDATE_FLOAT has "thousand" option but VALIDATE_FLOAT_THOUSAND flag is not set.

== FLOAT decimal == thousand ==
bool(false)
ERROR: VALIDATE_FLOAT "decimal" and "thousand" options must differ. Both are ",". [VALIDATE_FLOAT_THOUSAND]

== FLOAT invalid length ==
bool(false)
ERROR: VALIDATE_FLOAT "length" option must be a positive int.

== CALLBACK without callback ==
bool(false)
ERROR: VALIDATE_CALLBACK has no "callback" option.

== CALLBACK not callable ==
bool(false)
ERROR: VALIDATE_CALLBACK "callback" option is not callable.

== OBJECT without callback ==
bool(false)
ERROR: VALIDATE_OBJECT has no "callback" option.

== INT with useless callback ==
bool(true)
WARNING: VALIDATE_INT has "callback" option that has no effect.

== FLAG_ARRAY without amin/amax ==
bool(false)
ERROR: VALIDATE_INT has VALIDATE_FLAG_ARRAY flag, but "amin" and/or "amax" option is missing. [VALIDATE_FLAG_ARRAY]

== amin/amax not int ==
bool(false)
ERROR: VALIDATE_INT. "amin" and "amax" options must be int. [VALIDATE_FLAG_ARRAY]

== amin > amax ==
bool(false)
ERROR: VALIDATE_INT. "amin" option is larger than "amax" option. [VALIDATE_FLAG_ARRAY]

== key_callback without FLAG_ARRAY ==
bool(true)
WARNING: VALIDATE_INT has no VALIDATE_FLAG_ARRAY flag, but "key_callback" option is defined.

== KEY_ALNUM without FLAG_ARRAY ==
bool(true)
WARNING: VALIDATE_INT has VALIDATE_FLAG_ARRAY_KEY_ALNUM flag, but VALIDATE_FLAG_ARRAY is not defined. [VALIDATE_FLAG_ARRAY_KEY_ALNUM]

== alimit without FLAG_ARRAY ==
bool(true)
WARNING: VALIDATE_INT has "alimit" option but VALIDATE_FLAG_ARRAY is not set.

== RECURSIVE without FLAG_ARRAY ==
bool(true)
WARNING: VALIDATE_INT has VALIDATE_FLAG_ARRAY_RECURSIVE but VALIDATE_FLAG_ARRAY is not set. [VALIDATE_FLAG_ARRAY_RECURSIVE]

== filter not callable ==
bool(false)
ERROR: VALIDATE_INT "filter" option is not callable.

== key_callback not callable ==
bool(false)
ERROR: VALIDATE_INT "key_callback" option is not callable. [VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM]
WARNING: VALIDATE_INT has VALIDATE_FLAG_ARRAY_KEY_ALNUM flag that has no effect: "key_callback" option is defined. [VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM]

== INT with BINARY / RAW flags ==
bool(true)
WARNING: VALIDATE_INT has invalid flag for this validator. [__INVALID_BIT__(10)]
WARNING: VALIDATE_INT has VALIDATE_INT_BINARY flag that has no effect. [__INVALID_BIT__(10) | VALIDATE_FLAG_RAW]
NOTICE: VALIDATE_INT has dangerous VALIDATE_FLAG_RAW flag. [__INVALID_BIT__(10) | VALIDATE_FLAG_RAW]

== STRING ascii option not a string ==
bool(false)
ERROR: VALIDATE_STRING "ascii" option must be a string.

== STRING ascii option with non-ascii char ==
bool(false)
ERROR: VALIDATE_STRING "ascii" option must contain ASCII characters only.

== STRING values not an array ==
bool(false)
ERROR: VALIDATE_STRING "values" option is not an array.

== STRING values with non-bool value ==
bool(false)
ERROR: VALIDATE_STRING "values" option must have bool value.

== REGEXP ascii not a string ==
bool(false)
ERROR: VALIDATE_REGEXP "ascii" option must be a string.

== REGEXP ascii with non-ascii char ==
bool(false)
ERROR: VALIDATE_REGEXP "ascii" option must contain ASCII characters only.

== REGEXP without regexp option ==
bool(false)
ERROR: VALIDATE_REGEXP has no "regexp" option or invalid value.

== REGEXP with broken pattern ==
bool(false)
ERROR: VALIDATE_REGEXP "regexp" option is not a valid PCRE pattern: /[/

== CALLBACK ascii not a string ==
bool(false)
ERROR: VALIDATE_CALLBACK "ascii" option must be a string.

== CALLBACK ascii with non-ascii char ==
bool(false)
ERROR: VALIDATE_CALLBACK "ascii" option must contain ASCII characters only.

== INT values not an array ==
bool(false)
ERROR: VALIDATE_INT "values" option is not an array.

== INT values with non-bool value ==
bool(false)
ERROR: VALIDATE_INT "values" option must have bool value.

== INT values with non-int key ==
bool(false)
ERROR: VALIDATE_INT "values" option key must be integer.

== RESOURCE without resource option ==
bool(false)
ERROR: VALIDATE_RESOURCE has no $spec["resource"] option.

== RESOURCE resource option not string ==
bool(false)
ERROR: VALIDATE_RESOURCE must have string $spec["resource"] option.
