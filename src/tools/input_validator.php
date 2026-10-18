<?php
/**
 * Per-request input validator.
 *
 * Runs validate() against the spec produced by input_logger.php +
 * input_analyzer.php. By design, failures DO NOT throw — they are logged via
 * error_log() (parameter paths and messages only, no input values) and
 * stashed in $GLOBALS['_validate_errors_'], so dropping this in via
 * auto_prepend_file cannot crash an otherwise-working request.
 *
 * Flip the $exception flag below to throw ValidateInvalidValueException instead
 * (useful once you trust the spec enough to fail closed).
 *
 * A request whose "METHOD:PATH" has no spec (an endpoint the logger never saw,
 * or another spelling of a known path such as /index.php/x or //index.php) is
 * a failure too, reported the same way.
 *
 * Usage: register as auto_prepend_file in php.ini:
 *   auto_prepend_file="/path/to/input_validator.php"
 *
 * The spec is read from VALIDATE_TOOLS_DIR/spec.php (environment variable,
 * default /var/tmp/validate). It is not loaded unless the directory is owned
 * by the current user and not group/world writable.
 */

// Wrap in an IIFE so locals don't pollute the request's global scope.
(function () {
    // Path to validate_func.php (provides validate() and the constants).
    $validate_path = __DIR__.'/../validate_func.php';
    // Spec file produced by input_analyzer.php.
    $spec_dir = rtrim(getenv('VALIDATE_TOOLS_DIR') ?: '/var/tmp/validate', '/');
    $spec_path = $spec_dir.'/spec.php';
    // false = log and continue; true = throw ValidateInvalidValueException on failure.
    $exception = false;

    if (!@include_once($validate_path)) {
        error_log('Failed to include: '. $validate_path);
        return;
    }
    $st = @stat($spec_dir);
    if ($st === false || is_link($spec_dir) || ($st['mode'] & 0022)
        || (function_exists('posix_geteuid') && $st['uid'] !== posix_geteuid())) {
        error_log('input_validator: unsafe spec dir (must be owned by this user, mode 0700): '.$spec_dir);
        return;
    }
    if (!@include_once($spec_path)) {
        error_log('Invalid spec path: '. $spec_path);
        return;
    }

    // Specs are keyed by "METHOD:PATH". No spec means nothing validated
    // this request: a failure, never a pass.
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    $key = $method. ':' .$uri;
    // The key is request input: escape it for the log line.
    $log_key = addcslashes($key, "\0..\37\177..\377\\");
    if (empty($spec[$key])) {
        $message = 'No spec for this METHOD:PATH.';
        error_log('Validate: '.$log_key.' '.$message);
        $error = ['type' => E_ERROR, 'param' => ['ROOT'], 'message' => $message, 'value' => $key];
        $GLOBALS['_validate_errors_'] = [
            ['error' => ['ROOT' => [$error]], 'warning' => [], 'notice' => []],
            ['error' => [], 'warning' => [], 'notice' => []],
        ];
        if ($exception) {
            throw new ValidateInvalidValueException('Validate: '.$message, $error);
        }
        return;
    }
    $input = ["_GET"=>$_GET, "_POST"=>$_POST, "_COOKIE"=>$_COOKIE, "_SERVER"=>$_SERVER];
    $func_opt = $exception ? VALIDATE_OPT_NONE : VALIDATE_OPT_DISABLE_EXCEPTION;

    $ctx = null;
    validate($ctx, $input, $spec[$key], $func_opt | VALIDATE_OPT_ERROR_SQUASH);

    if (!$ctx->getStatus()) {
        // Surface both error buckets to the host application: error_log() for
        // ops tooling, $GLOBALS['_validate_errors_'] for the app to inspect.
        $system_err = validate_get_system_errors($ctx);
        $user_err   = validate_get_user_errors($ctx);
        $summary = [];
        foreach ($system_err['error'] as $param => $errors) {
            foreach ($errors as $error) {
                $summary[] = $param.': '.$error['message'];
            }
        }
        error_log('Validate: '.$log_key.' '.json_encode($summary));
        $GLOBALS['_validate_errors_'] = [$system_err, $user_err];
    }
})();
