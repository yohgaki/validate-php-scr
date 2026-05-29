--TEST--
validate() basic usage
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

// Sample inputs. Real apps also validate $_GET, $_COOKIE, $_FILES, $_SERVER
// and HTTP headers — they are omitted here for brevity.
$POST = array(
	'uid'    => '123456',
	'action' => 'update',
	'csrf'   => 'bdb237bf8c5de6b60ba1e2dcfe364fc24f583e568d1682f851a9d0f11a45c78d',
	'name'   => 'user name',
	'zip'    => "1234567",
	'addr'   => "user's address here",
	'groups' => array(1,2,3,4,5,6),
);
$GET = array();
$HEADER = array();

// The inputs to be validated.
$the_input = array(
	'POST'   => $POST,
	'GET'    => $GET,
	'HEADER' => $HEADER,
);


// Per-field specs. In real code these would live in a shared definitions
// file so every endpoint validates the same way.
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
		// amin/amax bound the element count; min/max bound each element's value.
		VALIDATE_FLAG_ARRAY,
		array('min'=>1, 'max'=>9999, 'amin'=>1, 'amax'=>10),
	),
	'debug' => array(
		VALIDATE_BOOL,
		// Plain VALIDATE_BOOL with no VALIDATE_FLAG_UNDEFINED — the field is required.
		// In production 'debug' would typically use VALIDATE_FLAG_REJECT to fail any request that includes it.
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

// Per-source specs. Production code should validate $_GET, headers, etc.
// as well; this test focuses on the $_POST shape.
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
	array(),
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

// VALIDATE_OPT_UNVALIDATED : leave leftover/unvalidated values in $the_input.
// VALIDATE_OPT_CHECK_SPEC  : validate $the_spec itself before applying it.
$func_opts = VALIDATE_OPT_UNVALIDATED | VALIDATE_OPT_CHECK_SPEC;

try {
	// $ctx       — fresh Validate context (assigned by validate()).
	// $result    — validated values (also reachable via $ctx->getValidated()).
	// $the_input — input array; validated keys are unset by reference.
	// $the_spec  — composed validation spec.
	// $func_opts — function-level VALIDATE_OPT_* bitmask.
	$result = validate($ctx, $the_input, $the_spec, $func_opts);
} catch (Exception $e) {
	var_dump($ctx->getStatus(), $the_input);
	die('Go away, crackers! Your activity is logged and reported.');
}

echo "OK to go! It\'s better to check all values.\n";
echo "Consider 2nd validation for the value.\n";
echo "We have following unvalidated(additional) inputs:\n";
print_r($the_input);

echo "You can safely use the \$result\n";
print_r($result);
?>
--EXPECT--
OK to go! It\'s better to check all values.
Consider 2nd validation for the value.
We have following unvalidated(additional) inputs:
Array
(
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
You can safely use the $result
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

    [GET] => 
    [HEADER] => 
)
