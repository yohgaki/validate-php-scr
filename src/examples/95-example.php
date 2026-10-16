<?php
/**
 * Example #5: Validate ALL HTTP inputs at once.
 *
 * Demonstrates a single trust-boundary validation for every HTTP source a
 * typical web app receives:
 *
 *   - $_GET     (URL query parameters)
 *   - $_POST    (form fields)
 *   - $_COOKIE  (cookies)
 *   - $_FILES   (file uploads)
 *   - HTTP request headers (the HTTP_* entries of $_SERVER)
 *
 * Per OWASP TOP 10 A10:2017, every input must be validated. Nesting per-source
 * specs under a single VALIDATE_ARRAY lets the four superglobals go through
 * one validate() call. HTTP headers follow Example #4: known headers
 * explicitly, the rest with a catch-all spec through the "undeclared" option.
 *
 * The script can be run directly from CLI: it falls back to representative
 * sample inputs when the superglobals are empty.
 */

require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $basicTypes

// =============================================================================
// 1. Per-source specs
// =============================================================================

// ---- $_GET: only a CSRF token is expected -----------------------------------
$getSpec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 1, 'max' => 1),
    array(
        'csrf' => $basicTypes['hex64'], // 64-char hex (SHA-256)
    )
);

// ---- $_POST: a user-registration form ---------------------------------------
$postSpec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 5, 'max' => 5),
    array(
        'username' => array(
            VALIDATE_STRING, VALIDATE_STRING_ALNUM,
            array('min' => 3, 'max' => 32, 'ascii' => '-_',
             'error_message' => 'Username must be 3-32 chars (alnum, "-", "_").')
        ),
        'email'    => $basicTypes['email'],
        'age'      => array(
            VALIDATE_INT, VALIDATE_FLAG_NONE,
            array('min' => 13, 'max' => 120,
             'error_message' => 'Age must be between 13 and 120.')
        ),
        'country'  => array(
            VALIDATE_REGEXP, VALIDATE_FLAG_NONE,
            array('min' => 2, 'max' => 2,
             'ascii' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
             'regexp' => '/\A(?:JP|US|GB|DE|FR)\z/', // ISO 3166-1 alpha-2 whitelist
             'error_message' => 'Country must be one of: JP, US, GB, DE, FR.')
        ),
        'accepted_tos' => $basicTypes['accepted'], // "yes"/"on"/"1"/"true"
    )
);

// ---- $_COOKIE: session id + optional UX cookies -----------------------------
$langSpec  = $basicTypes['alpha_dash32'];
$langSpec[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED; // optional
$themeSpec = $basicTypes['alpha_dash32'];
$themeSpec[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED;

$cookieSpec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 1, 'max' => 4),
    array(
        'PHPSESSID' => $basicTypes['sessid'],
        'lang'      => $langSpec,
        'theme'     => $themeSpec,
    )
);

// ---- $_FILES: single avatar upload (5-6 keys per upload) --------------------
// Each $_FILES[<field>] is an array: name, type, tmp_name, error, size, and
// full_path on PHP 8.1+ (the browser-supplied relative path; optional here).
// Whitelist MIME type by regex; bound size explicitly.
// NOTE: 'type' is browser-supplied and not trustworthy. Re-check the uploaded
// file with finfo_file() before storing or serving it.
$uploadSpec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 5, 'max' => 6),
    array(
        'name'     => array(VALIDATE_STRING, VALIDATE_STRING_ALNUM,
                       array('min' => 1, 'max' => 255, 'ascii' => '._-')),
        'full_path' => array(VALIDATE_STRING, VALIDATE_STRING_ALNUM | VALIDATE_FLAG_UNDEFINED,
                        array('min' => 1, 'max' => 255, 'ascii' => '._-/')),
        'type'     => array(VALIDATE_REGEXP, VALIDATE_FLAG_NONE,
                       array('min' => 6, 'max' => 32,
                        'ascii' => 'abcdefghijklmnopqrstuvwxyz/',
                        'regexp' => '/\Aimage\/(?:jpeg|png|gif|webp)\z/')),
        'tmp_name' => array(VALIDATE_STRING, VALIDATE_STRING_ALNUM,
                       array('min' => 1, 'max' => 4096, 'ascii' => '/_.-')),
        'error'    => array(VALIDATE_INT, VALIDATE_FLAG_NONE,
                       array('min' => 0, 'max' => 8)), // UPLOAD_ERR_OK..EXTENSION
        'size'     => array(VALIDATE_INT, VALIDATE_FLAG_NONE,
                       array('min' => 1, 'max' => 5 * 1024 * 1024)), // up to 5 MiB
    )
);

$filesSpec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 1, 'max' => 1),
    array(
        'avatar' => $uploadSpec,
    )
);

