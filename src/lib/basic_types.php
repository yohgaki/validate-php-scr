<?php
/**
 * Predefined specs for common input shapes.
 *
 * Loading this file populates the global $basicTypes array with ready-to-use
 * specs (e.g. $basicTypes['int32'], $basicTypes['uuid'], $basicTypes['email']).
 * Pick the entry that matches your input and pass it straight to validate(),
 * or copy it and tweak the third element (options) for per-call adjustments.
 *
 * Naming convention:
 *   - Bare names (e.g. 'alpha', 'header', 'text') leave 'min'/'max' set to 0,
 *     which is a placeholder meaning "you must override before use". Doing so
 *     forces every caller to pick a concrete length budget for their context.
 *   - Suffixes like 'alpha32', 'header4096' embed the max length in bytes.
 *   - The `_s` suffix loosens a base spec by also accepting SPACE and ASCII
 *     SYMBOL chars (more permissive — use with care).
 *   - The `u` suffix loosens a base spec by also accepting UTF-8 multibyte chars.
 *   - The `_dns` suffix performs an extra DNS lookup at validation time.
 *
 * Many specs assume the client (HTML/JS) has already done a sanity check on
 * length and character set; these specs are the server-side safety net.
 */
require_once __DIR__.'/../validate_defs.php';

// ---------------------------------------------------------------------------
// Integers — bounded by the named bit width. The string min/max bounds on
// 53+/64+ bit specs let validation work on 32-bit PHP builds (see the
// PHP_INT_SIZE adjustment below, which switches them to AS_STRING mode).
// ---------------------------------------------------------------------------
$basicTypes['int8'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => -128, 'max' => 127]
];

$basicTypes['uint8'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => 255]
];

$basicTypes['int16'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => -32768, 'max' => 32767]
];

$basicTypes['uint16'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => 65535]
];

$basicTypes['int32'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => -2147483648, 'max' => 2147483647]
];

$basicTypes['uint32'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => '4294967295']
];

// int53: safe integer range for IEEE 754 double / JavaScript Number (±(2^53-1))
$basicTypes['int53'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => '-9007199254740991', 'max' => '9007199254740991']
];

$basicTypes['uint53'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => '9007199254740991']
];

$basicTypes['int64'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => '-9223372036854775808', 'max' => '9223372036854775807']
];

$basicTypes['uint64'] = [
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    ['min' => 0, 'max' => '18446744073709551615']
];

$basicTypes['int128'] = [
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    ['min' => '-170141183460469231731687303715884105728', 'max' => '170141183460469231731687303715884105727']
];

$basicTypes['uint128'] = [
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    ['min' => 0, 'max' => '340282366920938463463374607431768211455']
];

// On 32-bit PHP builds, integers wider than PHP_INT_MAX (2^31-1) cannot be
// represented natively. Flip the affected specs to AS_STRING mode so the
// validator compares numeric strings without overflow.
if (PHP_INT_SIZE === 4) {
    $basicTypes['uint32'][2] = VALIDATE_INT_AS_STRING;
    $basicTypes['int53'][2] = VALIDATE_INT_AS_STRING;
    $basicTypes['uint53'][2] = VALIDATE_INT_AS_STRING;
    $basicTypes['int64'][2] = VALIDATE_INT_AS_STRING;
    $basicTypes['uint64'][2] = VALIDATE_INT_AS_STRING;
}

// Boolean — accepts PHP true/false. Add VALIDATE_BOOL_* flags below to also
// accept textual forms like "true"/"false", "yes"/"no", etc.
$basicTypes['bool'] = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_NONE,
    []
];

// ---------------------------------------------------------------------------
// Floats. 'float' has no useful range by default — every caller must narrow
// min/max for their domain (e.g. price, latitude, percent).
// ---------------------------------------------------------------------------
$basicTypes['float'] = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => -INF, 'max' => INF]
];

// Non-negative float (price, distance, etc.).
$basicTypes['float_pos'] = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => INF]
];

// ===========================================================================
// TEXT specs
// Some of these presets assume that the client (e.g. HTML/JS) has already
// done basic length and character-set validation.
// ===========================================================================

// Password: alnum + ASCII symbols + spaces. 8..768 bytes.
// Range chosen for argon2/bcrypt input safety (768 keeps single-block bcrypt
// inputs while leaving room for passphrases).
$basicTypes['password'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_SPACE,
    [
        'min' => 8, 'max' => 768,
    ]
];

