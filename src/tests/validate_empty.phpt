--TEST--
validate() empty input handling
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

// Per-field specs. Each entry mixes VALIDATE_FLAG_EMPTY / VALIDATE_FLAG_EMPTY_TO_DEFAULT
// to assert that empty inputs ('' / null) are either accepted as-is or replaced
// with the spec's 'default' value, depending on the flag combination.
// In real code these would live in a shared definitions file so every endpoint
// validates the same way.
$T = array(
	'uid' => array(
		VALIDATE_INT,
		VALIDATE_FLAG_NONE | VALIDATE_FLAG_EMPTY,
		array('min'=>10000, 'max'=>9999999),
	),
	'action' => array(
		VALIDATE_STRING,
		VALIDATE_STRING_ALPHA | VALIDATE_FLAG_EMPTY_TO_DEFAULT,
		array('min'=>2, 'max'=>12, 'default'=>'get'),
	),
	'csrf' => array(
		VALIDATE_STRING,
		VALIDATE_FLAG_EMPTY | VALIDATE_FLAG_EMPTY_TO_DEFAULT,
		array('min'=>64, 'max'=>64, 'ascii'=>'0123456789abcdef', 'default'=>'00000000000000000000000'),
	),
	'name' => array(
		VALIDATE_STRING,
		// Whitelist principle: every allowed class (SPACE, ALPHA) is opted in explicitly.
		VALIDATE_STRING_SPACE | VALIDATE_STRING_ALPHA | VALIDATE_FLAG_EMPTY_TO_DEFAULT,
		array('min'=>5, 'max'=>256, 'default'=>'name'),
	),
	'zip' => array(
		VALIDATE_STRING,
		VALIDATE_STRING_DIGIT | VALIDATE_FLAG_EMPTY_TO_DEFAULT,
		// 'error_message' overrides the default failure message for user-facing errors.
		array('min'=>7, 'max'=>7, 'error_message'=>'ZIP is 7 digits.', 'default'=>1234567),
	),
	'addr' => array(
		VALIDATE_STRING,
		VALIDATE_STRING_SPACE | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB | VALIDATE_FLAG_EMPTY_TO_DEFAULT,
		array('min'=>10, 'max'=>1024, 'ascii'=>'\'', 'default'=>'0123456789'),
	),
	'groups' => array(
		VALIDATE_INT,
		VALIDATE_FLAG_ARRAY, // Allow array of integers
		array('min'=>1, 'max'=>9999, 'amin'=>1, 'amax'=>10),
	),
	'debug' => array(
		VALIDATE_BOOL,
		// Expected absent in production; presence triggers a validation error.
		VALIDATE_FLAG_UNDEFINED,
		array()
	),
	'comment' => array(
		VALIDATE_STRING,
		// VALIDATE_FLAG_EMPTY accepts an empty value without substituting a default.
		VALIDATE_FLAG_EMPTY
		| VALIDATE_STRING_SPACE | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
		array('min'=>10, 'max'=>1024),
	),
	/* You can also use VALIDATE_REGEXP or VALIDATE_CALLBACK for the same field — examples kept here for reference. */
	/*
	'zip' => array(
		VALIDATE_REGEXP, VALIDATE_FLAG_NONE,
		array('min'=>7, 'max'=>7, 'regexp'=>'/^[0-9]{7}$/'),
	),
	'zip' => array(
		VALIDATE_CALLBACK, VALIDATE_FLAG_NONE,
		array('min'=>7, 'max'=>7, 'callback'=>'my_callback'), // Callback may be any 'callable'.
	),
	*/
);

// Per-source spec. Real apps should also validate $_GET, $_COOKIE, $_FILES,
// $_SERVER and HTTP headers — they are omitted here for brevity.
$POST_spec = array(
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	array('min'=> 7, 'max'=>8),
	array(
		'uid'     => $T['uid'],
		'action'  => $T['action'],
		// 'csrf'    => $T['csrf'],
		// 'name'    => $T['name'],
		'zip'     => $T['zip'],
		'addr'    => $T['addr'],
		'comment' => $T['comment'],
	),
);
$GET_spec = array(
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	array('min'=> 0, 'max'=>8),
	array(
        'allowed' => array(
            VALIDATE_INT,
            VALIDATE_FLAG_NONE,
            array('min'=>0, 'max'=>1),
        ),
        'undefined_to_default' => array(
            VALIDATE_INT,
            VALIDATE_FLAG_UNDEFINED_TO_DEFAULT,
            array('min'=>0, 'max'=>1, 'default'=>0),
        ),
        'undefined' => array( // VALIDATE_FLAG_UNDEFINED tolerates absence.
            VALIDATE_INT,
            VALIDATE_FLAG_UNDEFINED,
            array('min'=>0, 'max'=>1),
        ),
        'undefined_flag_none' => array( // No tolerance flag => absent input fails (this is the expected error path).
            VALIDATE_INT,
            VALIDATE_FLAG_NONE,
            array('min'=>0, 'max'=>1),
        ),
    ),
);
$HEADER_spec = array(
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	array('min'=> 0, 'max'=>8),
	array(),
);

