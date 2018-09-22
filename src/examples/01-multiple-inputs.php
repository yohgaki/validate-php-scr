<?php
/**
 *
 * Multiple inputs example.
 *
 * Relatively realistic input validation example.
 * All web apps must validate all inputs including HTTP headers.
 *
 */

require_once __DIR__.'/../validate_func.php';
// Example basic type definition
require_once __DIR__.'/../lib/basic_types.php';

/******************* Sample input data ***********************/
$POST = [
    'id'    => '2134',
    'name'  => 'test user',
    'utf8'  => '私はガラスを食べられます。',
    'float' => '1234.5678',
    'array' => [
        'id'     => '3452',
        'string' => "abcdefg\nxyz",
    ]
];

$GET = [
    'foo' => '123',
    'bar' => '456',
];

$HEADER = [
    'ACCEPT'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
    'USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.162 Safari/537.36',
];



/******************** Example input parameter validation specs. *************************/

// In practice, you should define these reusable standard specs
// some thing similar to these as input definition PHP script.

// "min" and "max" options are required options for all inputs. (White listing)
$id = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => PHP_INT_MAX]
];

// Any chars are rejected by default. Developer must explicitly define allowed chars. (White listing)
$name = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_SPACE,
    ['min' => 4, 'max' => 64]
];

// For convenience, you can use -INF/INF as min/max.
$float = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => 4, 'max' => INF]
    // INF is not included as valid float unless explicitly allowed by Float validator option.
    // i.e. 「"INF" => true」 to allow INF max, 「"-INF" => true」to allow -INF as min.
    // NAN is always invalid.
];

// Use "encoding" option to allow multibyte chars.
$utf8 = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_SPACE | VALIDATE_STRING_MB,
    ['min' => 4, 'max' => 64, 'encoding' => 'UTF-8'] // Only UTF-8 is supported and option may be omitted.
];

// Any chars are rejected by default. Developer must explicitly define allowed chars. (White listing)
$string = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_LF,
    ['min' => 4, 'max' => 128]
];

// Accept has more chars
$accept = [
    VALIDATE_STRING,
    VALIDATE_FLAG_UNDEFINED_TO_DEFAULT | VALIDATE_STRING_DIGIT | VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_SPACE,
    ['min' => 0, 'max' => 1024, 'ascii' => '=/,.;+()*', 'default' => ''] // You can allow additional ASCII chars as option.
];


// Complex validations can be defined as "callback" that is plain/simple PHP code.
$user_agent = [
    VALIDATE_CALLBACK, // You can use any "callable"
    VALIDATE_CALLBACK_SPACE | VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_MB,
    ['min' => 10, 'max' => 128,
    'callback' =>
    // Params: Validate $ctx, mixed $result, mixed $input
    // Return: TRUE/FALSE for success/failure.
    //
    // Update $input if you need modified value.
    // Never try to "sanitize" (remove bad from input), but
    // validate value is legitimate as the input. Be restrictive.
    function ($ctx, &$result, $input) {
        // DbC style type check is better than type def in signature
        // in many respects. Thus DbC style type check is recommended.
        assert($ctx instanceof Validate);

        // In general, you must check data type, then length.
        // Complex check must be done later for security reasons.
        // You must use strict "white-listing" unless strict "white-listing" is not feasible.
        // Note that even "an additional char", e.g. space/newline/etc, could be dangerous for
        // many logical/output context.
        if (!is_string($input)) {
            validate_error($ctx, 'User-Agent validation: User-Agent must be string.');
            return false; // Make sure this returns. validate_error() could be user error and return here.
        }

        $len = strlen($input);
        // This check can be done by String validator with "ascii" option. This is an example.
        if ($len !== strspn($input, ' 1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ()_.,/;"')) {
            validate_error($ctx, 'User-Agent validation: Invalid char detected.');
            return false;
        }
        // Make sure return "valid" value for successful validation.
        $result = $input;
        return true;
    }]
];



/******************** Example combined validation spec ****************************/