// ---------------------------------------------------------------------------
// Single-line text — letters only (no spaces, no digits).
// The bare 'alpha' has min=max=0 as placeholders; override before use.
// Numbered variants (alpha32 etc.) cap the byte length.
// ---------------------------------------------------------------------------
$basicTypes['alpha'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 0,
    ]
];

$basicTypes['alpha32'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 32,
    ]
];

$basicTypes['alpha64'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 64,
    ]
];

$basicTypes['alpha128'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 128,
    ]
];

$basicTypes['alpha256'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 256,
    ]
];

// ---------------------------------------------------------------------------
// Single-line text — letters + digits (no spaces, no symbols).
// Bare 'alnum' is a placeholder (min=max=0); override the length before use.
// ---------------------------------------------------------------------------
$basicTypes['alnum'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 0,
    ]
];

$basicTypes['alnum32'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 32,
    ]
];

$basicTypes['alnum64'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 64,
    ]
];

$basicTypes['alnum128'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 128,
    ]
];

$basicTypes['alnum256'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 256,
    ]
];


// ---------------------------------------------------------------------------
// Single-line UTF-8 text (no line breaks).
// 'line*'    : ALNUM + MB (letters/digits + multibyte) — strict.
// 'line*_s'  : ALNUM + MB + SPACE + all ASCII SYMBOLS — looser, use with care.
// The bare 'line'/'line_s' have min=max=0 placeholders; override before use.
// ---------------------------------------------------------------------------
$basicTypes['line'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 0,
    ]
];

$basicTypes['line_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 0,
    ]
];

$basicTypes['line32'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 32,
    ]
];

$basicTypes['line32_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 32,
    ]
];

$basicTypes['line64'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 64,
    ]
];

$basicTypes['line64_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 64,
    ]
];

$basicTypes['line128'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 128,
    ]
];

$basicTypes['line128_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 128,
    ]
];

$basicTypes['line256'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 256,
    ]
];

$basicTypes['line256_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 256,
    ]
];

$basicTypes['line512'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 512,
    ]
];

$basicTypes['line512_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 512,
    ]
];

// ---------------------------------------------------------------------------
// Multi-line UTF-8 text. LF is the only newline accepted in every variant.
// 'text*'    : ALNUM + LF + MB.
// 'text*_s'  : also SPACE + all ASCII SYMBOLS — riskier, use with explicit care.
// The bare 'text'/'text_s' have min=max=0 placeholders; override before use.
// NOTE: HTML <textarea> already normalizes CRLF/CR to LF on submit, which
// matches the LF-only policy below.
// ---------------------------------------------------------------------------
$basicTypes['text'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 0,
    ]
];

$basicTypes['text_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 0,
    ]
];

$basicTypes['text128'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 128,
    ]
];

$basicTypes['text128_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 128,
    ]
];

$basicTypes['text256'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 256,
    ]
];

$basicTypes['text256_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 256,
    ]
];

$basicTypes['text512'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 512,
    ]
];

$basicTypes['text512_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 512,
    ]
];

$basicTypes['text1024'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 1024,
    ]
];

$basicTypes['text1024_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 1024,
    ]
];

$basicTypes['text2048'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 2048,
    ]
];

$basicTypes['text2048_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 2048,
    ]
];

$basicTypes['text4096'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 4096,
    ]
];

$basicTypes['text4096_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 4096,
    ]
];

$basicTypes['text8192'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 8192,
    ]
];

$basicTypes['text8192_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 8192,
    ]
];

// ---------------------------------------------------------------------------
// SQL identifiers — alnum + underscore, first char must be a letter or '_'.
// Two length caps are provided:
//   sqlident63  : PostgreSQL default NAMEDATALEN limit (63 bytes).
//   sqlident127 : SQL-92 standard limit (127 bytes); also matches MySQL.
// ---------------------------------------------------------------------------
$basicTypes['sqlident63'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 1, 'max' => 63,
        'ascii' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_',
        'regexp' => '/\A[a-zA-Z_][a-zA-Z0-9_]*\z/',
    ]
];

$basicTypes['sqlident127'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 1, 'max' => 127,
        'ascii' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_',
        'regexp' => '/\A[a-zA-Z_][a-zA-Z0-9_]*\z/',
    ]
];


