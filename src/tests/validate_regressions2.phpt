--TEST--
validate() regressions - bugs fixed in the 2026-10-18 re-review
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting=-1
precision=14
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

// System error messages of $ctx, in the order of the error tree.
function msgs($ctx)
{
    $msgs = [];
    $errs = validate_get_system_errors($ctx)['error'];
    array_walk_recursive($errs, function ($v, $k) use (&$msgs) {
        if ($k === 'message') {
            $msgs[] = $v;
        }
    });
    return $msgs;
}
// Run one validation; print result, status and system error messages.
function t($label, $input, $spec, $opts = VALIDATE_OPT_DISABLE_EXCEPTION)
{
    $ctx = null;
    try {
        $r = validate($ctx, $input, $spec, $opts);
        $msgs = msgs($ctx);
        echo $label, ': ', json_encode($r), ' status=', var_export($ctx->getStatus(), true),
             $msgs ? ' errors='.json_encode($msgs) : '', "\n";
    } catch (Exception $e) {
        echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
    }
}
// Check a spec; print the result and every spec error / warning message.
function spec($label, $spec)
{
    $ctx = validate_init();
    $unvalidated = null;
    $ok = validate_spec($spec, $unvalidated, $ctx);
    $errs = validate_get_system_errors($ctx);
    echo $label, ': ', $ok ? 'OK' : 'NG', "\n";
    foreach (['error', 'warning'] as $type) {
        foreach ($errs[$type] as $e) {
            echo '  ', $type, ': ', $e['message'], "\n";
        }
    }
}
$A = function ($params, $flags = VALIDATE_FLAG_NONE) {
    return [VALIDATE_ARRAY, $flags, ['min'=>0, 'max'=>10], $params];
};
$S = function ($flags, $opts = []) {
    return [VALIDATE_STRING, $flags, $opts + ['min'=>0, 'max'=>100]];
};
$INT = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10]];
$CHECK = VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_CHECK_SPEC;

echo "**** the spec check keeps the caller's spec (PHP references) ****\n";
$spec = $A(['name' => $S(VALIDATE_STRING_ALPHA),
            'addr' => $A(['zip' => [VALIDATE_STRING, VALIDATE_STRING_DIGIT, ['min'=>7, 'max'=>7]]])]);
foreach ($spec[VALIDATE_PARAMS] as &$s) {
    // Leaves $s referencing the last sub-spec.
}
$before = json_encode($spec);
for ($i = 1; $i <= 3; $i++) { // Checked, checked again, then trusted.
    t("valid input #$i", ['name'=>'abc', 'addr'=>['zip'=>'1234567']], $spec, $CHECK);
}
t('invalid zip, UNVALIDATED', ['name'=>'abc', 'addr'=>['zip'=>'EVIL']], $spec, $CHECK | VALIDATE_OPT_UNVALIDATED);
echo 'spec unchanged: ', var_export(json_encode($spec) === $before, true), "\n";
unset($s);
$params = ['a' => $INT];
$ref = [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10], &$params];
var_dump(validate_spec($ref, $unvalidated), count($params), count($unvalidated[VALIDATE_PARAMS]));
$multi = [VALIDATE_MULTI, VALIDATE_MULTI_AND, [], [$INT]];
$alt = &$multi[VALIDATE_PARAMS][0];
t('MULTI sub-spec held by a reference', 5, $multi, $CHECK);
echo 'MULTI sub-specs: ', count($multi[VALIDATE_PARAMS]), "\n";
unset($alt);
$subParams = ['v' => $INT];
$sub = [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10], &$subParams];
t('"undeclared" spec with referenced params', ['k1' => ['v' => 1]],
  [VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY_KEY_ALNUM, ['min'=>0, 'max'=>10, 'undeclared'=>$sub], []], $CHECK);
echo '"undeclared" params: ', count($subParams), "\n";

