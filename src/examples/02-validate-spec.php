<?php

// Define validate(), etc.
require_once __DIR__.'/../validate_func.php';

// Load specs
require_once __DIR__.'/00-validate-web-param-specs.php';

// Make complex spec
$spec = [
    VALIDATE_ARRAY,           // 1st should be Validator type.
    VALIDATE_FLAG_NONE,       // 2nd should be validator flags.
    ['min' => 3, 'max' => 3], // 3rd should be validator options.
    [                         // 4th is "Array" parameter definition.
        'get' => [            // GET parameter may have 0 to 2 parameters.
            VALIDATE_ARRAY,
            VALIDATE_FLAG_NONE,
            ['min' => 0, 'max' => 2],
            [
                'debug' => $debug, // Rejected parameter
            ]
        ],
        'post' => [           // POST parameters
            VALIDATE_ARRAY,
            VALIDATE_FLAG_NONE,
            ['min' => 6, 'max' => 7],
            [
                'username' => $username,
                'email'    => $email,
                'age'      => $age,
                'weight'   => $weight,
                'country'  => $country,
                'comment'  => $comment,
            ],
        ],
        'header' => [         // HTTP headers (This example validates $_SERVER)
            // Not a good header validation example. Stricter validation is always better/good/recommended.
            // This spec simply validate HTTP headers as "Array of Strings".
            VALIDATE_STRING,
            VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM
             | VALIDATE_STRING_ALNUM | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_SPACE,
            ['min' => 0, 'max' => 1024, 'amin' => 20, 'amax' => 50],
        ],
    ],
];

$status = validate_spec($spec, $result, $ctx);
$errors = validate_get_system_errors($ctx);

var_dump($status, $errors, $result, $spec, $ctx);