// ---------------------------------------------------------------------------
// PHP session ID.
// NOTE: The PHP session module silently strips invalid characters from the
// incoming session id, so an extra sanity check at the application boundary
// is still valuable. The spec assumes the default session.sid_length (32),
// but allows up to 128 chars to accommodate longer ids configured via
// session.sid_length or session.sid_bits_per_character.
// ---------------------------------------------------------------------------
$basicTypes['sessid'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 32, 'max' => 128,
        'ascii' => '-,',
    ]
];


// ---------------------------------------------------------------------------
// Base64 (standard alphabet, RFC 4648 §4). 'base64' has min=max=1 placeholders;
// override the length to match your payload (length is in encoded bytes).
// See https://en.wikipedia.org/wiki/Base64
// ---------------------------------------------------------------------------
$basicTypes['base64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 1,
        'ascii' => '+/=',
    ]
];

$basicTypes['base64_32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 32,
        'ascii' => '+/=',
    ]
];

$basicTypes['base64_64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 64,
        'ascii' => '+/=',
    ]
];

$basicTypes['base64_128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 128,
        'ascii' => '+/=',
    ]
];

$basicTypes['base64_256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 256,
        'ascii' => '+/=',
    ]
];


// ---------------------------------------------------------------------------
// URL-safe Base64 (RFC 4648 §5). Uses '-' and '_' instead of '+' and '/';
// padding ('=') is optional but accepted. Common in JWT and OAuth tokens.
// 'base64url' has min=max=1 placeholders; override before use.
// ---------------------------------------------------------------------------
$basicTypes['base64url'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 1,
        'ascii' => '-_=',
    ]
];

$basicTypes['base64url_32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 32,
        'ascii' => '-_=',
    ]
];

$basicTypes['base64url_64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 64,
        'ascii' => '-_=',
    ]
];

$basicTypes['base64url_128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 128,
        'ascii' => '-_=',
    ]
];

$basicTypes['base64url_256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 256,
        'ascii' => '-_=',
    ]
];


// ---------------------------------------------------------------------------
// URL-encoded string (the output of rawurlencode()/urlencode()).
// Alnum + the percent-encoding escape character and the symbols left
// unreserved by RFC 3986 ('-_.~') plus '+' used by application/x-www-form-urlencoded.
// 'urle' has min=max=1 placeholders; override before use.
// ---------------------------------------------------------------------------
$basicTypes['urle'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 1,
        'ascii' => '%-_.~+',
    ]
];

$basicTypes['urle32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 32,
        'ascii' => '%-_.~+',
    ]
];

$basicTypes['urle64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 64,
        'ascii' => '%-_.~+',
    ]
];

$basicTypes['urle128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 128,
        'ascii' => '%-_.~+',
    ]
];

$basicTypes['urle256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 256,
        'ascii' => '%-_.~+',
    ]
];


