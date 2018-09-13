--TEST--
validate bool by array of specs (Multiple spec validation)
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting=-1
--FILE--
<?php
/**
 * Boolean value validation example.
 */
require_once __DIR__.'/../validate_func.php';


// Example1 Successful validation - AND condition
$bool_spec = [
    VALIDATE_MULTI,
    VALIDATE_MULTI_AND,
    [],
    [
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF, // By default no text value is valid
            ['amin' => 1, 'amax' =>4]
        ],
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF, // By default no text value is valid
            ['amin' => 1, 'amax' =>4]
        ],
    ]
];

// Value to be validated.
$bool = ['t', 'f', true, false];

echo "**** test #1 *****\n";
try {
    $result = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_CHECK_SPEC);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
echo "\n** \$result **\n";
var_dump($result);
echo "\n** \$status **\n";
var_dump($ctx->getStatus());


// Example2 Successful validation
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF, // Allow "t"/"f", "T"/"F" as valid bool
    ['amin' => 1, 'amax' => 4]
];

// Value to be validated.
$bool = ['t', 'f', true, false];

echo "**** test #2 *****\n";
try {
    $result = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
echo "\n** \$result **\n";
var_dump($result);
echo "\n** \$status **\n";
var_dump($ctx->getStatus());



// Example3 Successful validation - AND condition
$bool_spec = [
    VALIDATE_MULTI,
    VALIDATE_MULTI_OR,
    [],
    [
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY, // By default no text value is valid
            ['amin' => 1, 'amax' =>4]
        ],
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF, // By default no text value is valid
            ['amin' => 1, 'amax' =>4]
        ],
    ]
];

// Value to be validated.
$bool = ['t', 'f', true, false];

echo "**** test #3 *****\n";
try {
    $result = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_CHECK_SPEC);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
echo "\n** \$result **\n";
var_dump($result);
echo "\n** \$status **\n";
var_dump($ctx->GetStatus());
?>
--EXPECT--
**** test #1 *****

** $result **
array(4) {
  [0]=>
  bool(true)
  [1]=>
  bool(false)
  [2]=>
  bool(true)
  [3]=>
  bool(false)
}

** $status **
bool(true)
**** test #2 *****

** $result **
array(4) {
  [0]=>
  bool(true)
  [1]=>
  bool(false)
  [2]=>
  bool(true)
  [3]=>
  bool(false)
}

** $status **
bool(true)
**** test #3 *****

** $result **
array(4) {
  [0]=>
  bool(true)
  [1]=>
  bool(false)
  [2]=>
  bool(true)
  [3]=>
  bool(false)
}

** $status **
bool(true)
