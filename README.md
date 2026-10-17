# "Validate" for PHP - An INPUT data validation framework

[![CI](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml/badge.svg?branch=php-7.x)](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml?query=branch%3Aphp-7.x)

"Validate" is "input data validation **framework**" that is developed to be useful for "CERT Secure Coding" and "Design by Contract"(DbC). "Validate" is designed to develop [**OWASP TOP 10 A10:2017 vulnerability**](https://www.owasp.org/index.php/Top_10-2017_A10-Insufficient_Logging%26Monitoring) compliant application. "Validate" is planned to be implemented as C module for PHP.

[**CERT Top 10 Secure Coding Practices.**](https://wiki.sei.cmu.edu/confluence/display/seccode/Top+10+Secure+Coding+Practices
)

> **1. Validate input**. Validate input from **all untrusted data sources**. Proper input validation can eliminate the vast majority
> of software vulnerabilities. **Be suspicious of most external data sources**, including command line arguments, network
> interfaces, environmental variables, and user controlled files [Seacord 05].

Validate for PHP is designed to help ["Standard Input Validation"](https://cwe.mitre.org/top25/mitigations.html#Mit-M1).

> Use a **standard input validation** mechanism to validate all input for:
>
> * length
> * type of input
> * syntax
> * missing or extra inputs
> * consistency across related fields
> * business rules

## Basic Design

* **Framework** - "Validate" is a framework, not an out-of-the-box library by itself. Provides easy, yet flexible input data validations.
* **Secure** - No insecure defaults. Everything has to be specified explicitly. i.e. White list.
* **Fast & Simple** - Define data validation rules and use them. No code execution for building validation rules.
* **Easy to use** - Simple PHP array rule specification. Plain PHP code for complex inputs.
* **Native type** - Returns natively typed data by default. Eliminates type conversions and helps faster PHP code execution with type hints.

"Validate" is very flexible and able to perform any data validations, including HTTP header/Query parameter validations with multi stage validations, HTML form validations, JSON data validations, etc. Although "Validate" is designed to validate all inputs at once, you may validate values one by one also.

"Strings" are the most important for input validations and "Validate" is strict for string validations. Many apps miss to validate "char encodings" and "length". "Validate" forces them to be validated always by default. "Validate" does not allow Unicode control characters which cause problems by default.


## Requirements

* PHP 7.0 - 7.4 (this is the `php-7.x` branch; see [Branches](#branches)).
* mbstring module - character encodings are validated with `mb_check_encoding()` (see [Character Encoding Validation](#character-encoding-validation)). `new Validate` (and so `validate()`) throws `RuntimeException` without it.
* `mbstring.func_overload` without the string functions bit (2), which PHP 7.2 deprecated and PHP 8.0 removed. With it, `strlen()` counts chars instead of bytes, so byte lengths and the char checks would be wrong (a multibyte string could end with chars no flag allows); `new Validate` throws `RuntimeException` instead.

### Recommended

* BCMath or GMP module - used for big-integer range checks (VALIDATE_INT_AS_STRING); a pure PHP fallback is used otherwise.
* GMP module - VALIDATE_INT also accepts GMP objects.
* `tzdata-legacy` package on Debian 13 / Ubuntu 24.04 and later, when PHP comes from the distribution or another Debian-based package (e.g. ppa:ondrej/php, which GitHub Actions' setup-php also uses) - needed for `$basicTypes['timezone_all_with_bc']` to accept backward-compatible timezone names such as `US/Eastern` and `Asia/Calcutta`, which browsers still report (`Intl.DateTimeFormat().resolvedOptions().timeZone`). The spec accepts the names PHP's timezone database knows. These PHP packages read the system timezone database, and these OS releases moved the backward-compatible names out of `tzdata` into `tzdata-legacy`; without it, PHP does not know them (`timezone_open('US/Eastern')` fails) and the spec rejects them. Install it on production servers (`apt-get install tzdata-legacy`). PHP with its built-in timezone database (the official `php` Docker images, PHP built from the php.net source) is not affected.

## Branches

Every branch provides the same features with code written for its PHP versions. Pick the branch for your PHP version.

| Branch | PHP | CI (GitHub Actions) |
|---|---|---|
| [`master`](https://github.com/yohgaki/validate-php-scr/tree/master) | 8.0 and up, including versions in development | 8.0 - 8.5, `nightly`, `master` [![CI](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml?query=branch%3Amaster) |
| [`php-8.x`](https://github.com/yohgaki/validate-php-scr/tree/php-8.x) | 8.x | 8.0 - 8.5 [![CI](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml/badge.svg?branch=php-8.x)](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml?query=branch%3Aphp-8.x) |
| [`php-7.x`](https://github.com/yohgaki/validate-php-scr/tree/php-7.x) | 7.0 - 7.4 | 7.0 - 7.4 [![CI](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml/badge.svg?branch=php-7.x)](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml?query=branch%3Aphp-7.x) |
| [`php-5.x`](https://github.com/yohgaki/validate-php-scr/tree/php-5.x) | 5.3 - 5.6 | 5.3 - 5.6 [![CI](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml/badge.svg?branch=php-5.x)](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml?query=branch%3Aphp-5.x) |

* `master` is the development branch. Changes go to `master` first and are then ported to `php-8.x`, `php-7.x` and `php-5.x`, changing only what their PHP versions require (syntax, test runner, CI settings).
* [`dev/basic_types_corpus.php`](dev/basic_types_corpus.php) checks a port: it validates tricky inputs with every `$basicTypes` spec. Run it on each branch and PHP version (`php dev/basic_types_corpus.php src > out.txt`) and compare the output with `master`'s. [`dev/fuzz_inputs.php`](dev/fuzz_inputs.php) does the same with generated inputs for the `json` and `url` specs.
* `nightly` and `master` in the CI column are [setup-php](https://github.com/shivammathur/setup-php) builds of the next PHP release and of php-src master (8.6 and 8.7.0-dev as of October 2026).
* The CI runs the tests with BCMath, GMP and mbstring, and once more without BCMath and GMP on the newest PHP version of the branch (8.5 on `master` / `php-8.x`, 7.4, 5.6), where big integers are compared in pure PHP. A job fails if an extension is missing, or loaded where it should not be.
* `.gitlab-ci.yml` runs the same jobs in the official `php` Docker images for the released PHP versions of each branch, except PHP 5.3 / 5.4, whose images current Docker cannot pull (manifest schema 1).

### Releases

Install with Composer: `composer require yohgaki/validate-php-scr:^1.0`. The same constraint works on every supported PHP version: each branch has its own release tags, and Composer installs the newest release whose `php` requirement your PHP meets.

* Tags are `vMAJOR.MINOR.PATCH`. The hundreds digit of PATCH names the branch - `5xx` `php-5.x` (PHP 5.3 - 5.6), `7xx` `php-7.x` (PHP 7.0 - 7.4), `8xx` `php-8.x` (PHP 8.x) - and the last two digits count its fix releases: `v1.0.800`, `v1.0.801`, ...
* Every branch has the same features in the same MAJOR.MINOR. A fix is released as the next PATCH of the branches it affects, new features as the next MINOR of every branch (`v1.1.500`, `v1.1.700`, `v1.1.800`), incompatible changes as the next MAJOR.
* Moving to another PHP version needs no constraint change: after a move from PHP 7.4 to 8.x, `composer update` replaces e.g. `v1.0.701` with `v1.0.800`.
* `master` is not tagged; require `dev-master` for the development version. `v0.8.0` (2022) predates the branches.

### Differences of `php-7.x` from `master`

* `src/run-tests.php` is the one of PHP 7.4.33. Add `-q` (no questions); `--show-diff` works as on `master`.
* The internal class constants of `Validate` are public (PHP 7.0 has no class constant visibility). Do not use them.
* `preg_last_error_msg()` (PHP 8.0+) is replaced by an internal function returning the same messages.
* `mbstring.func_overload` with its string functions bit (2) makes `new Validate` throw `RuntimeException` (see Requirements); PHP 8.0 removed the setting. An `encoding` name mbstring does not know is detected by mbstring's warning instead of PHP 8's `ValueError`.
* Some tests set INI values or syntax for PHP 7: `assert.exception=1` (the PHP 8 default), `serialize_precision=14` (PHP 7.0 prints 17 digits), `list()` instead of `[...]` in `foreach`.

## Basic Behaviors

* validate() does not allow anything unless explicitly specified. i.e. Strictly white listing.
* validate() converts input values to native types when it is possible. e.g. '123' to int. 'yes'/'no' to bool.
* validate() processes input values and validation specs recursively. i.e. Any input values (scalar/array/object) are accepted.
* validate() checks validation spec format by default (spec errors throw ValidateInvalidSpecException). It remembers specs that passed, so a spec validated repeatedly in a process is not checked again after its second use. i.e. Disable spec format check for production.
* validate() rejects input keys that a VALIDATE_ARRAY spec does not declare ("Unvalidated value remains."), unless the spec's "undeclared" option gives a spec for them (e.g. a catch-all for unknown HTTP headers). Pass VALIDATE_OPT_UNVALIDATED for multi-stage validation.
* validate() stores validation statuses to context. e.g. $ctx in examples below. validate_get_status() reports the most recent validate() call.
* validate() throws ValidateInvalidValueException by default. Both exception classes extend InvalidArgumentException.

Tip:

* For application business logic data validations, disable exception and set 'error_message' option.
* Define your own 'filter' for normalization.
* Define your own 'key_callback' for custom array key validations.
* Use VALIDATE_CALLBACK and 'callback' for complex validations.

## Character Encoding Validation

Every string that VALIDATE_STRING, VALIDATE_REGEXP or VALIDATE_CALLBACK accepts has its character encoding validated:

* Without the `_MB` flag, only ASCII chars allowed by the flags and the 'ascii' option pass. Any byte 0x80 - 0xFF fails.
* With the `_MB` flag, `mb_check_encoding()` validates the string in the 'encoding' option: any character encoding name mbstring knows, e.g. `UTF-8` (default), `SJIS`, `Shift_JIS`, `CP932`, `EUC-JP`, `ISO-2022-JP`, `UTF-16`, `EUC-KR`, `BIG5`, `ISO-8859-1`. Then the string is converted to UTF-8 and every char is checked: ASCII chars by the flags and 'ascii', other chars against RFC 3454 table C (rejected unless `_RFC3454_C`) and the 'unicode' option. This works for encodings whose multibyte chars contain ASCII bytes (SJIS, BIG5) or that are not ASCII-compatible (ISO-2022-JP, UTF-16).
* An unknown 'encoding', and mbstring's names that are not character encodings (`BASE64`, `Quoted-Printable`, `HTML-ENTITIES`, `UUENCODE`, `7bit`, `8bit`, `pass`, ...), are spec errors. With the spec check disabled they fail validation of `_MB` strings (without `_MB` the 'encoding' option is not used).
* Only `_BINARY` (it accepts any bytes) and an exact match of the 'values' option (the spec author's whitelist) skip the check.
* The validated value is the input as received (after the spec's 'filter', if any), not the converted text.

Transfer-encoded strings are decoded before the check. The 'transfer_encoding' option checks the encoded form strictly, decodes it, and validates the decoded text as above ('encoding', flags, RFC 3454, 'unicode'). 'min' / 'max' apply to the input as received, VALIDATE_REGEXP matches 'regexp' against it, VALIDATE_CALLBACK passes it to the callback, and the validated value is the input as received.

| 'transfer_encoding' | Encoded form |
|---|---|
| `quoted-printable` | RFC 2045 6.7: lines of at most 76 chars separated by CRLF; printable ASCII, SPACE and TAB, but no SPACE / TAB at the end of a line; `=` only in `=XX` (uppercase hex digits) or as a soft line break at the end of a line other than the last. A hard line break is CRLF in the decoded text. |
| `percent` | The output of `urlencode()` / `rawurlencode()`: RFC 3986 unreserved chars, `%XX` and `+` (a space). |
| `base64` / `base64url` | RFC 4648 4 / 5. `=` padding is optional but must complete the last group; the unused bits of the last char must be zero. |
| `html` | The output of `htmlspecialchars($s, ENT_QUOTES)`: no raw `<` `>` `"` `'`; `&` only in `&amp;` `&lt;` `&gt;` `&quot;` `&apos;` or a numeric reference to U+0001 - U+10FFFF other than a surrogate. References are decoded after the conversion to UTF-8. |

mbstring of each PHP version decides what is valid in each encoding. UTF-8 gives the same results on every supported PHP version: [`dev/mb_check.php`](dev/mb_check.php) compares `mb_check_encoding()` with the RFC 3629 grammar on 300,000 byte strings of 1 - 6 bytes, and they agreed on every PHP version from 5.4 to 8.7.0-dev; on PHP 5.3, mbstring also accepted 41 of them, all UTF-8 forms of values beyond U+10FFFF, which "Validate" itself rejects. Other encodings can differ between PHP versions. Measured with the same script on PHP 5.3 - 8.7.0-dev (October 2026):

* `Windows-1252` bytes with no assigned char: 0x81, 0x8D, 0x8F and 0x90 are invalid on PHP 8.0 and older, 0x9D on PHP 7.0 and older; on later versions they are valid C1 controls, which "Validate" then rejects unless `_RFC3454_C` is set.
* `UTF-16` with a byte order mark (`FE FF 30 42`) is invalid on PHP 8.0 and older, and valid on PHP 8.1 and later.
* `GB18030` is unknown to mbstring of PHP 5.3 (a spec error there).

Input validation is not output sanitization. "Validate" rejects input that is not valid; it never modifies input to make it valid (only a 'filter' the spec author sets normalizes input before validation), and it never uses an output function (escaping or sanitizing, e.g. `htmlspecialchars()`) to decide whether input is valid. Escape or sanitize data where it is output, for that output. Input validation does not replace output sanitization, and output sanitization does not replace input validation.

## Example #1: Single value validation with exception

src/examples/91-example.php

```php
<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines the $basicTypes array.

// Validate a DNS-resolvable domain name.
$domain = 'es-i.jp';
$domain = validate($ctx, $domain, $basicTypes['fqdn']);
// Validate a record ID as a 32-bit unsigned integer.
$id = '1234';
$id = validate($ctx, $id, $basicTypes['uint32']);
// Both calls returned the validated, natively-typed values.
var_dump($domain, $id);
```

Validation error results in Exception. Application input data validation errors should be handled by exception without user interaction.

NOTE: Valid inputs are any inputs that the application is supposed to handle. i.e. **Input mistakes are valid inputs for an application.**

## Example #2: Single value validation without exception

src/examples/92-example.php

```php
<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines the $basicTypes array.

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION;
// validate_get_status() reports the most recent validate() call only, so
// check it after each call (or validate everything with one VALIDATE_ARRAY spec).
$ok = true;
// Validate a domain name without throwing on failure.
$domain = 'es-i.jp';
$domain = validate($ctx, $domain, $basicTypes['fqdn'], $func_opts);
$ok = $ok && validate_get_status($ctx);
// Validate a record ID as a 32-bit unsigned integer.
$id = '1234';
$id = validate($ctx, $id, $basicTypes['uint32'], $func_opts);
$ok = $ok && validate_get_status($ctx);

if (!$ok) {
    // At least one validation failed — re-render the form, etc.
}
// User-facing errors collected for any 'error_message' options or validate_error() calls.
$errors = validate_get_user_errors($ctx);

// Inspect the validated values and any error messages.
var_dump($domain, $id, $errors);
```

Validation errors are stored in $ctx (they accumulate across calls on the same context, while validate_get_status() reflects the most recent call). Application business logic data validation errors should be handled without error/exception for interactive error handling.

## Example #3: Multiple value validations at once.

src/examples/93-example.php

```php
<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines the $basicTypes array.

// In practice, define all input specifications in a central repository.
// If your web app does not have strict client-side validation, you will need
// BOTH an INPUT validation spec AND a BUSINESS LOGIC (form) validation spec.

// Username spec — relies on the client form to do basic length/character checks.
$username = [
    VALIDATE_STRING,        // "username" is a string.
    VALIDATE_STRING_ALNUM,  // Only alphanumeric characters are allowed.
    ['min'=> 6, 'max'=> 40, // Length must be between 6 and 40 chars.
    'error_message'=>'Username is 6 to 40 chars. Alphanumeric char only.']
];

// "Validate" can be extended with callbacks for rules that don't fit flags.
$email = [
    VALIDATE_CALLBACK, // Email is complex, so we write a PHP callback.
    VALIDATE_CALLBACK_ALNUM, // Allow alphanumeric characters.
    ['min'=> 6, 'max'=> 256, 'ascii'=>'@._-', // 6 to 256 chars plus the symbols '@._-'.
    'error_message'=>'Please enter a valid email address. We only accept addresses with a DNS MX record.',
    'callback'=> function($ctx, &$result, $input) {     // Custom rule in plain PHP.
        $parts = explode('@', $input);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {         // Character set, min, and max are already validated.
            $err =  "Exactly one '@' is required."; // This could be an i18n function for multilingual sites.
            validate_error($ctx, $err);
            return false;
        }
        if (!getmxrr($parts[1], $mx)) {
            $err = "Sorry, we only allow hosts with an MX record.";
            validate_error($ctx, $err);
            return false;
        }
        return true;
    }]
];

$spec = [ // Combine predefined parameter spec into one spec.
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min'=>2, 'max'=>10], // Inputs must have 2 to 10 elements.
    [
        // Simply reuse predefined spec for parameters.
        "username" => $username,
        "email"    => $email,
        // You can validate $_GET/$_POST/$_COOKIE/$_SERVER/$_FILES at once by nesting.
    ]
];

$inputs = [
    'username' => 'yohgaki',
    'email' => 'yohgaki@ohgaki.net'
];

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION; // Disable exception, to check errors, etc.
$results = validate($ctx, $inputs, $spec, $func_opts); // Now, let's validate and done.

// Inspect results.
var_dump(validate_get_status($ctx));        // $results is null on failure; validate_get_status() is the reliable check.
var_dump($results, $inputs);                // $inputs holds only the values that were NOT validated.
var_dump(validate_get_user_errors($ctx));   // User-facing errors (from 'error_message' / validate_error()).
var_dump(validate_get_system_errors($ctx)); // System-level errors (bad types, oversized strings, etc.).
```

"Validate" removes validated elements from $inputs. You can validate remaining elements
by next validation: pass VALIDATE_OPT_UNVALIDATED to every stage except the last, because
input keys a spec does not declare are errors by default. To validate keys you cannot
list in advance (e.g. HTTP headers) in the same call, use the "undeclared" option
instead (Example #4).

A little more realistic working example is here:
 * https://sample.ohgaki.net/validate-php/validate-php-scr/src/examples/99-web.php


## Example #4: Validation that validates all HTTP headers

src/examples/94-example.php

Known headers get explicit specs; the "undeclared" option validates every other header
with a catch-all spec, and VALIDATE_FLAG_ARRAY_KEY_ALNUM restricts their names.

```php
<?php
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
```

[OWASP TOP 10 A10:2017](https://www.owasp.org/index.php/Top_10-2017_A10-Insufficient_Logging%26Monitoring) requires to validate all inputs. Although you are better to do stricter validations against headers, this validation is OWASP TOP 10 A10:2017 compliant HTTP request header validation.

## Example #5: Validate all HTTP inputs ($_GET / $_POST / $_COOKIE / $_FILES / headers) at once

src/examples/95-example.php

A realistic user-registration form receives data from multiple sources: a CSRF token in `$_GET`, the form fields in `$_POST`, a session identifier in `$_COOKIE`, an avatar upload in `$_FILES`, and the HTTP request headers. All of them are untrusted and must be validated. "Validate" can verify the entire request in a single `validate()` call by nesting per-source specs under one `VALIDATE_ARRAY` spec.

```php
<?php
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
        'csrf' => $basicTypes['hex64'], // 64-char hex (SHA-256)
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

// ---- $_FILES: single avatar upload (5-6 keys per upload) --------------------
// Each $_FILES[<field>] is an array: name, type, tmp_name, error, size, and
// full_path on PHP 8.1+ (the browser-supplied relative path; optional here).
// Whitelist MIME type by regex; bound size explicitly.
// NOTE: 'type' is browser-supplied and not trustworthy. Re-check the uploaded
// file with finfo_file() before storing or serving it.
$uploadSpec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 5, 'max' => 6],
    [
        'name'     => [VALIDATE_STRING, VALIDATE_STRING_ALNUM,
                       ['min' => 1, 'max' => 255, 'ascii' => '._-']],
        'full_path' => [VALIDATE_STRING, VALIDATE_STRING_ALNUM | VALIDATE_FLAG_UNDEFINED,
                        ['min' => 1, 'max' => 255, 'ascii' => '._-/']],
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

$headerSpec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_ARRAY_KEY_ALNUM,
    ['min' => 1, 'max' => 100, 'undeclared' => $headerCatchSpec], // Host required + up to 99 others
    [
        'Host'       => $basicTypes['http_host'], // name or IP, optional ":port"
        'Cookie'     => $cookieHdr,
        'User-Agent' => $uaHdr,
    ]
];

// =============================================================================
// 2. Top-level spec: validate every superglobal + all headers in one call
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
        'header' => $headerSpec,
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

// Request headers from $_SERVER: HTTP_* plus CONTENT_TYPE / CONTENT_LENGTH,
// which PHP stores without the prefix. Names become "Accept-Language" style
// on every SAPI. (apache_request_headers() keeps the client's spelling —
// all lower case over HTTP/2 — so 'Host' would not match "host".) The
// other $_SERVER entries are server/environment variables, not headers.
// CLI has no request headers (its $_SERVER is the environment): use samples.
$headers = [];
if (PHP_SAPI !== 'cli') {
    foreach ($_SERVER as $k => $v) {
        if (strncmp($k, 'HTTP_', 5) === 0 || $k === 'CONTENT_TYPE' || $k === 'CONTENT_LENGTH') {
            $name = (strncmp($k, 'HTTP_', 5) === 0) ? substr($k, 5) : $k;
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))))] = $v;
        }
    }
} else {
    $headers = [
        'Host'            => 'example.com:8080',
        'User-Agent'      => 'Mozilla/5.0 (compatible; validate-php-scr/95)',
        'Cookie'          => 'PHPSESSID='.str_repeat('a', 32),
        'Accept'          => 'text/html',
        'Accept-Language' => 'ja, en;q=0.8',
    ];
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
```

Key points:

* **One call per request.** Nesting `VALIDATE_ARRAY` specs lets you validate every HTTP source in a single `validate()` call, so no input bypasses the trust boundary.
* **Undeclared keys are errors.** Unknown `$_POST` fields or extra cookies are reported as "Unvalidated value remains." instead of being silently passed through; missing required keys are errors too. `min`/`max` additionally bound the element count. For inputs whose keys you cannot list (HTTP headers), give the `VALIDATE_ARRAY` an `undeclared` spec as in Example #4.
* **`$_FILES` is just a nested array.** Each upload's keys (`name`, `type`, `tmp_name`, `error`, `size`, and `full_path` on PHP 8.1+) are validated like any other parameter; whitelist `type` against allowed MIME types and bound `size` explicitly. **Do not trust `type` for security decisions** — always re-check the uploaded file's content with `finfo_file()` before storing or serving it.
* **Use `validate_get_user_errors($ctx)` for the form**, and let the application reject "impossible" inputs (wrong types, oversized strings) fast with no user-facing message, per the "FAIL FAST" principle.

## "APPLICATION INPUT" and "BUSINESS LOGIC" Data Validation Basics

APPLICATION INPUT data validation and BUSINESS LOGIC data validation are **2 different validations**.


### APPLICATION INPUT Data Validation

IMPORTANT: User mistakes are valid inputs.

**APPLICATION INPUT data should be validated as fast as it can to minimize misbehavior/vulnerability.** Application INPUT data validation must validate all inputs are valid for the application. i.e. If ID is number between 1000 and INT_MAX, then you must only accepts number 1000 from INT_MAX. If a string is for names, then the string should never exceed 512 bytes unless attacker is tampering your application. If your application restricts name length to 100 by client side JavaScript, then you should never accept strings longer than 100. Do not forget to check too few/many parameters as this is one of a "standard input validation" requirement.

All of application input data must be validated and validations should be done at **Application Trust Boundary**. Application INPUT data validation assures "Values have correct **forms**".  e.g. length, range, encoding, used chars, specific formats such as date, phone, zip. Both **Correct and "Input mistake" values** are valid input data.

Application INPUT data validation failure MUST NOT require user interactions. INPUT data validation failure means "A user sent **Invalid values**.(= values clients cannot/should not send. unacceptable values. e.g. Too large, broken char encoding, malformed format/char, etc). These invalid inputs MUST simply be rejected and handled according to [**"FAIL FAST"**](https://en.wikipedia.org/wiki/Fail-fast) principle. i.e. Reject invalid inputs like WAF(Web Application Firewall) does. All inputs, including HTTP headers/query parameters, must be validated always.

Input data format correctness must be validated by server always AND the validation must be done as fast as it can. Input data validation should not require user interaction, should not provide meaningful error message (to attackers).

### BUSINESS LOGIC Data Validation

IMPORTANT: Detecting broken/totally wrong/invalid inputs at business logic code is too late. It is good for "fail safe", but "fail safe" is not an ideal security measure.

**BUSINESS LOGIC data validation is for input mistake detection that users allowed to, not for totally broken inputs sent from crackers.** Business logic (e.g. Model in MVC) should not supposed to handle totally wrong input value (e.g. number is expected, but string is supplied) because totally wrong values should be handled by APPLICATION INPUT validation already.

Application BUSINESS LOGIC data validation validates values against business logic. e.g. Reservation date is future date, min value is less than max value, has valid CSRF token, etc. BUSINESS LOGIC data validations are responsible for logical correctness mainly.

Unlike Application INPUT data validations, many BUSINESS LOGIC data validations require user interactions to correct input mistakes.

Logical data correctness must be validated by server always. Business logic validation usually requires user interaction and/or should provide meaningful error message what went wrong.


### References

 * Input and business logic validations are 2 different validations. [OWASP Code Review Guide](https://www.owasp.org/index.php/File:OWASP_Code_Review_Guide_v2.pdf) - Section 7.6 Input Validation, for details.
 * INPUT validation failure must not simply ignored. [2017 OWASP TOP 10 - "A10 Insufficient Logging & Monitoring"](https://www.owasp.org/index.php/Top_10-2017_A10-Insufficient_Logging%26Monitoring).
 * Input data validation is the most powerful security measure. [CWE/SANS Top 25 - Monster mitigations](http://cwe.mitre.org/top25/mitigations.html).
 * Input data validation is the first Secure Coding principle. [CERT TOP 10 Secure Coding Practices](https://wiki.sei.cmu.edu/confluence/display/seccode/Top+10+Secure+Coding+Practices)


## Documents

Reference.

 * [REFERENCE.md](REFERENCE.md)

Examples.

 * [Examples](src/examples) and [Tests](src/tests)

Codes.

 * validate() and other function definitions are in [validate_func.php](src/validate_func.php)
 * Validator behavior is defined in [Validate.php](src/Validate.php)
 * Validator flags is defined in [validate_defs.php](src/validate_defs.php)


## Status

* 1.0 (2026-10-17); see [Releases](#releases) and [CHANGELOG.md](CHANGELOG.md).
* Please test drive and report bugs.

## TODO

* Add Unicode validations. e.g. [RFC 3454](https://www.ietf.org/rfc/rfc3454.txt), [.NET like UnicodeCategory](https://en.wikipedia.org/wiki/Unicode_character_property) (RFC 3454 C check is implemented. Whitelist may be implemented in C module from Unicode standard char definition XML. The RFC 3454 C check already makes string validation slower.)
* More tests. (Most features are tested.)
* Back port this to C module - When API is fixed. (TODO: Cleanup, Optimize, Reorganize PHP code for C implementation.)
* Spec builder app(?).
* Learning and automatic spec builder tool(?).


## Input Data Security Tip

Strings are the most risky inputs and web apps are made by string inputs. i.e. Almost all inputs to web apps are strings. Invalid strings must not be processed by web apps' code at all.

While most web apps do not validate input string character encoding, web developers must validate character encodings. If you don't validate character encoding, your application became vulnerable to DoS easily. i.e. htmlspecialchars() return empty string, modern browsers refuse to render badly broken encoding, system has both binary safe and encoding aware APIs/storages. These facts create very hard to find DoS vulnerabilities.

In order to programs to work correctly, valid data is absolute mandatory requirement. Invalid data for program results in invalid state always.

Some apps/libraries sanitize input data and make "invalid data" into "valid data" because "valid data" is mandatory. However sanitization ignores and hides attacks from cyber criminals. Ignoring and hiding attacks is insecure practice should be avoided. Developers must not rely on sanitizer in general.


## Extending

Since "Validate" is designed as framework, it is easy to extend. It can work with other validators such as Respect.

"[src/tools](src/tools)" directory contains
tools that request logging, creating validation spec rules from log and validation script.


## "Validate" C extension module

This PHP script is based on validate C module for PHP 7. Features are planned to be ported to C module which can perform validations a lot faster.

https://github.com/yohgaki/validate-php (Do not use this. Use PHP script version for now.)


## Comments & Issues

Comments, Bug reports and PRs are welcomed! Please remember "Validate" is not optimized for OO nor PHP scripts, but C module. This script is planned to be implemented as a C module in the future.
