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
 *   - HTTP request headers (apache_request_headers() / $_SERVER)
 *
 * Per OWASP TOP 10 A10:2017, every input must be validated. Nesting per-source
 * specs under a single VALIDATE_ARRAY lets the four superglobals go through
 * one validate() call. HTTP headers use the two-stage pattern from Example #4:
 * known headers explicitly, the rest with a catch-all spec.
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
$getSpec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => 1],
    [
        'csrf' => $basicTypes['hex64'], // 64-char lowercase hex (SHA-256)
    ]
];

// ---- $_POST: a user-registration form ---------------------------------------
$postSpec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 5, 'max' => 5],
    [
        'username' => [
            VALIDATE_STRING, VALIDATE_STRING_ALNUM,
            ['min' => 3, 'max' => 32, 'ascii' => '-_',
             'error_message' => 'Username must be 3-32 chars (alnum, "-", "_").']
        ],
        'email'    => $basicTypes['email'],
        'age'      => [
            VALIDATE_INT, VALIDATE_FLAG_NONE,
            ['min' => 13, 'max' => 120,
             'error_message' => 'Age must be between 13 and 120.']
        ],
        'country'  => [
            VALIDATE_REGEXP, VALIDATE_FLAG_NONE,
            ['min' => 2, 'max' => 2,
             'ascii' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
             'regexp' => '/\A(?:JP|US|GB|DE|FR)\z/', // ISO 3166-1 alpha-2 whitelist
             'error_message' => 'Country must be one of: JP, US, GB, DE, FR.']
        ],
        'accepted_tos' => $basicTypes['accepted'], // "yes"/"on"/"1"/"true"
    ]
];

// ---- $_COOKIE: session id + optional UX cookies -----------------------------
$langSpec  = $basicTypes['alpha_dash32'];
$langSpec[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED; // optional
$themeSpec = $basicTypes['alpha_dash32'];
$themeSpec[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED;

$cookieSpec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => 4],
    [
        'PHPSESSID' => $basicTypes['sessid'],
        'lang'      => $langSpec,
        'theme'     => $themeSpec,
    ]
];

// ---- $_FILES: single avatar upload (5 keys per upload) ----------------------
// Each $_FILES[<field>] is an array: name, type, tmp_name, error, size.
// Whitelist MIME type by regex; bound size explicitly.
// NOTE: 'type' is browser-supplied and not trustworthy. Re-check the uploaded
// file with finfo_file() before storing or serving it.
$uploadSpec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 5, 'max' => 5],
    [
        'name'     => [VALIDATE_STRING, VALIDATE_STRING_ALNUM,
                       ['min' => 1, 'max' => 255, 'ascii' => '._-']],
        'type'     => [VALIDATE_REGEXP, VALIDATE_FLAG_NONE,
                       ['min' => 6, 'max' => 32,
                        'ascii' => 'abcdefghijklmnopqrstuvwxyz/',
                        'regexp' => '/\Aimage\/(?:jpeg|png|gif|webp)\z/']],
        'tmp_name' => [VALIDATE_STRING, VALIDATE_STRING_ALNUM,
                       ['min' => 1, 'max' => 4096, 'ascii' => '/_.-']],
        'error'    => [VALIDATE_INT, VALIDATE_FLAG_NONE,
                       ['min' => 0, 'max' => 8]], // UPLOAD_ERR_OK..EXTENSION
        'size'     => [VALIDATE_INT, VALIDATE_FLAG_NONE,
                       ['min' => 1, 'max' => 5 * 1024 * 1024]], // up to 5 MiB
    ]
];

$filesSpec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => 1],
    [
        'avatar' => $uploadSpec,
    ]
];

