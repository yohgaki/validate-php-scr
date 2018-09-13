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

echo "**** VALIDATE_BOOL ****\n";
// Value to be validated.
$bool = 't';

// Remember these simple "input type specs" can be reused as "array of specs" easily.

// Example1 validation failure
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_NONE, // By default no text value is valid
    []
];

$result =validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation failure


// Example2 Successful validation
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_BOOL_TF, // Allow "t"/"f", "T"/"F" as valid bool
    []
];

$result =validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation success

// OO API one liner
$result = (new Validate)->validate($bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);



echo "\n**** VALIDATE_CALLBACK ****\n";
// Value to be validated.
$salt = 'abcd12345678abcdX'; //Wrong HEX

// Remember these simple "input type specs" can be reused as "array of specs" easily.

$callback_spec = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_DIGIT,
    [
        'min' => 8, 'max' => 32, 'ascii' => 'abcdef', 'callback' =>
        // Validate HEX and return binary. Useful with binary salt for CSRF protection with hash_hkdf() hash.
        // IMPORTANT: Since there is noway to check success/failure other than return value, return value
        //    must be true/false respectively. Update $input to return modified value.
        function ($ctx, &$result, $input) {
            assert(is_string($input));
            assert($ctx instanceof Validate);
            // When input is string min/max is already checked.

            if (strlen($input) % 2) {
                validate_error($ctx, 'Salt validation: HEX value must be even length.');
                return false;
            }
            $input = @hex2bin($input);
            if (!$input) {
                // hex2bin() error. Malformed HEX
                $input = null; // NULL is for unsuccessful validation.
                // Please do not forget to pass $options and $func_opts.
                validate_error($ctx, 'Salt validation: Malformed HEX value.');
                return false; // Please add "return false" after validate_error(). Errors may be logged only. i.e. No Exception/Error.
            }
            return true; // Return true for success.
        }
    ]
];


// Example1 validation failure
$result =validate($ctx, $salt, $callback_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation failure with wrong HEX


// Value to be validated.
$salt = 'abcd12345678abcd'; // Correct HEX

// Example2 Successful validation
$result =validate($ctx, $salt, $callback_spec);
var_dump($result, $ctx->getStatus());// Validation success

// OO API one liner
$result = (new Validate)->validate($salt, $callback_spec, VALIDATE_OPT_DISABLE_EXCEPTION);



echo "\n**** VALIDATE_FILTER ****\n";
// Value to be validated.
$bool = ' t';

// Remember these simple "input type specs" can be reused as "array of specs" easily.

// Example1 validation failure
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_BOOL_TF, // Allow "t"/"f", "T"/"F" as valid bool
    []
];


$result =validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation failure. ' '(space) is not allowed nor trimmed by default.


// Example2 Successful validation
$bool_spec = [
  VALIDATE_BOOL,
  VALIDATE_BOOL_TF, // Allow "t"/"f", "T"/"F" as valid bool
  ['filter' =>
  // Unlike "callback" which reports status by return value.
  // "filter" simply returns filtered value.
  function ($ctx, $input, &$error='') {
      assert($ctx instanceof Validate);
      // If there error, you may set $error message as "string".
      // Validate removes the offending element from result array. (Scalar cannot be removed).
      if (!is_string($input)) {
          $error = 'Input is not a string.';
      }
      $ret = trim($input);
      return $ret;
  }
  ]
];

$result =validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation success. ' '(spaces) are trimmed.

// OO API one liner
$result = (new Validate)->validate($bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);


echo "\n**** VALIDATE_FLOAT ****\n";
// Value to be validated.
$float = -123;

// Remember these simple "input type specs" can be reused as "array of specs" easily.

// Example1 validation failure
$float_spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => PHP_INT_MAX]
];

$result =validate($ctx, $float, $float_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation failure


// Example2 Successful validation
$float = '2134.234567';

$result =validate($ctx, $float, $float_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation success

// OO API one liner
$result = (new Validate)->validate($float, $float_spec, VALIDATE_OPT_DISABLE_EXCEPTION);



echo "\n**** VALIDATE_INT ****\n";
// Example1 validation failure
// Value to be validated.
$int = '-1234';

$int_spec = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => PHP_INT_MAX]
];

$result = validate($ctx, $int, $int_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->GetStatus()); // Validation failure


// Example2 validation success
$int = 123;

