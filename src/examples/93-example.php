<?php
/**
 * Example #3: Form validation with multiple fields + a custom callback.
 *
 * "Validate" is well suited for form validation. The username spec is purely
 * declarative; the email spec adds a PHP callback for the parts that can't be
 * expressed with flags alone (one '@' and a DNS MX record).
 */
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines the $basicTypes array.

// In practice, define all input specifications in a central repository.
// If your web app does not have strict client-side validation, you will need
// BOTH an INPUT validation spec AND a BUSINESS LOGIC (form) validation spec.

// Username spec — relies on the client form to do basic length/character checks.
$username = [
    VALIDATE_STRING,        // "username" is a string.
    VALIDATE_STRING_ALNUM,  // Only alphanumeric characters are allowed.
    ['min'=> 6, 'max'=> 40, // Length must be between 6 and 40 chars.
    'error_message'=>'Username is 6 to 40 chars. Alphanumeric char only.']
];

// "Validate" can be extended with callbacks for rules that don't fit flags.
$email = [
    VALIDATE_CALLBACK, // Email is complex, so we write a PHP callback.
    VALIDATE_CALLBACK_ALNUM, // Allow alphanumeric characters.
    ['min'=> 6, 'max'=> 256, 'ascii'=>'@._-', // 6 to 256 chars plus the symbols '@._-'.
    'error_message'=>'Please enter a valid email address. We only accept addresses with a DNS MX record.',
    'callback'=> function($ctx, &$result, $input) {     // Custom rule in plain PHP.
        $parts = explode('@', $input);
        if (count($parts) > 2) {         // Character set, min, and max are already validated.
            $err =  "Only one '@' is allowed."; // This could be an i18n function for multilingual sites.
            validate_error($ctx, $err);
            return false;
        }
        if (!getmxrr($parts[1], $mx)) {
            $err = "Sorry, we only allow hosts with an MX record.";
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

// Inspect results.
var_dump(validate_get_status($ctx));        // $results is null on failure; validate_get_status() is the reliable check.
var_dump($results, $inputs);                // $inputs holds only the values that were NOT validated.
var_dump(validate_get_user_errors($ctx));   // User-facing errors (from 'error_message' / validate_error()).
var_dump(validate_get_system_errors($ctx)); // System-level errors (bad types, oversized strings, etc.).