// ---- HTTP headers -------------------------------------------------------------
// Same pattern as Example #4 (94-example.php). Cookie and User-Agent are
// optional; Host is required. "undeclared" validates every other header with
// $headerCatchSpec, and VALIDATE_FLAG_ARRAY_KEY_ALNUM restricts their names.
$cookieHdr = $basicTypes['cookie'];
$cookieHdr[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED;
$uaHdr = $basicTypes['user-agent'];
$uaHdr[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT;
$uaHdr[VALIDATE_OPTIONS]['default'] = '';
$uaHdr[VALIDATE_OPTIONS]['min']     = 0;

$headerCatchSpec = $basicTypes['header512'];
$headerCatchSpec[VALIDATE_OPTIONS]['min'] = 0; // Allow empty header values.

$headerSpec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_ARRAY_KEY_ALNUM,
    array('min' => 1, 'max' => 100, 'undeclared' => $headerCatchSpec), // Host required + up to 99 others
    array(
        'Host'       => $basicTypes['http_host'], // name or IP, optional ":port"
        'Cookie'     => $cookieHdr,
        'User-Agent' => $uaHdr,
    )
);

// =============================================================================
// 2. Top-level spec: validate every superglobal + all headers in one call
// =============================================================================

$spec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 5, 'max' => 5),
    array(
        'get'    => $getSpec,
        'post'   => $postSpec,
        'cookie' => $cookieSpec,
        'files'  => $filesSpec,
        'header' => $headerSpec,
    )
);

// =============================================================================
// 3. Collect inputs (with CLI-friendly fallback so this script is runnable)
// =============================================================================

if (PHP_SAPI === 'cli' && empty($_GET) && empty($_POST)) {
    $_GET = array('csrf' => str_repeat('a', 64));
    $_POST = array(
        'username'     => 'yohgaki',
        'email'        => 'yohgaki@ohgaki.net',
        'age'          => 42,
        'country'      => 'JP',
        'accepted_tos' => 'yes',
    );
    $_COOKIE = array(
        'PHPSESSID' => str_repeat('a', 32),
        'lang'      => 'ja',
        'theme'     => 'dark',
    );
    $_FILES = array(
        'avatar' => array(
            'name'     => 'photo.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => '/tmp/phpAbCdEf',
            'error'    => 0,
            'size'     => 102400,
        ),
    );
}

// Request headers from $_SERVER: HTTP_* plus CONTENT_TYPE / CONTENT_LENGTH,
// which PHP stores without the prefix. Names become "Accept-Language" style
// on every SAPI. (apache_request_headers() keeps the client's spelling —
// all lower case over HTTP/2 — so 'Host' would not match "host".) The
// other $_SERVER entries are server/environment variables, not headers.
// CLI has no request headers (its $_SERVER is the environment): use samples.
$headers = array();
if (PHP_SAPI !== 'cli') {
    foreach ($_SERVER as $k => $v) {
        if (strncmp($k, 'HTTP_', 5) === 0 || $k === 'CONTENT_TYPE' || $k === 'CONTENT_LENGTH') {
            $name = (strncmp($k, 'HTTP_', 5) === 0) ? substr($k, 5) : $k;
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))))] = $v;
        }
    }
} else {
    $headers = array(
        'Host'            => 'example.com:8080',
        'User-Agent'      => 'Mozilla/5.0 (compatible; validate-php-scr/95)',
        'Cookie'          => 'PHPSESSID='.str_repeat('a', 32),
        'Accept'          => 'text/html',
        'Accept-Language' => 'ja, en;q=0.8',
    );
}

$inputs = array(
    'get'    => $_GET,
    'post'   => $_POST,
    'cookie' => $_COOKIE,
    'files'  => $_FILES,
    'header' => $headers,
);

// =============================================================================
// 4. Validate
// =============================================================================

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION; // interactive form: collect errors

// One call validates everything. An undeclared key in get/post/cookie/files is
// an error; every header goes through its own spec or $headerCatchSpec —
// nothing bypasses the trust boundary.
$result = validate($ctx, $inputs, $spec, $func_opts);
$ok = validate_get_status($ctx);

// =============================================================================
// 5. Report
// =============================================================================

echo 'Status: ', var_export($ok, true), PHP_EOL;
if (!$ok) {
    // APPLICATION INPUT failures (broken types, oversized strings) should be
    // rejected fast without user-facing detail. BUSINESS LOGIC failures (using
    // the 'error_message' option) can be shown back to the form.
    echo 'User errors:', PHP_EOL;
    print_r(validate_get_user_errors($ctx));
    echo 'System errors:', PHP_EOL;
    print_r(validate_get_system_errors($ctx));
    exit(1);
}

echo 'Validated:', PHP_EOL;
print_r($result);
