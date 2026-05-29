--TEST--
validate() VALIDATE_MULTI — MULTI_AND / MULTI_OR over VALIDATE_BOOL[]
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
 * VALIDATE_MULTI lets a single value be validated by several sub-specs.
 *   MULTI_AND: every sub-spec must pass.
 *   MULTI_OR : at least one sub-spec must pass.
 */
require_once __DIR__.'/../validate_func.php';


// Test #1 — both sub-specs accept VALIDATE_BOOL_TF, so MULTI_AND succeeds.
$bool_spec = [
    VALIDATE_MULTI,
    VALIDATE_MULTI_AND,
    [],
    [
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF,
            ['amin' => 1, 'amax' =>4]
        ],
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF,
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


// Test #2 — single-spec baseline (no VALIDATE_MULTI), for comparison with #1.
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF,
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



// Test #3 — MULTI_OR: the first sub-spec rejects 't'/'f' (no BOOL_TF flag) but
// the second accepts them, so the OR clause is satisfied.
$bool_spec = [
    VALIDATE_MULTI,
    VALIDATE_MULTI_OR,
    [],
    [
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY,
            ['amin' => 1, 'amax' =>4]
        ],
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF,
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
var_dump($ctx->getStatus());
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
