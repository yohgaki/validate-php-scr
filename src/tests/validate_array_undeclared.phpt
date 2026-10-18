--TEST--
VALIDATE_ARRAY "undeclared" option: catch-all spec for undeclared keys
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

// Print status, result, leftover input and the error messages of one call.
function run($label, $input, $spec, $opts = VALIDATE_OPT_CHECK_SPEC)
{
    $ctx = null;
    $opts |= VALIDATE_OPT_DISABLE_EXCEPTION;
    try {
        $result = validate($ctx, $input, $spec, $opts);
    } catch (InvalidArgumentException $e) {
        echo "$label: ", get_class($e), "\n  ", $e->getMessage(), "\n";
        return;
    }
    echo "$label: ", var_export(validate_get_status($ctx), true),
         ' result=', json_encode($result), ' left=', json_encode($input), "\n";
    $_tmp = validate_get_system_errors($ctx); // PHP 5.3 cannot index a call result
    foreach ($_tmp['error'] as $param => $errors) {
        foreach ($errors as $error) {
            echo "  [$param] ", $error['message'], ' value=', json_encode($error['value']), "\n";
        }
    }
}

$value = array(VALIDATE_STRING, VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE, array('min' => 0, 'max' => 16));
$headers = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_ARRAY_KEY_ALNUM,
    array('min' => 1, 'max' => 4, 'undeclared' => $value),
    array(
        'Host' => array(VALIDATE_STRING, VALIDATE_STRING_ALNUM, array('min' => 1, 'max' => 16, 'ascii' => '.')),
    )
);

echo "**** declared and undeclared keys ****\n";
run('ok', array('X-Foo' => 'bar baz', 'Host' => 'example.com', 'Accept' => 'x'), $headers);
run('empty undeclared', array('Host' => 'example.com'), $headers);
run('bad value', array('Host' => 'example.com', 'X-Foo' => "bar\n", 'Accept' => 'x'), $headers);
run('count includes undeclared', array('Host' => 'h', 'a' => '1', 'b' => '2', 'c' => '3', 'd' => '4'), $headers);

echo "\n**** undeclared keys are checked ****\n";
run('KEY_ALNUM', array('Host' => 'h', 'X Foo' => 'bar'), $headers);
$plain = $headers;
$plain[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
run('default key rule', array('Host' => 'h', 7 => 'int key', 'X-Foo' => 'bar'), $plain);
$cb = $plain;
$cb[VALIDATE_OPTIONS]['key_callback'] = function ($ctx, $key) {
    return strncmp((string)$key, 'X-', 2) === 0;
};
run('key_callback', array('Host' => 'h', 'X-Foo' => 'bar', 'Y' => 'z'), $cb);

echo "\n**** options ****\n";
run('UNVALIDATED has no effect', array('Host' => 'h', 'X-Foo' => "bad\n"), $headers, VALIDATE_OPT_UNVALIDATED);
run('KEEP_INPUTS', array('Host' => 'h', 'X-Foo' => 'bar'), $headers, VALIDATE_OPT_KEEP_INPUTS);

echo "\n**** nested VALIDATE_ARRAY as undeclared spec ****\n";
$rows = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_ARRAY_KEY_ALNUM,
    array('min' => 0, 'max' => 3, 'undeclared' => array(
        VALIDATE_ARRAY,
        VALIDATE_FLAG_NONE,
        array('min' => 1, 'max' => 2),
        array(
            'id' => array(VALIDATE_INT, VALIDATE_FLAG_NONE, array('min' => 1, 'max' => 9)),
            'name' => array(VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_FLAG_UNDEFINED, array('min' => 1, 'max' => 5)),
        )
    )),
    array()
);
run('ok', array('a' => array('id' => '3'), 'b' => array('id' => 4, 'name' => 'xy')), $rows);
run('inner undeclared key', array('a' => array('id' => '3', 'zz' => 1)), $rows);

echo "\n**** VALIDATE_MULTI_OR alternative stays silent ****\n";
$loose = array(VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY_KEY_ALNUM, array('min' => 0, 'max' => 4,
          'undeclared' => array(VALIDATE_STRING, VALIDATE_STRING_ALNUM | VALIDATE_STRING_LF, array('min' => 0, 'max' => 16))), array());
run('or', array('Host' => 'h', 'X-Foo' => "bar\n"), array(VALIDATE_MULTI, VALIDATE_MULTI_OR, array(), array($headers, $loose)));

echo "\n**** spec check ****\n";
run('not an array', array('a' => 1), array(VALIDATE_ARRAY, VALIDATE_FLAG_NONE, array('min' => 0, 'max' => 1, 'undeclared' => 'x'), array()));
run('bad inner spec', array('a' => 1), array(VALIDATE_ARRAY, VALIDATE_FLAG_NONE, array('min' => 0, 'max' => 1,
    'undeclared' => array(VALIDATE_INT, VALIDATE_FLAG_NONE, array())), array()));
run('key_callback not callable', array('a' => 1), array(VALIDATE_ARRAY, VALIDATE_FLAG_NONE, array('min' => 0, 'max' => 1,
    'undeclared' => $value, 'key_callback' => 'no_such_function'), array()));
