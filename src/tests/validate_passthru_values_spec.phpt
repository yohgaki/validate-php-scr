--TEST--
PASSTHRU / validate_error() leave failed values out; INT "values" sign rules; BOOL RAW; spec check matches the engine
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

function run($title, $inputs, $spec)
{
    $ctx = null;
    try {
        $r = validate($ctx, $inputs, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
        $errs = validate_get_system_errors($ctx);
        $uerrs = validate_get_user_errors($ctx);
        echo $title, ': ', json_encode(array(
            'result' => $r,
            'validated' => $ctx->getValidated(),
            'status' => $ctx->getStatus(),
            'errors' => count($errs['error']) + count($uerrs['error']),
            'left' => $inputs,
        )), "\n";
    } catch (Exception $e) {
        echo $title, ': ', get_class($e), "\n";
    }
}

function check($title, $spec)
{
    $ctx = null;
    $un = null;
    $ok = validate_spec($spec, $un, $ctx);
    echo $title, ': ', $ok ? 'valid' : 'invalid';
    foreach (validate_get_system_errors($ctx) as $type => $list) {
        foreach ($list as $e) {
            echo ' / ', strtoupper($type), ': ', $e['message'];
        }
    }
    echo "\n";
    return $un;
}

$mm = array('min' => 0, 'max' => 10);
$cb_error = function ($ctx, &$result, $input) {
    validate_error($ctx, 'callback error');
    return true;
};
$cb_false = function ($ctx, &$result, $input) {
    return $input !== 'bad';
};

echo "**** VALIDATE_FLAG_PASSTHRU ****\n";
run('validate_error(), top level', 'abc',
    array(VALIDATE_CALLBACK, VALIDATE_CALLBACK_ALPHA | VALIDATE_FLAG_PASSTHRU, $mm + array('callback' => $cb_error)));
run('callback false, top level', 'bad',
    array(VALIDATE_CALLBACK, VALIDATE_CALLBACK_ALPHA | VALIDATE_FLAG_PASSTHRU, $mm + array('callback' => $cb_false)));
run('callback false, VALIDATE_FLAG_ARRAY', array('ok', 'bad', 'ok'),
    array(VALIDATE_CALLBACK, VALIDATE_CALLBACK_ALPHA | VALIDATE_FLAG_PASSTHRU | VALIDATE_FLAG_ARRAY,
     $mm + array('amin' => 0, 'amax' => 5, 'callback' => $cb_false)));
run('STRING, VALIDATE_FLAG_ARRAY', array('ok', 'b2d'),
    array(VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_FLAG_PASSTHRU | VALIDATE_FLAG_ARRAY,
     $mm + array('amin' => 0, 'amax' => 5)));
run('validate_error(), parameter', array('a' => 'abc', 'b' => 'xyz'),
    array(VALIDATE_ARRAY, VALIDATE_FLAG_NONE, array('min' => 0, 'max' => 2), array(
        'a' => array(VALIDATE_CALLBACK, VALIDATE_CALLBACK_ALPHA | VALIDATE_FLAG_PASSTHRU, $mm + array('callback' => $cb_error)),
        'b' => array(VALIDATE_STRING, VALIDATE_STRING_ALPHA, $mm),
    )));

echo "\n**** validate_error() fails the value ****\n";
run('callback returns true', array('a' => 'abc'),
    array(VALIDATE_ARRAY, VALIDATE_FLAG_NONE, array('min' => 0, 'max' => 1), array(
        'a' => array(VALIDATE_CALLBACK, VALIDATE_CALLBACK_ALPHA, $mm + array('callback' => $cb_error)),
    )));

echo "\n**** VALIDATE_INT \"values\" and signs ****\n";
run('"-5", no negative value', '-5', array(VALIDATE_INT, 0, array('values' => array(5 => true))));
run('"-5", negative value', '-5', array(VALIDATE_INT, 0, array('values' => array(-5 => true, 5 => true))));
run('"-5", VALIDATE_INT_NEGATIVE_SIGN', '-5', array(VALIDATE_INT, VALIDATE_INT_NEGATIVE_SIGN, array('values' => array(5 => true))));
run('"+5", no sign flag', '+5', array(VALIDATE_INT, 0, array('values' => array(5 => true))));
run('"+5", VALIDATE_INT_POSITIVE_SIGN', '+5', array(VALIDATE_INT, VALIDATE_INT_POSITIVE_SIGN, array('values' => array(5 => true))));
run('"+5", POSITIVE_SIGN | RAW', '+5', array(VALIDATE_INT, VALIDATE_INT_POSITIVE_SIGN | VALIDATE_FLAG_RAW, array('values' => array(5 => true))));
run('"-0", negative value', '-0', array(VALIDATE_INT, 0, array('values' => array(-1 => true, 0 => true))));
run('int -5, no sign flag', -5, array(VALIDATE_INT, 0, array('values' => array(-5 => true))));

echo "\n**** \$unvalidated keeps broken VALIDATE_ARRAY nodes ****\n";
$un = check('broken nested array', array(VALIDATE_ARRAY, 0, array('min' => 0, 'max' => 5), array(
    'ok' => array(VALIDATE_INT, 0, array('min' => 0, 'max' => 5)),
    'bad' => array(VALIDATE_ARRAY, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT, array('min' => 0, 'max' => 5), array(
        'c' => array(VALIDATE_INT, 0, array('min' => 0, 'max' => 5)),
    )),
)));
echo 'unvalidated: ', json_encode($un), "\n";

echo "\n**** VALIDATE_ARRAY with VALIDATE_FLAG_EMPTY_TO_DEFAULT ****\n";
$arr = array(VALIDATE_ARRAY, VALIDATE_FLAG_EMPTY_TO_DEFAULT, array('min' => 0, 'max' => 5, 'default' => array('x' => 1)), array(
    'x' => array(VALIDATE_INT, 0, array('min' => 0, 'max' => 5)),
));
check('spec', $arr);
check('spec without default', array(VALIDATE_ARRAY, VALIDATE_FLAG_EMPTY_TO_DEFAULT, array('min' => 0, 'max' => 5), array()));
run('parameter ""', array('a' => ''), array(VALIDATE_ARRAY, 0, array('min' => 0, 'max' => 1), array('a' => $arr)));
run('top level ""', '', $arr);
run('top level array', array('x' => '3'), $arr);

echo "\n**** VALIDATE_MULTI options ****\n";
$multi = array(VALIDATE_MULTI, VALIDATE_MULTI_OR, array('filter' => function ($ctx, $input, &$error) { return trim($input); }), array(
    array(VALIDATE_INT, 0, array('min' => 0, 'max' => 9)),
));
check('filter', $multi);
run('filter applied', ' 7 ', $multi);
check('filter not callable', array(VALIDATE_MULTI, VALIDATE_MULTI_OR, array('filter' => 'no_such_function'), array(array(VALIDATE_INT, 0, array('min' => 0, 'max' => 9)))));
check('error_message', array(VALIDATE_MULTI, VALIDATE_MULTI_OR, array('error_message' => 'x', '_user' => 1), array(array(VALIDATE_INT, 0, array('min' => 0, 'max' => 9)))));

echo "\n**** VALIDATE_BOOL without format flags ****\n";
check('spec', array(VALIDATE_BOOL, 0, array()));
run('true', true, array(VALIDATE_BOOL, 0, array()));
run('"1"', '1', array(VALIDATE_BOOL, 0, array()));
run('"YES", AS_STRING', 'YES', array(VALIDATE_BOOL, VALIDATE_BOOL_YES_NO | VALIDATE_BOOL_AS_STRING, array()));
run('"YES", AS_STRING | RAW', 'YES', array(VALIDATE_BOOL, VALIDATE_BOOL_YES_NO | VALIDATE_BOOL_AS_STRING | VALIDATE_FLAG_RAW, array()));
?>
--EXPECT--
**** VALIDATE_FLAG_PASSTHRU ****
validate_error(), top level: {"result":null,"validated":null,"status":true,"errors":0,"left":"abc"}
callback false, top level: {"result":null,"validated":null,"status":true,"errors":0,"left":"bad"}
callback false, VALIDATE_FLAG_ARRAY: {"result":{"0":"ok","2":"ok"},"validated":{"0":"ok","2":"ok"},"status":true,"errors":0,"left":{"1":"bad"}}
STRING, VALIDATE_FLAG_ARRAY: {"result":["ok"],"validated":["ok"],"status":true,"errors":0,"left":{"1":"b2d"}}
validate_error(), parameter: {"result":{"b":"xyz"},"validated":{"b":"xyz"},"status":true,"errors":0,"left":{"a":"abc"}}

**** validate_error() fails the value ****
callback returns true: {"result":null,"validated":[],"status":false,"errors":1,"left":{"a":"abc"}}

**** VALIDATE_INT "values" and signs ****
"-5", no negative value: {"result":null,"validated":null,"status":false,"errors":1,"left":"-5"}
"-5", negative value: {"result":-5,"validated":-5,"status":true,"errors":0,"left":"-5"}
"-5", VALIDATE_INT_NEGATIVE_SIGN: {"result":null,"validated":null,"status":false,"errors":1,"left":"-5"}
"+5", no sign flag: {"result":null,"validated":null,"status":false,"errors":1,"left":"+5"}
"+5", VALIDATE_INT_POSITIVE_SIGN: {"result":5,"validated":5,"status":true,"errors":0,"left":"+5"}
"+5", POSITIVE_SIGN | RAW: {"result":"+5","validated":"+5","status":true,"errors":0,"left":"+5"}
"-0", negative value: {"result":null,"validated":null,"status":false,"errors":1,"left":"-0"}
int -5, no sign flag: {"result":-5,"validated":-5,"status":true,"errors":0,"left":-5}

**** $unvalidated keeps broken VALIDATE_ARRAY nodes ****
broken nested array: invalid / ERROR: VALIDATE_ARRAY has VALIDATE_FLAG_UNDEFINED_TO_DEFAULT but "default" option is missing.
unvalidated: [6,0,{"min":0,"max":5},{"bad":[6,524288,{"min":0,"max":5},[]]}]

**** VALIDATE_ARRAY with VALIDATE_FLAG_EMPTY_TO_DEFAULT ****
spec: valid
spec without default: invalid / ERROR: VALIDATE_ARRAY has VALIDATE_FLAG_EMPTY_TO_DEFAULT but "default" option is missing.
parameter "": {"result":{"a":{"x":1}},"validated":{"a":{"x":1}},"status":true,"errors":0,"left":[]}
top level "": {"result":{"x":1},"validated":{"x":1},"status":true,"errors":0,"left":""}
top level array: {"result":{"x":3},"validated":{"x":3},"status":true,"errors":0,"left":[]}

**** VALIDATE_MULTI options ****
filter: valid
filter applied: {"result":7,"validated":7,"status":true,"errors":0,"left":" 7 "}
filter not callable: invalid / ERROR: VALIDATE_MULTI "filter" option is not callable.
error_message: valid / WARNING: VALIDATE_MULTI has option "error_message" that has no effect. Only "filter" and "_" prefixed user options are allowed.

**** VALIDATE_BOOL without format flags ****
spec: valid / NOTICE: VALIDATE_BOOL has no boolean format flags. Only bool values are accepted, no strings.
true: {"result":true,"validated":true,"status":true,"errors":0,"left":true}
"1": {"result":null,"validated":null,"status":false,"errors":1,"left":"1"}
"YES", AS_STRING: {"result":"yes","validated":"yes","status":true,"errors":0,"left":"YES"}
"YES", AS_STRING | RAW: {"result":"YES","validated":"YES","status":true,"errors":0,"left":"YES"}
