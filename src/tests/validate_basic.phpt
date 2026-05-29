--TEST--
validate() core features — happy-path examples for every validator type
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

ob_start(function($output) {
    return preg_replace(
        '/object\(Closure\)#(\d+) \(\d+\) \{\n(?:[ ]+\["(?:name|file|line)"\]=>\n[ ]+[^\n]+\n){3}/',
        "object(Closure)#$1 (1) {\n",
        $output
    );
});

echo "**** VALIDATE_BOOL ****\n";
// Input under test.
$bool = 't';

// Per-field specs like the ones below can be nested inside an array spec to
// validate whole structures — see the VALIDATE_REJECT block further down.

// Example 1 — failure: by default VALIDATE_BOOL only accepts true/false, no text forms.
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_NONE,
    []
];

$result =validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // false / failure.


// Example 2 — success: opt in to the "t"/"f" form (matches "T"/"F" too).
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_BOOL_TF,
    []
];

$result =validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // true / success.

// Equivalent OO one-liner (skips $ctx; throw on failure unless the OPT flag is set).
$result = (new Validate)->validate($bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);



echo "\n**** VALIDATE_CALLBACK ****\n";
// Input under test — malformed HEX (odd length, plus the trailing 'X' is non-hex).
$salt = 'abcd12345678abcdX';

// VALIDATE_CALLBACK lets you write the rule in plain PHP. The engine still
// runs the flag whitelist (DIGIT) and the 'ascii' option ('abcdef') first,
// so the callback only sees inputs whose characters are already accepted.

$callback_spec = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_DIGIT,
    [
        'min' => 8, 'max' => 32, 'ascii' => 'abcdef', 'callback' =>
        // Validate HEX and return its binary form. Handy for binary salts /
        // CSRF tokens consumed by hash_hkdf().
        //
        // Callback contract:
        //   - Return true  on success; the engine takes the value from $result.
        //   - Return false on failure; report a user message via validate_error()
        //     and ALWAYS pair the call with `return false` — validate_error()
        //     does not abort the callback on its own.
        function ($ctx, &$result, $input) {
            assert(is_string($input));
            assert($ctx instanceof Validate);
            // Length is already inside min/max here; we only need to check parity.

            if (strlen($input) % 2) {
                validate_error($ctx, 'Salt validation: HEX value must be even length.');
                return false;
            }
            $input = @hex2bin($input);
            if (!$input) {
                // hex2bin() returned false — malformed HEX. Clear $result and bail.
                $input = null;
                validate_error($ctx, 'Salt validation: Malformed HEX value.');
                return false;
            }
            return true;
        }
    ]
];


// Example 1 — failure: callback rejects the malformed input.
$result =validate($ctx, $salt, $callback_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus());


// Input under test — valid HEX of an acceptable length.
$salt = 'abcd12345678abcd';

// Example 2 — success.
$result =validate($ctx, $salt, $callback_spec);
var_dump($result, $ctx->getStatus());

// Equivalent OO one-liner.
$result = (new Validate)->validate($salt, $callback_spec, VALIDATE_OPT_DISABLE_EXCEPTION);



echo "\n**** VALIDATE_FILTER ****\n"; // Heading label only — demonstrates the 'filter' option on VALIDATE_BOOL.
// Input under test — leading space breaks the strict "t"/"f" form below.
$bool = ' t';

// Example 1 — failure: VALIDATE_BOOL does not trim by default.
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_BOOL_TF,
    []
];


$result =validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // failure — ' ' is not an accepted character.


// Example 2 — success: add a 'filter' option that trims whitespace before validation.
$bool_spec = [
  VALIDATE_BOOL,
  VALIDATE_BOOL_TF,
  ['filter' =>
  // Unlike VALIDATE_CALLBACK (which reports status via the return value),
  // a 'filter' simply returns the transformed value. Set $error to a string
  // to signal failure; otherwise the returned value replaces $input.
  function ($ctx, $input, &$error='') {
      assert($ctx instanceof Validate);
      if (!is_string($input)) {
          $error = 'Input is not a string.';
      }
      $ret = trim($input);
      return $ret;
  }
  ]
];

$result =validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus()); // success — the filter trimmed the leading space.

// Equivalent OO one-liner.
$result = (new Validate)->validate($bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);