echo "\n**** validate() nested in a callback (same context) ****\n";
$inner = $A(['q' => $INT]);
$cb = function ($innerInput) use ($inner) {
    return [VALIDATE_CALLBACK, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10, 'callback'=>
        function ($ctx, &$result, $input) use ($inner, $innerInput) {
            $in = $innerInput;
            $result = validate($ctx, $in, $inner, VALIDATE_OPT_DISABLE_EXCEPTION);
            return true;
        }]];
};
t('outer result after a nested call', ['n'=>1, 'id'=>5], $A(['n'=>$cb(['q'=>'1']), 'id'=>$INT]));
t('outer invalid value after a nested call', ['n'=>1, 'id'=>'x'], $A(['n'=>$cb(['q'=>'1']), 'id'=>$INT]));
t('nested failure fails the value', ['n'=>1], $A(['n'=>$cb(['q'=>'x'])]));
t('nested call in an OR alternative, later error throws', ['n'=>1, 'id'=>'DROP TABLE'],
  $A(['n'=>[VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$cb(['q'=>'1']), $INT]], 'id'=>$INT]), VALIDATE_OPT_NONE);
t('failed nested call fails only its OR alternative', ['n'=>1],
  $A(['n'=>[VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$cb(['q'=>'x']), $INT]]]), VALIDATE_OPT_NONE);
$catching = [VALIDATE_CALLBACK, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10, 'callback'=>
    function ($ctx, &$result, $input) use ($inner) {
        $in = ['q' => 'x'];
        try {
            validate($ctx, $in, $inner);
        } catch (ValidateInvalidValueException $e) {
            echo '  nested threw: ', $e->getMessage(), "\n";
        }
        $result = $input;
        return true;
    }]];
t('nested exception caught by the callback', ['n'=>1, 'm'=>2], $A(['n'=>$catching, 'm'=>$INT]));

echo "\n**** VALIDATE_MULTI_OR failure uses the MULTI spec ****\n";
t('PASSTHRU on the last alternative', '!!!',
  [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$INT, [VALIDATE_INT, VALIDATE_FLAG_PASSTHRU, ['min'=>0, 'max'=>10]]]],
  VALIDATE_OPT_NONE);
t('WARNING on the last alternative', '!!!',
  [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$INT, [VALIDATE_INT, VALIDATE_FLAG_WARNING, ['min'=>0, 'max'=>10]]]],
  VALIDATE_OPT_NONE);
$ctx = null;
$in = '!!!';
validate($ctx, $in, [VALIDATE_MULTI, VALIDATE_MULTI_OR, [],
    [$INT, [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10, 'error_message'=>'last alternative']]]],
    VALIDATE_OPT_DISABLE_EXCEPTION);
echo 'user errors: ', json_encode(validate_get_user_errors($ctx)['error']), "\n";

echo "\n**** VALIDATE_ARRAY accepts PHP arrays only ****\n";
t('ArrayObject', new ArrayObject(['a'=>1, 'evil'=>"\0"]), $A(['a'=>$INT]), VALIDATE_OPT_NONE);

echo "\n**** VALIDATE_OPT_ERROR_FULL: errors of a node and of its int-keyed child ****\n";
$list = [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10, 'error_message'=>'list'],
         [0 => [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10, 'error_message'=>'item']]]];
$ctx = null;
$in = 'x'; // Error of ROOT first ...
validate($ctx, $in, $list, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_ERROR_FULL);
$in = ['y']; // ... then of ROOT=>0.
validate($ctx, $in, $list, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_ERROR_FULL);
foreach (validate_get_system_errors($ctx)['error']['ROOT'] as $k => $v) {
    echo $k, ': ', isset($v['type']) ? 'error of ROOT: '.json_encode($v['message']) : 'child: '.json_encode($v[0]['message']), "\n";
}
echo 'user errors: ', json_encode(validate_get_user_errors($ctx)['error']), "\n";