// Combine them all
$the_spec = array(
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	array('min'=> 3, 'max'=>3),
	array(
		'POST'   => $POST_spec,
		'GET'    => $GET_spec,
		'HEADER' => $HEADER_spec,
	),
);

// Sample inputs. All POST fields are intentionally empty strings so the test
// exercises the VALIDATE_FLAG_EMPTY / VALIDATE_FLAG_EMPTY_TO_DEFAULT paths.
// 'groups' is present but not declared in the POST spec, so it stays unvalidated.
$POST = array(
	'uid'    => '',
	'action' => '',
	'csrf'   => '',
	'name'   => '',
	'zip'    => "",
	'addr'   => "",
    'groups' => array(1,2,3,4,5,6),
    'comment'=> '',
);
$GET = array(
    'allowed'  => '1',
    'unvalidated' => '1',
);
$HEADER = array();

// The inputs to be validated.
$the_input = array(
	'POST'   => $POST,
	'HEADER' => $HEADER,
	'GET'    => $GET,
);

// VALIDATE_OPT_UNVALIDATED  : leave leftover/unvalidated values in $the_input.
// VALIDATE_OPT_CHECK_SPEC   : validate $the_spec itself before applying it.
// VALIDATE_OPT_DISABLE_EXCEPTION : return null on failure instead of throwing.
$func_opts = VALIDATE_OPT_UNVALIDATED | VALIDATE_OPT_CHECK_SPEC | VALIDATE_OPT_DISABLE_EXCEPTION;

// Run validation. With VALIDATE_OPT_DISABLE_EXCEPTION set this should never
// throw — the catch is defensive in case a spec error itself raises.
try {
	// $ctx       — fresh Validate context (assigned by validate()).
	// $result    — validated values (also reachable via $ctx->getValidated()).
	// $the_input — input array; validated keys are unset by reference.
	// $the_spec  — composed validation spec.
	// $func_opts — function-level VALIDATE_OPT_* bitmask.
	$result = validate($ctx, $the_input, $the_spec, $func_opts);
} catch (Exception $e) {
    echo $e->getMessage()."\n";
}

echo "\nUser errors:\n";
var_dump(validate_get_user_errors($ctx));

echo "\nSystem errors:\n";
var_dump(validate_get_system_errors($ctx));

echo "\nValidated values \$result:\n";
print_r($ctx->getValidated());

echo "\nUnvalidated values \$the_input:\n";
print_r($the_input);

?>
--EXPECT--
User errors:
array(3) {
  ["error"]=>
  array(0) {
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(0) {
  }
}

System errors:
array(3) {
  ["error"]=>
  array(1) {
    ["undefined_flag_none"]=>
    array(1) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(3) {
          [0]=>
          string(4) "ROOT"
          [1]=>
          string(3) "GET"
          [2]=>
          string(19) "undefined_flag_none"
        }
        ["defined"]=>
        bool(false)
        ["message"]=>
        string(55) "Undefined parameter: Required parameter is not defined."
        ["spec"]=>
        array(3) {
          [0]=>
          int(3)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(0)
            ["max"]=>
            int(1)
          }
        }
        ["func_opts"]=>
        int(7)
        ["value"]=>
        NULL
        ["orig_value"]=>
        NULL
      }
    }
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(0) {
  }
}

Validated values $result:
Array
(
    [POST] => Array
        (
            [uid] => 
            [action] => get
            [zip] => 1234567
            [addr] => 0123456789
            [comment] => 
        )

    [GET] => Array
        (
            [allowed] => 1
            [undefined_to_default] => 0
        )

)

Unvalidated values $the_input:
Array
(
    [POST] => Array
        (
            [csrf] => 
            [name] => 
            [groups] => Array
                (
                    [0] => 1
                    [1] => 2
                    [2] => 3
                    [3] => 4
                    [4] => 5
                    [5] => 6
                )

        )

    [HEADER] => Array
        (
        )

    [GET] => Array
        (
            [unvalidated] => 1
        )

)
