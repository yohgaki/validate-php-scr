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


for ($i=1; $i < 10; $i++) {
    try {
        $data = '123456789';
        $spec = array(
            VALIDATE_STRING,
            VALIDATE_STRING_ALNUM,
            ['min'=>1, 'max'=>$i]
        );
        var_dump(validate($ctx, $data, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        echo $e->getMessage()."\n";
    }
}

for ($i=1; $i < 10; $i++) {
    try {
        $data = '1234567';
        $spec = array(
            VALIDATE_STRING,
            VALIDATE_STRING_ALNUM,
            ['min'=>$i, 'max'=>10]
        );
        var_dump(validate($ctx, $data, $spec), $ctx->getStatus());
    } catch (Exception $e) {
        echo $e->getMessage()."\n";
    }
}

try {
    $data = '1234567';
    $spec = array(
        VALIDATE_STRING,
        VALIDATE_STRING_ALNUM,
        ['min'=>10, 'max'=>1]
    );
    var_dump(validate($ctx, $data, $spec), $ctx->getStatus());
} catch (Exception $e) {
    echo $e->getMessage()."\n";
}
?>
--EXPECTF--
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "1"' val: '123456789'
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "2"' val: '123456789'
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "3"' val: '123456789'
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "4"' val: '123456789'
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "5"' val: '123456789'
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "6"' val: '123456789'
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "7"' val: '123456789'
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "1" max: "8"' val: '123456789'
string(9) "123456789"
bool(true)
string(7) "1234567"
bool(true)
string(7) "1234567"
bool(true)
string(7) "1234567"
bool(true)
string(7) "1234567"
bool(true)
string(7) "1234567"
bool(true)
string(7) "1234567"
bool(true)
string(7) "1234567"
bool(true)
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "8" max: "10"' val: '1234567'
param: 'ROOT' error: 'VALIDATE_STRING: Length is out of range. min: "9" max: "10"' val: '1234567'
Array
(
    [error] => Array
        (
            [0] => Array
                (
                    [message] => VALIDATE_STRING must have valid "min" and "max" options. min: "10" max:"1"
                    [spec] => Array
                        (
                            [0] => 5
                            [1] => 194
                            [2] => Array
                                (
                                    [min] => 10
                                    [max] => 1
                                )

                        )

                    [flags] => VALIDATE_STRING_DIGIT | VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA | VALIDATE_STRING_ALPHA | VALIDATE_STRING_ALNUM
                    [param] => ROOT
                )

        )

    [warning] => Array
        (
        )

    [notice] => Array
        (
        )

)

Fatal error: Invalid validation spec detected. Fix spec errors first. in %s/src/validate_func.php on line %d