$specs = array(
    'good' => $headers,
    'typo' => array(VALIDATE_ARRAY, VALIDATE_FLAG_NONE, array('min' => 0, 'max' => 1, 'undeclard' => $value, '_mine' => 1), array()),
    'KEY_ALNUM without undeclared' => array(VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY_KEY_ALNUM, array('min' => 0, 'max' => 1), array()),
    'key_callback without undeclared' => array(VALIDATE_ARRAY, VALIDATE_FLAG_NONE, array('min' => 0, 'max' => 1, 'key_callback' => 'is_string'), array()),
    'KEY_ALNUM with key_callback' => array(VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY_KEY_ALNUM, array('min' => 0, 'max' => 1,
        'undeclared' => $value, 'key_callback' => 'is_string'), array()),
);
foreach ($specs as $label => $spec) {
    $ctx = null;
    $unvalidated = null;
    echo "$label: ", var_export(validate_spec($spec, $unvalidated, $ctx), true), "\n";
    $_tmp = validate_get_system_errors($ctx); // PHP 5.3 cannot index a call result
    foreach ($_tmp['warning'] as $warning) {
        echo '  warning: ', $warning['message'], "\n";
    }
}
?>
--EXPECT--
**** declared and undeclared keys ****
ok: true result={"Host":"example.com","X-Foo":"bar baz","Accept":"x"} left=[]
empty undeclared: true result={"Host":"example.com"} left=[]
bad value: false result=null left={"X-Foo":"bar\n"}
  [X-Foo] VALIDATE_STRING: Illegal char detected. ord: "10" chr: "\x0A" value="bar\n"
count includes undeclared: false result=null left={"Host":"h","a":"1","b":"2","c":"3","d":"4"}
  [ROOT] VALIDATE_ARRAY: Count out of range. min: 1 max: 4 count 5 value=null

**** undeclared keys are checked ****
KEY_ALNUM: false result=null left={"X Foo":"bar"}
  [ROOT] VALIDATE_ARRAY: Undeclared key has invalid format. Hint: you may want VALIDATE_FLAG_ARRAY_KEY_ALNUM flag or "key_callback" option. value="key:X Foo"
default key rule: false result=null left={"X-Foo":"bar"}
  [ROOT] VALIDATE_ARRAY: Undeclared key has invalid format. Hint: you may want VALIDATE_FLAG_ARRAY_KEY_ALNUM flag or "key_callback" option. value="key:X-Foo"
key_callback: false result=null left={"Y":"z"}
  [ROOT] VALIDATE_ARRAY: Undeclared key has invalid format. Hint: you may want VALIDATE_FLAG_ARRAY_KEY_ALNUM flag or "key_callback" option. value="key:Y"

**** options ****
UNVALIDATED has no effect: false result=null left={"X-Foo":"bad\n"}
  [X-Foo] VALIDATE_STRING: Illegal char detected. ord: "10" chr: "\x0A" value="bad\n"
KEEP_INPUTS: true result={"Host":"h","X-Foo":"bar"} left={"Host":"h","X-Foo":"bar"}

**** nested VALIDATE_ARRAY as undeclared spec ****
ok: true result={"a":{"id":3},"b":{"id":4,"name":"xy"}} left=[]
inner undeclared key: false result=null left={"a":{"zz":1}}
  [a] Validate: Unvalidated value remains. Undeclared keys: 1. value=["zz"]

**** VALIDATE_MULTI_OR alternative stays silent ****
or: true result={"Host":"h","X-Foo":"bar\n"} left={"Host":"h","X-Foo":"bar\n"}

**** spec check ****
not an array: ValidateInvalidSpecException
  Invalid validation spec detected. Fix spec errors first. Errors: 1 Warnings: 0. First error: param: 'ROOT=>(undeclared)' message: 'Validation spec must be array.'. Hint: validate_spec($spec, $unvalidated, $ctx); validate_get_system_errors($ctx);
bad inner spec: ValidateInvalidSpecException
  Invalid validation spec detected. Fix spec errors first. Errors: 1 Warnings: 0. First error: param: 'ROOT=>(undeclared)' message: 'VALIDATE_INT "min" and/or "max" is missing.'. Hint: validate_spec($spec, $unvalidated, $ctx); validate_get_system_errors($ctx);
key_callback not callable: ValidateInvalidSpecException
  Invalid validation spec detected. Fix spec errors first. Errors: 1 Warnings: 0. First error: param: 'ROOT' message: 'VALIDATE_ARRAY "key_callback" option is not callable.'. Hint: validate_spec($spec, $unvalidated, $ctx); validate_get_system_errors($ctx);
good: true
typo: true
  warning: VALIDATE_ARRAY has unknown option "undeclard". Known options: "min", "max", "default", "error_message", "undeclared", "key_callback" and "_" prefixed user options.
KEY_ALNUM without undeclared: true
  warning: VALIDATE_ARRAY has flags that have no effect. Only VALIDATE_FLAG_REJECT, VALIDATE_FLAG_UNDEFINED, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT, VALIDATE_FLAG_EMPTY_TO_DEFAULT, VALIDATE_FLAG_WARNING, VALIDATE_FLAG_NOTICE, VALIDATE_FLAG_PASSTHRU and, with "undeclared" option, VALIDATE_FLAG_ARRAY_KEY_ALNUM apply.
key_callback without undeclared: true
  warning: VALIDATE_ARRAY has "key_callback" option that has no effect without "undeclared" option.
KEY_ALNUM with key_callback: true
  warning: VALIDATE_ARRAY has VALIDATE_FLAG_ARRAY_KEY_ALNUM flag that has no effect: "key_callback" option is defined.
