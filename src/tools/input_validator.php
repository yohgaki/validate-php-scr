<?php
/**
 * Simple input validator.
 *
 * Validate inputs by auto generated validation specs.
 * By default, it does not raise Exception!!
 * 
 * Usage: Set this file to "auto_prepend_file" in php.ini.
 * i.e. autoload="/path/to/input_validator.php"
 *
 * Change path for validation spec file path to generated by input_logger.php and input_analyzer.php.
 */

// Use closure to keep namespace clean.
(function() {
    // validate_func.php path
    $validate_path = '../validate_func.php';
    // Input spec file generated by input_analyzer.php
    $spec_path = '/var/tmp/validate/spec.php';
    // Exception - Set true to raise exception.
    $exception = false;

    if (!@include_once($validate_path)) {
        error_log('Failed to include: '. $validate_path);
        return;
    }
    if (!@include_once($spec_path)) {
        error_log('Invalid spec path: '. $spec_path);
        return;
    }

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if (empty($spec[$method. ':' .$uri])) {
        // input_logger.php didn't log this URI and METHOD
        return;
    }
    $input = ["_GET"=>$_GET, "_POST"=>$_POST, "_COOKIE"=>$_COOKIE, "_SERVER"=>$_SERVER];
    $func_opt = $exception ? 0 : VALIDATE_OPT_DISABLE_EXCEPTION;

    validate($ctx, $input, $spec, $func_opt);

    if (!$ctx->getStatus()) {
        $system_err = validate_get_system_errors($ctx);
        $user_err   = validate_get_user_errors($ctx);
        error_log('Validate: '. serialize([$system_err, $user_err]));
        $GLOBALS['_validate_errors_'] = [$system_err, $user_err];
    }
})();
