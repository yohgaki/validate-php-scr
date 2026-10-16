--TEST--
validate() regressions - bugs fixed in the 2026-10 design review
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

// Run one validation; print result, status and system error messages.
function t($label, $input, $spec, $opts = VALIDATE_OPT_DISABLE_EXCEPTION)
{
    $ctx = null;
    try {
        $r = validate($ctx, $input, $spec, $opts);
        $msgs = [];
        $errs = validate_get_system_errors($ctx)['error'];
        array_walk_recursive($errs, function ($v, $k) use (&$msgs) {
            if ($k === 'message') {
                $msgs[] = $v;
            }
        });
        echo $label, ': ', json_encode($r), ' status=', var_export($ctx->getStatus(), true),
             $msgs ? ' errors='.json_encode($msgs) : '', "\n";
    } catch (Throwable $e) {
        echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
    }
    return $input;
}
$S = function ($flags, $opts = []) {
    return [VALIDATE_STRING, $flags, $opts + ['min'=>0, 'max'=>100]];
};
$A = function ($params, $flags = VALIDATE_FLAG_NONE) {
    return [VALIDATE_ARRAY, $flags, ['min'=>0, 'max'=>10], $params];
};
$INT = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10]];

echo "**** STRING ****\n";
t('DEL is an ASCII control char, also with _MB', "a\x7Fb", $S(VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB));
t('trailing CR in strict CRLF', "ab\r", $S(VALIDATE_STRING_ALPHA | VALIDATE_STRING_CRLF));
t('CRLF pair in strict CRLF', "a\r\nb", $S(VALIDATE_STRING_ALPHA | VALIDATE_STRING_CRLF));
t('"0" with _MB', "0", $S(VALIDATE_STRING_DIGIT | VALIDATE_STRING_MB));
t('U+00A0 is rejected', "a\u{00A0}b", $S(VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB));
t('"unicode"=>false also applies with RFC3454_C', "a\u{3042}b",
  $S(VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB | VALIDATE_STRING_RFC3454_C, ['unicode'=>[0x3042=>false]]));
t('BINARY returns a string', 12.5, $S(VALIDATE_STRING_BINARY));
var_dump(validate_spec($S(VALIDATE_STRING_MB, ['unicode'=>[0x00A0=>true]])));

echo "\n**** VALIDATE_MULTI OR ****\n";
$or = [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$INT, $S(VALIDATE_STRING_ALPHA)]];
t('2nd alternative passes, exceptions on', 'abc', $or, VALIDATE_OPT_CHECK_SPEC);
t('no alternative passes', '###', $or);
t('OR cannot launder a rejected unicode char', "a\u{00A0}b",
  [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$S(VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB)]]);
$cb_error = [VALIDATE_CALLBACK, VALIDATE_STRING_ALPHA, ['min'=>0, 'max'=>10, 'callback'=>
    function ($ctx, &$result, $input) {
        validate_error($ctx, 'rejected by callback');
        return true;
    }]];
t('validate_error() fails an alternative', 'abc', [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$cb_error]]);
// 'a' records an error but its callback returns true; the passing inner OR
// on 'b' must not hide that failure of the outer alternative.
$inner_or = [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$INT, $S(VALIDATE_STRING_ALPHA)]];
$outer = [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$A(['a'=>$cb_error, 'b'=>$inner_or])]];
t('nested OR: inner pass must not hide outer failure', ['a'=>'x', 'b'=>'abc'], $outer);

echo "\n**** INT ****\n";
$values = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['values'=>[1=>true, 5=>true]]];
t('"values" accepts an integer string', '5', $values);
t('"values" rejects "05"', '05', $values);
$as_string = [VALIDATE_INT, VALIDATE_INT_AS_STRING | VALIDATE_INT_POSITIVE_SIGN | VALIDATE_INT_NEGATIVE_SIGN,
              ['min'=>'-100', 'max'=>'100']];