// ---------------------------------------------------------------------------
// HTML-encoded string. Useful as an "assertion" that a value has already
// been HTML-escaped (e.g. via htmlspecialchars with ENT_QUOTES) before
// reaching the validator. Assumes LF for newlines.
// 'htmle' has min=max=1 placeholders; override before use.
// ---------------------------------------------------------------------------
$basicTypes['htmle'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 1,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$basicTypes['htmle32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 32,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$basicTypes['htmle64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 64,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$basicTypes['htmle128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 128,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$basicTypes['htmle256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 256,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$basicTypes['htmle512'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 512,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$basicTypes['htmle1024'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 1024,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];



// ---------------------------------------------------------------------------
// Hexadecimal strings. Both upper- and lower-case are accepted.
// 'hex'           : generic placeholder — set min/max yourself.
// 'hex8' .. 'hex128' : exact length matching common hash output sizes (see comments).
// ---------------------------------------------------------------------------
$basicTypes['hex'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 0, 'max' => 0,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

$basicTypes['hex8'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 8, 'max' => 8,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// 64-bit hash (e.g. SipHash-64)
$basicTypes['hex16'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 16, 'max' => 16,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// 96-bit hash
$basicTypes['hex24'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 24, 'max' => 24,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// MD5 / RIPEMD-128 (128 bits = 16 bytes = 32 hex chars)
$basicTypes['hex32'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 32, 'max' => 32,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// SHA1 / RIPEMD-160 (160 bits = 20 bytes = 40 hex chars)
$basicTypes['hex40'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 40, 'max' => 40,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// SHA224 / SHA3-224 (224 bits = 28 bytes = 56 hex chars)
$basicTypes['hex56'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 56, 'max' => 56,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// SHA256 / SHA3-256 (256 bits = 32 bytes = 64 hex chars)
$basicTypes['hex64'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 64, 'max' => 64,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// SHA384 / SHA3-384 (384 bits = 48 bytes = 96 hex chars)
$basicTypes['hex96'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 96, 'max' => 96,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// SHA512 / SHA3-512 (512 bits = 64 bytes = 128 hex chars)
$basicTypes['hex128'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 128, 'max' => 128,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];


// ---------------------------------------------------------------------------
// UUID — RFC 4122 canonical form (8-4-4-4-12 hex). Input is lowercased by
// the filter so spec consumers receive a normalized value.
// See https://en.wikipedia.org/wiki/Universally_unique_identifier
// ---------------------------------------------------------------------------
$basicTypes['uuid'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 36, 'max' => 36,
        'filter' => function ($ctx, $input, &$error) {
            if (!is_string($input)) {
                $error = 'UUID Filter error: UUID must be string.';
                return;
            }
            return strtolower($input);
        },
        'ascii' => 'abcdef0123456789-',
        'regexp' => '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/',
    ]
];

// ---------------------------------------------------------------------------
// Date / Time — regex-based ISO 8601 forms. These accept syntactically valid
// values but do not reject impossible calendar dates (e.g. 2025-02-30 passes).
// Combine with a follow-up checkdate() call if calendar correctness matters.
// ---------------------------------------------------------------------------
// ISO 8601 date: YYYY-MM-DD
$basicTypes['date'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 10, 'max' => 10,
        'ascii' => '0123456789-',
        'regexp' => '/\A\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])\z/',
    ]
];

// ISO 8601 time: HH:MM or HH:MM:SS
$basicTypes['time'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 5, 'max' => 8,
        'ascii' => '0123456789:',
        'regexp' => '/\A([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?\z/',
    ]
];

// ISO 8601 datetime: YYYY-MM-DDTHH:MM[:SS][Z|±HH:MM] or space separator
$basicTypes['datetime'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 16, 'max' => 25,
        'ascii' => '0123456789T :-+Z',
        'regexp' => '/\A\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])[T ]([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?(?:Z|[+-](?:0\d|1[0-4]):[0-5]\d)?\z/',
    ]
];


// ---------------------------------------------------------------------------
// IP addresses and CIDR ranges (textual form). Regex-based — does not
// distinguish public/private/loopback ranges; layer your own policy on top.
// ---------------------------------------------------------------------------
$basicTypes['ipv4'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 7, 'max' => 15,
        'ascii' => '0123456789.',
        'regexp' => '/\A((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\z/'
    ]
];

$basicTypes['ipv6'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 3, 'max' => 45, // Includes IPv4 mapped IPv6. Otherwise, max => 39
        'ascii' => '0123456789abcdefABCDEF:.',
        // https://stackoverflow.com/questions/53497/regular-expression-that-matches-valid-ipv6-addresses
        // phpcs:ignore Generic.Files.LineLength.TooLong
        'regexp' => '/\A(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))\z/'
    ]
];

// IPv4 CIDR notation (e.g. 192.168.0.0/24)
$basicTypes['cidr4'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 9, 'max' => 18,
        'ascii' => '0123456789./',
        'regexp' => '/\A((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)\/(3[0-2]|[12]?\d)\z/',
    ]
];


// ---------------------------------------------------------------------------
// Domain / host names.
// 'fqdn'     : format check + live DNS lookup (rejects unresolvable names).
// 'hostname' : single label only, no dot, format only.
// 'domain'   : multi-label, format only (no DNS lookup).
// ---------------------------------------------------------------------------
$basicTypes['fqdn'] = [
    VALIDATE_CALLBACK,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 3, 'max' => 253,
        'ascii' => '.-_',
        'callback' => function ($ctx, &$result, $input) {
            if (!strpos($input, '.') || $input[strlen($input)-1] === '.') {
                validate_error($ctx, 'Invalid FQDN');
                return false;
            }
            if (!dns_get_record($input)) {
                validate_error($ctx, 'Cannot resolve by DNS');
                return false;
            }
            $result = $input;
            return true;
        }]
];

// Hostname only
$basicTypes['hostname'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 63,
        'ascii' => '-_',
    ]
];

// Domain name - format validation only, no DNS lookup (see 'fqdn' for DNS-verified version)
// RFC 1123: labels 1-63 chars, total max 253, must have at least one dot
$basicTypes['domain'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 3, 'max' => 253,
        'ascii' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.-',
        'regexp' => '/\A(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,63}\z/',
    ]
];

// Email address - format validation only (RFC 5321/5322 practical subset)
// Max 64 chars local-part, max 255 chars domain, max 254 chars total
$basicTypes['email'] = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_SYMBOL,
    [
        'min' => 6, 'max' => 254,
        'callback' => function ($ctx, &$result, $input) {
            if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                validate_error($ctx, 'Invalid email address format.');
                return false;
            }
            $result = $input;
            return true;
        },
    ]
];

// URL slug: lowercase alnum and hyphens, no leading/trailing hyphen
$basicTypes['slug'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 1, 'max' => 255,
        'ascii' => 'abcdefghijklmnopqrstuvwxyz0123456789-',
        'regexp' => '/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\z/',
    ]
];


