<?php
/**
 * Somewhat realistic web app input validation example.
 *
 * To try it: start the CLI web server with
 *     php -S 127.0.0.1:8888 -t src/examples
 * then open http://127.0.0.1:8888/99-web.php in your browser.
 *
 * Every web app must validate every input, including HTTP headers.
 */

// Define validate(), etc.
require_once __DIR__.'/../validate_func.php';

// Load parameter spec definitions
require_once __DIR__.'/99-web-specs.php';

// Compose the per-field specs (loaded above) into a request-level spec.
// Each spec array follows the [type, flags, options, sub-specs] layout.
$spec1 = [
    VALIDATE_ARRAY,           // [0] validator type
    VALIDATE_FLAG_NONE,       // [1] flag bitfield
    ['min' => 3, 'max' => 3], // [2] options — exactly three top-level groups
    [                         // [3] sub-specs (VALIDATE_PARAMS slot)
        'get' => [            // Query string: 0..2 declared params.
            VALIDATE_ARRAY,
            VALIDATE_FLAG_NONE,
            ['min' => 0, 'max' => 2],
            [
                'debug' => $debug, // VALIDATE_FLAG_REJECT — request fails if 'debug' is present.
            ]
        ],
        'post' => [           // Form body — 6..7 declared fields.
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
        'header' => [ // HTTP headers — stage 1 only handles the known ones.
                      // Stage 2 ($spec2 below) catches everything else.
            VALIDATE_ARRAY,
            VALIDATE_FLAG_NONE,
            ['min' => 2, 'max' => 50],
            [
                'HTTP_CONTENT_LENGTH' => $content_length,
                'HTTP_CONTENT_TYPE' => $content_type,
            ]
        ]
    ],
];

// Stage 2: catch-all spec for headers that $spec1 did not consume.
// VALIDATE_FLAG_ARRAY applies $basicTypes['header1024'] to every leftover
// entry; VALIDATE_FLAG_ARRAY_KEY_ALNUM bounds the allowed key names.
// Accepts ASCII RFC-conformant header values (no UTF-8 multibyte).
$tmp = $basicTypes['header1024'];
$tmp[VALIDATE_FLAGS] |= VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM;
$tmp[VALIDATE_OPTIONS]['amin'] = 10; // Require at least 10 leftover headers (realistic browser baseline).
$tmp[VALIDATE_OPTIONS]['amax'] = 50; // Reject obviously bloated header sets.
$spec2 = $tmp;

?>
<html>
<head>
    <title>Somewhat realistic web app input validation example.</title>
</head>
<body>
<form method="post">
    <h1> validate() function example</h1>
    <div style="width: 300px;text-align: left;margin: 1em;">
    <p>
        Although validate() function is designed for "Web Application Input Validations",
        it is also designed as general purpose validation function. It can be used for
        "Form/Logic and Output" validations and works perfectly well with them.
    </p>
    <p>
        This is a form validation example, but it also validates everything
        else a web app receives (HTTP headers, GET parameters, ...). Try
        appending "?debug=1" to the URL, or adding more than 3 GET parameters,
        to see those validations fire.
    </p>
    <p>
        <ul>
        <li><a href="https://github.com/yohgaki/validate-php-scr">Validate PHP - Script Version</a></li>
        <li><a href="https://github.com/yohgaki/validate-php-scr/blob/master/src/examples/99-web.php">Source Code</a></li>
        </ul>
    </p>
    </div>
    <div>
    <ul>
        <li><div>Username: </div><input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? 'Test User');?>" /></li>
        <li><div>Email: </div><input type="text" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? 'user@example.com');?>" /></li>
        <li><div>Age: </div><input type="text" name="age" value="<?php echo htmlspecialchars($_POST['age'] ?? 34);?>"  /></li>
        <li><div>Weight: </div><input type="text" name="weight" value="<?php echo htmlspecialchars($_POST['weight'] ?? '1234');?>"  /></li>
        <li><div>Country: </div>
        <input type="radio" name="country" value="japan" <?php if (isset($_POST['country']) && $_POST['country']=='japan') echo 'checked="checked"'; ?> />Japan<br />
        <input type="radio" name="country" value="other" <?php if (isset($_POST['country']) && $_POST['country']=='other') echo 'checked="checked"'; ?> />Other<br /></li>
        <li><div>Comment: </div><textarea name="comment"><?php echo htmlspecialchars($_POST['comment'] ?? 'Write comment');?></textarea>
        <li><div>Send These!</div><input type="submit" name="submit" value="submit" /></li>
    </ul>
    </div>

    <div>
     <pre>