$result = validate($ctx, $int, $int_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation success

// OO API one liner
$result = (new Validate)->validate($int, $int_spec, VALIDATE_OPT_DISABLE_EXCEPTION);


echo "\n**** VALIDATE_REGEXP *****\n";

// Example1 validation failure
$str = 'test user';

$str_spec = [
    VALIDATE_REGEXP,
    VALIDATE_REGEXP_SPACE | VALIDATE_REGEXP_LOWER_ALPHA,
    ['min' => 1, 'max' => 120, 'regexp' => '/^[a-z]*$/']
];

$result =validate($ctx, $str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation failure


// Example2 Successful validation
$str_spec = [
    VALIDATE_REGEXP,
    VALIDATE_REGEXP_SPACE | VALIDATE_REGEXP_LOWER_ALPHA,
    ['min' => 1, 'max' => 120, 'regexp' => '/^[ a-z]*$/'] // Add space as valid char
];

$result =validate($ctx, $str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation success

// OO API one liner
$result = (new Validate)->validate($str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);



echo "\n**** VALIDATE_REJECT ****\n";
// Input that has 2 parameters
$inputs = [
    'float' => 123,
    'debug' => 'true',
];


// Parameter 1
$float = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => PHP_INT_MAX]
];

// Parameter 2
$debug = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_NONE,
    []
];


/**
 *  Sometimes you would like to reject input values
 * such as "debug", "test", etc.
 * Following function add reject flag to spec.
 */
function reject($spec)
{
    $spec[VALIDATE_FLAGS] = ($spec[VALIDATE_FLAGS] | VALIDATE_FLAG_REJECT);
    return $spec;
}

// Validation SPEC
$specs = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => 2],
    [
        'float' => $float,
        'debug' => reject($debug),
    ]
];

// Example1 validation failure
$result =validate($ctx, $inputs, $specs, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation failure by rejected parameter

// OO API one liner
$result = (new Validate)->validate($inputs, $specs, VALIDATE_OPT_DISABLE_EXCEPTION);


echo "\n**** VALIDATE_STRING ****\n";
// Value to be validated.
$str = "test user";

// Remember these simple "input type specs" can be reused as "array of specs" easily.

// Example1 validation failure
$str_spec = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE, // String validator does not allow any char by default
    ['min' => 1, 'max' => 120]
];

$result =validate($ctx, $str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation failure


// Example2 Successful validation
$str_spec = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_SPACE, // now allow lower alpha and space.
    ['min' => 1, 'max' => 120]
];

$result =validate($ctx, $str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // Validation success

// OO API one liner
$result = (new Validate)->validate($str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);


echo "\n**** VALIDATE_STRING - UTF-8 ****\n";
// Value to be validated.
$utf8 = '第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）';


// Example1 validation failure
$utf8_spec = [
    VALIDATE_STRING,
    VALIDATE_STRING_UPPER_ALPHA
    | VALIDATE_STRING_LOWER_ALPHA
    | VALIDATE_STRING_SPACE
    | VALIDATE_STRING_LF
    | VALIDATE_STRING_DIGIT, // String validator does not allow any char by default
    ['min' => 1, 'max' => 1000, 'ascii' => '-/'] // No encoding option
];

try {
    $result =validate($ctx, $utf8, $utf8_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
} catch (Exception $e) {
    var_dump($d->getMessage());
}
var_dump($result, $ctx->getStatus()); // Validation failure - Warning: String validation: Multibyte char detected.


echo "\n**** VALIDATE_BOOL - array of bools ****\n";
// Value to be validated.
$bool = ['t', 'f', true, false];

// Remember these simple "input type specs" can be reused as "array of specs" easily.

// Example1 validation failure
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_ARRAY, // By default no text value is valid
    ['amin' => 1, 'amax' =>4]
];

echo "**** test #1 *****\n";
$result = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
echo "\n** \$result **\n";
var_dump($ctx->getValidated());
echo "\n** \$status **\n";
var_dump($ctx->getStatus());
echo "\n** Validate object **\n";
var_dump($ctx);


// Example2 Successful validation
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF, // Allow "t"/"f", "T"/"F" as valid bool
    ['amin' => 1, 'amax' => 4]
];

echo "**** test #2 *****\n";
$result = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
echo "\n** \$result **\n";
var_dump($ctx->getValidated());
echo "\n** \$status **\n";
var_dump($ctx->getStatus());
echo "\n** Validate object **\n";
var_dump($ctx);


echo "\n**** VALIDATE_BOOL - multiple specs ****\n";

