<?php
/**
 * Per-request input validator.
 *
 * Runs validate() against the spec produced by input_logger.php +
 * input_analyzer.php. By design, failures DO NOT throw — they are logged via
 * error_log() and stashed in $GLOBALS['_validate_errors_'], so dropping this
 * in via auto_prepend_file cannot crash an otherwise-working request.
 *
 * Flip the $exception flag below to raise InvalidArgumentException instead
 * (useful once you trust the spec enough to fail closed).
 *
 * Usage: register as auto_prepend_file in php.ini:
 *   auto_prepend_file="/path/to/input_validator.php"
 */

// Wrap in an IIFE so locals don't pollute the request's global scope.
(function () {
    // Path to validate_func.php (provides validate() and the constants).
    $validate_path = '../validate_func.php';
    // Spec file produced by input_analyzer.php.
    $spec_path = '/var/tmp/validate/spec.php';
    // false = log and continue; true = throw InvalidArgumentException on failure.
    $exception = false;

    if (!@include_once($validate_path)) {
        error_log('Failed to include: '. $validate_path);
        return;
    }
    if (!@include_once($spec_path)) {
        error_log('Invalid spec path: '. $spec_path);
        return;
    }

    // Specs are keyed by "METHOD:PATH". If logger never saw this endpoint
    // there is nothing meaningful to validate against — silently bail.
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if (empty($spec[$method. ':' .$uri])) {
        return;
    }
    $input = ["_GET"=>$_GET, "_POST"=>$_POST, "_COOKIE"=>$_COOKIE, "_SERVER"=>$_SERVER];
    $func_opt = $exception ? 0 : VALIDATE_OPT_DISABLE_EXCEPTION;

    validate($ctx, $input, $spec, $func_opt);

    if (!$ctx->getStatus()) {
        // Surface both error buckets to the host application: error_log() for
        // ops tooling, $GLOBALS['_validate_errors_'] for the app to inspect.
        $system_err = validate_get_system_errors($ctx);
        $user_err   = validate_get_user_errors($ctx);
        error_log('Validate: '. serialize([$system_err, $user_err]));
        $GLOBALS['_validate_errors_'] = [$system_err, $user_err];
    }
})();