echo "\n**** float input ****\n";
$I = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>-10, 'max'=>100]];
t('INT 3.0', 3.0, $I);
t('INT -0.0', -0.0, $I);
t('INT 2.9999999999999996', 2.9999999999999996, $I);
t('INT 41.99999999999999', 41.99999999999999, $I);
t('INT INF', INF, $I);
t('INT NAN', NAN, $I);
t('INT values 3.0', 3.0, [VALIDATE_INT, VALIDATE_FLAG_NONE, ['values'=>[3=>true]]]);
t('INT AS_STRING 1e20', 1e20, [VALIDATE_INT, VALIDATE_INT_AS_STRING, ['min'=>'0', 'max'=>'1000000000000000000000']]);
t('STRING 0.5', 0.5, $S(VALIDATE_STRING_DIGIT, ['ascii'=>'.']));
t('STRING 0.30000000000000004', 0.30000000000000004, $S(VALIDATE_STRING_DIGIT, ['ascii'=>'.']));
t('STRING INF', INF, $S(VALIDATE_STRING_ALPHA));
t('STRING "values", int input', 1, [VALIDATE_STRING, VALIDATE_FLAG_NONE, ['values'=>['1'=>true]]]);

echo "\n**** INT bounds and AS_STRING form ****\n";
t('PHP_INT_MAX, min beyond it', PHP_INT_MAX,
  [VALIDATE_INT, VALIDATE_INT_AS_STRING, ['min'=>'9223372036854775808', 'max'=>'9223372036854775809']]);
t('AS_STRING "+5"', '+5', [VALIDATE_INT, VALIDATE_INT_AS_STRING | VALIDATE_INT_POSITIVE_SIGN, ['min'=>0, 'max'=>10]]);
t('AS_STRING "+5" RAW', '+5',
  [VALIDATE_INT, VALIDATE_INT_AS_STRING | VALIDATE_INT_POSITIVE_SIGN | VALIDATE_FLAG_RAW, ['min'=>0, 'max'=>10]]);

echo "\n**** option bits ****\n";
var_dump(is_int(VALIDATE_OPT_UPPER), is_int(VALIDATE_FLAGS_UPPER));
$bit = 1 << (PHP_INT_SIZE * 8 - 2); // Bit 30 / 62.
t('unknown option bit', 1, $INT, VALIDATE_OPT_DISABLE_EXCEPTION | $bit);
$ctx = validate_init();
validate_spec([VALIDATE_INT, $bit, ['min'=>0, 'max'=>10]], $unvalidated, $ctx);
foreach (validate_get_system_errors($ctx)['warning'] as $w) {
    echo 'unknown flag bit: ', $w['message'], "\n";
}

echo "\n**** messages leave the input value out ****\n";
$ctx = null;
try {
    $in = "secret\r\n1234";
    validate($ctx, $in, $INT);
} catch (ValidateInvalidValueException $e) {
    var_dump($e->getMessage());
    $error = $e->getError();
    var_dump($error['value']);
}
$ctx = null;
$in = ['a'=>1, "evil\nkey"=>1];
validate($ctx, $in, $A(['a'=>$INT]), VALIDATE_OPT_DISABLE_EXCEPTION);
$error = validate_get_system_errors($ctx)['error']['ROOT'][0];
echo $error['message'], ' value: ', json_encode($error['value']), "\n";

echo "\n**** spec check ****\n";
spec('MULTI with non-array sub-specs', [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], 'x']);
spec('MULTI without sub-specs', [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], []]);
spec('INT empty "values"', [VALIDATE_INT, VALIDATE_FLAG_NONE, ['values'=>[]]]);
spec('STRING empty "values"', [VALIDATE_STRING, VALIDATE_FLAG_NONE, ['values'=>[]]]);
spec('option key ""', [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>1, ''=>1]]);
spec('CALLBACK string "values"', [VALIDATE_CALLBACK, VALIDATE_FLAG_NONE,
     ['min'=>0, 'max'=>10, 'values'=>['a'=>true], 'callback'=>'is_string']]);
spec('REGEXP non-bool "values"', [VALIDATE_REGEXP, VALIDATE_FLAG_NONE,
     ['min'=>0, 'max'=>10, 'regexp'=>'/a/', 'values'=>['a'=>1]]]);