// Example1 Successful validation - AND condition
$bool_spec = [
    VALIDATE_MULTI,
    VALIDATE_MULTI_AND,
    [],
    [
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF, // By default no text value is valid
            ['amin' => 1, 'amax' =>4]
        ],
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF, // By default no text value is valid
            ['amin' => 1, 'amax' =>4]
        ],
    ]
];

// Value to be validated.
$bool = ['t', 'f', true, false];

echo "**** test #1 *****\n";
$result  = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_CHECK_SPEC);
echo "\n** \$result **\n";
var_dump($result);
echo "\n** \$status **\n";
var_dump($ctx->getStatus());
echo "\n** Validate object **\n";
var_dump($ctx);


// Example2 Successful validation
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF, // Allow "t"/"f", "T"/"F" as valid bool
    ['amin' => 1, 'amax' => 4]
];

// Value to be validated.
$bool = ['t', 'f', true, false];

echo "**** test #2 *****\n";
$result  = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
echo "\n** \$result **\n";
var_dump($result);
echo "\n** \$status **\n";
var_dump($ctx->getStatus());
echo "\n** Validate context **\n";
var_dump($ctx);




// Example3 Successful validation - AND condition
$bool_spec = [
    VALIDATE_MULTI,
    VALIDATE_MULTI_OR,
    [],
    [
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY, // By default no text value is valid
            ['amin' => 1, 'amax' =>4]
        ],
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF, // By default no text value is valid
            ['amin' => 1, 'amax' =>4]
        ],
    ]
];

// Value to be validated.
$bool = ['t', 'f', true, false];

echo "**** test #3 *****\n";
$result = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_CHECK_SPEC);
echo "\n** \$result **\n";
var_dump($result);
echo "\n** \$status **\n";
var_dump($ctx->getStatus());
echo "\n** Validate object **\n";
var_dump($ctx);

?>
--EXPECT--
**** VALIDATE_BOOL ****
NULL
bool(false)
bool(true)
bool(true)

**** VALIDATE_CALLBACK ****
NULL
bool(false)
string(16) "abcd12345678abcd"
bool(true)

**** VALIDATE_FILTER ****
NULL
bool(false)
bool(true)
bool(true)

**** VALIDATE_FLOAT ****
NULL
bool(false)
float(2134.234567)
bool(true)

**** VALIDATE_INT ****
NULL
bool(false)
int(123)
bool(true)

**** VALIDATE_REGEXP *****
NULL
bool(false)
string(9) "test user"
bool(true)

**** VALIDATE_REJECT ****
NULL
bool(false)

**** VALIDATE_STRING ****
NULL
bool(false)
string(9) "test user"
bool(true)

**** VALIDATE_STRING - UTF-8 ****
NULL
bool(false)

**** VALIDATE_BOOL - array of bools ****
**** test #1 *****

** $result **
array(4) {
  [0]=>
  NULL
  [1]=>
  NULL
  [2]=>
  bool(true)
  [3]=>
  bool(false)
}

** $status **
bool(false)

