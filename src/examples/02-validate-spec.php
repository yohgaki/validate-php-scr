<?php
/**
 * Example: pre-flight spec validation.
 *
 * In production you typically disable VALIDATE_OPT_CHECK_SPEC for speed.
 * During development, call validate_spec() once on the assembled spec
 * to catch typos and structural mistakes before any request data arrives.
 */

// Define validate(), etc.
require_once __DIR__.'/../validate_func.php';

// Load per-field specs ($username, $email, $age, ...).
require_once __DIR__.'/99-web-specs.php';

// Assemble the per-request spec from those field specs.
$spec = [
    VALIDATE_ARRAY,           // [0] validator type
    VALIDATE_FLAG_NONE,       // [1] flag bitfield
    ['min' => 3, 'max' => 3], // [2] options — exactly three top-level groups
    [                         // [3] sub-specs (this is the VALIDATE_PARAMS slot)
        'get' => [            // Query string: 0..2 declared parameters.
            VALIDATE_ARRAY,
            VALIDATE_FLAG_NONE,
            ['min' => 0, 'max' => 2],
            [
                'debug' => $debug, // VALIDATE_REJECT — fails if 'debug' is present.
            ]
        ],
        'post' => [           // Form body fields.
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
        'header' => [         // HTTP headers (read from $_SERVER in real apps).
            // NOTE: this header check is intentionally loose — it treats the
            // header set as a generic "array of strings". Production code
            // should declare a per-header spec ($basicTypes['user-agent'],
            // $basicTypes['content-length'], etc.) instead.
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
