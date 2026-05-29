<?php
/**
 * Single-input validation examples.
 *
 * Demonstrates the simplest use of validate(): one variable, one spec at a
 * time, using ready-made entries from $basicTypes. Each block shows a value
 * that passes and one that fails, with VALIDATE_OPT_DISABLE_EXCEPTION used
 * to make the failing call return null instead of throwing.
 */

require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Loads the $basicTypes array with predefined specs.

// Install a fallback exception handler so a failing call without
// VALIDATE_OPT_DISABLE_EXCEPTION doesn't tear the script down silently.
set_exception_handler(function($exception) {
    echo "Uncaught exception: " , $exception->getMessage(), "\n";
});

// Validate is explicit by design: every validation must spell out its rules —
// there are no implicit defaults. The presets in basic_types.php are just
// pre-built spec arrays; nothing happens automatically.

// ---------------------------------------------------------------------------
// Integers.
// $basicTypes['int8']  : signed 8-bit (-128..127).
// $basicTypes['int53'] : signed 53-bit — the safe integer range for an IEEE 754
//                       double, i.e. the range web inputs survive as JS Numbers.
// ---------------------------------------------------------------------------
$var = 127;
$validated = validate($ctx, $var, $basicTypes['int8']); // Success — validate() returns the validated value.
var_dump($validated); // int(127)
$var = 128;
$validated = validate($ctx, $var, $basicTypes['int8'], VALIDATE_OPT_DISABLE_EXCEPTION); // Out of range — would throw, but the flag silences it.
var_dump($validated); // NULL — the conventional "failure" return when exceptions are disabled.

$var = -4503599627370496;
$validated = validate($ctx, $var, $basicTypes['int53']); // Success — minimum safe integer.
$var = '-4503599627370496'; // Web input typically arrives as a string; int validators accept the numeric string.
$validated = validate($ctx, $var, $basicTypes['int53']); // Success — integer validators normalize the result to int.
$var = -4503599627370497;
$validated = validate($ctx, $var, $basicTypes['int8'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure — out of int8 range.
$var = ''; // Empty string is not a valid integer; use VALIDATE_FLAG_EMPTY to permit it explicitly.
$validated = validate($ctx, $var, $basicTypes['int8'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure.


// ---------------------------------------------------------------------------
// Password: alnum + symbol + space (no control characters / no newlines).
// ---------------------------------------------------------------------------
$var = 'This is my stupid password';
$validated = validate($ctx, $var, $basicTypes['password']); // Success.
$var = "This is my \n \r stupid password";
$validated = validate($ctx, $var, $basicTypes['password'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure — \n and \r are not whitelisted.


// ---------------------------------------------------------------------------
// 'alpha128': ASCII letters only, up to 128 bytes.
// To loosen a stock spec, copy it and extend the 'ascii' option. The 'ascii'
// option lists extra individual characters to permit alongside the flag-based
// whitelist.
// ---------------------------------------------------------------------------
$var = 'Thisismystupidpassword';
$validated = validate($ctx, $var, $basicTypes['alpha128']); // Success.
$var = "This_is_my_stupid_password";
$validated = validate($ctx, $var, $basicTypes['alpha128'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure — '_' not allowed.
// Add '_' to the allowed character set without modifying the shared $basicTypes['alpha128'].
$var = "This_is_my_stupid_password";
$alpha128_with_underscore = $basicTypes['alpha128'];
$alpha128_with_underscore[VALIDATE_OPTIONS]['ascii'] = '_';
$validated = validate($ctx, $var, $alpha128_with_underscore, VALIDATE_OPT_DISABLE_EXCEPTION); // Success.


// ---------------------------------------------------------------------------
// HTTP header values.
// $basicTypes['header4096']  : up to 4096 ASCII bytes (typical RFC 7230 case).
// $basicTypes['header4096u'] : same length, but UTF-8 multibyte chars allowed.
// ---------------------------------------------------------------------------
$var = 'NID=139=gJnuNA8zuOetvT8uXxXG9PU53LMIXlH-i27alOHmkGGWhQ8Ah6jeDOSpstzbi9JgqWr_W4zPyuqZ75j1KN3yIFz7KqfZQTwIDhx9Gto4eIE6qakO5YvaBUjes9TI-d9i; 1P_JAR=2018-9-22-0';
$validated = validate($ctx, $var, $basicTypes['header4096']); // Success — ASCII-only header value.
$var = '日本語NID=139=gJnuNA8zuOetvT8uXxXG9PU53LMIXlH-i27alOHmkGGWhQ8Ah6jeDOSpstzbi9JgqWr_W4zPyuqZ75j1KN3yIFz7KqfZQTwIDhx9Gto4eIE6qakO5YvaBUjes9TI-d9i; 1P_JAR=2018-9-22-0';
$validated = validate($ctx, $var, $basicTypes['header4096'], VALIDATE_OPT_DISABLE_EXCEPTION); // Failure — UTF-8 bytes not allowed in 'header4096'. Use 'header4096u' for that.
// 'header*' specs accept strings as short as 1 byte by default. Raise 'min'
// to reject pathologically short values (e.g. when checking a Cookie header).
$header4096_min60 = $basicTypes['header4096'];
$header4096_min60[VALIDATE_OPTIONS]['min'] = 60;
$var = 'abc';
$validated = validate($ctx, $var, $header4096_min60, VALIDATE_OPT_DISABLE_EXCEPTION); // Failure — shorter than min=60.