// Now you can combine above predefined parameter specs to validation spec.
$specs = [
    VALIDATE_ARRAY, // 1st should be Validator type
    VALIDATE_FLAG_NONE, // 2nd should be validator flags
    ['min' => 3, 'max' => 3], // 3rd should be validator options.
    [
        'post' => [
            VALIDATE_ARRAY,
            VALIDATE_FLAG_NONE,
            ['min' => 4, 'max' => 5],
            // min/max options are required for array to handle stupid attack efficiently.
            // It's silly to perform costly validations for obvious attacks.
            // NOTE: Any inputs could be optional by VALIDATE_FLAG_OPTIONAL.
            [
                'id'    => $id,
                'name'  => $name,
                'utf8'  => $utf8,
                'float' => $float,
                'array' => [
                    VALIDATE_ARRAY,
                    VALIDATE_FLAG_NONE,
                    ['min' => 2, 'max' => 2],
                    [
                        'id'     => $id,
                        'string' => $string,
                    ],
                ],
            ],
        ],
        'get' => [
            VALIDATE_ARRAY,
            VALIDATE_FLAG_NONE,
            ['min' => 0, 'max' => 4],
            [] // Allow upto 4 optional parameters
        ],
        'header' => [
            VALIDATE_ARRAY,
            VALIDATE_FLAG_NONE,
            ['min' => 2, 'max' => 50],
            [
                'USER_AGENT' => $user_agent,
                'ACCEPT'     => $accept,
                'OTHER'     => $accept,
            ],
        ],
    ],
];

// Web apps often have extra parameters.
// Validate PHP can validate these by loose array spec
$post_default = $B['text128']; // UTF-8 char text upto 128 bytes
$post_default[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
                                 | VALIDATE_FLAG_ARRAY            // Validate post values as array.
                                 | VALIDATE_FLAG_ARRAY_KEY_ALNUM; // Allow only alnum keys.
$post_default[VALIDATE_OPTIONS]['amin'] = 0;     // At least 1 extra post parameter.
$post_default[VALIDATE_OPTIONS]['amax'] = 2;     // At most 2 extra post parameters.
$post_default[VALIDATE_OPTIONS]['default'] = []; // Empty array by default.

$get_default = $B['alnum128']; // Alnum char text up to 128 bytes
$get_default[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
                                 | VALIDATE_FLAG_ARRAY            // Validate post values as array.
                                 | VALIDATE_FLAG_ARRAY_KEY_ALNUM; // Allow only alnum keys.
$get_default[VALIDATE_OPTIONS]['amin'] = 0;     // At least 0 extra query parameter.
$get_default[VALIDATE_OPTIONS]['amax'] = 5;     // At most 5 extra query parameters.
$get_default[VALIDATE_OPTIONS]['default'] = []; // Empty array by default.

$header_default = $B['header1024u']; // UTF-8 text with HTTP header special chars.
$header_default[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
                                 | VALIDATE_FLAG_ARRAY            // Validate headers as array.
                                 | VALIDATE_FLAG_ARRAY_KEY_ALNUM; // Allow only alnum keys.
$header_default[VALIDATE_OPTIONS]['amin'] = 0;     // At least 0 extra headers.
$header_default[VALIDATE_OPTIONS]['amax'] = 10;    // At most 10 extra headers.
$header_default[VALIDATE_OPTIONS]['default'] = []; // Empty array by default.


$default_specs = [
    VALIDATE_ARRAY, // 1st should be Validator type
    VALIDATE_FLAG_NONE, // 2nd should be validator flags
    ['min' => 0, 'max' => 3], // 3rd should be validator options.
    [
        'post' => $post_default,
        'get' => $get_default,
        'header' => $header_default,
    ]
];


// You should validate ALL inputs. i.e. $_POST/$_GET/$_COOKIE/$_FILES/$_SERVER or apache_get_headers().
$my_inputs = ['post' => $POST, 'get' => $GET, 'header' => $HEADER];

// Let's validate them all at once!
// OO API one liner
// $result = (new Validate)->validate($my_input, $specs, VALIDATE_OPT_DISABLE_EXCEPTION);
$inputs = validate($ctx, $my_inputs, $specs, 0);
var_dump($inputs, $ctx->getStatus()); // Validation success!

// Unvalidated inputs remains in $my_inputs
var_dump($my_inputs);

// You may validate extra values by default validation spec
$extras = validate($ctx, $my_inputs, $default_specs, 0);


// Developers MUST log errors from validation Exception and/or Errors.
// If action, such as force user to logout, is possible, you should do it.
