<?php
/**
 * Sample per-field input specs reused by 02-validate-spec.php and 99-web.php.
 *
 * Each variable defines the validation rule for one form field
 * (username, email, age, ...) so endpoints can compose them as needed.
 */

// Load validate constants and the Validate class.
require_once __DIR__.'/../Validate.php';

// Load the predefined $basicTypes array.
require_once __DIR__.'/../lib/basic_types.php';

/**
 * POST form-field specs
 *
 * Each variable is a self-contained spec that can be slotted into a parent
 * VALIDATE_ARRAY definition. Strings reject every character by default; the
 * flags below opt classes in, the 'ascii' option opts individual extras in.
 */

// Whitelist principle: nothing is allowed unless explicitly permitted.
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
    VALIDATE_CALLBACK, // Email is complex enough that we use a PHP callback.
    VALIDATE_CALLBACK_ALNUM, // Allow alphanumeric characters.
    ['min'=> 6, 'max'=> 256, 'ascii'=>'@._-', // 6 to 256 chars plus '@._-'.
    'error_message'=>'Please enter a valid email address. We only accept addresses with a DNS MX record.',
    'callback'=> function($ctx, &$result, $input) {
        $parts = explode('@', $input);
        if (count($parts) > 2) {         // Character set, min, and max are already validated.
            $err =  "Only one '@' is allowed."; // Wrap in an i18n call for multilingual sites.
            validate_error($ctx, $err);
            return false;
        }
        if (!getmxrr($parts[1], $mx)) {
            $err = "No MX record for the email address host.";
            validate_error($ctx, $err);
            return false;
        }
        return true;
    }]
];

// 'min' and 'max' are mandatory for every spec — the framework has no implicit
// defaults; the caller must choose explicit bounds. This is the whitelist
// principle applied to numeric range as well as character set.
$age = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    [
        'min' => 0,
        'max' => 120,
        'error_message' => 'Age should be between {{min}} and {{max}}.',
    ]
];

// For floats you may use -INF/INF as min/max as a shorthand for "unbounded".
// 'min'/'max' themselves are still required.
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
            validate_error($ctx, 'Country validation: malformed input. A correctly behaving client cannot send this value.');
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
                // validate_error() reports a user-visible error. The spec-level
                // 'error_message' option above is the generic fallback shown
                // when no user-visible message has been emitted by a callback.
                validate_error($ctx, 'NG WORD in comment!');
                return false;
            }
            return true;
        }
    ]
];

// Same rule expressed via VALIDATE_REGEXP. Use when the pattern is a clean
// fit for a regex; the regex runs *after* the flag-based whitelist, so it
// only has to express the higher-level shape.
$comment_by_regexp_validator = [
    VALIDATE_REGEXP,
    VALIDATE_REGEXP_ALNUM | VALIDATE_REGEXP_SYMBOL
     | VALIDATE_REGEXP_SPACE | VALIDATE_REGEXP_CRLF | VALIDATE_REGEXP_MB,
    [
        'min' => $comment_min,
        'max' => $comment_max,
        'error_message' => 'Comment should be between {{min}} and {{max}} bytes.',
        'regexp' => '/damn|stupid|fool/', // Trivial deny-pattern, kept simple for the example.
    ]
];

// Same rule expressed via VALIDATE_STRING — pure flag-based whitelist with
// no regex. Cheapest variant when the constraint can be expressed by class
// and length alone.
$comment_by_string_validator = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL
     | VALIDATE_STRING_SPACE | VALIDATE_STRING_CRLF | VALIDATE_STRING_MB,
    [
        'min' => $comment_min,
        'max' => $comment_max,
        'ascii' => '\t', // (VALIDATE_STRING_TAB would do the same; shown here for the 'ascii' syntax.)
        'encoding' => 'UTF-8', // Informational — only UTF-8 is supported and the option may be omitted.
        'error_message' => 'Comment should be between {{min}} and {{max}} bytes.',
    ]
];


/**
 * HTTP header specs
 */
// Content-Length is a non-negative integer; bound it to whatever your endpoint
// actually accepts so oversized bodies are rejected before being read.
$content_length = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 10, 'max' => 10240],
];

// Content-Type is "type/subtype", lowercase alnum + '-' + '/'. The regex
// only has to enforce the slash because the flag-based whitelist has
// already restricted the character set.
$content_type = [
    VALIDATE_REGEXP,
    VALIDATE_REGEXP_LOWER_ALPHA | VALIDATE_REGEXP_DIGIT,
    [
        'min'=> 9,
        'max' => 40,
        'ascii'=>'-/',
        'regexp' => '#^.+/.+$#',
    ]
];


/**
 * GET parameter specs
 */
// 'debug' must never reach this endpoint. VALIDATE_FLAG_REJECT fails any
// request that includes it; VALIDATE_FLAG_UNDEFINED is added so absence
// (the expected case) is treated as success.
$debug = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_REJECT | VALIDATE_FLAG_UNDEFINED,
    []
];


/**
 * Extra HTTP header specs (unused here; kept as a reference example).
 */
// ACCEPT header — alnum + a few separator chars. Replace with a tighter
// per-header spec in production.
$accept = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 0, 'max' => 1024, 'ascii' => '=,.;']
];