// ===========================================================================
// HTTP headers — RFC 7230 §3.2.
// Leading and trailing whitespace is stripped (RFC 7230 allows the recipient
// to remove OWS); SP and HTAB stay legal inside the field-value.
// ===========================================================================
$vtrim = function ($ctx, $input, &$error) {
    assert($ctx instanceof Validate);
    if (is_array($input)) {
        $error = 'HTTP Header Filter error: HTTP header must be string.';
        return;
    }
    return trim($input);
};

// HTTP headers may contain SYMBOL characters by RFC, so the character-set
// restriction here is intentionally loose. Whenever the header has a tighter
// grammar (e.g. Content-Length is a uint), prefer a more specific spec —
// $basicTypes['uint32'] for Content-Length, $basicTypes['domain'] for Host, etc.
// Bare 'header' has min=max=0 placeholders; override before use.
$basicTypes['header'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 0,
        'filter' => $vtrim,
    ]
];

// Length-bounded HTTP header variants. The `u` suffix additionally allows
// UTF-8 multibyte characters (rarely used in practice but legal in obs-text
// per RFC 7230 §3.2.4). Pick the tightest length that fits the header you
// are checking — accepting more than you need is gratuitous attack surface.
$basicTypes['header64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 64,
        'filter' => $vtrim,
    ]
];

// UTF-8 is legal per RFC but seen rarely; only use the `u` variants when you
// actually expect multibyte data (e.g. some Cookie or referrer payloads).
$basicTypes['header64u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 64,
        'filter' => $vtrim,
    ]
];

$basicTypes['header128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 128,
        'filter' => $vtrim,
    ]
];

$basicTypes['header128u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 128,
        'filter' => $vtrim,
    ]
];

$basicTypes['header256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 256,
        'filter' => $vtrim,
    ]
];

$basicTypes['header256u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 256,
        'filter' => $vtrim,
    ]
];

$basicTypes['header512'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 512,
        'filter' => $vtrim,
    ]
];

$basicTypes['header512u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 512,
        'filter' => $vtrim,
    ]
];

$basicTypes['header1024'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 1024,
        'filter' => $vtrim,
    ]
];

$basicTypes['header1024u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 1024,
        'filter' => $vtrim,
    ]
];

$basicTypes['header2048'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 2048,
        'filter' => $vtrim,
    ]
];

$basicTypes['header2048u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 2048,
        'filter' => $vtrim,
    ]
];

$basicTypes['header4096'] =
[
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 4096,
        'filter' => $vtrim,
    ]
];

$basicTypes['header4096u'] =
[
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 4096,
        'filter' => $vtrim,
    ]
];

$basicTypes['header8192'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 8192,
        'filter' => $vtrim,
    ]
];

$basicTypes['header8192u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 8192,
        'filter' => $vtrim,
    ]
];

$basicTypes['header32k'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 32768,
        'filter' => $vtrim,
    ]
];

$basicTypes['header32ku'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 32768,
        'filter' => $vtrim,
    ]
];

// ---------------------------------------------------------------------------
// JSON string. The callback runs json_decode and reports a failure if the
// input cannot be parsed. VALIDATE_CALLBACK_SYMBOL is required so the
// surrounding character whitelist accepts JSON delimiters ({ } [ ] " \ , : etc.).
// The decoded value is discarded — only $input (the original string) is stored.
// ---------------------------------------------------------------------------
$basicTypes['json'] = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_SPACE
     | VALIDATE_CALLBACK_CRLF | VALIDATE_CALLBACK_TAB | VALIDATE_CALLBACK_MB,
    [
        'min' => 2, 'max' => 65535,
        'callback' => function ($ctx, &$result, $input) {
            json_decode($input);
            if (json_last_error() !== JSON_ERROR_NONE) {
                validate_error($ctx, 'Invalid JSON: ' . json_last_error_msg());
                return false;
            }
            $result = $input;
            return true;
        },
    ]
];