** Validate object **
object(Validate)#1 (17) {
  ["validate_params_checked"]=>
  bool(false)
  ["spec_params_checked"]=>
  bool(false)
  ["currentElem":"Validate":private]=>
  array(1) {
    [0]=>
    string(4) "ROOT"
  }
  ["context":"Validate":private]=>
  *RECURSION*
  ["context_vars":"Validate":private]=>
  array(6) {
    ["param"]=>
    string(5) "debug"
    ["orig_value"]=>
    array(4) {
      [0]=>
      string(1) "t"
      [1]=>
      string(1) "f"
      [2]=>
      bool(true)
      [3]=>
      bool(false)
    }
    ["defined"]=>
    bool(true)
    ["spec"]=>
    array(3) {
      [0]=>
      int(2)
      [1]=>
      int(8388608)
      [2]=>
      array(2) {
        ["amin"]=>
        int(1)
        ["amax"]=>
        int(4)
      }
    }
    ["func_opts"]=>
    int(2)
    ["value"]=>
    string(1) "f"
  }
  ["error_level":"Validate":private]=>
  int(256)
  ["errors":"Validate":private]=>
  array(2) {
    ["ROOT"]=>
    array(10) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(0)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        string(1) "t"
      }
      [1]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(60) "VALIDATE_CALLBACK: Illegal char detected. ord: "88" chr: "X""
        ["spec"]=>
        array(3) {
          [0]=>
          int(7)
          [1]=>
          int(2)
          [2]=>
          array(4) {
            ["min"]=>
            int(8)
            ["max"]=>
            int(32)
            ["ascii"]=>
            string(6) "abcdef"
            ["callback"]=>
            object(Closure)#3 (1) {
              ["parameter"]=>
              array(3) {
                ["$ctx"]=>
                string(10) "<required>"
                ["&$result"]=>
                string(10) "<required>"
                ["$input"]=>
                string(10) "<required>"
              }
            }
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(17) "abcd12345678abcdX"
        ["orig_value"]=>
        string(17) "abcd12345678abcdX"
      }
      [2]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(4)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(2) " t"
        ["orig_value"]=>
        string(2) " t"
      }
      [3]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(37) "VALIDATE_FLOAT: Invalid float format."
        ["spec"]=>
        array(3) {
          [0]=>
          int(4)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "-123"
        ["orig_value"]=>
        int(-123)
      }
      [4]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_INT: Invalid int string."
        ["spec"]=>
        array(3) {
          [0]=>
          int(3)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(5) "-1234"
        ["orig_value"]=>
        string(5) "-1234"
      }
      [5]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_REGEXP: Failed to match."
        ["spec"]=>
        array(3) {
          [0]=>
          int(8)
          [1]=>
          int(65)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
            ["regexp"]=>
            string(10) "/^[a-z]*$/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [6]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(59) "VALIDATE_STRING: Illegal char detected. ord: "116" chr: "t""
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [7]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(41) "VALIDATE_STRING: Multibyte char detected."
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(203)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(1000)
            ["ascii"]=>
            string(2) "-/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
        ["orig_value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
      }
      [8]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
      [9]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "f"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
    }
    ["debug"]=>
    array(1) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(2) {
          [0]=>
          string(4) "ROOT"
          [1]=>
          string(5) "debug"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(32) "VALIDATE_BOOL: Rejected by flag."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(131072)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "true"
        ["orig_value"]=>
        string(4) "true"
      }
    }
  }
  ["warnings":"Validate":private]=>
  array(0) {
  }
  ["notices":"Validate":private]=>
  array(0) {
  }
  ["userErrors":"Validate":private]=>
  array(0) {
  }
  ["userWarnings":"Validate":private]=>
  array(0) {
  }
  ["userNotices":"Validate":private]=>
  array(0) {
  }
  ["validated":"Validate":private]=>
  array(4) {
    [0]=>
    NULL
    [1]=>
    NULL
    [2]=>
    bool(true)
    [3]=>
    bool(false)
  }
  ["status":"Validate":private]=>
  bool(false)
  ["value_validation":"Validate":private]=>
  bool(true)
  ["loggerFunction":"Validate":private]=>
  NULL
  ["params_checked"]=>
  bool(true)
}
**** test #2 *****

** $result **
array(2) {
  [0]=>
  bool(true)
  [1]=>
  bool(false)
}

** $status **
bool(true)

