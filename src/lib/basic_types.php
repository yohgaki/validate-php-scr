<?php
// Define some basic type specs
require_once __DIR__.'/../validate_defs.php';

// Integers
$B['int8'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => -128, 'max' => 127]
];

$B['uint8'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => 255]
];

$B['int16'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => -32768, 'max' => 32767]
];

$B['uint16'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => 65535]
];

$B['int32'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => -2147483648, 'max' => 2147483647]
];

$B['uint32'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => '4294967295']
];

// int53 is max range that both 32/64 CPU can compute int correctly.
$B['int53'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => '-4503599627370496', 'max' => '4503599627370495']
];

$B['uint53'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 0, 'max' => '9007199254740992']
];

$B['int64'] = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => '-9223372036854775808', 'max' => '9223372036854775807']
];

$B['uint64'] = [
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    ['min' => 0, 'max' => '73786976294838206463']
];

$B['int128'] = [
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    ['min' => '-170141183460469231731687303715884105728', 'max' => '170141183460469231731687303715884105727']
];

$B['uint128'] = [
    VALIDATE_INT,
    VALIDATE_INT_AS_STRING,
    ['min' => 0, 'max' => '340282366920938463463374607431768211455']
];

// Adjust flags for 32 bit CPUs
if (PHP_INT_SIZE === 4) {
    $B['uint32'][2] = VALIDATE_INT_AS_STRING;
    $B['int53'][2] = VALIDATE_INT_AS_STRING;
    $B['uint53'][2] = VALIDATE_INT_AS_STRING;
    $B['int64'][2] = VALIDATE_INT_AS_STRING;
    $B['uint64'][2] = VALIDATE_INT_AS_STRING;
}

// TEXT specs
// Some of them assumes client side validation.

// Password
$B['password'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_SPACE,
    [
        'min' => 8, 'max' => 768,
    ]
];

// Single line texts
// Alphabets only line
// You should set min/max by yourself to use 'alpha'.
$B['alpha'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 0,
    ]
];

$B['alpha32'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 32,
    ]
];

$B['alpha64'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 64,
    ]
];

$B['alpha128'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 128,
    ]
];

$B['alpha256'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
    [
        'min' => 0, 'max' => 256,
    ]
];

// Alnum only line
// You should set min/max by yourself to use 'alnum'.
$B['alnum'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 0,
    ]
];

$B['alnum32'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 32,
    ]
];

$B['alnum64'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 64,
    ]
];

$B['alnum128'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 128,
    ]
];

$B['alnum256'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 256,
    ]
];


// Multibyte UTF-8 text line - Includes all SYMBOLS(Space and Symbols except newlines), so this could be dangerous.
// You should set min/max by yourself to use 'line'.
$B['line'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 0,
    ]
];

$B['line_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 0,
    ]
];

$B['line32'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 32,
    ]
];

$B['line32_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 32,
    ]
];

$B['line64'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 64,
    ]
];

$B['line64_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 64,
    ]
];

$B['line128'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 128,
    ]
];

$B['line128_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 128,
    ]
];

$B['line256'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 256,
    ]
];

$B['line256_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 256,
    ]
];

$B['line512'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 512,
    ]
];

$B['line512_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 512,
    ]
];

// Multiline texts - Includes all SYMBOLS, so this could be dangerous.
// NOTE: textarea normalizes newline to NL
// You should set min/max by yourself to use 'text'.
$B['text'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 0,
    ]
];

$B['text_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 0,
    ]
];

$B['text128'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 128,
    ]
];

$B['text128_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 128,
    ]
];

$B['text256'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 256,
    ]
];

$B['text256_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 256,
    ]
];

$B['text512'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 512,
    ]
];

$B['text512_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 512,
    ]
];

$B['text1024'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 1024,
    ]
];

$B['text1024_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 1024,
    ]
];

$B['text2048'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 2048,
    ]
];

$B['text2048_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 2048,
    ]
];

$B['text4096'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 4096,
    ]
];

$B['text4096_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 4096,
    ]
];

$B['text8192'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 8192,
    ]
];

$B['text8192_s'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_LF | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_MB,
    [
        'min' => 0, 'max' => 8192,
    ]
];

// SQL Identifiers
// Many chars are allowed, but restrict only alnum.
// SQL standards max is 127 chars, but PostgreSQL max is 63.
$B['sqlident63'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 63,
        'ascii' => '_',
    ]
];

$B['sqlident127'] =  [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 127,
        'ascii' => '_',
    ]
];


// PHP Session ID
// NOTE: PHP session module ignores bad chars w/o errors.
// Following assumes default setting. It allows longer ID also.
$B['sessid'] = [
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
$B['base64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 1,
        'ascii' => '+/=',
    ]
];

$B['base64_32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 32,
        'ascii' => '+/=',
    ]
];

$B['base64_64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 64,
        'ascii' => '+/=',
    ]
];

$B['base64_128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 128,
        'ascii' => '+/=',
    ]
];

$B['base64_256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 256,
        'ascii' => '+/=',
    ]
];


// URL encode
// You should set min/max by yourself to use 'urle'.
$B['urle'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 1,
        'ascii' => '%-_.~+',
    ]
];