foreach (['-', '+', '-0', '007', '+7', '-7'] as $in) {
    t('AS_STRING '.json_encode($in), $in, $as_string);
}
t('"-0" without AS_STRING', '-0', [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>-10, 'max'=>10]]);
validate_spec([VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>'1-2', 'max'=>'5']], $u, $sctx);
echo 'spec min "1-2": ', $sctx->getSystemErrors()['error'][0]['message'], "\n";

echo "\n**** BOOL ****\n";
t('EMPTY_TO_DEFAULT at top level', '',
  [VALIDATE_BOOL, VALIDATE_BOOL_YES_NO | VALIDATE_FLAG_EMPTY_TO_DEFAULT, ['default'=>'yes']]);

echo "\n**** NULL / presence ****\n";
t('null element with FLAG_NULL', ['a'=>null], $A(['a'=>[VALIDATE_INT, VALIDATE_FLAG_NULL, ['min'=>0, 'max'=>10]]]));
t('null element without FLAG_NULL', ['a'=>null], $A(['a'=>$INT]));
t('null top level with FLAG_NULL', null, [VALIDATE_INT, VALIDATE_FLAG_NULL, ['min'=>0, 'max'=>10]]);
t('null with FLAG_EMPTY', null, [VALIDATE_INT, VALIDATE_FLAG_EMPTY, ['min'=>0, 'max'=>10]]);
t('REJECT parameter absent', ['b'=>'1'], $A(['a'=>[VALIDATE_INT, VALIDATE_FLAG_REJECT, ['min'=>0, 'max'=>10]], 'b'=>$INT]));
t('REJECT parameter present', ['a'=>'1', 'b'=>'1'], $A(['a'=>[VALIDATE_INT, VALIDATE_FLAG_REJECT, ['min'=>0, 'max'=>10]], 'b'=>$INT]));

echo "\n**** undeclared input keys ****\n";
t('undeclared key', ['a'=>'1', 'evil'=>'x'], $A(['a'=>$INT]));
t('undeclared nested key', ['p'=>['a'=>'1', 'evil'=>'x']], $A(['p'=>$A(['a'=>$INT])]), VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_ERROR_SQUASH);
$left = t('VALIDATE_OPT_UNVALIDATED', ['a'=>'1', 'evil'=>'x'], $A(['a'=>$INT]), VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_UNVALIDATED);
var_dump($left);

echo "\n**** VALIDATE_OPT_KEEP_INPUTS keeps the caller's array untouched ****\n";
$in = ['a'=>'', 'f'=>' 3 '];
$spec = $A([
    'a'=>[VALIDATE_INT, VALIDATE_FLAG_EMPTY_TO_DEFAULT, ['min'=>0, 'max'=>10, 'default'=>7]],
    'b'=>[VALIDATE_INT, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT, ['min'=>0, 'max'=>10, 'default'=>8]],
    'f'=>[VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10, 'filter'=>function ($ctx, $v, &$e) { return trim($v); }]],
]);
var_dump(t('defaults + filter', $in, $spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_KEEP_INPUTS) === $in);

echo "\n**** VALIDATE_FLAG_ARRAY ****\n";
$arr = [VALIDATE_INT, VALIDATE_FLAG_ARRAY, ['min'=>0, 'max'=>10, 'amin'=>0, 'amax'=>5]];
$arr_null = $arr;
$arr_null[VALIDATE_FLAGS] |= VALIDATE_FLAG_NULL;
t('null input with FLAG_NULL', null, $arr_null);
t('object input', new stdClass, $arr);
t('null element with FLAG_NULL', [1, null], $arr_null);
$arr_default = $arr;
$arr_default[VALIDATE_FLAGS] |= VALIDATE_FLAG_EMPTY_TO_DEFAULT;
$arr_default[VALIDATE_OPTIONS]['default'] = ['1', '2'];
t('EMPTY_TO_DEFAULT validates the default array', '', $arr_default);
t('nested array without RECURSIVE', [['1']], $arr);
$arr_limit = $arr;
$arr_limit[VALIDATE_FLAGS] |= VALIDATE_FLAG_ARRAY_RECURSIVE;
$arr_limit[VALIDATE_OPTIONS]['amax'] = 3;
$arr_limit[VALIDATE_OPTIONS]['alimit'] = 6;
t('"alimit" counts every element (6)', [[1, 2], [3, 4]], $arr_limit);
t('"alimit" counts every element (7)', [[1, 2], [3, 4, 5]], $arr_limit);
$kc = $arr;
$kc[VALIDATE_OPTIONS]['key_callback'] = function ($ctx, $key) { return true; };
var_dump(validate_spec($kc, $u, $kctx), $kctx->getSystemErrors()['warning']);

