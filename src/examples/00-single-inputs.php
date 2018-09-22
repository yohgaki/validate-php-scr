<?php
/**
 *
 * Single input validation examples
 *
 */

require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Include $B array defines basic type specs.

// Set default exception handler
set_exception_handler(function($exception) {
    echo "Uncaught exception: " , $exception->getMessage(), "\n";
});

// Validate PHP is explicit. Every validation must have specific rules explicitly.
// i.e. No defaults what so ever. Please refer to basic_types.php for specs.

// Basic ints are defined in $B including uints.
// $B['int8'] is signed 8 bits int. i.e. -128 to 127
$var = 127;
$validated = validate($ctx, $var, $B['int8']); // Success. Returns validated value.
var_dump($validated); // 127
$var = 128;
$validated = validate($ctx, $var, $B['int8'], VALIDATE_OPT_DISABLE_EXCEPTION); // Exception, but disable by VALIDATE_OPT_DISABLE_EXCEPTION. Returns NULL for error.
var_dump($validated); // NULL

// $B['int53'] is signed 53 bit int. i.e. Max/min int value for float.
$var = -4503599627370496;
$validated = validate($ctx, $var, $B['int53']); // Success
$var = '-4503599627370496'; // Most inputs are strings in web
$validated = validate($ctx, $var, $B['int53']); // Success. Integer validation returns integer
$var = -4503599627370497;
$validated = validate($ctx, $var, $B['int8'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure.
$var = ''; // Empty string is invalid
$validated = validate($ctx, $var, $B['int8'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure.


// $B['password'] is alnum + symbol + space
$var = 'This is my stupid password';
$validated = validate($ctx, $var, $B['password']); // Success
$var = "This is my \n \r stupid password";
$validated = validate($ctx, $var, $B['password'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure.


// $B['alpha128'] is alphabets up to 128 chars
$var = 'Thisismystupidpassword';
$validated = validate($ctx, $var, $B['alpha128']); // Success
$var = "This_is_my_stupid_password";
$validated = validate($ctx, $var, $B['alpha128'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure.
// Allow '_' as valid char
$var = "This_is_my_stupid_password";
$B['alpha128'][VALIDATE_OPTIONS]['ascii'] = '_';
$validated = validate($ctx, $var, $B['alpha128'], VALIDATE_OPT_DISABLE_EXCEPTION); // Success.


// $B['header4096'] is a spec for HTTP header w/o UTF-8 chars. $B['header4096u'] allows UTF-8 chars.
$var = 'NID=139=gJnuNA8zuOetvT8uXxXG9PU53LMIXlH-i27alOHmkGGWhQ8Ah6jeDOSpstzbi9JgqWr_W4zPyuqZ75j1KN3yIFz7KqfZQTwIDhx9Gto4eIE6qakO5YvaBUjes9TI-d9i; 1P_JAR=2018-9-22-0';
$validated = validate($ctx, $var, $B['header4096']); // Success
$var = '日本語NID=139=gJnuNA8zuOetvT8uXxXG9PU53LMIXlH-i27alOHmkGGWhQ8Ah6jeDOSpstzbi9JgqWr_W4zPyuqZ75j1KN3yIFz7KqfZQTwIDhx9Gto4eIE6qakO5YvaBUjes9TI-d9i; 1P_JAR=2018-9-22-0';
$validated = validate($ctx, $var, $B['header4096'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure.
// $B's harder specs allow 1 char strings. Disallow short strings
$B['header4096'][VALIDATE_OPTIONS]['min'] = 60;
$var = 'abc';
$validated = validate($ctx, $var, $B['header4096'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure.
