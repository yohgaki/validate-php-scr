<?php
/**
 * Example #1: Single value validation with exceptions.
 *
 * Validation failures throw InvalidArgumentException by default — perfect for
 * APPLICATION INPUT validation, where invalid values should fail fast and not
 * involve the user.
 */

require_once __DIR__.'/../validate_func.php';
require_once __DIR__.'/../lib/basic_types.php'; // Defines the $basicTypes array.

// Validate a DNS-resolvable domain name.
$domain = 'es-i.jp';
$domain = validate($ctx, $domain, $basicTypes['fqdn']);
// Validate a record ID as a 32-bit unsigned integer.
$id = '1234';
$id = validate($ctx, $id, $basicTypes['uint32']);
// Both calls returned the validated, natively-typed values.
var_dump($domain, $id);