echo "\n**** OBJECT \"class\" option ****\n";
class RegressionObj
{
    public function check($ctx)
    {
        return true;
    }
}
$obj = new RegressionObj;
t('instanceof match', $obj, [VALIDATE_OBJECT, VALIDATE_FLAG_NONE, ['callback'=>'check', 'class'=>'RegressionObj']]);
t('instanceof mismatch', $obj, [VALIDATE_OBJECT, VALIDATE_FLAG_NONE, ['callback'=>'check', 'class'=>'DateTimeInterface']]);

echo "\n**** messages ****\n";
$cb_warn = [VALIDATE_CALLBACK, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10, 'callback'=>
    function ($ctx, &$result, $input) {
        validate_warning($ctx, 'just a warning');
        $result = $input;
        return true;
    }]];
t('validate_warning() does not fail validation', 5, $cb_warn);
t('unserializable value in an error message', function () {}, $S(VALIDATE_STRING_ALPHA));
t('PASSTHRU drops the failure', ['a'=>'x', 'b'=>'1'],
  $A(['a'=>[VALIDATE_INT, VALIDATE_FLAG_PASSTHRU, ['min'=>0, 'max'=>10]], 'b'=>$INT]),
  VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_UNVALIDATED);
try {
    $in = 'x';
    validate($ctx, $in, $INT);
} catch (ValidateInvalidValueException $e) {
    var_dump($e instanceof InvalidArgumentException, $e->getError()['message']);
}

echo "\n**** spec / argument checks ****\n";
var_dump(validate_spec([VALIDATE_UNVALIDATED, VALIDATE_FLAG_NONE, []]));
try {
    validate_set_error_level(validate_init(), E_ALL);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(), "\n";
}
try {
    $in = 1;
    validate($ctx, $in, $INT, VALIDATE_OPT_ERROR_FULL | VALIDATE_OPT_ERROR_SQUASH);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(), "\n";
}

echo "\n**** REGEXP with non-scalar input ****\n";
$RE = [VALIDATE_REGEXP, VALIDATE_REGEXP_ALNUM, ['min'=>0, 'max'=>100, 'regexp'=>'/\A[a-z]+\z/']];
t('array', ['a'], $RE);
t('object', new stdClass, $RE);
?>
--EXPECT--
**** STRING ****
DEL is an ASCII control char, also with _MB: null status=false errors=["VALIDATE_STRING: Illegal char detected. ord: \"127\" chr: \"\\x7F\""]
trailing CR in strict CRLF: null status=false errors=["VALIDATE_STRING: Invalid CR\/LF detected."]
CRLF pair in strict CRLF: "a\r\nb" status=true
"0" with _MB: "0" status=true
U+00A0 is rejected: null status=false errors=["VALIDATE_STRING: Unicode CNTRL char detected. code point: U+00A0"]
"unicode"=>false also applies with RFC3454_C: null status=false errors=["VALIDATE_STRING: Detected invalid unicode defined by $option[\"unicode\"]. code point: U+3042"]
BINARY returns a string: "12.5" status=true
bool(true)

**** VALIDATE_MULTI OR ****
2nd alternative passes, exceptions on: "abc" status=true
no alternative passes: null status=false errors=["VALIDATE_MULTI: All of specs are failed. Note: OR will not log failed validations."]
OR cannot launder a rejected unicode char: null status=false errors=["VALIDATE_MULTI: All of specs are failed. Note: OR will not log failed validations."]
validate_error() fails an alternative: null status=false errors=["VALIDATE_MULTI: All of specs are failed. Note: OR will not log failed validations."]
nested OR: inner pass must not hide outer failure: null status=false errors=["VALIDATE_MULTI: All of specs are failed. Note: OR will not log failed validations."]

