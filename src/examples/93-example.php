<?php
// Simple "username" and "email" form validation example.
// "Validate" is suitable for "From Validations" also.
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $B (basic type) array

// In practice, you would define all inputs specifications at central repository.
// If your web app does not have strict client side validations, you will need
// "Input validation spec" AND "Business logic(Form) validation spec".

// If client JavaScript has validation
$username = [
    VALIDATE_STRING,        // "username" is string
    VALIDATE_STRING_ALNUM,  // "username" has only alphanumeric chars.
    ['min'=> 6, 'max'=> 40, // "username" can be 6 to 40 chars.
    'error_message'=>'Username is 6 to 40 chars. Alphanumeric char only.']
];

// "Validate" can be extend by callbacks.
$email = [
    VALIDATE_CALLBACK, // "email" is complex, so write PHP script for it.
    VALIDATE_CALLBACK_ALNUM, // Allow alpha numeric chars.
    ['min'=> 6, 'max'=> 256, 'ascii'=>'@._-', // Allow 6 to 256 chars and additional '@._-'
    'error_message'=>'Please enter valid email address. We only accepts address with DNS MX record.',
    'callback'=> function($ctx, &$result, $input) {     // Let's define rules by PHP function.
        $parts = explode('@', $input);
        if (count($parts) > 2) {         // Chars/min/max is already validated.
            $err =  "Only one '@' is allowed."; // This could be i18n function for multilingual sites.
            validate_error($ctx, $err);
            return false;
        }
        if (!dns_get_mx($parts[1], $mx)) {
            $err = "Sorry, we only allow hosts with MX record.";
            validate_error($ctx, $err);
            return false;
        }
        return true;
    }]
];

$spec = [ // Combine predefined parameter spec into one spec.
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min'=>2, 'max'=>10], // Inputs must have 2 to 10 elements.
    [
        // Simply reuse predefined spec for parameters.
        "username" => $username,
        "email"    => $email,
        // You can validate $_GET/$_POST/$_COOKIE/$_SERVER/$_FILES at once by nesting.
    ]
];

$inputs = [
    'username' => 'yohgaki',
    'email' => 'yohgaki@ohgaki.net'
];

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION; // Disable exception, to check errors, etc.
$results = validate($ctx, $inputs, $spec, $func_opts); // Now, let's validate and done.

// Check results
var_dump(validate_get_status($ctx));        // $results is NULL when error. validate_get_status() can be used also.
var_dump($results, $inputs);                // $inputs contains unvalidated values.
var_dump(validate_get_user_errors($ctx));   // Get user errors.
var_dump(validate_get_system_errors($ctx)); // Get system errors.
