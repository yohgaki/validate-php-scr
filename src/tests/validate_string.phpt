--TEST--
validate() and string validation rules
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


try {
	$data = new StdClass;
	$spec = array(
		VALIDATE_STRING,
		VALIDATE_STRING_ALNUM,
		['min'=>1, 'max'=>10]
	);
	var_dump(validate($ctx, $data, $spec), $ctx->getStatus());
	die('Shoud not reach here');
} catch (Exception $e) {
	echo $e->getMessage();
}


$strings = array(
	'null' => null,
	'empty' => '',
	'num' =>'123456789',
	'num2' => 123456789,
	'float' => 123.456,
	'lower' => 'abc',
	'uppper' => 'XYZ',
	'alpha' => 'abcXYZ',
	'space' => '   ',
	'tab' => "\t",
	'almum' => 'abc1234',
	'mixed' => 'abcXYZ! "#$%&()',
	'utf8' => '日本',
	'multiline' => "abc\nXYZ\n",
	'cntrl' => "\b\0abc",
	'urf8broken' => "\xF0\xF0日本",
	'array' => array(1,2),
//	'object' => new StdClass,
	''
);
$flags = array(
	'none' => 0,
	'binary' => VALIDATE_STRING_BINARY, // Binary allow anything!! Avoid this option
	'lf' => VALIDATE_STRING_LF,
	'lower' => VALIDATE_STRING_LOWER_ALPHA,
	'upper' => VALIDATE_STRING_UPPER_ALPHA,
	'alpha' => VALIDATE_STRING_ALPHA,
	'digit' => VALIDATE_STRING_DIGIT,
	'alnum' => VALIDATE_STRING_ALNUM,
	'space' => VALIDATE_STRING_SPACE,
	'mb' => VALIDATE_STRING_MB,
);
$spec = array(
	VALIDATE_STRING,
	NULL, // replaced by above flags
	['min'=>0, 'max'=>20]
);
$func_opts = 0; //VALIDATE_OPT_CHECK_SPEC;


