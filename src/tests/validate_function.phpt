--TEST--
validate_* helper functions — logger, validate_error/warning/notice, error buckets
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
    VALIDATE_FLOAT,
    VALIDATE_FLAG_NONE,
    ['min'=>-1000, 'max'=>1000, 'filter'=>
    function($ctx, $input, &$error) {
        if ($input > 1000) {
            $error = "Error from filter callback.\n";
        }
        return trim($input);
    }]
];

$floats = [0, -1000, 1000, -1000.1, 1000.1];

// Custom logger registered via validate_set_logger_function(). Called once
// per error when VALIDATE_OPT_LOG_ERROR is set on the validate() call.
$logger = function($ctx, $error) {
    print("From logger:\n");
    var_dump($error['message']);
};

foreach ($floats as $float) {
    try {
        $func_opts = VALIDATE_OPT_LOG_ERROR;
        $ctx = validate_init();
        validate_set_logger_function($ctx, $logger);
        $result = validate($ctx, $float, $spec, $func_opts);
        var_dump($result, $ctx->getStatus());
    } catch (Exception $e) {
        echo "\nException raised\n\n";
    }
}

try {
  validate_error($ctx, 'TEST USER ERROR #1');
} catch (Exception $e) {
    echo $e->getMessage(), "\n\n";
}
try {
  validate_error($ctx, 'TEST USER ERROR #2');
} catch (Exception $e) {
    echo $e->getMessage(), "\n\n";
}

try {
  validate_warning($ctx, 'TEST USER WARNING #1');
} catch (Exception $e) {
  echo $e->getMessage(), "\n\n"; // No exception
}
try {
  validate_warning($ctx, 'TEST USER WARNING #2');
} catch (Exception $e) {
  echo $e->getMessage(), "\n\n"; // No exception
}

try {
  validate_notice($ctx, 'TEST USER NOTICE #1');
} catch (Exception $e) {
  echo $e->getMessage(), "\n\n"; // No exception
}
try {
  validate_notice($ctx, 'TEST USER NOTICE #2');
} catch (Exception $e) {
  echo $e->getMessage(), "\n\n"; // No exception
}

// User errors are plain strings — safe to surface to end users / form UIs.
var_dump(validate_get_user_errors($ctx));
// System errors carry the full structured record (type/param/spec/value/...)
// for logging and debugging.
var_dump(validate_get_system_errors($ctx));
?>
--EXPECTF--
float(0)
bool(true)
float(-1000)
bool(true)
float(1000)
bool(true)
From logger:
string(63) "VALIDATE_FLOAT: Value is out of range. min: "-1000" max: "1000""

Exception raised

From logger:
string(57) "VALIDATE_FLOAT filter error: Error from filter callback.
"

Exception raised

From logger:
string(18) "TEST USER ERROR #1"
param: 'ROOT' error: 'TEST USER ERROR #1' val: '1000.1'

From logger:
string(18) "TEST USER ERROR #2"
param: 'ROOT' error: 'TEST USER ERROR #2' val: '1000.1'

From logger:
string(20) "TEST USER WARNING #1"
From logger:
string(20) "TEST USER WARNING #2"
From logger:
string(19) "TEST USER NOTICE #1"
From logger:
string(19) "TEST USER NOTICE #2"
array(3) {
  ["error"]=>
  array(1) {
    ["ROOT"]=>
    array(2) {
      [0]=>
      string(18) "TEST USER ERROR #1"
      [1]=>
      string(18) "TEST USER ERROR #2"
    }
  }
  ["warning"]=>
  array(1) {
    ["ROOT"]=>
    array(2) {
      [0]=>
      string(20) "TEST USER WARNING #1"
      [1]=>
      string(20) "TEST USER WARNING #2"
    }
  }
  ["notice"]=>
  array(1) {
    ["ROOT"]=>
    array(2) {
      [0]=>
      string(19) "TEST USER NOTICE #1"
      [1]=>
      string(19) "TEST USER NOTICE #2"
    }
  }
}
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
        string(57) "VALIDATE_FLOAT filter error: Error from filter callback.
"
        ["spec"]=>
        array(3) {
          [0]=>
          int(4)
          [1]=>
          int(0)
          [2]=>
          array(3) {
            ["min"]=>
            int(-1000)
            ["max"]=>
            int(1000)
            ["filter"]=>
            object(Closure)#%d (%d) {
%A              ["parameter"]=>
              array(3) {
                ["$ctx"]=>
                string(10) "<required>"
                ["$input"]=>
                string(10) "<required>"
                ["&$error"]=>
                string(10) "<required>"
              }
            }
          }
        }
        ["func_opts"]=>
        int(32)
        ["value"]=>
        string(6) "1000.1"
        ["orig_value"]=>
        float(1000.1)
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