**** INT ****
"values" accepts an integer string: 5 status=true
"values" rejects "05": null status=false errors=["VALIDATE_INT: Failed option \"values\" match. Value is not integer."]
AS_STRING "-": null status=false errors=["VALIDATE_INT: Invalid int string."]
AS_STRING "+": null status=false errors=["VALIDATE_INT: Invalid int string."]
AS_STRING "-0": null status=false errors=["VALIDATE_INT: Invalid int string."]
AS_STRING "007": null status=false errors=["VALIDATE_INT: Invalid int string."]
AS_STRING "+7": "+7" status=true
AS_STRING "-7": "-7" status=true
"-0" without AS_STRING: null status=false errors=["VALIDATE_INT: Invalid int string."]
spec min "1-2": VALIDATE_INT "min" and "max" options must be int or integer string.

**** BOOL ****
EMPTY_TO_DEFAULT at top level: true status=true

**** NULL / presence ****
null element with FLAG_NULL: {"a":null} status=true
null element without FLAG_NULL: null status=false errors=["VALIDATE_INT: NULL input is rejected by default."]
null top level with FLAG_NULL: null status=true
null with FLAG_EMPTY: null status=true
REJECT parameter absent: {"b":1} status=true
REJECT parameter present: null status=false errors=["VALIDATE_INT: Rejected by flag."]

**** undeclared input keys ****
undeclared key: null status=false errors=["Validate: Unvalidated value remains. Undeclared keys: 'evil'"]
undeclared nested key: null status=false errors=["Validate: Unvalidated value remains. Undeclared keys: 'evil'"]
VALIDATE_OPT_UNVALIDATED: {"a":1} status=true
array(1) {
  ["evil"]=>
  string(1) "x"
}

**** VALIDATE_OPT_KEEP_INPUTS keeps the caller's array untouched ****
defaults + filter: {"a":7,"b":8,"f":3} status=true
bool(true)

**** VALIDATE_FLAG_ARRAY ****
null input with FLAG_NULL: null status=true
object input: null status=false errors=["VALIDATE_INT: Array of scalars validation. Non-array value is not allowed."]
null element with FLAG_NULL: [1,null] status=true
EMPTY_TO_DEFAULT validates the default array: [1,2] status=true
nested array without RECURSIVE: null status=false errors=["VALIDATE_INT: Array validation. Nested array is not allowed by VALIDATE_FLAG_ARRAY_RECURSIVE."]
"alimit" counts every element (6): [[1,2],[3,4]] status=true
"alimit" counts every element (7): null status=false errors=["VALIDATE_INT: Array validation. Number of elements exceed limit: \"6\". Hint: you may want to set \"alimit\" option to allow larger array."]
bool(true)
array(0) {
}

**** OBJECT "class" option ****
instanceof match: {} status=true
instanceof mismatch: null status=false errors=["VALIDATE_OBJECT: Object is not an instance of 'DateTimeInterface'. Class: 'RegressionObj'"]

**** messages ****
validate_warning() does not fail validation: 5 status=true
unserializable value in an error message: null status=false errors=["VALIDATE_STRING: Array or object parameter is passed for scalar."]
PASSTHRU drops the failure: {"b":1} status=true
bool(true)
string(33) "VALIDATE_INT: Invalid int string."

**** spec / argument checks ****
bool(false)
Error level must be E_USER_ERROR, E_USER_WARNING or E_USER_NOTICE.
VALIDATE_OPT_ERROR_FULL, VALIDATE_OPT_ERROR_PARAM and VALIDATE_OPT_ERROR_SQUASH are mutually exclusive.

**** REGEXP with non-scalar input ****
array: null status=false errors=["VALIDATE_REGEXP: Array or object parameter is passed for scalar."]
object: null status=false errors=["VALIDATE_REGEXP: Array or object parameter is passed for scalar."]
