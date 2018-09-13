--TEST--
validate_spec()
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

echo "Invalid spec\n";
$test_spec = array(
	array(
		array(
			array(
				VALIDATE_STRING, // 1st: Validator ID
				VALIDATE_FLAG_NONE, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcdef0123456789',
				),
			),
			array(
				VALIDATE_STRING, // 1st: Validator ID
				VALIDATE_FLAG_NONE, // 2nd: Validator flags. Replaced by above flags one by one
				array( // 3rd: Validator options
					'min' => 0,
					'max' => 10,
					'ascii' => 'abcdef0123456789',
				),
			),
		),
	),
);

$ctx = validate_init();
var_dump(validate_spec($test_spec, $result, $ctx), $result);
var_dump('Error counts:', count(validate_get_system_errors($ctx)['error']), validate_get_system_errors($ctx));


echo "\nValid spec\n";
$test_spec = array(
	VALIDATE_ARRAY,
	VALIDATE_FLAG_NONE,
	['min' => 2, 'max' => 2],
	array(
		array(
			VALIDATE_STRING, // 1st: Validator ID
			VALIDATE_FLAG_NONE, // 2nd: Validator flags. Replaced by above flags one by one
			array( // 3rd: Validator options
				'min' => 0,
				'max' => 10,
				'ascii' => 'abcdef0123456789',
			),
		),
		array(
			VALIDATE_STRING, // 1st: Validator ID
			VALIDATE_FLAG_NONE, // 2nd: Validator flags. Replaced by above flags one by one
			array( // 3rd: Validator options
				'min' => 0,
				'max' => 10,
				'ascii' => 'abcdef0123456789',
			),
		),
	),
);

$ctx = validate_init();
var_dump(validate_spec($test_spec, $result), $result);
var_dump('Error counts:', count(validate_get_system_errors($ctx)['error']), validate_get_system_errors($ctx));

echo "\nUser option\n";
$test_spec = array(
	VALIDATE_INT,
	VALIDATE_FLAG_NONE,
	['min' => 2, 'max' => 2, '_user_option'=>'foo'],
);

$ctx = validate_init();
var_dump(validate_spec($test_spec, $result), $result);
var_dump('Error counts:', count(validate_get_system_errors($ctx)['error']), validate_get_system_errors($ctx));

?>
--EXPECTF--
Invalid spec
bool(false)
array(1) {
  [0]=>
  array(1) {
    [0]=>
    array(2) {
      [0]=>
      array(3) {
        [0]=>
        int(5)
        [1]=>
        int(0)
        [2]=>
        array(3) {
          ["min"]=>
          int(0)
          ["max"]=>
          int(10)
          ["ascii"]=>
          string(16) "abcdef0123456789"
        }
      }
      [1]=>
      array(3) {
        [0]=>
        int(5)
        [1]=>
        int(0)
        [2]=>
        array(3) {
          ["min"]=>
          int(0)
          ["max"]=>
          int(10)
          ["ascii"]=>
          string(16) "abcdef0123456789"
        }
      }
    }
  }
}
string(13) "Error counts:"
int(1)
array(3) {
  ["error"]=>
  array(1) {
    [0]=>
    array(4) {
      ["message"]=>
      string(31) "Validator ID must be valid int."
      ["spec"]=>
      array(1) {
        [0]=>
        array(1) {
          [0]=>
          array(2) {
            [0]=>
            array(3) {
              [0]=>
              int(5)
              [1]=>
              int(0)
              [2]=>
              array(3) {
                ["min"]=>
                int(0)
                ["max"]=>
                int(10)
                ["ascii"]=>
                string(16) "abcdef0123456789"
              }
            }
            [1]=>
            array(3) {
              [0]=>
              int(5)
              [1]=>
              int(0)
              [2]=>
              array(3) {
                ["min"]=>
                int(0)
                ["max"]=>
                int(10)
                ["ascii"]=>
                string(16) "abcdef0123456789"
              }
            }
          }
        }
      }
      ["flags"]=>
      string(0) ""
      ["param"]=>
      string(4) "ROOT"
    }
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(0) {
  }
}

Valid spec
bool(true)
array(4) {
  [0]=>
  int(6)
  [1]=>
  int(0)
  [2]=>
  array(2) {
    ["min"]=>
    int(2)
    ["max"]=>
    int(2)
  }
  [3]=>
  array(0) {
  }
}
string(13) "Error counts:"
int(0)
array(3) {
  ["error"]=>
  array(0) {
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(0) {
  }
}

User option
bool(true)
array(3) {
  [0]=>
  int(3)
  [1]=>
  int(0)
  [2]=>
  array(3) {
    ["min"]=>
    int(2)
    ["max"]=>
    int(2)
    ["_user_option"]=>
    string(3) "foo"
  }
}
string(13) "Error counts:"
int(0)
array(3) {
  ["error"]=>
  array(0) {
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(0) {
  }
}