** Validate object **
object(Validate)#1 (17) {
  ["validate_params_checked"]=>
  bool(false)
  ["spec_params_checked"]=>
  bool(false)
  ["currentElem":"Validate":private]=>
  array(1) {
    [0]=>
    string(4) "ROOT"
  }
  ["context":"Validate":private]=>
  *RECURSION*
  ["context_vars":"Validate":private]=>
  array(6) {
    ["param"]=>
    string(5) "debug"
    ["orig_value"]=>
    array(2) {
      [0]=>
      string(1) "t"
      [1]=>
      string(1) "f"
    }
    ["defined"]=>
    bool(true)
    ["spec"]=>
    array(3) {
      [0]=>
      int(2)
      [1]=>
      int(8388612)
      [2]=>
      array(2) {
        ["amin"]=>
        int(1)
        ["amax"]=>
        int(4)
      }
    }
    ["func_opts"]=>
    int(2)
    ["value"]=>
    array(2) {
      [0]=>
      string(1) "t"
      [1]=>
      string(1) "f"
    }
  }
  ["error_level":"Validate":private]=>
  int(256)
  ["errors":"Validate":private]=>
  array(2) {
    ["ROOT"]=>
    array(10) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(0)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        string(1) "t"
      }
      [1]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(60) "VALIDATE_CALLBACK: Illegal char detected. ord: "88" chr: "X""
        ["spec"]=>
        array(3) {
          [0]=>
          int(7)
          [1]=>
          int(2)
          [2]=>
          array(4) {
            ["min"]=>
            int(8)
            ["max"]=>
            int(32)
            ["ascii"]=>
            string(6) "abcdef"
            ["callback"]=>
            object(Closure)#3 (1) {
              ["parameter"]=>
              array(3) {
                ["$ctx"]=>
                string(10) "<required>"
                ["&$result"]=>
                string(10) "<required>"
                ["$input"]=>
                string(10) "<required>"
              }
            }
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(17) "abcd12345678abcdX"
        ["orig_value"]=>
        string(17) "abcd12345678abcdX"
      }
      [2]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(4)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(2) " t"
        ["orig_value"]=>
        string(2) " t"
      }
      [3]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(37) "VALIDATE_FLOAT: Invalid float format."
        ["spec"]=>
        array(3) {
          [0]=>
          int(4)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "-123"
        ["orig_value"]=>
        int(-123)
      }
      [4]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_INT: Invalid int string."
        ["spec"]=>
        array(3) {
          [0]=>
          int(3)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(5) "-1234"
        ["orig_value"]=>
        string(5) "-1234"
      }
      [5]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_REGEXP: Failed to match."
        ["spec"]=>
        array(3) {
          [0]=>
          int(8)
          [1]=>
          int(65)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
            ["regexp"]=>
            string(10) "/^[a-z]*$/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [6]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(59) "VALIDATE_STRING: Illegal char detected. ord: "116" chr: "t""
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [7]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(41) "VALIDATE_STRING: Multibyte char detected."
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(203)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(1000)
            ["ascii"]=>
            string(2) "-/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
        ["orig_value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
      }
      [8]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
      [9]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "f"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
    }
    ["debug"]=>
    array(1) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(2) {
          [0]=>
          string(4) "ROOT"
          [1]=>
          string(5) "debug"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(32) "VALIDATE_BOOL: Rejected by flag."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(131072)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "true"
        ["orig_value"]=>
        string(4) "true"
      }
    }
  }
  ["warnings":"Validate":private]=>
  array(0) {
  }
  ["notices":"Validate":private]=>
  array(0) {
  }
  ["userErrors":"Validate":private]=>
  array(0) {
  }
  ["userWarnings":"Validate":private]=>
  array(0) {
  }
  ["userNotices":"Validate":private]=>
  array(0) {
  }
  ["validated":"Validate":private]=>
  array(2) {
    [0]=>
    bool(true)
    [1]=>
    bool(false)
  }
  ["status":"Validate":private]=>
  bool(true)
  ["value_validation":"Validate":private]=>
  bool(true)
  ["loggerFunction":"Validate":private]=>
  NULL
  ["params_checked"]=>
  bool(true)
}

**** VALIDATE_BOOL - multiple specs ****
**** test #1 *****

** $result **
array(4) {
  [0]=>
  bool(true)
  [1]=>
  bool(false)
  [2]=>
  bool(true)
  [3]=>
  bool(false)
}

** $status **
bool(true)