echo "\n**** VALIDATE_FLOAT ****\n";
// Input under test — outside the [1, PHP_INT_MAX] range.
$float = -123;

// Example 1 — failure: -123 < min.
$float_spec = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => PHP_INT_MAX]
];

$result =validate($ctx, $float, $float_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus());


// Example 2 — success: float string in range.
$float = '2134.234567';

$result =validate($ctx, $float, $float_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus());

// Equivalent OO one-liner.
$result = (new Validate)->validate($float, $float_spec, VALIDATE_OPT_DISABLE_EXCEPTION);



echo "\n**** VALIDATE_INT ****\n";
// Example 1 — failure: input is below min.
$int = '-1234';

$int_spec = [
    VALIDATE_INT,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => PHP_INT_MAX]
];

$result = validate($ctx, $int, $int_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->GetStatus());


// Example 2 — success.
$int = 123;

$result = validate($ctx, $int, $int_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus());

// Equivalent OO one-liner.
$result = (new Validate)->validate($int, $int_spec, VALIDATE_OPT_DISABLE_EXCEPTION);


echo "\n**** VALIDATE_REGEXP *****\n";

// Example 1 — failure: pattern doesn't permit the space character.
$str = 'test user';

$str_spec = [
    VALIDATE_REGEXP,
    VALIDATE_REGEXP_SPACE | VALIDATE_REGEXP_LOWER_ALPHA,
    ['min' => 1, 'max' => 120, 'regexp' => '/^[a-z]*$/']
];

$result =validate($ctx, $str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus());


// Example 2 — success: same flags but pattern now matches the space too.
$str_spec = [
    VALIDATE_REGEXP,
    VALIDATE_REGEXP_SPACE | VALIDATE_REGEXP_LOWER_ALPHA,
    ['min' => 1, 'max' => 120, 'regexp' => '/^[ a-z]*$/']
];

$result =validate($ctx, $str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus());

// Equivalent OO one-liner.
$result = (new Validate)->validate($str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);



echo "\n**** VALIDATE_REJECT ****\n";
// Two-field input — 'float' is required, 'debug' must NOT be present.
$inputs = [
    'float' => 123,
    'debug' => 'true',
];


// Per-field spec — 'float'.
$float = [
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => PHP_INT_MAX]
];

// Per-field spec — 'debug'. Promoted to a rejected parameter by reject() below.
$debug = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_NONE,
    []
];


/**
 * Add VALIDATE_FLAG_REJECT to an existing spec.
 *
 * Useful for sentinel parameters like 'debug', 'test', 'admin' that should
 * never reach a production endpoint — the spec stays reusable, but presence
 * of the field becomes a hard validation failure.
 */
function reject($spec)
{
    $spec[VALIDATE_FLAGS] = ($spec[VALIDATE_FLAGS] | VALIDATE_FLAG_REJECT);
    return $spec;
}

// Combined spec — declares both fields. reject($debug) marks 'debug' as forbidden.
$specs = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => 2],
    [
        'float' => $float,
        'debug' => reject($debug),
    ]
];

// Validation fails — the request includes 'debug', which is rejected.
$result =validate($ctx, $inputs, $specs, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus());

// Equivalent OO one-liner.
$result = (new Validate)->validate($inputs, $specs, VALIDATE_OPT_DISABLE_EXCEPTION);


echo "\n**** VALIDATE_STRING ****\n";
// Input under test.
$str = "test user";

// Example 1 — failure: no character classes are whitelisted, so every char is rejected.
$str_spec = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    ['min' => 1, 'max' => 120]
];

$result =validate($ctx, $str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus());


// Example 2 — success: opt in to lowercase letters and space.
$str_spec = [
    VALIDATE_STRING,
    VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_SPACE,
    ['min' => 1, 'max' => 120]
];

