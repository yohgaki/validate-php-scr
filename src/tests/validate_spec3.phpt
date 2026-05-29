--TEST--
validate_spec() - stricter checks
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

// Each check() runs validate_spec() against one intentionally-malformed spec
// and prints the resulting status + errors. The --EXPECT-- block below pins
// the exact message wording, so any regression in spec validation is caught.
function check($label, $spec) {
    $ctx = validate_init();
    $ret = validate_spec($spec, $unvalidated, $ctx);
    $errs = validate_get_system_errors($ctx);
    echo $label.': '.($ret ? 'OK' : 'FAIL')."\n";
    foreach ($errs['error'] as $e) {
        echo '  error: '.$e['message']."\n";
    }
    foreach ($errs['warning'] as $w) {
        echo '  warning: '.$w['message']."\n";
    }
}

// 1. amin == amax is valid (bug fix)
check('amin==amax', [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 1, 'max' => 10, 'amin' => 2, 'amax' => 2],
]);

// 2. VALIDATE_FLAG_UNDEFINED_TO_DEFAULT without 'default'
check('UNDEFINED_TO_DEFAULT no default', [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_FLAG_UNDEFINED_TO_DEFAULT,
    ['min' => 1, 'max' => 10],
]);

// 3. VALIDATE_FLAG_EMPTY_TO_DEFAULT without 'default'
check('EMPTY_TO_DEFAULT no default', [
    VALIDATE_INT,
    VALIDATE_FLAG_EMPTY_TO_DEFAULT,
    ['min' => 1, 'max' => 100],
]);

// 4. Invalid PCRE pattern
check('invalid regexp', [
    VALIDATE_REGEXP,
    VALIDATE_REGEXP_ALNUM,
    ['min' => 1, 'max' => 10, 'regexp' => '/[unclosed/'],
]);

// 5. filter is not callable
check('filter not callable', [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 1, 'max' => 10, 'filter' => 12345],
]);

// 6. key_callback is not callable
check('key_callback not callable', [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_FLAG_ARRAY,
    ['min' => 1, 'max' => 10, 'amin' => 0, 'amax' => 5, 'key_callback' => 'not_a_function_xyz'],
]);

// 7. VALIDATE_BOOL with no format flags
check('BOOL no format flags', [
    VALIDATE_BOOL,
    VALIDATE_FLAG_NONE,
    [],
]);

// 8. ascii with non-ASCII bytes
check('ascii non-ASCII', [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 1, 'max' => 10, 'ascii' => "\xc3\xa9"],
]);

// 9. INF on VALIDATE_INT
check('INF on INT', [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => 100, 'INF' => true],
]);

// 10. alimit without VALIDATE_FLAG_ARRAY
check('alimit no ARRAY flag', [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 1, 'max' => 10, 'alimit' => 5],
]);

// 11. VALIDATE_FLAG_ARRAY_RECURSIVE without VALIDATE_FLAG_ARRAY
check('ARRAY_RECURSIVE no ARRAY', [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_FLAG_ARRAY_RECURSIVE,
    ['min' => 1, 'max' => 10],
]);

// 12. STRING min as float
check('min as float', [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 1.0, 'max' => 10],
]);
?>
--EXPECT--
amin==amax: OK
UNDEFINED_TO_DEFAULT no default: FAIL
  error: VALIDATE_STRING has VALIDATE_FLAG_UNDEFINED_TO_DEFAULT but "default" option is missing.
EMPTY_TO_DEFAULT no default: FAIL
  error: VALIDATE_INT has VALIDATE_FLAG_EMPTY_TO_DEFAULT but "default" option is missing.
invalid regexp: FAIL
  error: VALIDATE_REGEXP "regexp" option is not a valid PCRE pattern: /[unclosed/
filter not callable: FAIL
  error: VALIDATE_STRING "filter" option is not callable.
key_callback not callable: FAIL
  error: VALIDATE_STRING "key_callback" option is not callable.
  warning: VALIDATE_STRING has VALIDATE_FLAG_ARRAY_KEY_ALNUM flag, but "key_callback" option is defined.
BOOL no format flags: OK
  warning: VALIDATE_BOOL has no boolean format flags. No value can be accepted.
ascii non-ASCII: FAIL
  error: VALIDATE_STRING "ascii" option must contain ASCII characters only.
INF on INT: OK
  warning: VALIDATE_INT has "INF"/"-INF"/"length" options that have no effect.
alimit no ARRAY flag: OK
  warning: VALIDATE_STRING has "alimit" option but VALIDATE_FLAG_ARRAY is not set.
ARRAY_RECURSIVE no ARRAY: OK
  warning: VALIDATE_STRING has VALIDATE_FLAG_ARRAY_RECURSIVE but VALIDATE_FLAG_ARRAY is not set.
min as float: FAIL
  error: VALIDATE_STRING "min" option must be int.
