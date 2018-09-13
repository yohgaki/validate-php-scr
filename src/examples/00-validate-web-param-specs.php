<?php
/**
 * This file defines sample input parameter spec.
 */

// Load validate constants
require_once __DIR__.'/../Validate.php';

// Load basic types
require_once __DIR__.'../../lib/basic_types.php';

/**
 * POST parameter definition
 */

// Any chars are rejected by default. Developer must explicitly define allowed chars. (White listing)
$username = [
    VALIDATE_STRING,
    VALIDATE_STRING_UPPER_ALPHA | VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_SPACE,
    [
        'min' => 4,
        'max' => 64,
        'ascii' => '\'', // Additionally allowed ASCII chars
        'error_message' => 'Username should be between {{min}} and {{max}} chars',
    ]
];

$email = [
    VALIDATE_CALLBACK, // "email" is complex, so write PHP script for it.
    VALIDATE_CALLBACK_ALNUM, // Allow alpha numeric chars.
    ['min'=> 6, 'max'=> 256, 'ascii'=>'@._-', // Allow 6 to 256 chars and additional '@._-'
    'error_message'=>'Please enter valid email address. We only accepts address with DNS MX record.',
    'callback'=> function($ctx, &$result, $input) {     // Let's define rules by PHP function.
        $parts = explode('@', $input);
        if (count($parts) > 2) {         // Chars/min/max is already validated.
            $err =  "Only one '@' is allowed."; // This could be i18n function for multilingual sites.
            validate_error($ctx, $err); // 3rd param "true" make this a error message for users.
            return false;
        }
        if (!dns_get_mx($parts[1], $mx)) {
            $err = "No MX record for the email address host.";
            validate_error($ctx, $err);
            return false;
        }
        return true;
    }]
];

// "min" and "max" options are required options for all inputs. (White listing)
$age = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    [
        'min' => 0,
        'max' => 120,
        'error_message' => 'Age should be between {{min}} and {{max}}.',
    ]
];

// For convenience, you can use -INF/INF as min/max.
$weight = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    [
        'min' => 0,
        'max' => 300,
        'error_message' => 'Weight should be between {{min}} and {{max}}.',
    ]
];

$country = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_LF | VALIDATE_CALLBACK_SPACE | VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_MB,
    [
        'min' => 4,
        'max' => 128,
        // error_message is required, since length validation is done by validate().
        'error_message' => 'Please choose country.',
        'callback' =>
        function ($ctx, &$result, $input) {
            switch ($input) {
                case 'japan':
                case 'other':
                $result = $input;
                return true;
            }
            if ($input === '') {
                validate_error($ctx, 'Country validation: Empty input.');
                return false;
            }
            $ctx['options']['error_message'] = 'Invalid country should not be sent. Go away, criminals.';
            validate_error($ctx, 'Country validation: malformed input detected.');
            return false;
        }
    ]
];

$comment_min = 4;
$comment_max = 128;

$comment = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_SPACE | VALIDATE_CALLBACK_CRLF
     | VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_MB,
    [
        'min' => $comment_min,
        'max' => $comment_max,
        // error_message is required, since length validation is done by validate().
        'error_message' => 'Comment should be between {{min}} and {{max}} bytes. (Not num of chars) Please avoid aggressive words.',
        'callback' =>
        // This can be done by regexp validator also.
        function ($ctx, &$result, $input) {
            $ng_words = 'damn|stupid|fool';
            if (preg_match('/'.$ng_words.'/', $input)) {
                // This error message is ignored by "error_message" option. Logged as system error message.
                validate_error($ctx, 'NG WORD in comment!');
                return false;
            }
            return true;
        }
    ]
];

$comment_by_regexp_validator = [
    VALIDATE_REGEXP,
    VALIDATE_REGEXP_ALNUM | VALIDATE_REGEXP_SYMBOL
     | VALIDATE_REGEXP_SPACE | VALIDATE_REGEXP_CRLF | VALIDATE_REGEXP_MB,
    [
        'min' => $comment_min,
        'max' => $comment_max,
        'error_message' => 'Comment should be between {{min}} and {{max}} bytes.',
        'regexp' => '/damn|stupid|fool/', // Keep it simple for an example
    ]
];

$comment_by_string_validator = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL
     | VALIDATE_STRING_SPACE | VALIDATE_STRING_CRLF | VALIDATE_STRING_MB,
    [
        'min' => $comment_min,
        'max' => $comment_max,
        'ascii' => '\t', // There is VALIDATE_STRING_TAB, though.
        'encoding' => 'UTF-8', // Allow UTF-8 strings
        'error_message' => 'Comment should be between {{min}} and {{max}} bytes.',
    ]
];


/**
 * HTTP headers
 */
$content_length = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 10, 'max' => 10240],
];

$content_type = [
    VALIDATE_REGEXP,
    VALIDATE_REGEXP_LOWER_ALPHA | VALIDATE_REGEXP_DIGIT,
    [
        'min'=> 9,
        'max' => 40,
        'ascii'=>'-/',
        'regexp' => '#^.+/.+$#', // Input strings are only lower alpha + digits. Simple regex is enough.
    ]
];


/**
 * GET parameter definition
 */
// Reject stupid crackers.
$debug = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_REJECT | VALIDATE_FLAG_UNDEFINED,
    []
];


/**
 * HTTP header parameter definition
 */
// An example for ACCEPT headers. Not used here.
$accept = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 0, 'max' => 1024, 'ascii' => '=,.;'] // You can allow additional ASCII chars as option.
];


