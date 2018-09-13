--TEST--
validate() and 'key_callback'
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

$spec = [
    VALIDATE_STRING,
    VALIDATE_STRING_ALNUM | VALIDATE_FLAG_ARRAY,
    [
        'min' => 1,
        'max' => 3,
        'amin' => 1,
        'amax' => 5,
        'key_callback' => function($ctx, $key) {
            if (strlen($key) > 4) {
                return false;
            }
            if (strspn($key, 'abcd') !== strlen($key)) {
                return false;
            }
            if ($key === 'a') {
                validate_warning($ctx, 'Has "a" key.');
            }
            if ($key === '') {
                validate_notice($ctx, 'Empty key.');
            }
            return true;
        }
    ]
];

$value = [
    'abcd' => 1,
    'a' => 1,
    '' => 1,
    'abcde' => 1,
];

try {
    $result = validate($ctx, $value, $spec);
} catch (Exception $e) {
    var_dump($e->getMessage());
}

var_dump('**** User errors ****', validate_get_user_errors($ctx));
var_dump('**** System errors ****', validate_get_system_errors($ctx));
?>
--EXPECT--
string(194) "param: 'ROOT' error: 'VALIDATE_STRING: Array validation. Array parameter has invalid key format. Hint: you may want VALIDATE_FLAG_ARRAY_KEY_ALNUM flag or "key_callback" option.' val: 'key:abcde'"
string(21) "**** User errors ****"
array(3) {
  ["error"]=>
  array(0) {
  }
  ["warning"]=>
  array(1) {
    ["ROOT"]=>
    array(1) {
      [0]=>
      string(12) "Has "a" key."
    }
  }
  ["notice"]=>
  array(1) {
    ["ROOT"]=>
    array(1) {
      [0]=>
      string(10) "Empty key."
    }
  }
}
string(23) "**** System errors ****"
array(3) {
  ["error"]=>
  array(1) {
    ["ROOT"]=>
    array(1) {
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
        string(154) "VALIDATE_STRING: Array validation. Array parameter has invalid key format. Hint: you may want VALIDATE_FLAG_ARRAY_KEY_ALNUM flag or "key_callback" option."
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(8388802)
          [2]=>
          array(5) {
            ["min"]=>
            int(1)
            ["max"]=>
            int(3)
            ["amin"]=>
            int(1)
            ["amax"]=>
            int(5)
            ["key_callback"]=>
            object(Closure)#1 (1) {
              ["parameter"]=>
              array(2) {
                ["$ctx"]=>
                string(10) "<required>"
                ["$key"]=>
                string(10) "<required>"
              }
            }
          }
        }
        ["func_opts"]=>
        int(1)
        ["value"]=>
        string(9) "key:abcde"
        ["orig_value"]=>
        array(4) {
          ["abcd"]=>
          int(1)
          ["a"]=>
          int(1)
          [""]=>
          int(1)
          ["abcde"]=>
          int(1)
        }
      }
    }
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(0) {
  }
}
