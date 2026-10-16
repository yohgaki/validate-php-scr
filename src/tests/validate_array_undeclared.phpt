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
    foreach (validate_get_system_errors($ctx)['error'] as $param => $errors) {
        foreach ($errors as $error) {
            echo "  [$param] ", $error['message'], ' value=', json_encode($error['value']), "\n";
        }
    }
}

$value = [VALIDATE_STRING, VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE, ['min' => 0, 'max' => 16]];
$headers = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_ARRAY_KEY_ALNUM,
    ['min' => 1, 'max' => 4, 'undeclared' => $value],
    [
        'Host' => [VALIDATE_STRING, VALIDATE_STRING_ALNUM, ['min' => 1, 'max' => 16, 'ascii' => '.']],
    ]
];

echo "**** declared and undeclared keys ****\n";
run('ok', ['X-Foo' => 'bar baz', 'Host' => 'example.com', 'Accept' => 'x'], $headers);
run('empty undeclared', ['Host' => 'example.com'], $headers);
run('bad value', ['Host' => 'example.com', 'X-Foo' => "bar\n", 'Accept' => 'x'], $headers);
run('count includes undeclared', ['Host' => 'h', 'a' => '1', 'b' => '2', 'c' => '3', 'd' => '4'], $headers);

echo "\n**** undeclared keys are checked ****\n";
run('KEY_ALNUM', ['Host' => 'h', 'X Foo' => 'bar'], $headers);
$plain = $headers;
$plain[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
run('default key rule', ['Host' => 'h', 7 => 'int key', 'X-Foo' => 'bar'], $plain);
$cb = $plain;
$cb[VALIDATE_OPTIONS]['key_callback'] = function ($ctx, $key) {
    return strncmp((string)$key, 'X-', 2) === 0;
};
run('key_callback', ['Host' => 'h', 'X-Foo' => 'bar', 'Y' => 'z'], $cb);

echo "\n**** options ****\n";
run('UNVALIDATED has no effect', ['Host' => 'h', 'X-Foo' => "bad\n"], $headers, VALIDATE_OPT_UNVALIDATED);
run('KEEP_INPUTS', ['Host' => 'h', 'X-Foo' => 'bar'], $headers, VALIDATE_OPT_KEEP_INPUTS);

echo "\n**** nested VALIDATE_ARRAY as undeclared spec ****\n";
$rows = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_ARRAY_KEY_ALNUM,
    ['min' => 0, 'max' => 3, 'undeclared' => [
        VALIDATE_ARRAY,
        VALIDATE_FLAG_NONE,
        ['min' => 1, 'max' => 2],
        [
            'id' => [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min' => 1, 'max' => 9]],
            'name' => [VALIDATE_STRING, VALIDATE_STRING_ALPHA | VALIDATE_FLAG_UNDEFINED, ['min' => 1, 'max' => 5]],
        ]
    ]],
    []
];
run('ok', ['a' => ['id' => '3'], 'b' => ['id' => 4, 'name' => 'xy']], $rows);
run('inner undeclared key', ['a' => ['id' => '3', 'zz' => 1]], $rows);

echo "\n**** VALIDATE_MULTI_OR alternative stays silent ****\n";
$loose = [VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY_KEY_ALNUM, ['min' => 0, 'max' => 4,
          'undeclared' => [VALIDATE_STRING, VALIDATE_STRING_ALNUM | VALIDATE_STRING_LF, ['min' => 0, 'max' => 16]]], []];
run('or', ['Host' => 'h', 'X-Foo' => "bar\n"], [VALIDATE_MULTI, VALIDATE_MULTI_OR, [], [$headers, $loose]]);

echo "\n**** spec check ****\n";
run('not an array', ['a' => 1], [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 1, 'undeclared' => 'x'], []]);
run('bad inner spec', ['a' => 1], [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 1,
    'undeclared' => [VALIDATE_INT, VALIDATE_FLAG_NONE, []]], []]);
run('key_callback not callable', ['a' => 1], [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 1,
    'undeclared' => $value, 'key_callback' => 'no_such_function'], []]);
$specs = [
    'good' => $headers,
    'typo' => [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 1, 'undeclard' => $value, '_mine' => 1], []],
    'KEY_ALNUM without undeclared' => [VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY_KEY_ALNUM, ['min' => 0, 'max' => 1], []],
    'key_callback without undeclared' => [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 1, 'key_callback' => 'is_string'], []],
    'KEY_ALNUM with key_callback' => [VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY_KEY_ALNUM, ['min' => 0, 'max' => 1,
        'undeclared' => $value, 'key_callback' => 'is_string'], []],
];
foreach ($specs as $label => $spec) {
    $ctx = null;
    $unvalidated = null;
    echo "$label: ", var_export(validate_spec($spec, $unvalidated, $ctx), true), "\n";
    foreach (validate_get_system_errors($ctx)['warning'] as $warning) {
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
  [a] Validate: Unvalidated value remains. Undeclared keys: 'zz' value=["zz"]

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
  warning: VALIDATE_ARRAY has flags that have no effect. Only VALIDATE_FLAG_REJECT, VALIDATE_FLAG_UNDEFINED, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT, VALIDATE_FLAG_WARNING, VALIDATE_FLAG_NOTICE, VALIDATE_FLAG_PASSTHRU and, with "undeclared" option, VALIDATE_FLAG_ARRAY_KEY_ALNUM apply.
key_callback without undeclared: true
  warning: VALIDATE_ARRAY has "key_callback" option that has no effect without "undeclared" option.
KEY_ALNUM with key_callback: true
  warning: VALIDATE_ARRAY has VALIDATE_FLAG_ARRAY_KEY_ALNUM flag that has no effect: "key_callback" option is defined.
