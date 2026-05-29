--TEST--
Laravel-compatible basicTypes validators (DNS required)
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
if (!@dns_get_record('example.com', DNS_A)) die("skip no DNS resolution available");
?>
--INI--
error_reporting=E_ALL & ~E_DEPRECATED
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/../lib/basic_types.php';

// DNS-failure cases are intentionally omitted because local resolvers may
// apply search-domain completion or NXDOMAIN hijacking, making "guaranteed
// unresolvable" hostnames unreliable across environments.
$cases = [
    // active_url - valid URL with resolvable host
    ['active_url', 'https://example.com/', 'pass'],
    ['active_url', 'http://example.com', 'pass'],
    ['active_url', 'not-a-url', 'fail'],

    // email_dns - valid email with resolvable domain
    ['email_dns', 'postmaster@example.com', 'pass'],
    ['email_dns', 'not-an-email', 'fail'],
];

foreach ($cases as [$type, $input, $expected]) {
    $ctx = null;
    $r = validate($ctx, $input, $basicTypes[$type], VALIDATE_OPT_DISABLE_EXCEPTION);
    $actual = ($ctx instanceof Validate && $ctx->getStatus()) ? 'pass' : 'fail';
    $mark = ($actual === $expected) ? 'OK' : 'NG';
    printf("[%s] %s(%s) => %s (expected %s)\n", $mark, $type, var_export($input, true), $actual, $expected);
}
echo "Done.\n";
?>
--EXPECTF--
[OK] active_url('https://example.com/') => pass (expected pass)
[OK] active_url('http://example.com') => pass (expected pass)
[OK] active_url('not-a-url') => fail (expected fail)
[OK] email_dns('postmaster@example.com') => pass (expected pass)
[OK] email_dns('not-an-email') => fail (expected fail)
Done.