// Aliases — convenient, opinionated names for common HTTP headers. They
// reference one of the specs above so adjustments propagate automatically.
$basicTypes['content-length'] = $basicTypes['uint32'];
$basicTypes['content-type']   = $basicTypes['header128'];
$basicTypes['user-agent']     = $basicTypes['header512'];
$basicTypes['cookie']         = $basicTypes['header4096'];

// Networking aliases — TCP/UDP port range fits inside uint16.
$basicTypes['port'] = $basicTypes['uint16'];


// =============================================================================
// Laravel-compatible basic validators.
//
// Single-value rules that exist in Laravel's Validator but had no equivalent
// above. Implemented with no external dependencies. Cross-field, DB, file,
// and control-flow rules are intentionally not included — those belong in
// application code, not a generic input validator.
// =============================================================================

// MAC address — 6 hex octets separated by ':' or '-' (e.g. "AA:BB:CC:DD:EE:FF").
$basicTypes['mac_address'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 17, 'max' => 17,
        'ascii' => '0123456789abcdefABCDEF:-',
        'regexp' => '/\A(?:[0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}\z/',
    ]
];

// ULID - 26 chars, Crockford Base32 (excludes I, L, O, U), case-insensitive
$basicTypes['ulid'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 26, 'max' => 26,
        'ascii' => '0123456789ABCDEFGHJKMNPQRSTVWXYZabcdefghjkmnpqrstvwxyz',
        'regexp' => '/\A[0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{26}\z/',
    ]
];

// HTML hex color: #RGB, #RGBA, #RRGGBB, #RRGGBBAA
$basicTypes['hex_color'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 4, 'max' => 9,
        'ascii' => '#0123456789abcdefABCDEF',
        'regexp' => '/\A#(?:[0-9A-Fa-f]{3,4}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})\z/',
    ]
];

// URL — format-only check via filter_var(FILTER_VALIDATE_URL). Use 'active_url'
// when you also need the host to resolve.
$basicTypes['url'] = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_SYMBOL,
    [
        'min' => 7, 'max' => 2048,
        'callback' => function ($ctx, &$result, $input) {
            if (!filter_var($input, FILTER_VALIDATE_URL)) {
                validate_error($ctx, 'Invalid URL format.');
                return false;
            }
            $result = $input;
            return true;
        },
    ]
];

// URL with a DNS resolution check on the host portion (analogous to 'fqdn').
$basicTypes['active_url'] = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_SYMBOL,
    [
        'min' => 7, 'max' => 2048,
        'callback' => function ($ctx, &$result, $input) {
            if (!filter_var($input, FILTER_VALIDATE_URL)) {
                validate_error($ctx, 'Invalid URL format.');
                return false;
            }
            $host = parse_url($input, PHP_URL_HOST);
            if (!$host || !dns_get_record($host)) {
                validate_error($ctx, 'Cannot resolve URL host by DNS.');
                return false;
            }
            $result = $input;
            return true;
        },
    ]
];

// Alpha-dash — alnum + '-' + '_' (Laravel's alpha_dash rule). Bare 'alpha_dash'
// has min=max=0 as a placeholder; override before use.
$basicTypes['alpha_dash'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 0, 'max' => 0, 'ascii' => '-_']
];
$basicTypes['alpha_dash32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 0, 'max' => 32, 'ascii' => '-_']
];
$basicTypes['alpha_dash64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 0, 'max' => 64, 'ascii' => '-_']
];
$basicTypes['alpha_dash128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 0, 'max' => 128, 'ascii' => '-_']
];
$basicTypes['alpha_dash256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    ['min' => 0, 'max' => 256, 'ascii' => '-_']
];

