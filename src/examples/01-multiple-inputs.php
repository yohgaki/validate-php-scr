<?php
/**
 * Multiple-input validation example.
 *
 * A realistic shape: a request with $_POST, $_GET and HTTP headers, all
 * validated in a single validate() call. Shows how to:
 *   - compose reusable per-field specs into an array spec,
 *   - validate top-level + nested arrays in one pass,
 *   - apply a "default" pass to catch extra/optional parameters,
 *   - and use VALIDATE_FLAG_ARRAY / VALIDATE_FLAG_ARRAY_KEY_ALNUM to keep
 *     unknown keys controlled.
 */

require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Predefined spec presets.

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



/******************** Per-field validation specs *********************/

// In a real codebase, declare reusable specs in a central file and
// require_once it from each endpoint so every input has a single source of
// truth (and audits or changes touch one place).

// 'min'/'max' are mandatory for every spec — the framework refuses to make
// guesses on the caller's behalf. This is the whitelist principle in action:
// the caller must state the exact range they expect.
$id = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => PHP_INT_MAX]
];

// Strings reject every character by default. Opt characters in explicitly
// via VALIDATE_STRING_* flags (whole classes) or the 'ascii' option
// (individual extra characters).
$name = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_SPACE,
    ['min' => 4, 'max' => 64]
];

// For floats you may use -INF/INF as min/max as a shorthand for "unbounded".
// min/max are still required — there is no implicit default.
$float = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => 4, 'max' => INF]
    // Even with max => INF, an actual INF input is still rejected unless
    // explicitly allowed via 'INF' => true (and likewise '-INF' => true for
    // negative infinity). NAN is always rejected.
];

// VALIDATE_STRING_MB opens up UTF-8 multibyte characters. The 'encoding'
// option is informational — only UTF-8 is supported, so it can be omitted.
$utf8 = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_SPACE | VALIDATE_STRING_MB,
    ['min' => 4, 'max' => 64, 'encoding' => 'UTF-8']
];

// Same whitelist principle as $name above: VALIDATE_STRING_LF must be set
// explicitly to permit '\n' inside the value.
$string = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_LF,
    ['min' => 4, 'max' => 128]
];

// The Accept HTTP header contains commas, slashes, semicolons and other
// punctuation. The flag-based whitelist would be too coarse, so we list the
// extra individual characters via the 'ascii' option.
$accept = [
    VALIDATE_STRING,
    VALIDATE_FLAG_UNDEFINED_TO_DEFAULT | VALIDATE_STRING_DIGIT | VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_SPACE,
    ['min' => 0, 'max' => 1024, 'ascii' => '=/,.;+()*', 'default' => '']
];


// Anything the built-in flags cannot express goes through VALIDATE_CALLBACK,
// where the rules are plain PHP. The callback receives an already pre-filtered
// input — only the characters allowed by the flags below ever reach it.
$user_agent = [
    VALIDATE_CALLBACK, // Any PHP callable is accepted.
    VALIDATE_CALLBACK_SPACE | VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_MB,
    ['min' => 10, 'max' => 128,
    'callback' =>
    // Signature: function (Validate $ctx, mixed &$result, mixed $input): bool
    //
    // - Assign $result to publish the validated (possibly normalized) value.
    // - Return true on success, false on failure.
    // - Never *sanitize* untrusted input by stripping bad characters; instead,
    //   verify that the input is legitimate as-is. Be restrictive.
    function ($ctx, &$result, $input) {
        // Design-by-contract assertions document and enforce preconditions
        // without affecting production code (PHP assertions are zero-cost when
        // disabled), and are easier to maintain than parameter type hints.
        assert($ctx instanceof Validate);

        // Order matters: check type first, then length, then content. Heavy
        // or complex checks come last — short-circuiting earlier saves CPU
        // and avoids passing malformed data into deeper logic.
        // Always whitelist; even "one extra harmless character" (space, LF, ...)
        // can be dangerous depending on the downstream output context.
        if (!is_string($input)) {
            validate_error($ctx, 'User-Agent validation: User-Agent must be string.');
            return false; // Always return after validate_error(); it does not abort the callback.
        }

        $len = strlen($input);
        // This length+charset check could be expressed via VALIDATE_STRING
        // with an 'ascii' option; doing it manually here is purely illustrative.
        if ($len !== strspn($input, ' 1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ()_.,/;"')) {
            validate_error($ctx, 'User-Agent validation: Invalid char detected.');
            return false;
        }
        // Publish the validated value on success.
        $result = $input;
        return true;
    }]
];



