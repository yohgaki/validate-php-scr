--TEST--
validate_spec() — rejects malformed spec shapes (missing ID, wrong types, ...)
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

// Each block feeds a deliberately malformed value to validate_spec() and
// dumps the system errors so the --EXPECT-- block can pin the wording.
// Block 1: empty array — no VALIDATE_ID at all.
$test_spec = array();
var_dump(validate_spec($test_spec, $result, $ctx));
var_dump(validate_get_system_errors($ctx));

// Block 2: ID slot holds an array instead of an int.
$test_spec = array(array());
var_dump(validate_spec($test_spec, $result, $ctx));
var_dump(validate_get_system_errors($ctx));

// Block 3: shape is right but the values are wrong types (flags/options must be int/array).
$test_spec = array(1,2,3);
var_dump(validate_spec($test_spec, $result, $ctx));
var_dump(validate_get_system_errors($ctx));

// Block 4: ID is array, flags is scalar — multiple errors reported in one pass.
$test_spec = array(array(),2,array());
var_dump(validate_spec($test_spec, $result, $ctx));
var_dump(validate_get_system_errors($ctx));

// Block 5: not an array at all — input is a bare int.
$test_spec = 1;
var_dump(validate_spec($test_spec, $result, $ctx));
var_dump(validate_get_system_errors($ctx));
?>
--EXPECTF--
bool(false)
array(3) {
  ["error"]=>
  array(1) {
    [0]=>
    array(4) {
      ["message"]=>
      string(29) "Validator ID(int) is missing."
      ["spec"]=>
      array(0) {
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
bool(false)
array(3) {
  ["error"]=>
  array(2) {
    [0]=>
    array(4) {
      ["message"]=>
      string(29) "Validator ID(int) is missing."
      ["spec"]=>
      array(0) {
      }
      ["flags"]=>
      string(0) ""
      ["param"]=>
      string(4) "ROOT"
    }
    [1]=>
    array(4) {
      ["message"]=>
      string(31) "Validator ID must be valid int."
      ["spec"]=>
      array(1) {
        [0]=>
        array(0) {
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
bool(false)
array(3) {
  ["error"]=>
  array(3) {
    [0]=>
    array(4) {
      ["message"]=>
      string(29) "Validator ID(int) is missing."
      ["spec"]=>
      array(0) {
      }
      ["flags"]=>
      string(0) ""
      ["param"]=>
      string(4) "ROOT"
    }
    [1]=>
    array(4) {
      ["message"]=>
      string(31) "Validator ID must be valid int."
      ["spec"]=>
      array(1) {
        [0]=>
        array(0) {
        }
      }
      ["flags"]=>
      string(0) ""
      ["param"]=>
      string(4) "ROOT"
    }
    [2]=>
    array(4) {
      ["message"]=>
      string(32) "Validation option must be array."
      ["spec"]=>
      array(3) {
        [0]=>
        int(1)
        [1]=>
        int(2)
        [2]=>
        int(3)
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
bool(false)
array(3) {
  ["error"]=>
  array(4) {
    [0]=>
    array(4) {
      ["message"]=>
      string(29) "Validator ID(int) is missing."
      ["spec"]=>
      array(0) {
      }
      ["flags"]=>
      string(0) ""
      ["param"]=>
      string(4) "ROOT"
    }
    [1]=>
    array(4) {
      ["message"]=>
      string(31) "Validator ID must be valid int."
      ["spec"]=>
      array(1) {
        [0]=>
        array(0) {
        }
      }
      ["flags"]=>
      string(0) ""
      ["param"]=>
      string(4) "ROOT"
    }
    [2]=>
    array(4) {
      ["message"]=>
      string(32) "Validation option must be array."
      ["spec"]=>
      array(3) {
        [0]=>
        int(1)
        [1]=>
        int(2)
        [2]=>
        int(3)
      }
      ["flags"]=>
      string(0) ""
      ["param"]=>
      string(4) "ROOT"
    }
    [3]=>
    array(4) {
      ["message"]=>
      string(31) "Validator ID must be valid int."
      ["spec"]=>
      array(3) {
        [0]=>
        array(0) {
        }
        [1]=>
        int(2)
        [2]=>
        array(0) {
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

Fatal error: Uncaught InvalidArgumentException: 1st parameter must be validation spec array. in %s/src/validate_func.php:%d
Stack trace:
#0 %s/tests/validate_spec2.php(%d): validate_spec(%a)
#1 {main}
  thrown in %s/src/validate_func.php on line %d