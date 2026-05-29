--TEST--
validate() VALIDATE_FLAG_UNDEFINED / VALIDATE_FLAG_UNDEFINED_TO_DEFAULT — handle absent input
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

// Per-field specs. In real code these would live in a shared definitions file.
$T = array(
	'uid' => array(
		VALIDATE_INT,
		VALIDATE_FLAG_NONE,
		array('min'=>10000, 'max'=>9999999),
	),
	'action' => array(
		VALIDATE_STRING,
		VALIDATE_STRING_ALPHA,
		array('min'=>2, 'max'=>12),
	),
	'csrf' => array(
		VALIDATE_STRING,
		VALIDATE_FLAG_NONE,
		array('min'=>64, 'max'=>64, 'ascii'=>'0123456789abcdef'),
	),
	'name' => array(
		VALIDATE_STRING,
		// Whitelist principle: each allowed class (SPACE, ALPHA) is opted in explicitly.
		VALIDATE_STRING_SPACE | VALIDATE_STRING_ALPHA,
		array('min'=>1, 'max'=>256),
	),
	'zip' => array(
		VALIDATE_STRING,
		VALIDATE_STRING_DIGIT,
		// 'error_message' overrides the default failure message for user-facing errors.
		array('min'=>7, 'max'=>7, 'error_message'=>'ZIP is 7 digits.'),
	),
	'addr' => array(
		VALIDATE_STRING,
		VALIDATE_STRING_SPACE | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
		array('min'=>10, 'max'=>1024, 'ascii'=>'\''),
	),
	'groups' => array(
		VALIDATE_INT,
		// VALIDATE_FLAG_ARRAY validates each array element under the same int spec.
		// amin/amax bound element count; min/max bound each element's value.
		VALIDATE_FLAG_ARRAY,
		array('min'=>1, 'max'=>9999, 'amin'=>1, 'amax'=>10),
	),
	'debug' => array(
		VALIDATE_BOOL,
		// Production endpoints would typically use VALIDATE_FLAG_REJECT here.
		VALIDATE_FLAG_NONE,
		array()
	),
	'comment' => array(
		VALIDATE_STRING,
		// VALIDATE_FLAG_UNDEFINED_TO_DEFAULT substitutes the 'default' option when input is absent.
		VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
		| VALIDATE_STRING_SPACE | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
		array('min'=>10, 'max'=>1024, 'default'=>'my default'),
	),
	/* You can also use VALIDATE_REGEXP or VALIDATE_CALLBACK for the same field - examples kept here for reference. */
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

// Per-source specs. Real apps should also validate cookies, files, headers, etc.
$POST_spec = array(
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	array('min'=> 7, 'max'=>8),
	array(
		'uid'     => $T['uid'],
		'action'  => $T['action'],
		'csrf'    => $T['csrf'],
		'name'    => $T['name'],
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
        'undefined' => array( // VALIDATE_FLAG_UNDEFINED tolerates absence (no default substituted).
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
		'HEADER' => $HEADER_spec,
		'GET'    => $GET_spec, // Contains the 'undefined_flag_none' missing key that drives the failure.
	),
);


// Sample inputs. $GET is intentionally missing 'undefined_flag_none' and
// 'undefined_to_default'; the spec above demonstrates how each absence is
// handled differently depending on the flag.
$POST = array(
	'uid'    => '123456',
	'action' => 'update',
	'csrf'   => 'bdb237bf8c5de6b60ba1e2dcfe364fc24f583e568d1682f851a9d0f11a45c78d',
	'name'   => 'user name',
	'zip'    => "1234567",
	'addr'   => "user's address here",
	'groups' => array(1,2,3,4,5,6),
);
$GET = array(
    'allowed'  => '1',
    'unvalidated' => '1',
);
$HEADER = array();

// The inputs to be validated.
$the_input = array(
	'GET'    => $GET,
	'POST'   => $POST,
	'HEADER' => $HEADER,
);

// VALIDATE_OPT_UNVALIDATED : leave leftover/unvalidated values in $the_input.
// VALIDATE_OPT_CHECK_SPEC  : validate $the_spec itself before applying it.
$func_opts = VALIDATE_OPT_UNVALIDATED | VALIDATE_OPT_CHECK_SPEC;

// Expected to throw via 'undefined_flag_none' being absent from $GET.
try {
	// $ctx       - fresh Validate context (assigned by validate()).
	// $result    - validated values (also reachable via $ctx->getValidated()).
	// $the_input - input array; validated keys are unset by reference.
	// $the_spec  - composed validation spec.
	// $func_opts - function-level VALIDATE_OPT_* bitmask.
	$result = validate($ctx, $the_input, $the_spec, $func_opts);
} catch (Exception $e) {
    var_dump( $ctx->getStatus());
    echo $e->getMessage()."\n";
}

echo "\nInput array - validated values are removed\n";
print_r($the_input);

echo "\nYou can safely use validated result, but it could be partial.\n";
print_r($ctx->getValidated());
?>
--EXPECT--
bool(false)
param: 'ROOT=>GET=>undefined_flag_none' error: 'Undefined parameter: Required parameter is not defined.' val: 'N;'

Input array - validated values are removed
Array
(
    [GET] => Array
        (
            [unvalidated] => 1
        )

    [POST] => Array
        (
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

)

You can safely use validated result, but it could be partial.
Array
(
    [POST] => Array
        (
            [uid] => 123456
            [action] => update
            [csrf] => bdb237bf8c5de6b60ba1e2dcfe364fc24f583e568d1682f851a9d0f11a45c78d
            [name] => user name
            [zip] => 1234567
            [addr] => user's address here
            [comment] => my default
        )

    [HEADER] => 
    [GET] => Array
        (
            [allowed] => 1
            [undefined_to_default] => 0
        )

)
