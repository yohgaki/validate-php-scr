<?php
/**
 * Per-request input logger.
 *
 * One log file per request: contains a single $inputs array with the request
 * superglobals as PHP source, so input_analyzer.php can include() it directly.
 *
 * Usage: register as auto_prepend_file in php.ini:
 *   auto_prepend_file="/path/to/input_logger.php"
 *
 * The log directory is the VALIDATE_TOOLS_DIR environment variable (default
 * /var/tmp/validate). It is created with mode 0700; logging is skipped when
 * it is not owned by the current user or is group/world writable, because
 * the analyzer include()s every log file. Run only in a controlled
 * environment — every request body (passwords, cookies) lands on disk in
 * cleartext (files are created with mode 0600).
 */

// Wrap in an IIFE so locals don't pollute the request's global scope.
(function () {
    // Directory the per-request log files are written to.
    $path = rtrim(getenv('VALIDATE_TOOLS_DIR') ?: '/var/tmp/validate', '/');
    if (!is_dir($path) && !@mkdir($path, 0700, true)) {
        error_log('input_logger: cannot create log dir: '.$path);
        return;
    }
    $st = @stat($path);
    if ($st === false || is_link($path) || ($st['mode'] & 0022)
        || (function_exists('posix_geteuid') && $st['uid'] !== posix_geteuid())) {
        error_log('input_logger: unsafe log dir (must be owned by this user, mode 0700): '.$path);
        return;
    }
    // Each log file is valid PHP defining a single $inputs array. This lets
    // input_analyzer.php pull it in with include() instead of unserializing.
    $log = '<?php
$inputs = ';
    $log .= var_export(["_GET"=>$_GET, "_POST"=>$_POST, "_COOKIE"=>$_COOKIE, "_SERVER"=>$_SERVER], true) . ";\n";
    // microtime + random suffix gives a unique filename per request without
    // coordinating between processes.
    $file = $path.'/'.microtime(true).'-'.bin2hex(random_bytes(4)).'-log.php';
    $umask = umask(0077);
    file_put_contents($file, $log, LOCK_EX);
    umask($umask);
})();