<?php
if (!empty($_POST)):
    // Strip the noisier $_SERVER entries so the rendered page is safe to share.
    unset($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_SOFTWARE'], $_SERVER['SCRIPT_FILENAME'], $_SERVER['CONTEXT_DOCUMENT_ROOT']);

    // Validate ALL inputs the request brings in — $_POST, $_GET, $_COOKIE,
    // $_FILES, the $_SERVER HTTP_* entries, apache_request_headers(). This
    // example covers GET / POST / headers; cookies and files would be added
    // the same way.
    $inputs = ['get' => $_GET, 'post' => $_POST, 'header' => $_SERVER];

    // Two-stage validation pattern.
    // Stage 1: enforce the strict $spec1. Validated values are removed from
    // $inputs (the engine unsets them by reference).
    $result1 = validate($ctx, $inputs, $spec1, VALIDATE_OPT_DISABLE_EXCEPTION);
    $status1 = $ctx->getStatus();
    $partial_result1 = $ctx->getValidated();
    // Stage 2: catch headers $spec1 left behind using the loose $spec2.
    // Without this, unexpected headers would slip past the trust boundary.
    $result2 = validate($ctx, $inputs['header'], $spec2, VALIDATE_OPT_DISABLE_EXCEPTION);
    $status2 = $ctx->getStatus();
    $partial_result2 = $ctx->getValidated();
    // Equivalent OO one-liner (no separate $ctx):
    //   $result = (new Validate)->validate($inputs, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
?>

<hr />
<br />
<h1>USER ERROR MESSAGES</h1>
<br />
<?php echo htmlspecialchars(print_r(validate_get_user_errors($ctx), true)); ?>
<br />
<h1>SYSTEM ERROR MESSAGES</h1>
<br />
<?php echo htmlspecialchars(print_r(validate_get_system_errors($ctx), true)); ?>
<br />
<h1>VALIDATION STATUS</h1>
<br />
<?php echo $status1 ? 'Validation1 Success' :  'Validation1 failed'; ?>
<br />
<?php echo $status2 ? 'Validation2 Success' :  'Validation2 failed'; ?>
<br />
<hr />
<br />
<h1>INPUTS</h1><br />
<?php echo htmlspecialchars(print_r(['get' => $_GET, 'post' => $_POST, 'header' => $_SERVER], true)); ?>
<br />
<h1>UNVALIDATED INPUTS</h1>
NOTE: You should always <b>validate every input as strictly as possible</b>.
This example is intentionally a bit loose — it skips strict validation of
the "submit" button and miscellaneous GET parameters.
<br />
<?php echo htmlspecialchars(print_r($inputs, true)); ?>
<br />
<h1>VALIDATED RESULTS</h1>
TIP: HTTP headers are awkward inputs because you can't be certain which
headers any given client will send.

Since validate() removes validated inputs, the recommended pattern is:
1) strictly validate the headers you expect ("always-present" ones) first,
2) then catch any remaining headers with a loose validation spec.

This way, downstream code <b>only ever sees strictly validated values for
the inputs it needs</b>, while every other input still gets validated.
<br />
<h3> Validation #1 </h3>
<h4> Validation result: (Empty when validation error)</h4>
<?php echo htmlspecialchars(print_r($result1, true)); ?>
<h4> Validated Values: (May contain partial validation results when failure, exactly the same as above when success.)</h4>
<?php echo htmlspecialchars(print_r($partial_result1, true)); ?>
<h3> Validation #2 </h3>
<h4> Validation result: (Empty when validation error)</h4>
<?php echo htmlspecialchars(print_r($result2, true)); ?>
<h4> Validated Values: (May contain partial validation results when failure, exactly the same as above when success.)</h4>
<?php echo htmlspecialchars(print_r($partial_result2, true)); ?>
<?php
endif;
?>
        </pre>
    </div>
</form>
</body>
</html>
