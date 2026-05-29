--TEST--
validate() and VALIDATE_REGEXP
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting=-1
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

// Each entry is one options array fed to a VALIDATE_REGEXP spec. The driver
// loop below applies it against the fixed input 'data' so each pattern's
// match/non-match behaviour is exercised once.
$opts = array(
	array("min"=>0,"max"=>100,"regexp"=>'/.*/'),       // matches anything
	array("min"=>0,"max"=>100,"regexp"=>'/^b(.*)/'),   // requires leading 'b' — fails on 'data'
	array("min"=>0,"max"=>100,"regexp"=>'/^d(.*)/'),   // requires leading 'd' — succeeds
	array("min"=>0,"max"=>100,"regexp"=>'/blah/'),     // literal not present — fails
	array("min"=>0,"max"=>100,"regexp"=>'/\[/'),       // literal '[' not present — fails
	// Commented-out forms exist as a reminder of inputs the spec validator should reject
	// outright (empty options, NULL, non-array). Re-enable temporarily when adjusting validateSpec().
	// array(),
	// NULL,
	// "foo",
);

foreach($opts as $opt) {
	try {
		$data = 'data';
		echo "****TEST OPT: ". serialize($opt) . "\n";
		$tmp = $data;
		$result = validate(
			$ctx,
			$tmp,
			[
				VALIDATE_REGEXP, VALIDATE_REGEXP_ALNUM, $opt
			]
		);
		var_dump($ctx->getStatus(), $result);
	} catch (Exception $e) {
		var_dump($e->getMessage());
	}
}

echo "Done\n";
?>
--EXPECTF--
****TEST OPT: a:3:{s:3:"min";i:0;s:3:"max";i:100;s:6:"regexp";s:4:"/.*/";}
bool(true)
string(4) "data"
****TEST OPT: a:3:{s:3:"min";i:0;s:3:"max";i:100;s:6:"regexp";s:8:"/^b(.*)/";}
string(68) "param: 'ROOT' error: 'VALIDATE_REGEXP: Failed to match.' val: 'data'"
****TEST OPT: a:3:{s:3:"min";i:0;s:3:"max";i:100;s:6:"regexp";s:8:"/^d(.*)/";}
bool(true)
string(4) "data"
****TEST OPT: a:3:{s:3:"min";i:0;s:3:"max";i:100;s:6:"regexp";s:6:"/blah/";}
string(68) "param: 'ROOT' error: 'VALIDATE_REGEXP: Failed to match.' val: 'data'"
****TEST OPT: a:3:{s:3:"min";i:0;s:3:"max";i:100;s:6:"regexp";s:4:"/\[/";}
string(68) "param: 'ROOT' error: 'VALIDATE_REGEXP: Failed to match.' val: 'data'"
Done
