# "Validate" for PHP - An INPUT data validation framework

[![CI](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml/badge.svg?branch=php-5.x)](https://github.com/yohgaki/validate-php-scr/actions/workflows/ci.yml?query=branch%3Aphp-5.x)

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

* PHP 5.3 - 5.6 (this is the `php-5.x` branch; see [Branches](#branches)).
* No extension is required.

### Recommended

* BCMath or GMP module - used for big-integer range checks (VALIDATE_INT_AS_STRING); a pure PHP fallback is used otherwise.
* GMP module - VALIDATE_INT also accepts GMP objects.

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
* `.gitlab-ci.yml` runs the same tests in the official `php` Docker images for the released PHP versions of each branch, except PHP 5.3 / 5.4, whose images current Docker cannot pull (manifest schema 1).

### Differences of `php-5.x` from `master`

* PHP 5.3 syntax: `array()` instead of `[]`, `isset($a) ? $a : $b` instead of `??`, no `(new Validate)->validate()` one-liner (PHP 5.4+). Callbacks are called with `call_user_func()` / `call_user_func_array()`, since PHP 5.3 cannot call `array($class, $method)` as `$f()`.
* The `json` basic type does not call `json_decode()`, which accepts `TRUE`, `+1`, `07`, `[1.]`, lone `\ud800` and control characters in strings on PHP 5. It checks the input with the `json_decode()` rules of PHP 8.6+ implemented in `basic_types.php` (`$jsonCheck`, also used by `master` on PHP < 8.6).
* `VALIDATE_INT` also accepts the GMP resources of PHP 5.3 - 5.5 (GMP objects from PHP 5.6).
* The internal class constants of `Validate` are public (no class constant visibility before PHP 7.1). Do not use them. `preg_last_error_msg()` (PHP 8.0+) is replaced by an internal function returning the same messages.
* `src/run-tests.php` is the one of PHP 5.5.38. It has no `--show-diff` (a failed test leaves `src/tests/*.diff`) and exits with a failure status only when `REPORT_EXIT_STATUS=1` is set.
* Tests differ where PHP 5 prints differently: `assert()` warns instead of throwing `AssertionError`, uncaught exceptions read "Uncaught exception 'X' with message ...", `var_export(1.0)` prints `1` (the float tests use a helper that prints `1.0`).
* Tools: `random_bytes()` (PHP 7.0+) falls back to `mt_rand()` for log file names; `PHP_INT_MIN` is defined when missing.

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
$username = array(
    VALIDATE_STRING,        // "username" is a string.
    VALIDATE_STRING_ALNUM,  // Only alphanumeric characters are allowed.
    array('min'=> 6, 'max'=> 40, // Length must be between 6 and 40 chars.
    'error_message'=>'Username is 6 to 40 chars. Alphanumeric char only.')
);

// "Validate" can be extended with callbacks for rules that don't fit flags.
$email = array(
    VALIDATE_CALLBACK, // Email is complex, so we write a PHP callback.
    VALIDATE_CALLBACK_ALNUM, // Allow alphanumeric characters.
    array('min'=> 6, 'max'=> 256, 'ascii'=>'@._-', // 6 to 256 chars plus the symbols '@._-'.
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
    })
);

$spec = array( // Combine predefined parameter spec into one spec.
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min'=>2, 'max'=>10), // Inputs must have 2 to 10 elements.
    array(
        // Simply reuse predefined spec for parameters.
        "username" => $username,
        "email"    => $email,
        // You can validate $_GET/$_POST/$_COOKIE/$_SERVER/$_FILES at once by nesting.
    )
);

$inputs = array(
    'username' => 'yohgaki',
    'email' => 'yohgaki@ohgaki.net'
);

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
$request_headers_orig = array('a'=>'abc', 'b'=>'456');

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

$spec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_ARRAY_KEY_ALNUM, // Undeclared header names: alnum, '_' and '-' only.
    array('min'=>2, 'max'=>20, 'undeclared'=>$other_spec), // Total request must have 2-20 headers.
    array(
        'Cookie' => $cookie_spec,
        'User-Agent' => $useragent_spec,
    )
);

// $request_headers holds only validated values — no control characters, no
// multibyte characters. validate() removes the values it consumed from
// $request_headers_orig, so it is empty after a successful call.
$request_headers = validate($ctx, $request_headers_orig, $spec);
var_dump($request_headers, $request_headers_orig);
```

[OWASP TOP 10 A10:2017](https://www.owasp.org/index.php/Top_10-2017_A10-Insufficient_Logging%26Monitoring) requires to validate all inputs. Although you are better to do stricter validations against headers, this validation is OWASP TOP 10 A10:2017 compliant HTTP request header validation.

## Example #5: Validate all HTTP inputs ($_GET / $_POST / $_COOKIE / $_FILES) at once

A realistic user-registration form receives data from multiple superglobals: a CSRF token in `$_GET`, the form fields in `$_POST`, a session identifier in `$_COOKIE`, and an avatar upload in `$_FILES`. All of them are untrusted and must be validated. "Validate" can verify the entire request in a single `validate()` call by nesting per-source specs under one `VALIDATE_ARRAY` spec.

```php
<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $basicTypes

// ---- Per-source specs -------------------------------------------------------

// $_GET: only a CSRF token is expected (64-char hex, SHA-256).
$getSpec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 1, 'max' => 1), // exactly 1 element
    array(
        'csrf' => $basicTypes['hex64'],
    )
);