spec('FLOAT "values"', [VALIDATE_FLOAT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>1, 'values'=>['1'=>true]]]);
spec('INT "ascii"', [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>1, 'ascii'=>'+']]);
spec('STRING "regexp" and "INF"', [VALIDATE_STRING, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>1, 'regexp'=>'/a/', 'INF'=>true]]);
spec('BOOL "class"', [VALIDATE_BOOL, VALIDATE_BOOL_01, ['class'=>'Foo']]);

echo "\n**** VALIDATE_OPT_KEEP_INPUTS and a referenced sub-array ****\n";
$sub = ['v' => ' 5'];
$in = ['s' => &$sub];
$trim = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>10, 'filter'=>function ($ctx, $value, &$error) {
    return trim($value);
}]];
$ctx = null;
var_dump(validate($ctx, $in, $A(['s'=>$A(['v'=>$trim])]), VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_KEEP_INPUTS));
echo 'caller sub-array: ', json_encode($sub), "\n";

echo "\n**** RFC 3454 table C messages ****\n";
$mb = $S(VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB);
t('U+3000', "a\xE3\x80\x80b", $mb);
t('U+3000 allowed by "unicode"', "a\xE3\x80\x80b", $S(VALIDATE_STRING_ALPHA | VALIDATE_STRING_MB, ['unicode'=>[0x3000=>true]]));
t('U+200B', "a\xE2\x80\x8Bb", $mb);
t('U+2028', "a\xE2\x80\xA8b", $mb);
t('U+E000', "a\xEE\x80\x80b", $mb);
t('U+FFFD', "a\xEF\xBF\xBDb", $mb);
?>
--EXPECT--
**** the spec check keeps the caller's spec (PHP references) ****
valid input #1: {"name":"abc","addr":{"zip":"1234567"}} status=true
valid input #2: {"name":"abc","addr":{"zip":"1234567"}} status=true
valid input #3: {"name":"abc","addr":{"zip":"1234567"}} status=true
invalid zip, UNVALIDATED: null status=false errors=["VALIDATE_STRING: Length is out of range. min: \"7\" max: \"7\""]
spec unchanged: true
bool(true)
int(1)
int(0)
MULTI sub-spec held by a reference: 5 status=true
MULTI sub-specs: 1
"undeclared" spec with referenced params: {"k1":{"v":1}} status=true
"undeclared" params: 1

**** validate() nested in a callback (same context) ****
outer result after a nested call: {"n":{"q":1},"id":5} status=true
outer invalid value after a nested call: null status=false errors=["VALIDATE_INT: Invalid int string."]
nested failure fails the value: null status=false errors=["VALIDATE_INT: Invalid int string.","Validate: validate() called by a callback with this context failed."]
nested call in an OR alternative, later error throws: ValidateInvalidValueException: param: 'ROOT=>id' error: 'VALIDATE_INT: Invalid int string.'
failed nested call fails only its OR alternative: {"n":1} status=true
  nested threw: param: 'ROOT=>n=>q' error: 'VALIDATE_INT: Invalid int string.'
nested exception caught by the callback: null status=false errors=["VALIDATE_INT: Invalid int string.","Validate: validate() called by a callback with this context failed."]

**** VALIDATE_MULTI_OR failure uses the MULTI spec ****
PASSTHRU on the last alternative: ValidateInvalidValueException: param: 'ROOT' error: 'VALIDATE_MULTI: All of specs are failed. Note: OR will not log failed validations.'
WARNING on the last alternative: ValidateInvalidValueException: param: 'ROOT' error: 'VALIDATE_MULTI: All of specs are failed. Note: OR will not log failed validations.'
user errors: []

**** VALIDATE_ARRAY accepts PHP arrays only ****
ArrayObject: ValidateInvalidValueException: param: 'ROOT' error: 'VALIDATE_ARRAY: Input value is not array. '

**** VALIDATE_OPT_ERROR_FULL: errors of a node and of its int-keyed child ****
0: child: "VALIDATE_INT: Invalid int string."
1: error of ROOT: "VALIDATE_ARRAY: Input value is not array. "
user errors: {"ROOT":[["item"],"list"]}