** Validate object **
object(Validate)#1 (17) {
  ["validate_params_checked"]=>
  bool(false)
  ["spec_params_checked"]=>
  bool(false)
  ["currentElem":"Validate":private]=>
  array(1) {
    [0]=>
    string(4) "ROOT"
  }
  ["context":"Validate":private]=>
  *RECURSION*
  ["context_vars":"Validate":private]=>
  array(6) {
    ["param"]=>
    string(5) "debug"
    ["orig_value"]=>
    array(4) {
      [0]=>
      string(1) "t"
      [1]=>
      string(1) "f"
      [2]=>
      bool(true)
      [3]=>
      bool(false)
    }
    ["defined"]=>
    bool(true)
    ["spec"]=>
    array(3) {
      [0]=>
      int(2)
      [1]=>
      int(8388612)
      [2]=>
      array(2) {
        ["amin"]=>
        int(1)
        ["amax"]=>
        int(4)
      }
    }
    ["func_opts"]=>
    int(3)
    ["value"]=>
    array(4) {
      [0]=>
      string(1) "t"
      [1]=>
      string(1) "f"
      [2]=>
      bool(true)
      [3]=>
      bool(false)
    }
  }
  ["error_level":"Validate":private]=>
  int(256)
  ["errors":"Validate":private]=>
  array(2) {
    ["ROOT"]=>
    array(10) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(0)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        string(1) "t"
      }
      [1]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(60) "VALIDATE_CALLBACK: Illegal char detected. ord: "88" chr: "X""
        ["spec"]=>
        array(3) {
          [0]=>
          int(7)
          [1]=>
          int(2)
          [2]=>
          array(4) {
            ["min"]=>
            int(8)
            ["max"]=>
            int(32)
            ["ascii"]=>
            string(6) "abcdef"
            ["callback"]=>
            object(Closure)#3 (1) {
              ["parameter"]=>
              array(3) {
                ["$ctx"]=>
                string(10) "<required>"
                ["&$result"]=>
                string(10) "<required>"
                ["$input"]=>
                string(10) "<required>"
              }
            }
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(17) "abcd12345678abcdX"
        ["orig_value"]=>
        string(17) "abcd12345678abcdX"
      }
      [2]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(4)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(2) " t"
        ["orig_value"]=>
        string(2) " t"
      }
      [3]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(37) "VALIDATE_FLOAT: Invalid float format."
        ["spec"]=>
        array(3) {
          [0]=>
          int(4)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "-123"
        ["orig_value"]=>
        int(-123)
      }
      [4]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_INT: Invalid int string."
        ["spec"]=>
        array(3) {
          [0]=>
          int(3)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(5) "-1234"
        ["orig_value"]=>
        string(5) "-1234"
      }
      [5]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_REGEXP: Failed to match."
        ["spec"]=>
        array(3) {
          [0]=>
          int(8)
          [1]=>
          int(65)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
            ["regexp"]=>
            string(10) "/^[a-z]*$/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [6]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(59) "VALIDATE_STRING: Illegal char detected. ord: "116" chr: "t""
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [7]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(41) "VALIDATE_STRING: Multibyte char detected."
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(203)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(1000)
            ["ascii"]=>
            string(2) "-/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
        ["orig_value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
      }
      [8]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
      [9]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "f"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
    }
    ["debug"]=>
    array(1) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(2) {
          [0]=>
          string(4) "ROOT"
          [1]=>
          string(5) "debug"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(32) "VALIDATE_BOOL: Rejected by flag."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(131072)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "true"
        ["orig_value"]=>
        string(4) "true"
      }
    }
  }
  ["warnings":"Validate":private]=>
  array(0) {
  }
  ["notices":"Validate":private]=>
  array(0) {
  }
  ["userErrors":"Validate":private]=>
  array(0) {
  }
  ["userWarnings":"Validate":private]=>
  array(0) {
  }
  ["userNotices":"Validate":private]=>
  array(0) {
  }
  ["validated":"Validate":private]=>
  array(4) {
    [0]=>
    bool(true)
    [1]=>
    bool(false)
    [2]=>
    bool(true)
    [3]=>
    bool(false)
  }
  ["status":"Validate":private]=>
  bool(true)
  ["value_validation":"Validate":private]=>
  bool(true)
  ["loggerFunction":"Validate":private]=>
  NULL
  ["params_checked"]=>
  bool(true)
}
**** test #2 *****

** $result **
array(4) {
  [0]=>
  bool(true)
  [1]=>
  bool(false)
  [2]=>
  bool(true)
  [3]=>
  bool(false)
}

** $status **
bool(true)