$B['urle32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 32,
        'ascii' => '%-_.~+',
    ]
];

$B['urle64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 64,
        'ascii' => '%-_.~+',
    ]
];

$B['urle128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 128,
        'ascii' => '%-_.~+',
    ]
];

$B['urle256'] = [
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
$B['htmle'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 1,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$B['htmle32'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 32,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$B['htmle64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 64,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$B['htmle128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 128,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$B['htmle256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 256,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$B['htmle512'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 512,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];

$B['htmle1024'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE | VALIDATE_STRING_LF | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 1024,
        'ascii' => '[]{}!#$%&()-=^~\\|@`*+:;_/?.,',
    ]
];



// HEX
// You should set min/max by yourself to use 'hex'.
$B['hex'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 0, 'max' => 0,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

$B['hex8'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 8, 'max' => 8,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// MD5 or like
$B['hex16'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 16, 'max' => 16,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// SHA-1
$B['hex24'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 24, 'max' => 24,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// SHA256 / SHA3-256
$B['hex32'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 32, 'max' => 32,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

// SHA512 / SHA3-512
$B['hex64'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 64, 'max' => 64,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];

$B['hex128'] = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    [
        'min' => 128, 'max' => 128,
        'ascii' => 'abcdefABCDEF0123456789',
    ]
];


// UUID
// https://en.wikipedia.org/wiki/Universally_unique_identifier
$B['uuid'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 36, 'max' => 36,
        'filter' => function($input, &$error) {
            if (!is_string($input)) {
                $error = 'UUID Filter error: UUID must be string.';
                return;
            }
            return strtolower($input);
        },
        'ascii' => 'abcdef0123456789-',
        'regexp' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
    ]
];

// IP Address
$B['ipv4'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 7, 'max' => 15,
        'ascii' => '0123456789.',
        'regexp' => '/^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/'
    ]
];

$B['ipv6'] = [
    VALIDATE_REGEXP,
    VALIDATE_FLAG_NONE,
    [
        'min' => 3, 'max' => 45, // Includes IPv4 mapped IPv6. Otherwise, max => 39
        'ascii' => '0123456789abcdefABCDEF:.',
        // https://stackoverflow.com/questions/53497/regular-expression-that-matches-valid-ipv6-addresses
        'regexp' => '/^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$/'
    ]
];

// Domain name
$B['fqdn'] = [
    VALIDATE_CALLBACK,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 3, 'max' => 253,
        'ascii' => '.-_',
        'callback' => function($ctx, &$result, $input) {
            if (!strpos($input, '.') || $input{strlen($input)-1} === '.') {
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
$B['hostname'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 63,
        'ascii' => '-_',
    ]
];

// HTTP header
// https://tools.ietf.org/html/rfc7230#section-3.2
// Trim is required(=spaces are allowed) by RFC
$vtrim = function($ctx, $input, &$error) {
    assert($ctx instanceof Validate);
    if (is_array($input)) {
        $error = 'HTTP Header Filter error: HTTP header must be string.';
        return;
    }
    return trim($input);
};

// SYMBOL is allowed, so risk mitigation is minimum!!
// You should set min/max by yourself to use 'header'.
$B['header'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 0,
        'filter' => $vtrim,
    ]
];

// You should do strict validation to get most benefits from validation.
// e.g. Use $B['uint32'] for Content-Length.
$B['header64'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 64,
        'filter' => $vtrim,
    ]
];

// UTF-8 is allowed by RFC, but it's not used often.
$B['header64u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 64,
        'filter' => $vtrim,
    ]
];

$B['header128'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 128,
        'filter' => $vtrim,
    ]
];

$B['header128u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 128,
        'filter' => $vtrim,
    ]
];

$B['header256'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 256,
        'filter' => $vtrim,
    ]
];

$B['header256u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 256,
        'filter' => $vtrim,
    ]
];

$B['header512'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 512,
        'filter' => $vtrim,
    ]
];

$B['header512u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 512,
        'filter' => $vtrim,
    ]
];

$B['header1024'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 1024,
        'filter' => $vtrim,
    ]
];

$B['header1024u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 1024,
        'filter' => $vtrim,
    ]
];

$B['header2048'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 2048,
        'filter' => $vtrim,
    ]
];

$B['header2048u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 2048,
        'filter' => $vtrim,
    ]
];

$B['header4096'] =
[
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 4096,
        'filter' => $vtrim,
    ]
];

$B['header4096u'] =
[
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 4096,
        'filter' => $vtrim,
    ]
];

$B['header8192'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 8192,
        'filter' => $vtrim,
    ]
];

$B['header8192u'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 8192,
        'filter' => $vtrim,
    ]
];

$B['header32k'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 1, 'max' => 32768,
        'filter' => $vtrim,
    ]
];

$B['header32ku'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB,
    [
        'min' => 1, 'max' => 32768,
        'filter' => $vtrim,
    ]
];

// Some HTTP header aliases
$B['content-length'] = $B['uint32'];
$B['content-type'] = $B['header128'];
$B['user-agent'] = $B['header512'];
$B['cookie'] = $B['header4096'];
