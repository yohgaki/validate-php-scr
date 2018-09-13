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

// Inputs.
//     Real world apps have GET/HTTP headers. These must be validated also!
//     GET/HTTP headers are omitted for simplicity.
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


// Input type specs should be defined in central definition file
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
		VALIDATE_STRING_SPACE | VALIDATE_STRING_ALPHA, // Everything is explicit(=whitelist)
		array('min'=>1, 'max'=>256),
	),
	'zip' => array(
		VALIDATE_STRING,
		VALIDATE_STRING_DIGIT,
		array('min'=>7, 'max'=>7, 'error_message'=>'ZIP is 7 digits.'), // Custom error message is allowed.
	),
	'addr' => array(
		VALIDATE_STRING,
		VALIDATE_STRING_SPACE | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
		array('min'=>10, 'max'=>1024, 'ascii'=>'\''),
	),
	'groups' => array(
		VALIDATE_INT,
		VALIDATE_FLAG_ARRAY, // Allow array of integers
		array('min'=>1, 'max'=>9999, 'amin'=>1, 'amax'=>10),
	),
	'debug' => array(
		//VALIDATE_UNDEFINED,
		VALIDATE_BOOL,
		VALIDATE_FLAG_NONE, // Must be undefined for production. If defined, exception/error
		array()
	),
	'comment' => array(
		VALIDATE_STRING,
		VALIDATE_FLAG_UNDEFINED_TO_DEFAULT  // Values can be optional and set default
		| VALIDATE_STRING_SPACE | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
		array('min'=>10, 'max'=>1024, 'default'=>'my default'),
	),
	/* You can use 'regexp' and 'callback' for validation, too */
	/*
	'zip' => array(
		VALIDATE_REGEX, VALIDATE_FLAG_NONE, // PCRE is used
		array('min'=>7, 'max'=>7, 'regexp'=>'/^[0-9]{7}$/'),
	),
	'zip' => array(
		VALIDATE_CALLBACK, VALIDATE_FLAG_NONE, // PCRE is used
		array('min'=>7, 'max'=>7, 'callback'=>'my_callback'),
		// Callback can be any 'callable'
	),
	*/
);

// Input validation specification for $POST input.
//    You should validate "HTTP headers" and "GET" for real apps!
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

// Ignore unvalidated values & check spec before validation.
$func_opts = VALIDATE_OPT_UNVALIDATED | VALIDATE_OPT_CHECK_SPEC;

try {
	// $ctx       - Validator context. Automatically created. Used for playing around validation result.
	// $result    - Contains validated(valid) values from input($POST)
	// $the_input - The input. Validated values are removed from this.
	// $the_spec  - Input value specifications.
	// $func_opts - Function options.
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
