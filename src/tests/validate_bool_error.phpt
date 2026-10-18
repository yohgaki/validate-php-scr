--TEST--
validate() and VALIDATE_BOOL errors
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting=E_ALL & ~E_DEPRECATED
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

$booleans = array(
    NULL => false,
    2 => false,
    '2' => false,
    'on ' => false,
    ' On' => false,
    'oN ' => false,
    ' False' => false,
    'TrUe ' => false,
    ' oN' => false,
    '' => false, // This fails by design
    "\t" => false, // This fails by design
    ' ' => false, // This fails by design
    "\n" => false, // This fails by design
);

foreach($booleans as $val=>$exp) {
    try {
        unset($ctx);
        $result = validate($ctx, $val,
					 [VALIDATE_BOOL,
					  VALIDATE_BOOL_01|VALIDATE_BOOL_TF|VALIDATE_BOOL_TRUE_FALSE|VALIDATE_BOOL_ON_OFF,
					  []
					 ],
                     VALIDATE_OPT_NONE);
        var_dump($result);
    } catch (Exception $e) {
        var_dump($e->getMessage());
    }
}
echo "Ok.";
?>
--EXPECTF--
string(50) "param: 'ROOT' error: 'VALIDATE_BOOL: Empty input.'"
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
string(52) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid input.'"
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
string(51) "param: 'ROOT' error: 'VALIDATE_BOOL: Invalid bool.'"
Ok.
