--TEST--
Test basic validate module features
	All Test cases should pass
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

$test_cases = array(

	'string_abc' => array(
		'abc', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
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
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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

	'string_abc ' => array(
		'abc ', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'alpha+space    ' => VALIDATE_STRING_ALPHA | VALIDATE_STRING_SPACE,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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
		' abc ', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
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
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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
			'binary       ' => VALIDATE_STRING_BINARY,
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
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => ' abcxyz',
				),
			),
		),
	),

	'string_123' => array(
		'123', // test string
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
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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
			'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'digit+space  ' => VALIDATE_STRING_DIGIT | VALIDATE_STRING_SPACE,
			'alnum+space  ' => VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => ' abcdef0123456789',
				),
			),
		),
	),

	'string_ 123 ' => array(
		' 123 ', // test string
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
			'digit+space  ' => VALIDATE_STRING_DIGIT | VALIDATE_STRING_SPACE,
			'alnum+space  ' => VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE,
			'mb            ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => ' abcdef0123456789',
				),
			),
		),
	),

	'string_ 123 xyz ' => array(
		' 123 xyz ', // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum+space  ' => VALIDATE_STRING_ALNUM | VALIDATE_STRING_SPACE,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => ' xyzabcdef0123456789',
				),
			),
		),
	),

	'string_日本' => array(
		'日本', // test string
		array( // test flags
			// 'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			// 'tab          ' => VALIDATE_STRING_TAB,
			// 'lf           ' => VALIDATE_STRING_LF,
			// 'cr           ' => VALIDATE_STRING_CR,
			// 'crlf         ' => VALIDATE_STRING_CRLF,
			// 'alpha        ' => VALIDATE_STRING_ALPHA,
			// 'digit        ' => VALIDATE_STRING_DIGIT,
			// 'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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
			'binary       ' => VALIDATE_STRING_BINARY,
			'tab+space    ' => VALIDATE_STRING_TAB | VALIDATE_STRING_SPACE,
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
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyzabcdef0123456789\t",
				),
			),
		),
	),

	'string_lf'  => array(
		"abc\nxyz\n", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf+lower     ' => VALIDATE_STRING_LF | VALIDATE_STRING_LOWER_ALPHA,
			// 'cr           ' => VALIDATE_STRING_CR,
			// 'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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

	'string_cr'  => array(
		"abc\rxyz\r", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			// 'lf           ' => VALIDATE_STRING_LF,
			'cr+alpha     ' => VALIDATE_STRING_CR | VALIDATE_STRING_ALPHA,
			// 'cr           ' => VALIDATE_STRING_CR,
			// 'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyzabcdef0123456789\r",
				),
			),
		),
	),

	'string_crlf'  => array(
		"abc\r\nxyz\r\n", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			// 'lf           ' => VALIDATE_STRING_LF,
			// 'cr           ' => VALIDATE_STRING_CR,
			'crlf+alpha   ' => VALIDATE_STRING_CRLF | VALIDATE_STRING_ALPHA,
			'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyzabcdef0123456789\r\n",
				),
			),
		),
	),

	'string_lfcr'  => array(
		"abc\n\rxyz\n\r", // test string
		array( // test flags
			'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			'tab          ' => VALIDATE_STRING_TAB,
			'lf           ' => VALIDATE_STRING_LF,
			'cr           ' => VALIDATE_STRING_CR,
			//'crlf         ' => VALIDATE_STRING_CRLF,
			'alpha        ' => VALIDATE_STRING_ALPHA,
			'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
		),
		array( // test spec
			array(
				VALIDATE_STRING, // 1st: Validator ID
				NULL, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => "xyzabcdef0123456789\0\b",
				),
			),
		),
	),

	'string_urf8broken' => array(
		"\xF0\xF0日本", // test string
		array( // test flags
			// 'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			// 'tab          ' => VALIDATE_STRING_TAB,
			// 'lf           ' => VALIDATE_STRING_LF,
			// 'cr           ' => VALIDATE_STRING_CR,
			// 'crlf         ' => VALIDATE_STRING_CRLF,
			// 'alpha        ' => VALIDATE_STRING_ALPHA,
			// 'digit        ' => VALIDATE_STRING_DIGIT,
			// 'alnum        ' => VALIDATE_STRING_ALNUM,
			// 'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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

	'string_spin_broken' => array(
		"abdZ867e3", // test string
		array( // test flags
			// 'none         ' => VALIDATE_FLAG_NONE,
			'binary       ' => VALIDATE_STRING_BINARY,
			// 'tab          ' => VALIDATE_STRING_TAB,
			// 'lf           ' => VALIDATE_STRING_LF,
			// 'cr           ' => VALIDATE_STRING_CR,
			// 'crlf         ' => VALIDATE_STRING_CRLF,
			// 'alpha        ' => VALIDATE_STRING_ALPHA,
			// 'digit        ' => VALIDATE_STRING_DIGIT,
			'alnum        ' => VALIDATE_STRING_ALNUM,
			// 'mb           ' => VALIDATE_STRING_MB,
		),
		array( // test func options
			0,
			VALIDATE_OPT_RAISE_ERROR,
			VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_RAISE_ERROR,
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



echo "***String tests: All tests should pass***\n";
foreach($test_cases as $test_name => $topts) {
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
				//die();
			} catch (Exception $e) {
				var_dump(['ErrorMsg' => $e->getMessage()]);
			}
			if ($ctx->getStatus() !== true) {
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
***String tests: All tests should pass***
START ***** TEST: string_abc VALUE: 'abc' (string) ******
FALG(0) none          OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(0) none          OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(0) none          OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(4) tab           OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(4) tab           OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(8) lf            OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(8) lf            OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(16) cr            OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(16) cr            OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(193) alpha+space   OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(193) alpha+space   OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(193) alpha+space   OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(2) digit         OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(2) digit         OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(3) "abc"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(3) "abc"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(3) "abc"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(3) "abc"
bool(true)
******
END ***** TEST: string_abc VALUE: 'abc' (string) ******


START ***** TEST: string_abc  VALUE: 'abc ' (string) ******
FALG(0) none          OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(0) none          OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(0) none          OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(4) tab           OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(4) tab           OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(8) lf            OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(8) lf            OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(16) cr            OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(16) cr            OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(193) alpha+space     OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(193) alpha+space     OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(193) alpha+space     OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(2) digit         OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(2) digit         OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(4) "abc "
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(4) "abc "
bool(true)
FALG(512) mb            OPT(16) RESULT: string(4) "abc "
bool(true)
FALG(512) mb            OPT(18) RESULT: string(4) "abc "
bool(true)
******
END ***** TEST: string_abc  VALUE: 'abc ' (string) ******


START ***** TEST: string_ abc  VALUE: ' abc ' (string) ******
FALG(0) none          OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(0) none          OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(0) none          OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(4) tab           OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(4) tab           OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(8) lf            OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(8) lf            OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(16) cr            OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(16) cr            OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(193) alpha+space   OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(193) alpha+space   OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(193) alpha+space   OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(2) digit         OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(2) digit         OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(5) " abc "
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(5) " abc "
bool(true)
FALG(512) mb            OPT(16) RESULT: string(5) " abc "
bool(true)
FALG(512) mb            OPT(18) RESULT: string(5) " abc "
bool(true)
******
END ***** TEST: string_ abc  VALUE: ' abc ' (string) ******


START ***** TEST: string_ abc xyz  VALUE: ' abc xyz ' (string) ******
FALG(0) none          OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(0) none          OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(0) none          OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(4) tab           OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(4) tab           OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(8) lf            OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(8) lf            OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(16) cr            OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(16) cr            OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(193) alpha+space   OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(193) alpha+space   OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(193) alpha+space   OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(2) digit         OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(2) digit         OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(9) " abc xyz "
bool(true)
FALG(512) mb            OPT(16) RESULT: string(9) " abc xyz "
bool(true)
FALG(512) mb            OPT(18) RESULT: string(9) " abc xyz "
bool(true)
******
END ***** TEST: string_ abc xyz  VALUE: ' abc xyz ' (string) ******


START ***** TEST: string_123 VALUE: '123' (string) ******
FALG(0) none          OPT(0) RESULT: string(3) "123"
bool(true)
FALG(0) none          OPT(16) RESULT: string(3) "123"
bool(true)
FALG(0) none          OPT(18) RESULT: string(3) "123"
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(3) "123"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(3) "123"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(3) "123"
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(3) "123"
bool(true)
FALG(4) tab           OPT(16) RESULT: string(3) "123"
bool(true)
FALG(4) tab           OPT(18) RESULT: string(3) "123"
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(3) "123"
bool(true)
FALG(8) lf            OPT(16) RESULT: string(3) "123"
bool(true)
FALG(8) lf            OPT(18) RESULT: string(3) "123"
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(3) "123"
bool(true)
FALG(16) cr            OPT(16) RESULT: string(3) "123"
bool(true)
FALG(16) cr            OPT(18) RESULT: string(3) "123"
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(3) "123"
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(3) "123"
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(3) "123"
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(3) "123"
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(3) "123"
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(3) "123"
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(3) "123"
bool(true)
FALG(2) digit         OPT(16) RESULT: string(3) "123"
bool(true)
FALG(2) digit         OPT(18) RESULT: string(3) "123"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(3) "123"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(3) "123"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(3) "123"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(3) "123"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(3) "123"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(3) "123"
bool(true)
******
END ***** TEST: string_123 VALUE: '123' (string) ******


START ***** TEST: string_123  VALUE: '123 ' (string) ******
FALG(0) none          OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(0) none          OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(0) none          OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(4) tab           OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(4) tab           OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(8) lf            OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(8) lf            OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(16) cr            OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(16) cr            OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(2) digit         OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(2) digit         OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(3) digit+space   OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(3) digit+space   OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(3) digit+space   OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(195) alnum+space   OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(195) alnum+space   OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(195) alnum+space   OPT(18) RESULT: string(4) "123 "
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(4) "123 "
bool(true)
FALG(512) mb            OPT(16) RESULT: string(4) "123 "
bool(true)
FALG(512) mb            OPT(18) RESULT: string(4) "123 "
bool(true)
******
END ***** TEST: string_123  VALUE: '123 ' (string) ******


START ***** TEST: string_ 123  VALUE: ' 123 ' (string) ******
FALG(0) none          OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(0) none          OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(0) none          OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(4) tab           OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(4) tab           OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(8) lf            OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(8) lf            OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(16) cr            OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(16) cr            OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(2) digit         OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(2) digit         OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(3) digit+space   OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(3) digit+space   OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(3) digit+space   OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(195) alnum+space   OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(195) alnum+space   OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(195) alnum+space   OPT(18) RESULT: string(5) " 123 "
bool(true)
******
FALG(512) mb             OPT(0) RESULT: string(5) " 123 "
bool(true)
FALG(512) mb             OPT(16) RESULT: string(5) " 123 "
bool(true)
FALG(512) mb             OPT(18) RESULT: string(5) " 123 "
bool(true)
******
END ***** TEST: string_ 123  VALUE: ' 123 ' (string) ******


START ***** TEST: string_ 123 xyz  VALUE: ' 123 xyz ' (string) ******
FALG(0) none          OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(0) none          OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(0) none          OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(4) tab           OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(4) tab           OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(8) lf            OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(8) lf            OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(16) cr            OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(16) cr            OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(2) digit         OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(2) digit         OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(195) alnum+space   OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(195) alnum+space   OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(195) alnum+space   OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(9) " 123 xyz "
bool(true)
FALG(512) mb            OPT(16) RESULT: string(9) " 123 xyz "
bool(true)
FALG(512) mb            OPT(18) RESULT: string(9) " 123 xyz "
bool(true)
******
END ***** TEST: string_ 123 xyz  VALUE: ' 123 xyz ' (string) ******


START ***** TEST: string_日本 VALUE: '日本' (string) ******
FALG(1024) binary        OPT(0) RESULT: string(6) "日本"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(6) "日本"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(6) "日本"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(6) "日本"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(6) "日本"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(6) "日本"
bool(true)
******
END ***** TEST: string_日本 VALUE: '日本' (string) ******


START ***** TEST: string_tab VALUE: 'abc	xyz' (string) ******
FALG(0) none          OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(0) none          OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(0) none          OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
FALG(5) tab+space     OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(5) tab+space     OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(5) tab+space     OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(8) lf            OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(8) lf            OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(16) cr            OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(16) cr            OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(2) digit         OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(2) digit         OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(7) "abc	xyz"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(7) "abc	xyz"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(7) "abc	xyz"
bool(true)
******
END ***** TEST: string_tab VALUE: 'abc	xyz' (string) ******


START ***** TEST: string_lf VALUE: 'abc
xyz
' (string) ******
FALG(0) none          OPT(0) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(0) none          OPT(16) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(0) none          OPT(18) RESULT: string(8) "abc
xyz
"
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(8) "abc
xyz
"
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(4) tab           OPT(16) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(4) tab           OPT(18) RESULT: string(8) "abc
xyz
"
bool(true)
******
FALG(72) lf+lower      OPT(0) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(72) lf+lower      OPT(16) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(72) lf+lower      OPT(18) RESULT: string(8) "abc
xyz
"
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(8) "abc
xyz
"
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(2) digit         OPT(16) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(2) digit         OPT(18) RESULT: string(8) "abc
xyz
"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(8) "abc
xyz
"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(8) "abc
xyz
"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(8) "abc
xyz
"
bool(true)
******
END ***** TEST: string_lf VALUE: 'abc
xyz
' (string) ******


START ***** TEST: string_cr VALUE: 'abcxyz' (string) ******
FALG(0) none          OPT(0) RESULT: string(8) "abcxyz"
bool(true)
FALG(0) none          OPT(16) RESULT: string(8) "abcxyz"
bool(true)
FALG(0) none          OPT(18) RESULT: string(8) "abcxyz"
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(8) "abcxyz"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(8) "abcxyz"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(8) "abcxyz"
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(8) "abcxyz"
bool(true)
FALG(4) tab           OPT(16) RESULT: string(8) "abcxyz"
bool(true)
FALG(4) tab           OPT(18) RESULT: string(8) "abcxyz"
bool(true)
******
FALG(208) cr+alpha      OPT(0) RESULT: string(8) "abcxyz"
bool(true)
FALG(208) cr+alpha      OPT(16) RESULT: string(8) "abcxyz"
bool(true)
FALG(208) cr+alpha      OPT(18) RESULT: string(8) "abcxyz"
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(8) "abcxyz"
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(8) "abcxyz"
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(8) "abcxyz"
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(8) "abcxyz"
bool(true)
FALG(2) digit         OPT(16) RESULT: string(8) "abcxyz"
bool(true)
FALG(2) digit         OPT(18) RESULT: string(8) "abcxyz"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(8) "abcxyz"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(8) "abcxyz"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(8) "abcxyz"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(8) "abcxyz"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(8) "abcxyz"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(8) "abcxyz"
bool(true)
******
END ***** TEST: string_cr VALUE: 'abcxyz' (string) ******


START ***** TEST: string_crlf VALUE: 'abc
xyz
' (string) ******
FALG(0) none          OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(0) none          OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(0) none          OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(4) tab           OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(4) tab           OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(216) crlf+alpha    OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(216) crlf+alpha    OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(216) crlf+alpha    OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(2) digit         OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(2) digit         OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
END ***** TEST: string_crlf VALUE: 'abc
xyz
' (string) ******


START ***** TEST: string_lfcr VALUE: 'abc
xyz
' (string) ******
FALG(0) none          OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(0) none          OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(0) none          OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(4) tab           OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(4) tab           OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(8) lf            OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(8) lf            OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(16) cr            OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(16) cr            OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(2) digit         OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(2) digit         OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(10) "abc
xyz
"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(10) "abc
xyz
"
bool(true)
******
END ***** TEST: string_lfcr VALUE: 'abc
xyz
' (string) ******


START ***** TEST: string_cntrl VALUE: '\b abc' (string) ******
FALG(0) none          OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(0) none          OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(0) none          OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(4) tab           OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(4) tab           OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(8) lf            OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(8) lf            OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(16) cr            OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(16) cr            OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(2) digit         OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(2) digit         OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(6) "\b abc"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(6) "\b abc"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(6) "\b abc"
bool(true)
******
END ***** TEST: string_cntrl VALUE: '\b abc' (string) ******


START ***** TEST: string_urf8broken VALUE: '��日本' (string) ******
FALG(1024) binary        OPT(0) RESULT: string(8) "��日本"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(8) "��日本"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(8) "��日本"
bool(true)
******
END ***** TEST: string_urf8broken VALUE: '��日本' (string) ******


START ***** TEST: string_spin_hex VALUE: 'a0b0d8e3' (string) ******
FALG(0) none          OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(0) none          OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(0) none          OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
FALG(1024) binary        OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
FALG(4) tab           OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(4) tab           OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(4) tab           OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
FALG(8) lf            OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(8) lf            OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(8) lf            OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
FALG(16) cr            OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(16) cr            OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(16) cr            OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
FALG(24) crlf          OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(24) crlf          OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(24) crlf          OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
FALG(192) alpha         OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(192) alpha         OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(192) alpha         OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
FALG(2) digit         OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(2) digit         OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(2) digit         OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
FALG(512) mb            OPT(0) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(512) mb            OPT(16) RESULT: string(8) "a0b0d8e3"
bool(true)
FALG(512) mb            OPT(18) RESULT: string(8) "a0b0d8e3"
bool(true)
******
END ***** TEST: string_spin_hex VALUE: 'a0b0d8e3' (string) ******


START ***** TEST: string_spin_broken VALUE: 'abdZ867e3' (string) ******
FALG(1024) binary        OPT(0) RESULT: string(9) "abdZ867e3"
bool(true)
FALG(1024) binary        OPT(16) RESULT: string(9) "abdZ867e3"
bool(true)
FALG(1024) binary        OPT(18) RESULT: string(9) "abdZ867e3"
bool(true)
******
FALG(194) alnum         OPT(0) RESULT: string(9) "abdZ867e3"
bool(true)
FALG(194) alnum         OPT(16) RESULT: string(9) "abdZ867e3"
bool(true)
FALG(194) alnum         OPT(18) RESULT: string(9) "abdZ867e3"
bool(true)
******
END ***** TEST: string_spin_broken VALUE: 'abdZ867e3' (string) ******
