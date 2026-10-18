--TEST--
validate() VALIDATE_STRING - flag/option matrix; every case should fail
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

# Same matrix shape as validate_basic1.phpt - see that file for the layout.
# Inputs and flags here are deliberately mismatched, so every combination
# in the driver loop is expected to fail validation.
$test_cases = array(

	'string_abcX' => array(
		'abcX', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			// 'alpha        ' => VALIDATE_STRING_ALPHA,
			// 'alpha+space  ' => VALIDATE_STRING_ALPHA | VALIDATE_STRING_SPACE,
			'digit        ' => VALIDATE_STRING_DIGIT,
			// 'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcdef0123456789',
				),
			),
		),
	),

	'string_abc# ' => array(
		'abc# ', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'alpha+space  ' => VALIDATE_STRING_ALPHA | VALIDATE_STRING_SPACE,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcdef0123456789 ',
				),
			),
		),
	),

	'string_ abc ' => array(
		" abc \t", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			// 'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'alpha+space  ' => VALIDATE_STRING_ALPHA | VALIDATE_STRING_SPACE,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abc ',
				),
			),
		),
	),

	'string_ abc xyz ' => array(
		' abc xyz ', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			// 'alpha+space  ' => VALIDATE_STRING_ALPHA | VALIDATE_STRING_SPACE,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcxyz',
				),
			),
		),
	),

	'string_123\n' => array(
		"123\n", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			// 'lf           ' => VALIDATE_STRING_LF, //pass
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => '0123456789',
				),
			),
		),
	),

	'string_123 ' => array(
		'123 ', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			// 'digit        ' => VALIDATE_STRING_DIGIT,
			// 'alnum        ' => VALIDATE_STRING_ALNUM,
			// 'digit+space  ' => VALIDATE_STRING_DIGIT | VALIDATE_STRING_SPACE,
			// 'alnum+space  ' => VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE,
			'mb            ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => ' abcdef012456789',
				),
			),
		),
	),

	'string_ 123 ' => array(
		' 123 ', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			// 'space        ' => VALIDATE_STRING_SPACE,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			// 'digit+space  ' => VALIDATE_STRING_DIGIT | VALIDATE_STRING_SPACE,
			// 'alnum+space  ' => VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE,
			'mb            ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcdef0123456789',
				),
			),
		),
	),

	'string_ 123 xyz ' => array(
		' 123 xyz ', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			// 'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			// 'alnum+space  ' => VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE,
			// 'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => ' ZYZabcdef0123456789',
				),
			),
		),
	),

	'string_日本' => array(
		'日本', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY, 
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			// 'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcdef0123456789',
				),
			),
		),
	),

	'string_tab'  => array(
		"abc\txyz",
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			// 'tab+space    ' => VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb            ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyzabcdef0123456789",
				),
			),
		),
	),

	'string_lf'  => array(
		"abc\nxyz\n", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			// 'lf+lower     ' => VALIDATE_STRING_LF | VALIDATE_STRING_LOWER_ALPHA, 
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			// 'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			// 'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyz0123456789\n",
				),
			),
		),
	),

	'string_cr'  => array(
		"abc\rxyz\r", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			// 'cr+alpha     ' => VALIDATE_STRING_CR | VALIDATE_STRING_ALPHA,
			// 'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyzabcdef0123456789",
				),
			),
		),
	),

	'string_crlf'  => array(
		"abc\r\nxyz\r\n", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			// 'cr           ' => VALIDATE_STRING_CR,
			// 'crlf+alpha   ' => VALIDATE_STRING_CRLF | VALIDATE_STRING_ALPHA,
			// 'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyzabcdef0123456789\n",
				),
			),
		),
	),

	'string_lfcr'  => array(
		"abc\n\rxyz\n\r", // test string
		array( // test flags
			// 'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			// 'tab          ' => VALIDATE_STRING_TAB,
			// 'lf           ' => VALIDATE_STRING_LF,
			// 'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			// 'alpha        ' => VALIDATE_STRING_ALPHA,
			// 'digit        ' => VALIDATE_STRING_DIGIT,
			// 'alnum        ' => VALIDATE_STRING_ALNUM,
			// 'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyzabcdef0123456789\n\r",
				),
			),
		),
	),

	'string_cntrl' => array(
		"\b\0abc", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyzabcdef0123456789\b",
				),
			),
		),
	),

	'string_urf8broken' => array(
		"\xF0\xF0日本", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcdef0123456789',
				),
			),
		),
	),

	/*
	'string_spin_hex' => array(
		"a0b0d8e3", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcdef0123456789',
				),
			),
		),
	),
	*/

	'string_spin_broken' => array(
		"abdZ867e3", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			// 'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			// 'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			// 'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			// VALIDATE_OPT_RAISE_ERROR,
			// VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcdef0123456789',
				),
			),
		),
	),
);



echo "***String tests: All tests should fail***\n";
foreach($test_cases as $test_name => $topts) {
	echo '***********************************************************************************************'."\n";
	echo 'START ***** TEST: '. $test_name ." VALUE: '". $topts[0] ."' (". gettype($topts[0]). ") ******\n";
	foreach($topts[1] as $flag => $fval) {
		foreach($topts[2] as $func_opt) {
			echo "FALG(". $fval .") ". $flag ." OPT(". $func_opt .") RESULT: ";
			try {
				unset($ctx);
				$val = $topts[0];
				$spec = $topts[3][0];
				$spec[VALIDATE_FLAGS] = $fval;
				$result = validate($ctx, $val, $spec, $func_opt);
				var_dump($result, $ctx->getStatus());
				// die();
			} catch (Exception $e) {
				var_dump(['ErrorMsg' => $e->getMessage()]);
			}
			if ($ctx->getStatus() !== false) {
				echo "BUG!!!\n";
			}
		}
		echo "******\n";
	}
	echo 'END ***** TEST: '. $test_name ." VALUE: '". $topts[0] ."' (". gettype($topts[0]). ") ******\n";
	echo "\n\n";
}

