--TEST--
validate() errors
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

echo "User error\n";
$spec = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NONE,
    ['min'=>2, 'max'=>3, '_user_opt'=>'USER_OPT', 'error_message'=>'WARING ERROR min={{min}} max={{max}} _user_opt={{_user_opt}}'],
];
$val = 'a';

try {
    $ctx = validate_init();
    validate($ctx, $val, $spec);
} catch (Exception $e) {
    var_dump($e->getMessage());
    var_dump(validate_get_system_errors($ctx), validate_get_user_errors($ctx));
}

echo "\nUser warning\n";
$spec = [
    VALIDATE_STRING,
    VALIDATE_FLAG_WARNING,
    ['min'=>2, 'max'=>3, '_user_opt'=>'USER_OPT', 'error_message'=>'WARING ERROR min={{min}} max={{max}} _user_opt={{_user_opt}}'],
];
$val = 'a';

$ctx = validate_init();
validate($ctx, $val, $spec);
var_dump(validate_get_system_errors($ctx), validate_get_user_errors($ctx));

echo "\nUser notice\n";
$spec = [
    VALIDATE_STRING,
    VALIDATE_FLAG_NOTICE,
    ['min'=>2, 'max'=>3, '_user_opt'=>'USER_OPT', 'error_message'=>'NOTICE ERROR min={{min}} max={{max}} _user_opt={{_user_opt}}'],
];
$val = 'a';

$ctx = validate_init();
validate($ctx, $val, $spec);
var_dump(validate_get_system_errors($ctx), validate_get_user_errors($ctx));

?>
--EXPECT--
User error
string(90) "param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "2" max: "3"' val: 'a'"
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
        string(58) "VALIDATE_STRING: Length is out of range. min: "2" max: "3""
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(0)
          [2]=>
          array(4) {
            ["min"]=>
            int(2)
            ["max"]=>
            int(3)
            ["_user_opt"]=>
            string(8) "USER_OPT"
            ["error_message"]=>
            string(60) "WARING ERROR min={{min}} max={{max}} _user_opt={{_user_opt}}"
          }
        }
        ["func_opts"]=>
        int(1)
        ["value"]=>
        string(1) "a"
        ["orig_value"]=>
        string(1) "a"
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
array(3) {
  ["error"]=>
  array(1) {
    ["ROOT"]=>
    array(1) {
      [0]=>
      string(43) "WARING ERROR min=2 max=3 _user_opt=USER_OPT"
    }
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(0) {
  }
}

User warning
array(3) {
  ["error"]=>
  array(0) {
  }
  ["warning"]=>
  array(1) {
    ["ROOT"]=>
    array(1) {
      [0]=>
      array(8) {
        ["type"]=>
        int(2)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(58) "VALIDATE_STRING: Length is out of range. min: "2" max: "3""
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(67108864)
          [2]=>
          array(4) {
            ["min"]=>
            int(2)
            ["max"]=>
            int(3)
            ["_user_opt"]=>
            string(8) "USER_OPT"
            ["error_message"]=>
            string(60) "WARING ERROR min={{min}} max={{max}} _user_opt={{_user_opt}}"
          }
        }
        ["func_opts"]=>
        int(1)
        ["value"]=>
        string(1) "a"
        ["orig_value"]=>
        string(1) "a"
      }
    }
  }
  ["notice"]=>
  array(0) {
  }
}
array(3) {
  ["error"]=>
  array(0) {
  }
  ["warning"]=>
  array(1) {
    ["ROOT"]=>
    array(1) {
      [0]=>
      string(43) "WARING ERROR min=2 max=3 _user_opt=USER_OPT"
    }
  }
  ["notice"]=>
  array(0) {
  }
}

User notice
array(3) {
  ["error"]=>
  array(0) {
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(1) {
    ["ROOT"]=>
    array(1) {
      [0]=>
      array(8) {
        ["type"]=>
        int(8)
        ["param"]=>
        array(1) {
          [0]=>
          string(4) "ROOT"
        }
        ["defined"]=>
        NULL
        ["message"]=>
        string(58) "VALIDATE_STRING: Length is out of range. min: "2" max: "3""
        ["spec"]=>
        array(3) {
          [0]=>
          int(5)
          [1]=>
          int(134217728)
          [2]=>
          array(4) {
            ["min"]=>
            int(2)
            ["max"]=>
            int(3)
            ["_user_opt"]=>
            string(8) "USER_OPT"
            ["error_message"]=>
            string(60) "NOTICE ERROR min={{min}} max={{max}} _user_opt={{_user_opt}}"
          }
        }
        ["func_opts"]=>
        int(1)
        ["value"]=>
        string(1) "a"
        ["orig_value"]=>
        string(1) "a"
      }
    }
  }
}
array(3) {
  ["error"]=>
  array(0) {
  }
  ["warning"]=>
  array(0) {
  }
  ["notice"]=>
  array(1) {
    ["ROOT"]=>
    array(1) {
      [0]=>
      string(43) "NOTICE ERROR min=2 max=3 _user_opt=USER_OPT"
    }
  }
}