// Printable ASCII only (no multibyte, no control chars beyond SPACE).
$asciiFlags = VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL;
$basicTypes['ascii'] = [VALIDATE_STRING, $asciiFlags, ['min' => 0, 'max' => 0]];
$basicTypes['ascii32'] = [VALIDATE_STRING, $asciiFlags, ['min' => 0, 'max' => 32]];
$basicTypes['ascii64'] = [VALIDATE_STRING, $asciiFlags, ['min' => 0, 'max' => 64]];
$basicTypes['ascii128'] = [VALIDATE_STRING, $asciiFlags, ['min' => 0, 'max' => 128]];
$basicTypes['ascii256'] = [VALIDATE_STRING, $asciiFlags, ['min' => 0, 'max' => 256]];
$basicTypes['ascii512'] = [VALIDATE_STRING, $asciiFlags, ['min' => 0, 'max' => 512]];
unset($asciiFlags);

// Lowercase ASCII letters only
$basicTypes['lowercase'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA,
    ['min' => 0, 'max' => 0]
];
$basicTypes['lowercase32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA,
    ['min' => 0, 'max' => 32]
];
$basicTypes['lowercase64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA,
    ['min' => 0, 'max' => 64]
];
$basicTypes['lowercase128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA,
    ['min' => 0, 'max' => 128]
];
$basicTypes['lowercase256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA,
    ['min' => 0, 'max' => 256]
];

// Uppercase ASCII letters only
$basicTypes['uppercase'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_UPPER_ALPHA,
    ['min' => 0, 'max' => 0]
];
$basicTypes['uppercase32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_UPPER_ALPHA,
    ['min' => 0, 'max' => 32]
];
$basicTypes['uppercase64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_UPPER_ALPHA,
    ['min' => 0, 'max' => 64]
];
$basicTypes['uppercase128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_UPPER_ALPHA,
    ['min' => 0, 'max' => 128]
];
$basicTypes['uppercase256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_UPPER_ALPHA,
    ['min' => 0, 'max' => 256]
];

// Digit strings of exact length (Laravel's `digits:n`). Generates
// $basicTypes['digits1'] through $basicTypes['digits10'].
for ($n = 1; $n <= 10; $n++) {
    $basicTypes['digits' . $n] = [
        VALIDATE_STRING,
        VALIDATE_STRING_DIGIT,
        ['min' => $n, 'max' => $n]
    ];
}
unset($n);

// Digit string with a 1..10 length range (Laravel's `digits_between:1,10`).
$basicTypes['digits_between_1_10'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_DIGIT,
    ['min' => 1, 'max' => 10]
];

// Laravel accepted: input must be exactly one of "yes", "on", "1", 1, true,
// or "true" (case-sensitive). On success $result is set to bool true.
$basicTypes['accepted'] = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_ALNUM,
    [
        'min' => 1, 'max' => 5,
        'callback' => function ($ctx, &$result, $input) {
            if (in_array($input, [true, 1, '1', 'on', 'yes', 'true'], true)) {
                $result = true;
                return true;
            }
            validate_error($ctx, 'Value must be accepted (yes/on/1/true).');
            return false;
        },
    ]
];

// Laravel declined: input must be exactly one of "no", "off", "0", 0, false,
// or "false" (case-sensitive). On success $result is set to bool false.
$basicTypes['declined'] = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_ALNUM,
    [
        'min' => 1, 'max' => 5,
        'callback' => function ($ctx, &$result, $input) {
            if (in_array($input, [false, 0, '0', 'off', 'no', 'false'], true)) {
                $result = false;
                return true;
            }
            validate_error($ctx, 'Value must be declined (no/off/0/false).');
            return false;
        },
    ]
];

// IANA timezone identifier (e.g., "Asia/Tokyo", "UTC")
$basicTypes['timezone'] = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_ALNUM,
    [
        'min' => 1, 'max' => 64,
        'ascii' => '/_+-',
        'callback' => function ($ctx, &$result, $input) {
            if (!in_array($input, DateTimeZone::listIdentifiers(), true)) {
                validate_error($ctx, 'Invalid timezone identifier.');
                return false;
            }
            $result = $input;
            return true;
        },
    ]
];

// Email with DNS lookup on domain part (Laravel 'email:dns')
$basicTypes['email_dns'] = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_SYMBOL,
    [
        'min' => 6, 'max' => 254,
        'callback' => function ($ctx, &$result, $input) {
            if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                validate_error($ctx, 'Invalid email address format.');
                return false;
            }
            $at = strrpos($input, '@');
            $domain = $at === false ? '' : substr($input, $at + 1);
            if ($domain === '' || (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A'))) {
                validate_error($ctx, 'Email domain does not resolve.');
                return false;
            }
            $result = $input;
            return true;
        },
    ]
];
