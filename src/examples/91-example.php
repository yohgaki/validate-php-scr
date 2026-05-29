<?php
require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines $basicTypes (basic type) array

// Validate domain name
$domain = 'es-i.jp';
$domain = validate($ctx, $domain, $basicTypes['fqdn']);
// Validate record ID
$id = '1234';
$id = validate($ctx, $id, $basicTypes['uint32']);
// Check results
var_dump($domain, $id);