// ---- HTTP headers: stage 1 (known) ------------------------------------------
// Same pattern as Example #4 (94-example.php). Cookie and User-Agent are
// optional; Host is required.
$cookieHdr = $basicTypes['cookie'];
$cookieHdr[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED;
$uaHdr = $basicTypes['user-agent'];
$uaHdr[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT;
$uaHdr[VALIDATE_OPTIONS]['default'] = '';
$uaHdr[VALIDATE_OPTIONS]['min']     = 0;

$headerKnownSpec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => 100], // Host required + up to 99 others
    [
        'Host'       => $basicTypes['domain'],
        'Cookie'     => $cookieHdr,
        'User-Agent' => $uaHdr,
    ]
];

// ---- HTTP headers: stage 2 (catch-all for everything else) ------------------
$headerCatchSpec = $basicTypes['header512'];
$headerCatchSpec[VALIDATE_FLAGS]   |= VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$headerCatchSpec[VALIDATE_OPTIONS]['min']  = 0;
$headerCatchSpec[VALIDATE_OPTIONS]['amin'] = 0;
$headerCatchSpec[VALIDATE_OPTIONS]['amax'] = 100;

// =============================================================================
// 2. Top-level spec: validate every superglobal + known headers in one call
// =============================================================================

$spec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 5, 'max' => 5],
    [
        'get'    => $getSpec,
        'post'   => $postSpec,
        'cookie' => $cookieSpec,
        'files'  => $filesSpec,
        'header' => $headerKnownSpec,
    ]
];

// =============================================================================
// 3. Collect inputs (with CLI-friendly fallback so this script is runnable)
// =============================================================================

if (PHP_SAPI === 'cli' && empty($_GET) && empty($_POST)) {
    $_GET = ['csrf' => str_repeat('a', 64)];
    $_POST = [
        'username'     => 'yohgaki',
        'email'        => 'yohgaki@ohgaki.net',
        'age'          => 42,
        'country'      => 'JP',
        'accepted_tos' => 'yes',
    ];
    $_COOKIE = [
        'PHPSESSID' => str_repeat('a', 32),
        'lang'      => 'ja',
        'theme'     => 'dark',
    ];
    $_FILES = [
        'avatar' => [
            'name'     => 'photo.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => '/tmp/phpAbCdEf',
            'error'    => 0,
            'size'     => 102400,
        ],
    ];
}

if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
} else {
    // Extract headers from $_SERVER (HTTP_*). Empty under CLI.
    $headers = [];
    foreach ($_SERVER as $k => $v) {
        if (strpos($k, 'HTTP_') === 0) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
            $headers[$name] = $v;
        }
    }
    if (PHP_SAPI === 'cli' && !$headers) {
        $headers = [
            'Host'            => 'example.com',
            'User-Agent'      => 'Mozilla/5.0 (compatible; validate-php-scr/95)',
            'Cookie'          => 'PHPSESSID='.str_repeat('a', 32),
            'Accept'          => 'text/html',
            'Accept-Language' => 'ja, en;q=0.8',
        ];
    }
}

$inputs = [
    'get'    => $_GET,
    'post'   => $_POST,
    'cookie' => $_COOKIE,
    'files'  => $_FILES,
    'header' => $headers,
];

// =============================================================================
// 4. Validate
// =============================================================================

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION; // interactive form: collect errors

// Stage 1: validate everything at once. validate() removes validated entries
// from $inputs, so $inputs['header'] will end up holding only the headers
// that $headerKnownSpec did NOT consume.
$result = validate($ctx, $inputs, $spec, $func_opts);

// Stage 2: validate the leftover headers with the catch-all spec, so EVERY
// header is checked - no header bypasses the trust boundary.
$headersRemaining = $inputs['header'] ?? [];
$catchResult = validate($ctx, $headersRemaining, $headerCatchSpec, $func_opts);
if (is_array($catchResult) && is_array($result) && isset($result['header'])) {
    $result['header'] += $catchResult;
}

// =============================================================================
// 5. Report
// =============================================================================

echo 'Status: ', var_export(validate_get_status($ctx), true), PHP_EOL;
if (!validate_get_status($ctx)) {
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