foreach($strings as $type => $val) {
	foreach($flags as $flag => $fval) {
		try {
			$input = $val;
			$spec[VALIDATE_FLAGS] = $fval;
			unset($ctx);
			$val = is_scalar($val) ? $val : serialize($val);
			echo "\n\nDATA: ".$type."=>'".$val."'  FLAG: ".$flag."=>". $fval. "\n";
			var_dump(validate($ctx, $input, $spec, $func_opts), $ctx->getStatus());
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}

$spec = array(
	VALIDATE_STRING,
	NULL, // replaced by above flags
	['min'=>1, 'max'=>10]
);

foreach($strings as $type => $val) {
	foreach($flags as $flag => $fval) {
		try {
			$input = $val;
			$spec[VALIDATE_FLAGS] = $fval;
			unset($ctx);
			$val = is_scalar($val) ?: serialize($val);
			echo "\n\n\DATA: ".$type."=>'".$val."'  FLAG: ".$flag."=>". $fval. "\n";
			var_dump(validate($ctx, $input, $spec, $func_opts), $ctx->getStatus());
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}
?>
--EXPECT--
param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.' val: 'O:8:"stdClass":0:{}'

DATA: null=>'N;'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: NULL input is rejected by default.' val: 'N;'

DATA: null=>'N;'  FLAG: binary=>1024
string(2) "N;"
bool(true)


DATA: null=>'N;'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "78" chr: "N"' val: 'N;'

DATA: null=>'N;'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "78" chr: "N"' val: 'N;'

DATA: null=>'N;'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "59" chr: ";"' val: 'N;'

DATA: null=>'N;'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "59" chr: ";"' val: 'N;'

DATA: null=>'N;'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "78" chr: "N"' val: 'N;'

DATA: null=>'N;'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "59" chr: ";"' val: 'N;'

DATA: null=>'N;'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "78" chr: "N"' val: 'N;'

DATA: null=>'N;'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "78" chr: "N"' val: 'N;'

DATA: empty=>''  FLAG: none=>0
string(0) ""
bool(true)


DATA: empty=>''  FLAG: binary=>1024
string(0) ""
bool(true)


DATA: empty=>''  FLAG: lf=>8
string(0) ""
bool(true)


DATA: empty=>''  FLAG: lower=>64
string(0) ""
bool(true)


DATA: empty=>''  FLAG: upper=>128
string(0) ""
bool(true)


DATA: empty=>''  FLAG: alpha=>192
string(0) ""
bool(true)


DATA: empty=>''  FLAG: digit=>2
string(0) ""
bool(true)


DATA: empty=>''  FLAG: alnum=>194
string(0) ""
bool(true)


DATA: empty=>''  FLAG: space=>1
string(0) ""
bool(true)


DATA: empty=>''  FLAG: mb=>512
string(0) ""
bool(true)


DATA: num=>'123456789'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num=>'123456789'  FLAG: binary=>1024
string(9) "123456789"
bool(true)


DATA: num=>'123456789'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num=>'123456789'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num=>'123456789'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num=>'123456789'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num=>'123456789'  FLAG: digit=>2
string(9) "123456789"
bool(true)


DATA: num=>'123456789'  FLAG: alnum=>194
string(9) "123456789"
bool(true)


DATA: num=>'123456789'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num=>'123456789'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num2=>'123456789'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num2=>'123456789'  FLAG: binary=>1024
int(123456789)
bool(true)


DATA: num2=>'123456789'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num2=>'123456789'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num2=>'123456789'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num2=>'123456789'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num2=>'123456789'  FLAG: digit=>2
string(9) "123456789"
bool(true)


DATA: num2=>'123456789'  FLAG: alnum=>194
string(9) "123456789"
bool(true)


DATA: num2=>'123456789'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: num2=>'123456789'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

DATA: float=>'123.456'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123.456'

DATA: float=>'123.456'  FLAG: binary=>1024
float(123.456)
bool(true)


DATA: float=>'123.456'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123.456'

DATA: float=>'123.456'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123.456'

DATA: float=>'123.456'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123.456'

DATA: float=>'123.456'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123.456'

DATA: float=>'123.456'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "46" chr: "."' val: '123.456'

DATA: float=>'123.456'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "46" chr: "."' val: '123.456'

DATA: float=>'123.456'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123.456'

DATA: float=>'123.456'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123.456'

DATA: lower=>'abc'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc'

DATA: lower=>'abc'  FLAG: binary=>1024
string(3) "abc"
bool(true)


DATA: lower=>'abc'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc'

DATA: lower=>'abc'  FLAG: lower=>64
string(3) "abc"
bool(true)


DATA: lower=>'abc'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc'

DATA: lower=>'abc'  FLAG: alpha=>192
string(3) "abc"
bool(true)


DATA: lower=>'abc'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc'

DATA: lower=>'abc'  FLAG: alnum=>194
string(3) "abc"
bool(true)


DATA: lower=>'abc'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc'

DATA: lower=>'abc'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc'

DATA: uppper=>'XYZ'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"' val: 'XYZ'

DATA: uppper=>'XYZ'  FLAG: binary=>1024
string(3) "XYZ"
bool(true)


DATA: uppper=>'XYZ'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"' val: 'XYZ'

DATA: uppper=>'XYZ'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"' val: 'XYZ'

DATA: uppper=>'XYZ'  FLAG: upper=>128
string(3) "XYZ"
bool(true)


DATA: uppper=>'XYZ'  FLAG: alpha=>192
string(3) "XYZ"
bool(true)


DATA: uppper=>'XYZ'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"' val: 'XYZ'

DATA: uppper=>'XYZ'  FLAG: alnum=>194
string(3) "XYZ"
bool(true)


DATA: uppper=>'XYZ'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"' val: 'XYZ'

DATA: uppper=>'XYZ'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"' val: 'XYZ'

DATA: alpha=>'abcXYZ'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ'

DATA: alpha=>'abcXYZ'  FLAG: binary=>1024
string(6) "abcXYZ"
bool(true)


DATA: alpha=>'abcXYZ'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ'

DATA: alpha=>'abcXYZ'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"' val: 'abcXYZ'

DATA: alpha=>'abcXYZ'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ'

DATA: alpha=>'abcXYZ'  FLAG: alpha=>192
string(6) "abcXYZ"
bool(true)


DATA: alpha=>'abcXYZ'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ'

DATA: alpha=>'abcXYZ'  FLAG: alnum=>194
string(6) "abcXYZ"
bool(true)


DATA: alpha=>'abcXYZ'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ'

DATA: alpha=>'abcXYZ'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ'

DATA: space=>'   '  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "' val: '   '

DATA: space=>'   '  FLAG: binary=>1024
string(3) "   "
bool(true)


DATA: space=>'   '  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "' val: '   '

DATA: space=>'   '  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "' val: '   '

DATA: space=>'   '  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "' val: '   '

DATA: space=>'   '  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "' val: '   '

DATA: space=>'   '  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "' val: '   '

DATA: space=>'   '  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "' val: '   '

DATA: space=>'   '  FLAG: space=>1
string(3) "   "
bool(true)


DATA: space=>'   '  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "' val: '   '

DATA: tab=>'	'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

DATA: tab=>'	'  FLAG: binary=>1024
string(1) "	"
bool(true)


DATA: tab=>'	'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

DATA: tab=>'	'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

DATA: tab=>'	'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

DATA: tab=>'	'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

DATA: tab=>'	'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

DATA: tab=>'	'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

DATA: tab=>'	'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

DATA: tab=>'	'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

DATA: almum=>'abc1234'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc1234'

DATA: almum=>'abc1234'  FLAG: binary=>1024
string(7) "abc1234"
bool(true)


DATA: almum=>'abc1234'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc1234'

DATA: almum=>'abc1234'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: 'abc1234'

DATA: almum=>'abc1234'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc1234'

DATA: almum=>'abc1234'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: 'abc1234'

DATA: almum=>'abc1234'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc1234'

DATA: almum=>'abc1234'  FLAG: alnum=>194
string(7) "abc1234"
bool(true)


DATA: almum=>'abc1234'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc1234'

DATA: almum=>'abc1234'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc1234'

DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ! "#$%&()'

DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: binary=>1024
string(15) "abcXYZ! "#$%&()"
bool(true)


DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ! "#$%&()'

DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"' val: 'abcXYZ! "#$%&()'

DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ! "#$%&()'

DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "33" chr: "!"' val: 'abcXYZ! "#$%&()'

DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ! "#$%&()'

DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "33" chr: "!"' val: 'abcXYZ! "#$%&()'

DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ! "#$%&()'

DATA: mixed=>'abcXYZ! "#$%&()'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ! "#$%&()'

DATA: utf8=>'日本'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '日本'

DATA: utf8=>'日本'  FLAG: binary=>1024
string(6) "日本"
bool(true)


DATA: utf8=>'日本'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '日本'

DATA: utf8=>'日本'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '日本'

DATA: utf8=>'日本'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '日本'

DATA: utf8=>'日本'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '日本'

DATA: utf8=>'日本'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '日本'

DATA: utf8=>'日本'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '日本'

DATA: utf8=>'日本'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '日本'

DATA: utf8=>'日本'  FLAG: mb=>512
string(6) "日本"
bool(true)


DATA: multiline=>'abc
XYZ
'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc
XYZ
'

DATA: multiline=>'abc
XYZ
'  FLAG: binary=>1024
string(8) "abc
XYZ
"
bool(true)


DATA: multiline=>'abc
XYZ
'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc
XYZ
'

DATA: multiline=>'abc
XYZ
'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "
"' val: 'abc
XYZ
'

DATA: multiline=>'abc
XYZ
'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc
XYZ
'

DATA: multiline=>'abc
XYZ
'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "
"' val: 'abc
XYZ
'

DATA: multiline=>'abc
XYZ
'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc
XYZ
'

DATA: multiline=>'abc
XYZ
'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "
"' val: 'abc
XYZ
'

DATA: multiline=>'abc
XYZ
'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc
XYZ
'

DATA: multiline=>'abc
XYZ
'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc
XYZ
'

DATA: cntrl=>'\b abc'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

DATA: cntrl=>'\b abc'  FLAG: binary=>1024
string(6) "\b abc"
bool(true)


DATA: cntrl=>'\b abc'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

DATA: cntrl=>'\b abc'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

DATA: cntrl=>'\b abc'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

DATA: cntrl=>'\b abc'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

DATA: cntrl=>'\b abc'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

DATA: cntrl=>'\b abc'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

DATA: cntrl=>'\b abc'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

DATA: cntrl=>'\b abc'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

DATA: urf8broken=>'��日本'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '��日本'

DATA: urf8broken=>'��日本'  FLAG: binary=>1024
string(8) "��日本"
bool(true)


DATA: urf8broken=>'��日本'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '��日本'

DATA: urf8broken=>'��日本'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '��日本'

DATA: urf8broken=>'��日本'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '��日本'

DATA: urf8broken=>'��日本'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '��日本'

DATA: urf8broken=>'��日本'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '��日本'

DATA: urf8broken=>'��日本'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '��日本'

DATA: urf8broken=>'��日本'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '��日本'

DATA: urf8broken=>'��日本'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Invalid UTF-8 encoding.' val: '��日本'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "0" max: "20"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "0" max: "20"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "0" max: "20"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "0" max: "20"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "0" max: "20"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "0" max: "20"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "0" max: "20"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "0" max: "20"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "0" max: "20"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

DATA: 0=>''  FLAG: none=>0
string(0) ""
bool(true)


DATA: 0=>''  FLAG: binary=>1024
string(0) ""
bool(true)


DATA: 0=>''  FLAG: lf=>8
string(0) ""
bool(true)


DATA: 0=>''  FLAG: lower=>64
string(0) ""
bool(true)


DATA: 0=>''  FLAG: upper=>128
string(0) ""
bool(true)


DATA: 0=>''  FLAG: alpha=>192
string(0) ""
bool(true)


DATA: 0=>''  FLAG: digit=>2
string(0) ""
bool(true)


DATA: 0=>''  FLAG: alnum=>194
string(0) ""
bool(true)


DATA: 0=>''  FLAG: space=>1
string(0) ""
bool(true)


DATA: 0=>''  FLAG: mb=>512
string(0) ""
bool(true)


\DATA: null=>'N;'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: NULL input is rejected by default.' val: 'N;'

\DATA: null=>'1'  FLAG: binary=>1024
string(2) "N;"
bool(true)


\DATA: null=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: null=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: null=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: null=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: null=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: null=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: null=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: null=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: empty=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "10"' val: ''

\DATA: empty=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: empty=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: empty=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: empty=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: empty=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: empty=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: empty=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: empty=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: empty=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

\DATA: num=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num2=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123456789'

\DATA: num2=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num2=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num2=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num2=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num2=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num2=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num2=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num2=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: num2=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: float=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "49" chr: "1"' val: '123.456'

\DATA: float=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: float=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: float=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: float=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: float=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: float=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: float=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: float=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: float=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: lower=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc'

\DATA: lower=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: lower=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: lower=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: lower=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: lower=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: lower=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: lower=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: lower=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: lower=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: uppper=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"' val: 'XYZ'

\DATA: uppper=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: uppper=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: uppper=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: uppper=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: uppper=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: uppper=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: uppper=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: uppper=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: uppper=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: alpha=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abcXYZ'

\DATA: alpha=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: alpha=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: alpha=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: alpha=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: alpha=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: alpha=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: alpha=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: alpha=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: alpha=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: space=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "' val: '   '

\DATA: space=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: space=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: space=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: space=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: space=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: space=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: space=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: space=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: space=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: tab=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "	"' val: '	'

\DATA: tab=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: tab=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: tab=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: tab=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: tab=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: tab=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: tab=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: tab=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: tab=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: almum=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc1234'

\DATA: almum=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: almum=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: almum=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: almum=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: almum=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: almum=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: almum=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: almum=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: almum=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: mixed=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "10"' val: 'abcXYZ! "#$%&()'

\DATA: mixed=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: mixed=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: mixed=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: mixed=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: mixed=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: mixed=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: mixed=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: mixed=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: mixed=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: utf8=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '日本'

\DATA: utf8=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: utf8=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: utf8=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: utf8=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: utf8=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: utf8=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: utf8=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: utf8=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: utf8=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: multiline=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"' val: 'abc
XYZ
'

\DATA: multiline=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: multiline=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: multiline=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: multiline=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: multiline=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: multiline=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: multiline=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: multiline=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: multiline=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: cntrl=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "92" chr: "\\"' val: '\b abc'

\DATA: cntrl=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: cntrl=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: cntrl=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: cntrl=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: cntrl=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: cntrl=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: cntrl=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: cntrl=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: cntrl=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: urf8broken=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.' val: '��日本'

\DATA: urf8broken=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: urf8broken=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: urf8broken=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: urf8broken=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: urf8broken=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: urf8broken=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: urf8broken=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: urf8broken=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: urf8broken=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: array=>'a:2:{i:0;i:1;i:1;i:2;}'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Array or object parameter is passed for scalar.' val: 'a:2:{i:0;i:1;i:1;i:2;}'

\DATA: array=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "10"' val: 'a:2:{i:0;i:1;i:1;i:2;}'

\DATA: array=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: array=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: array=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: array=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: array=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: array=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: array=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: array=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: 0=>'1'  FLAG: none=>0
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "10"' val: ''

\DATA: 0=>'1'  FLAG: binary=>1024
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: 0=>'1'  FLAG: lf=>8
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: 0=>'1'  FLAG: lower=>64
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: 0=>'1'  FLAG: upper=>128
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: 0=>'1'  FLAG: alpha=>192
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: 0=>'1'  FLAG: digit=>2
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: 0=>'1'  FLAG: alnum=>194
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: 0=>'1'  FLAG: space=>1
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'

\DATA: 0=>'1'  FLAG: mb=>512
param: 'ROOT' error: 'VALIDATE_STRING: Bool value cannot be treated as valid value for this validator.' val: '1'
