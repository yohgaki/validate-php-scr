# "Validate" for PHP - An INPUT data validation framework

[![Build Status](https://travis-ci.org/yohgaki/validate-php-scr.svg?branch=master)](https://travis-ci.org/yohgaki/validate-php-scr)

"Validate" is "input data validation **framework**" that is developed to be useful for "CERT Secure Coding" and "Design by Contract"(DbC). "Validate" is planned to be implemented as C module for PHP.

[**CERT Top 10 Secure Coding Practices.**](https://wiki.sei.cmu.edu/confluence/display/seccode/Top+10+Secure+Coding+Practices
)

> **1. Validate input**. Validate input from **all untrusted data sources**. Proper input validation can eliminate the vast majority
> of software vulnerabilities. **Be suspicious of most external data sources**, including command line arguments, network
> interfaces, environmental variables, and user controlled files [Seacord 05].

## Basic Design

* **Framework** - "Validate" is framework, not an out of box library by itself. Provides easy, yet flexible input data validations.
* **Secure** - No insecure defaults. Everything has to be specified explicitly. i.e. White list.
* **Fast & Simple** - Define data validation rules and use them. No code execution for building validation rules.
* **Easy to use** - Simple PHP array rule specification. Plain PHP code for complex inputs.
* **Native type** - Returns natively typed data by default. Eliminates type conversions and helps faster PHP code execution with type hints.

"Validate" is very flexible and able to perform any data validations, including HTTP header/Query parameter validations with multi stage validations, HTML form validations, JSON data validations, etc. Although "Validate" is designed to validate all inputs at once, you may validate values one by one also.

"Strings" are the most important for input validations and "Validate" is strict for string validations. Many apps miss to validate "char encodings" and "length". "Validate" forces them to be validated always by default. "Validate" does not allow Unicode control characters which cause problems by default.


## Requirements

* PHP 7.0 and up.
* bcmath module.

### Recommended

* PHP 7.2 and up - Newer mbstring provides better performance.
* mbstring module - mb_ord() improves performance, more supported encodings and better encoding checks.
* gmp module - "Validate" supports GMP integer also.

## Example #1

Single value validation.

```php
<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $B (basic type) array

// Validate domain name
validate($ctx, $domain, $B['fqdn']);
// Validte record ID
validate($ctx, $id, $B['uint32']);
```

Validation error results in Exception.

## Example #2

Single value validation without exception.

```php
<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $B (basic type) array

// Validate domain name w/o exception
validate($ctx, $value, $B['fqdn'], VALIDATE_OPT_DISABLE_EXCEPTION);
// Validte record ID
validate($ctx, $id, $B['uint32'], VALIDATE_OPT_DISABLE_EXCEPTION);

// Check $errors for interactive responses
$errors = validate_get_user_errors($ctx);
```


## Example #3

Multiple value validations at once.

```php
<?php
// Simple "username" and "email" form validation example.
// "Validate" is suitable for "From Validations" also.
require_once __DIR__.'/../validate_func.php';
// In practice, you would define all inputs specifications at central repository.
// If your web app does not have strict client side validations, you will need
// "Input validation spec" AND "Logic(Form) validation spec".
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

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION; // Disable exception, to check errors, etc.
$results = validate($ctx, $inputs, $spec, $func_opts); // Now, let's validate and done.
var_dump(validate_get_status($ctx));             // $results is NULL when error. validate_get_status() can be used also.
var_dump(validate_get_user_errors($ctx));   // Get user errors.
var_dump(validate_get_system_errors($ctx)); // Get system errors.
?>
```

"Validate" removes validated elements from $inputs. You can validate remaining elements
by next validation.

A little more realistic working example is here:
 * https://sample.ohgaki.net/validate-php/validate-php-scr/src/examples/99-web.php


## Application "INPUT" and "BUSINESS LOGIC" data validation basics

Application INPUT data validation and BUSINESS LOGIC data validation are **2 different validations**.


### Application INPUT Data Validation

All of application INPUT data must be validated and validations should be done at **Application Trust Boundary**. Application INPUT data validation assures "Values have correct **forms**".  e.g. length, range, encoding, used chars, specific formats such as date, phone, zip. Both **Correct and "Input mistake" values** are valid input data.

Application INPUT data validation failure MUST NOT require user interactions. INPUT data validation failure means "A user sent **Invalid values**.(= values clients cannot/should not send, unacceptable values. e.g. Too large, broken char encoding, malformed format/char, etc). These invalid inputs MUST simply be rejected and handled according to [**"FAIL FAST"**](https://en.wikipedia.org/wiki/Fail-fast) principle. i.e. Reject invalid inputs like WAF(Web Application Firewall) does. All inputs, including HTTP headers/query parameters, must be validated always.

Input data format correctness must be validated by server always.


### Application BUSINESS LOGIC Data Validation

Application BUSINESS LOGIC data validation validates values against business logic. e.g. Reservation date is future date, min value is less than max value, has valid CSRF token, etc. BUSINESS LOGIC data validations are responsible for logical correctness mainly.

Unlike Application INPUT data validations, many BUSINESS LOGIC data validations require user interactions to correct input mistakes.

Logical data correctness must be validated by server always.


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

* **Not suitable for production use yet.**
* Please test drive and report bugs.

## TODO

* Add Unicode validations. e.g. [RFC 3454](https://www.ietf.org/rfc/rfc3454.txt), [.NET like UnicodeCategory](https://en.wikipedia.org/wiki/Unicode_character_property) (RFC 3454 C check is implemented. Whitelist may be implemented in C module from Unicode standard char definition XML. RFC 3454 C check makes string validation 6 times slower already.)
* More tests. (Most features are tested.)
* Some minor features - Float is not validated like C module, etc.
* Back port this to C module - When API is fixed. (TODO: Cleanup, Optimize, Reorganize PHP code for C implementation.)
* Spec builder app(?).
* Learning and automatic spec builder tool(?).


## String Security Tip and Note

While most web apps does not validate input string character encoding, web developers must validate character encodings. If you don't validate character encoding, your application became vulnerable to DoS easily. i.e. htmlspecialchars() return empty, today's browsers refuse to render badly broken encoding, system has binary safe and encoding aware APIs/storages which cause DoS.

"Validate" validates character encoding by default. Script version only validates UTF-8 encoding and deny Unicode control characters. C module version will support explicitly selected (white list) Unicode categories (character types) for maximum security.


## Extending

Since "Validate" is designed as framework, it is easy to extend. It can work with other validators such as Respect.

"[src/tools](https://github.com/yohgaki/validate-php-scr/tree/master/src/tools)" directory contains
tools that request logging, creating validation spec rules from log and validation script.


## "Validate" C extension module

This PHP script is based on validate C module for PHP 7. Features are planned to be ported to C module which can perform validations a lot faster.

https://github.com/yohgaki/validate-php (Do not use this, but PHP script version now.)


## Comments & Issues

Comments, Bug reports and PRs are welcomed! Please remember "Validate" is not optimized for OO nor PHP scripts, but C module. This script is planed to be implemented as C module in the future.
