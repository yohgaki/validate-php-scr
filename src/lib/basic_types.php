<?php
// Define some basic type specs
require_once __DIR__.'/../validate_defs.php';

// Integers
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

// Adjust flags for 32 bit CPUs
if (PHP_INT_SIZE === 4) {
    $basicTypes['uint32'][2] = VALIDATE_INT_AS_STRING;
    $basicTypes['int53'][2] = VALIDATE_INT_AS_STRING;
    $basicTypes['uint53'][2] = VALIDATE_INT_AS_STRING;
    $basicTypes['int64'][2] = VALIDATE_INT_AS_STRING;
    $basicTypes['uint64'][2] = VALIDATE_INT_AS_STRING;
}

// Boolean
$basicTypes['bool'] = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_NONE,
    []
];

// Floats
// You should set min/max by yourself to use 'float'.
$basicTypes['float'] = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => -INF, 'max' => INF]
];

// Non-negative float
$basicTypes['float_pos'] = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => INF]
];

// TEXT specs
// Some of them assumes client side validation.

// Password
$basicTypes['password'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_SPACE,
    [
        'min' => 8, 'max' => 768,
    ]
];

// Single line texts
// Alphabets only line
// You should set min/max by yourself to use 'alpha'.
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

// Alnum only line
// You should set min/max by yourself to use 'alnum'.
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


// Multibyte UTF-8 text line - Includes all SYMBOLS(Space and Symbols except newlines), so this could be dangerous.
// You should set min/max by yourself to use 'line'.
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

// Multiline texts - Includes all SYMBOLS, so this could be dangerous.
// NOTE: textarea normalizes newline to NL
// You should set min/max by yourself to use 'text'.
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

// SQL Identifiers
// Restrict to alnum + underscore. First char must not be a digit (SQL standard).
// SQL standards max is 127 chars, but PostgreSQL max is 63.
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


// PHP Session ID
// NOTE: PHP session module ignores bad chars w/o errors.
// Following assumes default setting. It allows longer ID also.
$basicTypes['sessid'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 32, 'max' => 128,
        'ascii' => '-,',
    ]
];


// Base64
// https://en.wikipedia.org/wiki/Base64
// You should set min/max by yourself to use 'base64'.
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


// URL-safe Base64 (RFC 4648 §5): uses '-' and '_' instead of '+' and '/', no padding
// You should set min/max by yourself to use 'base64url'.
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


// URL encode
// You should set min/max by yourself to use 'urle'.
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


// HTML encode
// This is useful to "assert" strings are HTML escaped already.
// LF newline, ENT_QUOTES assumed.

// You should set min/max by yourself to use 'header'.
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



// HEX
// You should set min/max by yourself to use 'hex'.
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


// UUID
// https://en.wikipedia.org/wiki/Universally_unique_identifier
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

// Date / Time
// ISO 8601 date: YYYY-MM-DD (day-of-month range is not fully validated without calendar logic)
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


// IP Address
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


// Domain name
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


// HTTP header
// https://tools.ietf.org/html/rfc7230#section-3.2
// Trim is required(=spaces are allowed) by RFC
$vtrim = function ($ctx, $input, &$error) {
    assert($ctx instanceof Validate);
    if (is_array($input)) {
        $error = 'HTTP Header Filter error: HTTP header must be string.';
        return;
    }
    return trim($input);
};

// SYMBOL is allowed, so risk mitigation is minimum!!
// You should set min/max by yourself to use 'header'.
$basicTypes['header'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 0,
        'filter' => $vtrim,
    ]
];

// You should do strict validation to get most benefits from validation.
// e.g. Use $basicTypes['uint32'] for Content-Length.
$basicTypes['header64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 64,
        'filter' => $vtrim,
    ]
];

// UTF-8 is allowed by RFC, but it's not used often.
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

// JSON string - validates that input is parseable JSON
// VALIDATE_CALLBACK_SYMBOL allows all ASCII symbols including { } [ ] " \ etc.
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


// Some HTTP header aliases
$basicTypes['content-length'] = $basicTypes['uint32'];
$basicTypes['content-type'] = $basicTypes['header128'];
$basicTypes['user-agent'] = $basicTypes['header512'];
$basicTypes['cookie'] = $basicTypes['header4096'];

// Networking aliases
$basicTypes['port'] = $basicTypes['uint16'];