**** float input ****
INT 3.0: 3 status=true
INT -0.0: 0 status=true
INT 2.9999999999999996: null status=false errors=["VALIDATE_INT: Float value is not an integer."]
INT 41.99999999999999: null status=false errors=["VALIDATE_INT: Float value is not an integer."]
INT INF: null status=false errors=["VALIDATE_INT: Float value is not an integer."]
INT NAN: null status=false errors=["VALIDATE_INT: Float value is not an integer."]
INT values 3.0: 3 status=true
INT AS_STRING 1e20: "100000000000000000000" status=true
STRING 0.5: "0.5" status=true
STRING 0.30000000000000004: null status=false errors=["VALIDATE_STRING: Float value has no exact string form. Pass it as a string."]
STRING INF: null status=false errors=["VALIDATE_STRING: Float value has no exact string form. Pass it as a string."]
STRING "values", int input: "1" status=true

**** INT bounds and AS_STRING form ****
PHP_INT_MAX, min beyond it: null status=false errors=["VALIDATE_INT: Out of defined range. min: \"9223372036854775808\" max: \"9223372036854775809\""]
AS_STRING "+5": "5" status=true
AS_STRING "+5" RAW: "+5" status=true

**** option bits ****
bool(true)
bool(true)
unknown option bit: InvalidArgumentException: Function option must be int made of VALIDATE_OPT_* bits.
unknown flag bit: VALIDATE_INT has invalid flag in common flags.

**** messages leave the input value out ****
string(56) "param: 'ROOT' error: 'VALIDATE_INT: Invalid int string.'"
string(12) "secret
1234"
Validate: Unvalidated value remains. Undeclared keys: 1. value: ["evil\nkey"]

**** spec check ****
MULTI with non-array sub-specs: NG
  error: VALIDATE_MULTI spec parameters ($spec[VALIDATE_PARAMS]) must be a non-empty array of specs.
MULTI without sub-specs: NG
  error: VALIDATE_MULTI spec parameters ($spec[VALIDATE_PARAMS]) must be a non-empty array of specs.
INT empty "values": NG
  error: VALIDATE_INT "values" option is empty.
STRING empty "values": NG
  error: VALIDATE_STRING "values" option is empty.
option key "": OK
  warning: VALIDATE_INT has unknown spec option and/or invalid spec option.
CALLBACK string "values": OK
REGEXP non-bool "values": NG
  error: VALIDATE_REGEXP "values" option must have bool value.
FLOAT "values": OK
  warning: VALIDATE_FLOAT has "values" option that has no effect.
INT "ascii": OK
  warning: VALIDATE_INT has "ascii" option that has no effect.
STRING "regexp" and "INF": OK
  warning: VALIDATE_STRING has "regexp" option that has no effect.
  warning: VALIDATE_STRING has "INF"/"-INF"/"length"/"decimal"/"thousand" options that have no effect.
BOOL "class": OK
  warning: VALIDATE_BOOL has "class" option that has no effect.

**** VALIDATE_OPT_KEEP_INPUTS and a referenced sub-array ****
array(1) {
  ["s"]=>
  array(1) {
    ["v"]=>
    int(5)
  }
}
caller sub-array: {"v":" 5"}

**** RFC 3454 table C messages ****
U+3000: null status=false errors=["VALIDATE_STRING: Unicode char prohibited by RFC 3454 table C.1.2 (non-ASCII space) detected. code point: U+3000"]
U+3000 allowed by "unicode": "a\u3000b" status=true
U+200B: null status=false errors=["VALIDATE_STRING: Unicode char prohibited by RFC 3454 table C.1.2 (non-ASCII space) detected. code point: U+200B"]
U+2028: null status=false errors=["VALIDATE_STRING: Unicode char prohibited by RFC 3454 table C.2.2 (non-ASCII control) detected. code point: U+2028"]
U+E000: null status=false errors=["VALIDATE_STRING: Unicode char prohibited by RFC 3454 table C.3 (private use) detected. code point: U+E000"]
U+FFFD: null status=false errors=["VALIDATE_STRING: Unicode char prohibited by RFC 3454 table C.6 (inappropriate for plain text) detected. code point: U+FFFD"]