/******************** Combined validation spec ****************************/

// Compose the per-field specs above into a single top-level spec. Each spec
// array follows the [type, flags, options, sub-specs] layout — see
// validate_spec() in validate_func.php for the formal grammar.
$specs = [
    VALIDATE_ARRAY,           // [0] validator type
    VALIDATE_FLAG_NONE,       // [1] flag bitfield
    ['min' => 3, 'max' => 3], // [2] options: exactly three top-level keys
    [
        'post' => [
            VALIDATE_ARRAY,
            VALIDATE_FLAG_NONE,
            ['min' => 4, 'max' => 5],
            // min/max on arrays cap the *element count*, not byte length. This
            // rejects unexpectedly large or empty payloads up front, before any
            // per-element rule runs.
            // Mark an individual element optional with VALIDATE_FLAG_UNDEFINED
            // (or VALIDATE_FLAG_UNDEFINED_TO_DEFAULT if a default is needed).
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
            [] // No declared keys; the default-spec pass below handles them.
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

// Real-world web apps often see extra (optional, unforeseen) parameters.
// Build a second pass with a loose array spec to validate whatever remains.
// VALIDATE_FLAG_ARRAY makes every element validated under the same spec;
// VALIDATE_FLAG_ARRAY_KEY_ALNUM restricts the allowed key names to alnum+'_'.
$post_default = $basicTypes['text128']; // UTF-8 text, up to 128 bytes.
$post_default[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
                                 | VALIDATE_FLAG_ARRAY
                                 | VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$post_default[VALIDATE_OPTIONS]['amin'] = 0;     // At least 0 extra POST keys.
$post_default[VALIDATE_OPTIONS]['amax'] = 2;     // At most 2 extra POST keys.
$post_default[VALIDATE_OPTIONS]['default'] = []; // Substitute [] when entirely absent.

$get_default = $basicTypes['alnum128']; // Alnum text, up to 128 bytes.
$get_default[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
                                 | VALIDATE_FLAG_ARRAY
                                 | VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$get_default[VALIDATE_OPTIONS]['amin'] = 0;     // At least 0 extra query params.
$get_default[VALIDATE_OPTIONS]['amax'] = 5;     // At most 5 extra query params.
$get_default[VALIDATE_OPTIONS]['default'] = [];

$header_default = $basicTypes['header1024u']; // UTF-8 HTTP header value, up to 1024 bytes.
$header_default[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
                                 | VALIDATE_FLAG_ARRAY
                                 | VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$header_default[VALIDATE_OPTIONS]['amin'] = 0;     // At least 0 extra headers.
$header_default[VALIDATE_OPTIONS]['amax'] = 10;    // At most 10 extra headers.
$header_default[VALIDATE_OPTIONS]['default'] = [];


$default_specs = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => 3],
    [
        'post'   => $post_default,
        'get'    => $get_default,
        'header' => $header_default,
    ]
];


// You should validate ALL request inputs — $_POST/$_GET/$_COOKIE/$_FILES,
// the relevant $_SERVER entries, and apache_request_headers() — not just the
// fields your business logic happens to read this minute.
$my_inputs = ['post' => $POST, 'get' => $GET, 'header' => $HEADER];

// Validate the full request in a single call.
// Equivalent OO one-liner (no need to keep a $ctx):
//   $result = (new Validate)->validate($my_inputs, $specs, VALIDATE_OPT_DISABLE_EXCEPTION);
$inputs = validate($ctx, $my_inputs, $specs, 0);
var_dump($inputs, $ctx->getStatus()); // true on success.

// Any keys NOT consumed by $specs remain in $my_inputs (validate() unsets
// keys as it validates them). Pass VALIDATE_OPT_KEEP_INPUTS to disable this.
var_dump($my_inputs);

// Second pass — validate the leftover, optional parameters with looser rules.
$extras = validate($ctx, $my_inputs, $default_specs, 0);


// Always log validation failures. A failed input validation almost always
// indicates a misbehaving client or an attacker — when a stronger response
// is appropriate (forcing logout, throttling, blocking an IP), take it.
