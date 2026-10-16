--TEST--
validate() VALIDATE_FLAG_ARRAY edge branches - empty, scalar input, key formats, alimit, nested, filters
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

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION;

$base = array(VALIDATE_INT, VALIDATE_FLAG_ARRAY, array('min'=>0, 'max'=>100, 'amin'=>0, 'amax'=>5));

echo "**** empty input with VALIDATE_FLAG_EMPTY ****\n";
$spec = $base;
$spec[VALIDATE_FLAGS] |= VALIDATE_FLAG_EMPTY;
$ctx = validate_init();
$copy = '';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** scalar input is rejected ****\n";
$ctx = validate_init();
$copy = 42;
var_dump(validate($ctx, $copy, $base, $func_opts), $ctx->getStatus());

echo "\n**** element count out of range ****\n";
$ctx = validate_init();
$copy = array(1, 2, 3, 4, 5, 6);
var_dump(validate($ctx, $copy, $base, $func_opts), $ctx->getStatus());

echo "\n**** invalid key format (string key without KEY_ALNUM) ****\n";
$ctx = validate_init();
$copy = array('name'=>1);
var_dump(validate($ctx, $copy, $base, $func_opts), $ctx->getStatus());

echo "\n**** VALIDATE_FLAG_ARRAY_KEY_ALNUM ****\n";
$spec = $base;
$spec[VALIDATE_FLAGS] |= VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$ctx = validate_init();
$copy = array('name'=>1, 'other_key-2'=>2);
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
$ctx = validate_init();
$copy = array('bad key!'=>1);
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** key_callback option ****\n";
$spec = $base;
$spec[VALIDATE_FLAGS] |= VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$spec[VALIDATE_OPTIONS]['key_callback'] = function ($ctx, $key) {
    return strncmp((string)$key, 'k_', 2) === 0;
};
$ctx = validate_init();
$copy = array('k_1'=>1, 'x_2'=>2);
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** nested array without RECURSIVE flag ****\n";
$ctx = validate_init();
$copy = array(1, array(2));
var_dump(validate($ctx, $copy, $base, $func_opts), $ctx->getStatus());

echo "\n**** nested array with RECURSIVE flag ****\n";
$spec = $base;
$spec[VALIDATE_FLAGS] |= VALIDATE_FLAG_ARRAY_RECURSIVE;
$ctx = validate_init();
$copy = array(1, array(2, 3));
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** alimit exceeded through recursion ****\n";
$spec = $base;
$spec[VALIDATE_FLAGS] |= VALIDATE_FLAG_ARRAY_RECURSIVE;
$spec[VALIDATE_OPTIONS]['amax'] = 4;
$spec[VALIDATE_OPTIONS]['alimit'] = 5;
$ctx = validate_init();
$copy = array(1, 2, 3, array(10, 20, 30, 40));
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** filter applied per element (VALIDATE_FLAG_ARRAY) ****\n";
$spec = $base;
$spec[VALIDATE_OPTIONS]['filter'] = function ($ctx, $input, &$error) {
    return trim((string)$input);
};
$ctx = validate_init();
$copy = array(' 1 ', ' 2 ');
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** filter error on element ****\n";
$spec[VALIDATE_OPTIONS]['filter'] = function ($ctx, $input, &$error) {
    if ($input > 1) {
        $error = 'too big';
        return null;
    }
    return $input;
};
$ctx = validate_init();
$copy = array(1, 2);
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** array filter with non-array input ****\n";
$ctx = validate_init();
$copy = 'scalar';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());

echo "\n**** scalar filter error (no FLAG_ARRAY) ****\n";
$spec = array(VALIDATE_INT, VALIDATE_FLAG_NONE,
         array('min'=>0, 'max'=>100,
          'filter'=>function ($ctx, $input, &$error) { $error = 'filter says no'; return null; }));
$ctx = validate_init();
$copy = '42';
var_dump(validate($ctx, $copy, $spec, $func_opts), $ctx->getStatus());
$_tmp = $ctx->getSystemErrors(); // PHP 5.3 cannot index a call result
var_dump($_tmp['error']['ROOT'][0]['message']);

?>
--EXPECT--
**** empty input with VALIDATE_FLAG_EMPTY ****
string(0) ""
bool(true)

**** scalar input is rejected ****
NULL
bool(false)

**** element count out of range ****
NULL
bool(false)

**** invalid key format (string key without KEY_ALNUM) ****
NULL
bool(false)

**** VALIDATE_FLAG_ARRAY_KEY_ALNUM ****
array(2) {
  ["name"]=>
  int(1)
  ["other_key-2"]=>
  int(2)
}
bool(true)
NULL
bool(false)

**** key_callback option ****
NULL
bool(false)

**** nested array without RECURSIVE flag ****
NULL
bool(false)

**** nested array with RECURSIVE flag ****
array(2) {
  [0]=>
  int(1)
  [1]=>
  array(2) {
    [0]=>
    int(2)
    [1]=>
    int(3)
  }
}
bool(true)

**** alimit exceeded through recursion ****
NULL
bool(false)

**** filter applied per element (VALIDATE_FLAG_ARRAY) ****
array(2) {
  [0]=>
  int(1)
  [1]=>
  int(2)
}
bool(true)

**** filter error on element ****
NULL
bool(false)

**** array filter with non-array input ****
NULL
bool(false)

**** scalar filter error (no FLAG_ARRAY) ****
NULL
bool(false)
string(41) "VALIDATE_INT filter error: filter says no"
