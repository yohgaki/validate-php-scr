# "Validate" for PHP - An INPUT data validation framework

[![Build Status](https://travis-ci.org/yohgaki/validate-php-scr.svg?branch=master)](https://travis-ci.org/yohgaki/validate-php-scr)

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

* **Framework** - "Validate" is framework, not an out of box library by itself. Provides easy, yet flexible input data validations.
* **Secure** - No insecure defaults. Everything has to be specified explicitly. i.e. White list.
* **Fast & Simple** - Define data validation rules and use them. No code execution for building validation rules.
* **Easy to use** - Simple PHP array rule specification. Plain PHP code for complex inputs.
* **Native type** - Returns natively typed data by default. Eliminates type conversions and helps faster PHP code execution with type hints.

"Validate" is very flexible and able to perform any data validations, including HTTP header/Query parameter validations with multi stage validations, HTML form validations, JSON data validations, etc. Although "Validate" is designed to validate all inputs at once, you may validate values one by one also.

"Strings" are the most important for input validations and "Validate" is strict for string validations. Many apps miss to validate "char encodings" and "length". "Validate" forces them to be validated always by default. "Validate" does not allow Unicode control characters which cause problems by default.


## Requirements

* PHP 8.0 and up.
* PHP 7.0 and up.
* BCMath module.

### Recommended

* PHP 7.2 and up - Newer mbstring provides better performance.
* mbstring module - mb_ord() improves performance, more supported encodings and better encoding checks.
* GMP module - "Validate" supports GMP integer also.

## Basic Behaviors

* validate() does now allow anything unless explicitly specified. i.e. Strictly white listing.
* validate() converts input values to native types when it is possible. e.g. '123' to int. 'yes'/'no' to bool.
* validate() processes input values and and validation specs recursively. i.e. Any input values (scalar/array/object) are accepted.
* validate() checks validation spec format by default. i.e. Disable spec format check for production.
* validate() stores validation statues to context. e.g. $ctx in examples below.
* validate() throws InvalidArgumentException by default.

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
// Define basic type specifications array $B. You can define any validation rule as you like.
require_once __DIR__.'/../lib/basic_types.php';

// Validate domain name
$domain = 'es-i.jp';
$domain = validate($ctx, $domain, $B['fqdn']);
// Validate record ID
$id = '1234';
$id = validate($ctx, $id, $B['uint32']);
// Check results
var_dump($domain, $id);
```

Validation error results in Exception. Application input data validation errors should be handled by exception without user interaction.

NOTE: Valid inputs are any inputs that the application is supposed to handle. i.e. **Input mistakes are valid inputs for an application.**

## Example #2: Single value validation without exception

src/examples/92-example.php

```php
<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines basic type array $B

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION;
// Validate domain name w/o exception
$domain = 'es-i.jp';
$domain = validate($ctx, $domain, $B['fqdn'], $func_opts);
// Validate record ID
$id = '1234';
$id = validate($ctx, $id, $B['uint32'], $func_opts);

if (validate_get_status($ctx) == false) {
    // Check last validation error
}
// Get all user error
$errors = validate_get_user_errors($ctx);

//Check results
var_dump($domain, $id, $errors);
```

Validation errors are stored in $ctx. Application business logic data validation errors should be handled without error/exception for interactive error handling.

## Example #3: Multiple value validations at once.

src/examples/93-example.php

```php
<?php
// Simple "username" and "email" form validation example.
// "Validate" is suitable for "From Validations" also.
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines basic type array $B

// In practice, you would define all inputs specifications at central repository.
// If your web app does not have strict client side validations, you will need
// "Input validation spec" AND "Business logic(Form) validation spec".

// If client JavaScript has validation
$username = [
    VALIDATE_STRING,        // "username" is string
    VALIDATE_STRING_ALNUM,  // "username" has only alphanumeric chars.
    ['min'=> 6, 'max'=> 40, // "username" can be 6 to 40 chars.
    'error_message'=>'Username is 6 to 40 chars. Alphanumeric char only.']
];

// "Validate" can be extend by callbacks.
$email = [
    VALIDATE_CALLBACK, // "email" is complex, so write PHP script for it.
    VALIDATE_CALLBACK_ALNUM, // Allow alpha numeric chars.
    ['min'=> 6, 'max'=> 256, 'ascii'=>'@._-', // Allow 6 to 256 chars and additional '@._-'
    'error_message'=>'Please enter valid email address. We only accepts address with DNS MX record.',
    'callback'=> function($ctx, &$result, $input) {     // Let's define rules by PHP function.
        $parts = explode('@', $input);
        if (count($parts) > 2) {         // Chars/min/max is already validated.
            $err =  "Only one '@' is allowed."; // This could be i18n function for multilingual sites.
            validate_error($ctx, $err);
            return false;
        }
        if (!dns_get_mx($parts[1], $mx)) {
            $err = "Sorry, we only allow hosts with MX record.";
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

// Check results
var_dump(validate_get_status($ctx));        // $results is NULL when error. validate_get_status() can be used also.
var_dump($results, $inputs);                // $inputs contains unvalidated values.
var_dump(validate_get_user_errors($ctx));   // Get user errors.
var_dump(validate_get_system_errors($ctx)); // Get system errors.
```

"Validate" removes validated elements from $inputs. You can validate remaining elements
by next validation.

A little more realistic working example is here:
 * https://sample.ohgaki.net/validate-php/validate-php-scr/src/examples/99-web.php


## Example #4: Validation that validates all HTTP headers

src/examples/94-example.php

```php
<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $B (basic type) array

$request_headers_orig = ['a'=>'abc', 'b'=>'456']; //apache_request_headers(); // Get request headers

// Check cookie and user agent. Allow undefined and extra headers.
$B['cookie'][VALIDATE_FLAGS]                 |= VALIDATE_FLAG_UNDEFINED; // Allow undefined(optional)
$B['user-agent'][VALIDATE_FLAGS]             |= VALIDATE_FLAG_UNDEFINED_TO_DEFAULT; // Allow undefined and set default
$B['user-agent'][VALIDATE_OPTIONS]['default'] = '';
$B['user-agent'][VALIDATE_OPTIONS]['min']     = 0; // Allow 0 length(empty)
$spec1 = [ // Explicit validations
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min'=>2, 'max'=>20], // Inputs must have 2 to 20 elements.
    [
        'Cookie' => $B['cookie'],
        'User-Agent' => $B['user-agent'],
    ]
];

// validate() removes validated values from $request_headers_orig
$request_headers = validate($ctx, $request_headers_orig, $spec1);

// Check the rest of headers.
// Allow array 'header512' strings and ALNUM + '_' + '-' keys
$B['header512'][VALIDATE_FLAGS]   |= VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$B['header512'][VALIDATE_OPTIONS]['min'] = 0; // Allow 0 length(empty) headers
$B['header512'][VALIDATE_OPTIONS]['amin'] = 0; // Allow 0 extra headers
$B['header512'][VALIDATE_OPTIONS]['amax'] = 20; // Allow 20 extra headers
$spec2 = $B['header512'];

// $request_headers has only validated values. No control chars nor multibyte chars.
$request_headers += validate($ctx, $request_headers_orig, $spec2);
// Check results
var_dump($request_headers, $request_headers_orig);
```

[OWASP TOP 10 A10:2017](https://www.owasp.org/index.php/Top_10-2017_A10-Insufficient_Logging%26Monitoring) requires to validate all inputs. Although you are better to do stricter validations against headers, this validation is OWASP TOP 10 A10:2017 compliant HTTP request header validation.

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

 * [Examples](https://github.com/yohgaki/validate-php-scr/tree/master/src/examples) and [Tests](https://github.com/yohgaki/validate-php-scr/tree/master/src/tests)

Codes.

 * validate() and other function definitions are in [validate_func.php](https://github.com/yohgaki/validate-php-scr/blob/master/src/validate_func.php)
 * Validator behavior is defined in [validate.php](https://github.com/yohgaki/validate-php-scr/blob/master/src/validate.php)
 * Validator flags is defined in [validate_defs.php](https://github.com/yohgaki/validate-php-scr/blob/master/src/validate_defs.php)


## Status

* **Pre Alpha**
* Please test drive and report bugs.

## TODO

* Add Unicode validations. e.g. [RFC 3454](https://www.ietf.org/rfc/rfc3454.txt), [.NET like UnicodeCategory](https://en.wikipedia.org/wiki/Unicode_character_property) (RFC 3454 C check is implemented. Whitelist may be implemented in C module from Unicode standard char definition XML. RFC 3454 C check makes string validation 6 times slower already.)
* More tests. (Most features are tested.)
* Some minor features - Float is not validated like C module, etc.
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

"[src/tools](https://github.com/yohgaki/validate-php-scr/tree/master/src/tools)" directory contains
tools that request logging, creating validation spec rules from log and validation script.


## "Validate" C extension module

This PHP script is based on validate C module for PHP 7. Features are planned to be ported to C module which can perform validations a lot faster.

https://github.com/yohgaki/validate-php (Do not use this. Use PHP script version for now.)


## Comments & Issues

Comments, Bug reports and PRs are welcomed! Please remember "Validate" is not optimized for OO nor PHP scripts, but C module. This script is planed to be implemented as C module in the future.
