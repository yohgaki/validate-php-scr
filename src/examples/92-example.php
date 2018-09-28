<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $B (basic type) array

$func_opts = VALIDATE_OPT_DISABLE_EXCEPTION;
// Validate domain name w/o exception
$domain = 'es-i.jp';
$domain = validate($ctx, $domain, $B['fqdn'], $func_opts);
// Validate record ID
$id = '1234';
$id = validate($ctx, $id, $B['uint32'], $func_opts);

if (validate_get_status($ctx) == false) {
    // Check last validation error
}
// Get all user error
$errors = validate_get_user_errors($ctx);

//Check results
var_dump($domain, $id, $errors);
