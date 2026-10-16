<?php
/**
 * Example #4: Validate ALL HTTP request headers in one validate() call.
 *
 * Known headers (Cookie, User-Agent) get explicit specs. The "undeclared"
 * option validates every other header with a catch-all spec (a
 * length-bounded string), and VALIDATE_FLAG_ARRAY_KEY_ALNUM restricts their
 * names. Every header crosses the trust boundary through some validation —
 * OWASP A10:2017 compliant.
 */

require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines the $basicTypes array.

// In a real app, build it from the HTTP_* entries of $_SERVER as
// 95-example.php does. (apache_request_headers() keeps the client's
// spelling of names — all lower case over HTTP/2.)
$request_headers_orig = ['a'=>'abc', 'b'=>'456'];

// Known headers. Cookie is optional; User-Agent is optional with a default
// of '' so the rest of the code can assume it exists.
$cookie_spec = $basicTypes['cookie'];
$cookie_spec[VALIDATE_FLAGS]                 |= VALIDATE_FLAG_UNDEFINED;
$useragent_spec = $basicTypes['user-agent'];
$useragent_spec[VALIDATE_FLAGS]             |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT;
$useragent_spec[VALIDATE_OPTIONS]['default'] = '';
$useragent_spec[VALIDATE_OPTIONS]['min']     = 0; // Allow zero-length / empty value.

// Catch-all spec for every other header.
$other_spec = $basicTypes['header512'];
$other_spec[VALIDATE_OPTIONS]['min'] = 0; // Allow empty header values.

$spec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_ARRAY_KEY_ALNUM, // Undeclared header names: alnum, '_' and '-' only.
    ['min'=>2, 'max'=>20, 'undeclared'=>$other_spec], // Total request must have 2-20 headers.
    [
        'Cookie' => $cookie_spec,
        'User-Agent' => $useragent_spec,
    ]
];

// $request_headers holds only validated values — no control characters, no
// multibyte characters. validate() removes the values it consumed from
// $request_headers_orig, so it is empty after a successful call.
$request_headers = validate($ctx, $request_headers_orig, $spec);
var_dump($request_headers, $request_headers_orig);