// $_POST: a registration form.
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

// $_COOKIE: session id ('sessid' allows the session.sid_length range 22-256; default 32).
$cookieSpec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 1, 'max' => 4), // session + optional UX cookies
    array(
        'PHPSESSID' => $basicTypes['sessid'],
        'lang'      => array_replace($basicTypes['alpha_dash32'],
                                     array(VALIDATE_FLAGS => VALIDATE_STRING_ALNUM | VALIDATE_FLAG_UNDEFINED)),
        'theme'     => array_replace($basicTypes['alpha_dash32'],
                                     array(VALIDATE_FLAGS => VALIDATE_STRING_ALNUM | VALIDATE_FLAG_UNDEFINED)),
    )
);

// $_FILES: single avatar upload. Each $_FILES[<name>] is itself an array of 5
// keys: name, type, tmp_name, error, size (plus full_path on PHP 8.1+).
// Validate every key.
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
                       array('min' => 0, 'max' => 8)), // UPLOAD_ERR_OK..UPLOAD_ERR_EXTENSION
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

// ---- Top-level spec: validate every HTTP input source at once ---------------

$spec = array(
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    array('min' => 4, 'max' => 4),
    array(
        'get'    => $getSpec,
        'post'   => $postSpec,
        'cookie' => $cookieSpec,
        'files'  => $filesSpec,
    )
);

// Bundle the four superglobals under matching keys.
$inputs = array(
    'get'    => $_GET,
    'post'   => $_POST,
    'cookie' => $_COOKIE,
    'files'  => $_FILES,
);

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION; // interactive form: collect errors
$result = validate($ctx, $inputs, $spec, $func_opts);

if (!validate_get_status($ctx)) {
    // One or more inputs are invalid. Show user-facing errors back to the form.
    $errors = validate_get_user_errors($ctx);
    // $inputs now contains only the values that did NOT validate.
    // Re-render the form with $errors.
    // FAIL FAST for APPLICATION INPUT validation errors that no honest client
    // would ever produce (broken types, oversized strings, etc.).
    var_dump($errors);
    exit;
}

// $result is a fully-typed, fully-validated nested array. Hand it to the
// business-logic layer (model) without further input-shape checks.
var_dump($result);
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

* **Pre Alpha**
* Please test drive and report bugs.

## TODO

* Add Unicode validations. e.g. [RFC 3454](https://www.ietf.org/rfc/rfc3454.txt), [.NET like UnicodeCategory](https://en.wikipedia.org/wiki/Unicode_character_property) (RFC 3454 C check is implemented. Whitelist may be implemented in C module from Unicode standard char definition XML. RFC 3454 C check makes string validation 6 times slower already.)
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
