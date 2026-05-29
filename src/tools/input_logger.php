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
 * Adjust $path below to change where the logs are written. Run only in a
 * controlled environment — every request body lands on disk in cleartext.
 */

// Wrap in an IIFE so locals don't pollute the request's global scope.
(function () {
    // Directory the per-request log files are written to.
    $path = "/var/tmp/validate/";
    if (!is_dir($path)) {
        mkdir($path);
    }
    // Each log file is valid PHP defining a single $inputs array. This lets
    // input_analyzer.php pull it in with include() instead of unserializing.
    $log = '<?php
$inputs = ';
    $log .= var_export(["_GET"=>$_GET, "_POST"=>$_POST, "_COOKIE"=>$_COOKIE, "_SERVER"=>$_SERVER], true) . ";\n";
    // microtime gives a unique filename per request without coordinating between processes.
    $path .= microtime(true) . '-log.php';
    file_put_contents($path, $log);
})();
