<?php
/**
 * Example #2: Single value validation without exceptions.
 *
 * Use this style for BUSINESS LOGIC validation where the user needs an
 * actionable error message (e.g. "Email is required") rather than a hard
 * abort. Errors are collected on $ctx and the call returns null on failure.
 */

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
