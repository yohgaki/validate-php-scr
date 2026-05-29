--TEST--
validate() VALIDATE_FLAG_REJECT — request fails when a forbidden parameter is present
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

// Sample inputs. Note $GET['rejected'] is present — the GET spec below
// declares it with VALIDATE_FLAG_REJECT, so its presence is the failure.
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
    'rejected' => '1',
    'unvalidated' => '1',
);
$HEADER = array();

// The inputs to be validated.
$the_input = array(
	'GET'    => $GET,
	'POST'   => $POST,
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
        'rejected' => array(
            VALIDATE_INT,
            // VALIDATE_FLAG_REJECT: validation fails if this key is present.
            VALIDATE_FLAG_REJECT,
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
		'GET'    => $GET_spec,
		'POST'   => $POST_spec,
		'HEADER' => $HEADER_spec,
	),
);

// VALIDATE_OPT_UNVALIDATED : leave leftover/unvalidated values in $the_input.
// VALIDATE_OPT_CHECK_SPEC  : validate $the_spec itself before applying it.
$func_opts = VALIDATE_OPT_UNVALIDATED | VALIDATE_OPT_CHECK_SPEC;

// Expected to throw: 'rejected' is present in $GET and was declared with
// VALIDATE_FLAG_REJECT above. The catch dumps state and aborts the script.
try {
	// $ctx       - fresh Validate context (assigned by validate()).
	// $result    - validated values (also reachable via $ctx->getValidated()).
	// $the_input - input array; validated keys are unset by reference.
	// $the_spec  - composed validation spec.
	// $func_opts - function-level VALIDATE_OPT_* bitmask.
	$result = validate($ctx, $the_input, $the_spec, $func_opts);
} catch (Exception $e) {
	var_dump($ctx->getStatus(), $ctx->getValidated(), $the_input);
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
bool(false)
array(1) {
  ["GET"]=>
  array(1) {
    ["allowed"]=>
    int(1)
  }
}
array(3) {
  ["GET"]=>
  array(2) {
    ["rejected"]=>
    string(1) "1"
    ["unvalidated"]=>
    string(1) "1"
  }
  ["POST"]=>
  array(7) {
    ["uid"]=>
    string(6) "123456"
    ["action"]=>
    string(6) "update"
    ["csrf"]=>
    string(64) "bdb237bf8c5de6b60ba1e2dcfe364fc24f583e568d1682f851a9d0f11a45c78d"
    ["name"]=>
    string(9) "user name"
    ["zip"]=>
    string(7) "1234567"
    ["addr"]=>
    string(19) "user's address here"
    ["groups"]=>
    array(6) {
      [0]=>
      int(1)
      [1]=>
      int(2)
      [2]=>
      int(3)
      [3]=>
      int(4)
      [4]=>
      int(5)
      [5]=>
      int(6)
    }
  }
  ["HEADER"]=>
  array(0) {
  }
}
Go away, crackers! Your activity is logged and reported.
