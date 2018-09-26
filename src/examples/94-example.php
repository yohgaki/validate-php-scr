<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $B (basic type) array

$request_headers_orig = ['a'=>'abc', 'b'=>'456']; //apache_reuqest_headers(); // Get request headers

// Check cookei and user agent. Allow undefined and extra headers.
$B['cookie'][VALIDATE_FLAGS]                 |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT;
$B['cookie'][VALIDATE_OPTIONS]['default']     = '';
$B['cookie'][VALIDATE_OPTIONS]['min']         = 0; // Allow 0 length(empty)
$B['user-agent'][VALIDATE_FLAGS]             |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT;
$B['user-agent'][VALIDATE_OPTIONS]['default'] = '';
$B['user-agent'][VALIDATE_OPTIONS]['min']     = 0; // Allow 0 length(empty)
$spec1 = [ // Explicit validations
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min'=>2, 'max'=>20], // Inputs must have 2 to 20 elements.
    [
        'Cookie' => $B['cookie'],
        'User-Agent' => $B['user-agent'],
    ]
];

// validate() removes validated values from $request_headers_orig
$request_headers = validate($ctx, $request_headers_orig, $spec1);

// Check the rest of headers.
// Allow array 'header512' strings and ALNUM + '_' + '-' keys
$B['header512'][VALIDATE_FLAGS]   |= VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$B['header512'][VALIDATE_OPTIONS]['min'] = 0; // Allow 0 length(empty) headers
$B['header512'][VALIDATE_OPTIONS]['amin'] = 0; // Allow 0 extra headers
$B['header512'][VALIDATE_OPTIONS]['amax'] = 20; // Allow 20 extra headers
$spec2 = $B['header512'];

// $request_headers has only validated values. No control chars nor multibyte chars.
$request_headers += validate($ctx, $request_headers_orig, $spec2);
// Check result
var_dump($request_headers, $request_headers_orig);