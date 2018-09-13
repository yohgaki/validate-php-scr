<?php
/**
 * Simple input logger.
 *
 * Usage: Set this file to "auto_prepend_file" in php.ini.
 * i.e. autoload="/path/to/input_logger.php"
 *
 * Change path to store input log.
 */

// Use closure to keep namespace clean.
(function() {
    // Input log path
    $path = "/var/tmp/validate/";
    if (!is_dir($path)) {
        mkdir($path);
    }
    $log = '<?php
$inputs = ';
    $log .= var_export(["_GET"=>$_GET, "_POST"=>$_POST, "_COOKIE"=>$_COOKIE, "_SERVER"=>$_SERVER], true) . ";\n";
    $path .= microtime(true) . '-log.php';
    file_put_contents($path, $log);
})();