?>
--EXPECT--
***String tests: All tests should fail***
***********************************************************************************************
START ***** TEST: string_abcX VALUE: 'abcX' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "88" chr: "X"'"
}
******
END ***** TEST: string_abcX VALUE: 'abcX' (string) ******


***********************************************************************************************
START ***** TEST: string_abc#  VALUE: 'abc# ' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
FALG(193) alpha+space   OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "35" chr: "#"'"
}
******
END ***** TEST: string_abc#  VALUE: 'abc# ' (string) ******


***********************************************************************************************
START ***** TEST: string_ abc  VALUE: ' abc 	' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(193) alpha+space   OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
END ***** TEST: string_ abc  VALUE: ' abc 	' (string) ******


***********************************************************************************************
START ***** TEST: string_ abc xyz  VALUE: ' abc xyz ' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
END ***** TEST: string_ abc xyz  VALUE: ' abc xyz ' (string) ******


***********************************************************************************************
START ***** TEST: string_123\n VALUE: '123
' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "\x0A"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "\x0A"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "\x0A"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(60) "param: 'ROOT' error: 'VALIDATE_STRING: Invalid LF detected.'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "\x0A"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "\x0A"'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "\x0A"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "10" chr: "\x0A"'"
}
******
END ***** TEST: string_123\n VALUE: '123
' (string) ******


***********************************************************************************************
START ***** TEST: string_123  VALUE: '123 ' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "51" chr: "3"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "51" chr: "3"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "51" chr: "3"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "51" chr: "3"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "51" chr: "3"'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "51" chr: "3"'"
}
******
FALG(512) mb             OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "51" chr: "3"'"
}
******
END ***** TEST: string_123  VALUE: '123 ' (string) ******


***********************************************************************************************
START ***** TEST: string_ 123  VALUE: ' 123 ' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
FALG(512) mb             OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "32" chr: " "'"
}
******
END ***** TEST: string_ 123  VALUE: ' 123 ' (string) ******


***********************************************************************************************
START ***** TEST: string_ 123 xyz  VALUE: ' 123 xyz ' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(82) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "120" chr: "x"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(82) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "120" chr: "x"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(82) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "120" chr: "x"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(82) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "120" chr: "x"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(82) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "120" chr: "x"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(82) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "120" chr: "x"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(82) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "120" chr: "x"'"
}
******
END ***** TEST: string_ 123 xyz  VALUE: ' 123 xyz ' (string) ******


***********************************************************************************************
START ***** TEST: string_日本 VALUE: '日本' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
END ***** TEST: string_日本 VALUE: '日本' (string) ******


***********************************************************************************************
START ***** TEST: string_tab VALUE: 'abc	xyz' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
FALG(512) mb             OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "9" chr: "\x09"'"
}
******
END ***** TEST: string_tab VALUE: 'abc	xyz' (string) ******


***********************************************************************************************
START ***** TEST: string_lf VALUE: 'abc
xyz
' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "97" chr: "a"'"
}
******
END ***** TEST: string_lf VALUE: 'abc
xyz
' (string) ******


***********************************************************************************************
START ***** TEST: string_cr VALUE: 'abcxyz' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(63) "param: 'ROOT' error: 'VALIDATE_STRING: Invalid CR/LF detected.'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
END ***** TEST: string_cr VALUE: 'abcxyz' (string) ******


***********************************************************************************************
START ***** TEST: string_crlf VALUE: 'abc
xyz
' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(84) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "13" chr: "\x0D"'"
}
******
END ***** TEST: string_crlf VALUE: 'abc
xyz
' (string) ******


***********************************************************************************************
START ***** TEST: string_lfcr VALUE: 'abc
xyz
' (string) ******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(60) "param: 'ROOT' error: 'VALIDATE_STRING: Invalid LF detected.'"
}
******
END ***** TEST: string_lfcr VALUE: 'abc
xyz
' (string) ******


***********************************************************************************************
START ***** TEST: string_cntrl VALUE: '\b abc' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(83) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "0" chr: "\x00"'"
}
******
END ***** TEST: string_cntrl VALUE: '\b abc' (string) ******


***********************************************************************************************
START ***** TEST: string_urf8broken VALUE: '��日本' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(192) alpha         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(194) alnum         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(64) "param: 'ROOT' error: 'VALIDATE_STRING: Multibyte char detected.'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(63) "param: 'ROOT' error: 'VALIDATE_STRING: Invalid UTF-8 encoding.'"
}
******
END ***** TEST: string_urf8broken VALUE: '��日本' (string) ******


***********************************************************************************************
START ***** TEST: string_spin_broken VALUE: 'abdZ867e3' (string) ******
FALG(0) none          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "90" chr: "Z"'"
}
******
FALG(4) tab           OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "90" chr: "Z"'"
}
******
FALG(8) lf            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "90" chr: "Z"'"
}
******
FALG(16) cr            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "90" chr: "Z"'"
}
******
FALG(24) crlf          OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "90" chr: "Z"'"
}
******
FALG(2) digit         OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "90" chr: "Z"'"
}
******
FALG(512) mb            OPT(0) RESULT: array(1) {
  ["ErrorMsg"]=>
  string(81) "param: 'ROOT' error: 'VALIDATE_STRING: Illegal char detected. ord: "90" chr: "Z"'"
}
******
END ***** TEST: string_spin_broken VALUE: 'abdZ867e3' (string) ******
