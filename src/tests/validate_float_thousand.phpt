--TEST--
validate() VALIDATE_FLOAT_THOUSAND — accept thousand separators (default ',')
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
precision=14
error_reporting=-1
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

function tryone($label, $value, $spec) {
    $ctx = null;
    $r = validate($ctx, $value, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
    printf("[%s] in=%s => out=%s status=%s\n",
        $label,
        var_export($value, true),
        var_export($r, true),
        var_export($ctx instanceof Validate ? $ctx->getStatus() : null, true));
}

echo "**** Grouped integer parts (default ',' separator) ****\n";
$spec = [VALIDATE_FLOAT, VALIDATE_FLOAT_THOUSAND, ['min' => 0, 'max' => 1.0e13]];
tryone('1,234',         '1,234',         $spec); // ok
tryone('12,345',        '12,345',        $spec); // ok
tryone('123,456',       '123,456',       $spec); // ok
tryone('1,234,567',     '1,234,567',     $spec); // ok
tryone('12,345,678,901', '12,345,678,901', $spec); // ok

echo "\n**** Malformed groupings rejected ****\n";
tryone('12,34',         '12,34',         $spec); // fail — group not 3 digits
tryone('1,2345',        '1,2345',        $spec); // fail — group too long
tryone('1234,567',      '1234,567',      $spec); // fail — head > 3 then group
tryone('123,4',         '123,4',         $spec); // fail
tryone(',123',          ',123',          $spec); // fail — leading sep
tryone('123,',          '123,',          $spec); // fail — trailing sep
tryone('1,,234',        '1,,234',        $spec); // fail — empty group

echo "\n**** Ungrouped form still accepted (separator optional) ****\n";
tryone('1234',          '1234',          $spec); // ok
tryone('12345',         '12345',         $spec); // ok
tryone('0',             '0',             $spec); // ok

echo "\n**** Combined with fractional part ****\n";
tryone('1,234.5',       '1,234.5',       $spec); // ok
tryone('1,234.567',     '1,234.567',     $spec); // ok
tryone('1,234.',        '1,234.',        $spec); // fail — bare dot

echo "\n**** Combined with sign ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_THOUSAND | VALIDATE_FLOAT_POSITIVE_SIGN,
    ['min' => -1.0e6, 'max' => 1.0e6],
];
tryone('+1,234',        '+1,234',        $spec); // ok
tryone('-1,234',        '-1,234',        $spec); // ok (min < 0 implies neg)
tryone('1,234',         '1,234',         $spec); // ok — sign optional

echo "\n**** Combined with SCIENTIFIC ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_THOUSAND | VALIDATE_FLOAT_SCIENTIFIC,
    ['min' => 0, 'max' => 1.0e10],
];
tryone('1,234.5e2',     '1,234.5e2',     $spec); // ok
tryone('1,234e3',       '1,234e3',       $spec); // ok

echo "\n**** Native double inputs unaffected by THOUSAND flag ****\n";
$spec = [VALIDATE_FLOAT, VALIDATE_FLOAT_THOUSAND, ['min' => 0, 'max' => 1.0e10]];
tryone('1234.5 (dbl)',  1234.5,          $spec); // ok
tryone('0.0 (dbl)',     0.0,             $spec); // ok

echo "\n**** AS_STRING preserves separators in output ****\n";
$spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLOAT_THOUSAND | VALIDATE_FLOAT_AS_STRING,
    ['min' => 0, 'max' => 1.0e6],
];
tryone('"1,234.5"',     '1,234.5',       $spec); // string out, sep preserved

echo "\n**** Range applies to numeric value, not raw string ****\n";
$spec = [VALIDATE_FLOAT, VALIDATE_FLOAT_THOUSAND, ['min' => 0, 'max' => 1000]];
tryone('1,234',         '1,234',         $spec); // fail — 1234 > 1000
tryone('999',           '999',           $spec); // ok

?>
--EXPECT--
**** Grouped integer parts (default ',' separator) ****
[1,234] in='1,234' => out=1234.0 status=true
[12,345] in='12,345' => out=12345.0 status=true
[123,456] in='123,456' => out=123456.0 status=true
[1,234,567] in='1,234,567' => out=1234567.0 status=true
[12,345,678,901] in='12,345,678,901' => out=12345678901.0 status=true

**** Malformed groupings rejected ****
[12,34] in='12,34' => out=NULL status=false
[1,2345] in='1,2345' => out=NULL status=false
[1234,567] in='1234,567' => out=NULL status=false
[123,4] in='123,4' => out=NULL status=false
[,123] in=',123' => out=NULL status=false
[123,] in='123,' => out=NULL status=false
[1,,234] in='1,,234' => out=NULL status=false

**** Ungrouped form still accepted (separator optional) ****
[1234] in='1234' => out=1234.0 status=true
[12345] in='12345' => out=12345.0 status=true
[0] in='0' => out=0.0 status=true

**** Combined with fractional part ****
[1,234.5] in='1,234.5' => out=1234.5 status=true
[1,234.567] in='1,234.567' => out=1234.567 status=true
[1,234.] in='1,234.' => out=NULL status=false

**** Combined with sign ****
[+1,234] in='+1,234' => out=1234.0 status=true
[-1,234] in='-1,234' => out=-1234.0 status=true
[1,234] in='1,234' => out=1234.0 status=true

**** Combined with SCIENTIFIC ****
[1,234.5e2] in='1,234.5e2' => out=123450.0 status=true
[1,234e3] in='1,234e3' => out=1234000.0 status=true

**** Native double inputs unaffected by THOUSAND flag ****
[1234.5 (dbl)] in=1234.5 => out=1234.5 status=true
[0.0 (dbl)] in=0.0 => out=0.0 status=true

**** AS_STRING preserves separators in output ****
["1,234.5"] in='1,234.5' => out='1,234.5' status=true

**** Range applies to numeric value, not raw string ****
[1,234] in='1,234' => out=NULL status=false
[999] in='999' => out=999.0 status=true
