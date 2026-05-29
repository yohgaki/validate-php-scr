<?php
/**
 * Example #4: Validate ALL HTTP request headers in two stages.
 *
 * Stage 1 ($spec1) explicitly validates known headers (Cookie, User-Agent).
 * Stage 2 ($spec2) is a catch-all that validates any remaining headers as
 * length-bounded strings. Together they guarantee every header crosses the
 * trust boundary through some validation — OWASP A10:2017 compliant.
 */

require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines the $basicTypes array.

// In a real app: $request_headers_orig = apache_request_headers();
$request_headers_orig = ['a'=>'abc', 'b'=>'456'];

// Stage 1: explicit specs for known headers. Cookie is optional; User-Agent
// is optional with a default of '' so the rest of the code can assume it exists.
$cookie_spec = $basicTypes['cookie'];
$cookie_spec[VALIDATE_FLAGS]                 |= VALIDATE_FLAG_UNDEFINED;
$useragent_spec = $basicTypes['user-agent'];
$useragent_spec[VALIDATE_FLAGS]             |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT;
$useragent_spec[VALIDATE_OPTIONS]['default'] = '';
$useragent_spec[VALIDATE_OPTIONS]['min']     = 0; // Allow zero-length / empty value.
$spec1 = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min'=>2, 'max'=>20], // Total request must have 2-20 headers.
    [
        'Cookie' => $cookie_spec,
        'User-Agent' => $useragent_spec,
    ]
];

// validate() removes the values it consumed from $request_headers_orig,
// leaving only the unvalidated (unknown) headers behind.
$request_headers = validate($ctx, $request_headers_orig, $spec1);

// Stage 2: catch-all spec for the remaining headers.
// VALIDATE_FLAG_ARRAY applies $basicTypes['header512'] to each element.
// VALIDATE_FLAG_ARRAY_KEY_ALNUM also restricts keys to alnum + '_' + '-'.
$spec2 = $basicTypes['header512'];
$spec2[VALIDATE_FLAGS]   |= VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$spec2[VALIDATE_OPTIONS]['min'] = 0; // Allow empty header values.
$spec2[VALIDATE_OPTIONS]['amin'] = 0; // Accept 0 extra headers.
$spec2[VALIDATE_OPTIONS]['amax'] = 20; // Accept up to 20 extra headers.

// Merge stage 2 results back in. $request_headers now holds only validated
// values — no control characters, no multibyte characters.
$request_headers += validate($ctx, $request_headers_orig, $spec2);
var_dump($request_headers, $request_headers_orig);