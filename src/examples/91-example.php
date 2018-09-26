<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $B (basic type) array

// Validate domain name
$domain = 'es-i.jp';
$domain = validate($ctx, $domain, $B['fqdn']);
// Validte record ID
$id = '1234';
$id = validate($ctx, $id, $B['uint32']);
// Check result
var_dump($domain, $id);
