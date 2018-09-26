<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $B (basic type) array

// Validate domain name w/o exception
$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION;
$domain = 'es-i.jp';
$domain = validate($ctx, $domain, $B['fqdn'], $func_opts);
// Validate record ID
$id = '1234';
$id = validate($ctx, $id, $B['uint32'], $func_opts);

if (validate_get_status($ctx) == false) {
    // Check $errors for interactive responses
    $error = validate_get_user_errors($ctx);
    // Show useful $error here
}
//Check results
var_dump($domain, $id, $error);