** Validate context **
object(Validate)#1 (17) {
  ["validate_params_checked"]=>
  bool(false)
  ["spec_params_checked"]=>
  bool(false)
  ["currentElem":"Validate":private]=>
  array(1) {
    [0]=>
    string(4) "ROOT"
  }
  ["context":"Validate":private]=>
  *RECURSION*
  ["context_vars":"Validate":private]=>
  array(6) {
    ["param"]=>
    string(5) "debug"
    ["orig_value"]=>
    array(4) {
      [0]=>
      string(1) "t"
      [1]=>
      string(1) "f"
      [2]=>
      bool(true)
      [3]=>
      bool(false)
    }
    ["defined"]=>
    bool(true)
    ["spec"]=>
    array(3) {
      [0]=>
      int(2)
      [1]=>
      int(8388612)
      [2]=>
      array(2) {
        ["amin"]=>
        int(1)
        ["amax"]=>
        int(4)
      }
    }
    ["func_opts"]=>
    int(2)
    ["value"]=>
    array(4) {
      [0]=>
      string(1) "t"
      [1]=>
      string(1) "f"
      [2]=>
      bool(true)
      [3]=>
      bool(false)
    }
  }
  ["error_level":"Validate":private]=>
  int(256)
  ["errors":"Validate":private]=>
  array(2) {
    ["ROOT"]=>
    array(10) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(0)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        string(1) "t"
      }
      [1]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(60) "VALIDATE_CALLBACK: Illegal char detected. ord: "88" chr: "X""
        ["spec"]=>
        array(3) {
          [0]=>
          int(7)
          [1]=>
          int(2)
          [2]=>
          array(4) {
            ["min"]=>
            int(8)
            ["max"]=>
            int(32)
            ["ascii"]=>
            string(6) "abcdef"
            ["callback"]=>
            object(Closure)#3 (1) {
              ["parameter"]=>
              array(3) {
                ["$ctx"]=>
                string(10) "<required>"
                ["&$result"]=>
                string(10) "<required>"
                ["$input"]=>
                string(10) "<required>"
              }
            }
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(17) "abcd12345678abcdX"
        ["orig_value"]=>
        string(17) "abcd12345678abcdX"
      }
      [2]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(4)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(2) " t"
        ["orig_value"]=>
        string(2) " t"
      }
      [3]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(37) "VALIDATE_FLOAT: Invalid float format."
        ["spec"]=>
        array(3) {
          [0]=>
          int(4)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "-123"
        ["orig_value"]=>
        int(-123)
      }
      [4]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_INT: Invalid int string."
        ["spec"]=>
        array(3) {
          [0]=>
          int(3)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(5) "-1234"
        ["orig_value"]=>
        string(5) "-1234"
      }
      [5]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_REGEXP: Failed to match."
        ["spec"]=>
        array(3) {
          [0]=>
          int(8)
          [1]=>
          int(65)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
            ["regexp"]=>
            string(10) "/^[a-z]*$/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [6]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(59) "VALIDATE_STRING: Illegal char detected. ord: "116" chr: "t""
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [7]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(41) "VALIDATE_STRING: Multibyte char detected."
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(203)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(1000)
            ["ascii"]=>
            string(2) "-/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
        ["orig_value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
      }
      [8]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
      [9]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "f"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
    }
    ["debug"]=>
    array(1) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(2) {
          [0]=>
          string(4) "ROOT"
          [1]=>
          string(5) "debug"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(32) "VALIDATE_BOOL: Rejected by flag."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(131072)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "true"
        ["orig_value"]=>
        string(4) "true"
      }
    }
  }
  ["warnings":"Validate":private]=>
  array(0) {
  }
  ["notices":"Validate":private]=>
  array(0) {
  }
  ["userErrors":"Validate":private]=>
  array(0) {
  }
  ["userWarnings":"Validate":private]=>
  array(0) {
  }
  ["userNotices":"Validate":private]=>
  array(0) {
  }
  ["validated":"Validate":private]=>
  array(4) {
    [0]=>
    bool(true)
    [1]=>
    bool(false)
    [2]=>
    bool(true)
    [3]=>
    bool(false)
  }
  ["status":"Validate":private]=>
  bool(true)
  ["value_validation":"Validate":private]=>
  bool(true)
  ["loggerFunction":"Validate":private]=>
  NULL
  ["params_checked"]=>
  bool(true)
}
**** test #3 *****

** $result **
array(4) {
  [0]=>
  bool(true)
  [1]=>
  bool(false)
  [2]=>
  bool(true)
  [3]=>
  bool(false)
}

** $status **
bool(true)