$result =validate($ctx, $str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
var_dump($result, $ctx->getStatus());

// Equivalent OO one-liner.
$result = (new Validate)->validate($str, $str_spec, VALIDATE_OPT_DISABLE_EXCEPTION);


echo "\n**** VALIDATE_STRING - UTF-8 ****\n";
// Multibyte input — rejected because VALIDATE_STRING_MB is not opted in.
$utf8 = '第1位 入力をバリデーションする
全ての信頼できないデータソースからの入力をバリデーションする。適切な入力バリデーション
は非常に多くのソフトウェア脆弱性を排除できる。ほぼ全ての外部データソースに用心が必要で
ある。これらにはコマンドライン引数、ネットワークインターフェース、環境変数やユーザーが
制御可能なファイルなどが含まれる。
（訳注： 2000年から国際情報セキュリティ標準 – ISO 17799/27000、にセキュアプログラ
ミング技術の重要技術として入力バリデーションの実装／管理方法が記載されていた。2013年の
改訂でセキュアプログラミング技術は標準化され普及したので”セキュアプログラミング技術を
採用する”とする簡潔な記述に更新された。）';


// Spec accepts ASCII letters/digits/space/LF only — no VALIDATE_STRING_MB,
// so any multibyte byte in the Japanese text triggers a failure.
$utf8_spec = [
    VALIDATE_STRING,
    VALIDATE_STRING_UPPER_ALPHA
    | VALIDATE_STRING_LOWER_ALPHA
    | VALIDATE_STRING_SPACE
    | VALIDATE_STRING_LF
    | VALIDATE_STRING_DIGIT,
    ['min' => 1, 'max' => 1000, 'ascii' => '-/']
];

try {
    $result =validate($ctx, $utf8, $utf8_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
} catch (Exception $e) {
    var_dump($d->getMessage());
}
var_dump($result, $ctx->getStatus()); // failure — "Multibyte char detected." in the system error log.


echo "\n**** VALIDATE_BOOL - array of bools ****\n";
// Input under test — VALIDATE_FLAG_ARRAY applies the per-element spec to each entry.
$bool = ['t', 'f', true, false];

// amin/amax bound the element count (1..4). The element-level flags below
// are still VALIDATE_FLAG_NONE, so 't'/'f' will fail like in the first example.
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_ARRAY,
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


// Test #2 — opt in to VALIDATE_BOOL_TF so the textual entries pass too.
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF,
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

// VALIDATE_MULTI applies several sub-specs to the same value.
// MULTI_AND requires every sub-spec to pass; MULTI_OR requires at least one.
$bool_spec = [
    VALIDATE_MULTI,
    VALIDATE_MULTI_AND,
    [],
    [
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF,
            ['amin' => 1, 'amax' =>4]
        ],
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF,
            ['amin' => 1, 'amax' =>4]
        ],
    ]
];

// Test #1 — both sub-specs match, MULTI_AND succeeds.
$bool = ['t', 'f', true, false];

echo "**** test #1 *****\n";
$result  = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_CHECK_SPEC);
echo "\n** \$result **\n";
var_dump($result);
echo "\n** \$status **\n";
var_dump($ctx->getStatus());
echo "\n** Validate object **\n";
var_dump($ctx);


// Test #2 — single-spec baseline (no VALIDATE_MULTI wrapper), for comparison.
$bool_spec = [
    VALIDATE_BOOL,
    VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF,
    ['amin' => 1, 'amax' => 4]
];

$bool = ['t', 'f', true, false];

echo "**** test #2 *****\n";
$result  = validate($ctx, $bool, $bool_spec, VALIDATE_OPT_DISABLE_EXCEPTION);
echo "\n** \$result **\n";
var_dump($result);
echo "\n** \$status **\n";
var_dump($ctx->getStatus());
echo "\n** Validate context **\n";
var_dump($ctx);




// Test #3 — MULTI_OR: the first sub-spec rejects 't'/'f' (no BOOL_TF flag) but
// the second accepts them, so the OR is satisfied.
$bool_spec = [
    VALIDATE_MULTI,
    VALIDATE_MULTI_OR,
    [],
    [
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY,
            ['amin' => 1, 'amax' =>4]
        ],
        [
            VALIDATE_BOOL,
            VALIDATE_FLAG_ARRAY | VALIDATE_BOOL_TF,
            ['amin' => 1, 'amax' =>4]
        ],
    ]
];

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
object(Validate)#2 (16) {
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
            object(Closure)#4 (1) {
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
object(Validate)#2 (16) {
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
            object(Closure)#4 (1) {
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
object(Validate)#2 (16) {
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
            object(Closure)#4 (1) {
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
object(Validate)#2 (16) {
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
            object(Closure)#4 (1) {
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
object(Validate)#2 (16) {
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
            object(Closure)#4 (1) {
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
}