** Validate object **
object(Validate)#1 (17) {
  ["validate_params_checked"]=>
  bool(false)
  ["spec_params_checked"]=>
  bool(false)
  ["currentElem":"Validate":private]=>
  array(1) {
    [0]=>
    string(4) "ROOT"
  }
  ["context":"Validate":private]=>
  *RECURSION*
  ["context_vars":"Validate":private]=>
  array(6) {
    ["param"]=>
    string(5) "debug"
    ["orig_value"]=>
    array(4) {
      [0]=>
      string(1) "t"
      [1]=>
      string(1) "f"
      [2]=>
      bool(true)
      [3]=>
      bool(false)
    }
    ["defined"]=>
    bool(true)
    ["spec"]=>
    array(3) {
      [0]=>
      int(2)
      [1]=>
      int(276824068)
      [2]=>
      array(2) {
        ["amin"]=>
        int(1)
        ["amax"]=>
        int(4)
      }
    }
    ["func_opts"]=>
    int(3)
    ["value"]=>
    array(4) {
      [0]=>
      string(1) "t"
      [1]=>
      string(1) "f"
      [2]=>
      bool(true)
      [3]=>
      bool(false)
    }
  }
  ["error_level":"Validate":private]=>
  int(256)
  ["errors":"Validate":private]=>
  array(2) {
    ["ROOT"]=>
    array(12) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(0)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        string(1) "t"
      }
      [1]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(60) "VALIDATE_CALLBACK: Illegal char detected. ord: "88" chr: "X""
        ["spec"]=>
        array(3) {
          [0]=>
          int(7)
          [1]=>
          int(2)
          [2]=>
          array(4) {
            ["min"]=>
            int(8)
            ["max"]=>
            int(32)
            ["ascii"]=>
            string(6) "abcdef"
            ["callback"]=>
            object(Closure)#3 (1) {
              ["parameter"]=>
              array(3) {
                ["$ctx"]=>
                string(10) "<required>"
                ["&$result"]=>
                string(10) "<required>"
                ["$input"]=>
                string(10) "<required>"
              }
            }
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(17) "abcd12345678abcdX"
        ["orig_value"]=>
        string(17) "abcd12345678abcdX"
      }
      [2]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(4)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(2) " t"
        ["orig_value"]=>
        string(2) " t"
      }
      [3]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(37) "VALIDATE_FLOAT: Invalid float format."
        ["spec"]=>
        array(3) {
          [0]=>
          int(4)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "-123"
        ["orig_value"]=>
        int(-123)
      }
      [4]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_INT: Invalid int string."
        ["spec"]=>
        array(3) {
          [0]=>
          int(3)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(9223372036854775807)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(5) "-1234"
        ["orig_value"]=>
        string(5) "-1234"
      }
      [5]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(33) "VALIDATE_REGEXP: Failed to match."
        ["spec"]=>
        array(3) {
          [0]=>
          int(8)
          [1]=>
          int(65)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
            ["regexp"]=>
            string(10) "/^[a-z]*$/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [6]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(59) "VALIDATE_STRING: Illegal char detected. ord: "116" chr: "t""
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(0)
          [2]=>
          array(2) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(120)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(9) "test user"
        ["orig_value"]=>
        string(9) "test user"
      }
      [7]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(41) "VALIDATE_STRING: Multibyte char detected."
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(203)
          [2]=>
          array(3) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(1000)
            ["ascii"]=>
            string(2) "-/"
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
        ["orig_value"]=>
        string(921) "第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）"
      }
      [8]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
      [9]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(8388608)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(1) "f"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
      [10]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(276824064)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(3)
        ["value"]=>
        string(1) "t"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
      [11]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(28) "VALIDATE_BOOL: Invalid bool."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(276824064)
          [2]=>
          array(2) {
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(4)
          }
        }
        ["func_opts"]=>
        int(3)
        ["value"]=>
        string(1) "f"
        ["orig_value"]=>
        array(4) {
          [0]=>
          string(1) "t"
          [1]=>
          string(1) "f"
          [2]=>
          bool(true)
          [3]=>
          bool(false)
        }
      }
    }
    ["debug"]=>
    array(1) {
      [0]=>
      array(8) {
        ["type"]=>
        int(1)
        ["param"]=>
        array(2) {
          [0]=>
          string(4) "ROOT"
          [1]=>
          string(5) "debug"
        }
        ["defined"]=>
        bool(true)
        ["message"]=>
        string(32) "VALIDATE_BOOL: Rejected by flag."
        ["spec"]=>
        array(3) {
          [0]=>
          int(2)
          [1]=>
          int(131072)
          [2]=>
          array(0) {
          }
        }
        ["func_opts"]=>
        int(2)
        ["value"]=>
        string(4) "true"
        ["orig_value"]=>
        string(4) "true"
      }
    }
  }
  ["warnings":"Validate":private]=>
  array(0) {
  }
  ["notices":"Validate":private]=>
  array(0) {
  }
  ["userErrors":"Validate":private]=>
  array(0) {
  }
  ["userWarnings":"Validate":private]=>
  array(0) {
  }
  ["userNotices":"Validate":private]=>
  array(0) {
  }
  ["validated":"Validate":private]=>
  array(4) {
    [0]=>
    bool(true)
    [1]=>
    bool(false)
    [2]=>
    bool(true)
    [3]=>
    bool(false)
  }
  ["status":"Validate":private]=>
  bool(true)
  ["value_validation":"Validate":private]=>
  bool(true)
  ["loggerFunction":"Validate":private]=>
  NULL
  ["params_checked"]=>
  bool(true)
}
