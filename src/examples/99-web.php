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

// Echo a submitted form value back into the page. Inputs can be arrays
// (e.g. "username[]=x"), so never pass them to htmlspecialchars() unchecked.
function h($value)
{
    return htmlspecialchars(is_string($value) ? $value : '', ENT_QUOTES, 'UTF-8');
}

// Load parameter spec definitions
require_once __DIR__.'/99-web-specs.php';

// Catch-all spec for the headers the request-level spec does not declare
// ("undeclared" option below). Accepts ASCII RFC-conformant header values
// (no UTF-8 multibyte).
$other_header = $basicTypes['header1024'];

// Compose the per-field specs (loaded above) into a request-level spec.
// Each spec array follows the [type, flags, options, sub-specs] layout.
$spec = [
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
                // The submit button is an input too: declare it, or it is
                // an undeclared (leftover) key.
                'submit'   => [VALIDATE_STRING, VALIDATE_STRING_LOWER_ALPHA | VALIDATE_FLAG_UNDEFINED,
                               ['values' => ['submit' => true]]],
            ],
        ],
        'header' => [ // HTTP headers: known ones strictly, every other one
                      // through $other_header.
            VALIDATE_ARRAY,
            VALIDATE_FLAG_ARRAY_KEY_ALNUM, // Undeclared header names: alnum, '_' and '-' only.
            // 2 declared + at least 10 others (realistic browser baseline);
            // reject obviously bloated header sets.
            ['min' => 12, 'max' => 50, 'undeclared' => $other_header],
            [
                // PHP stores these two request headers without the HTTP_ prefix.
                'CONTENT_LENGTH' => $content_length,
                'CONTENT_TYPE' => $content_type,
            ]
        ]
    ],
];

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
        <li><div>Username: </div><input type="text" name="username" value="<?php echo h($_POST['username'] ?? 'Test User');?>" /></li>
        <li><div>Email: </div><input type="text" name="email" value="<?php echo h($_POST['email'] ?? 'user@example.com');?>" /></li>
        <li><div>Age: </div><input type="text" name="age" value="<?php echo h($_POST['age'] ?? '34');?>"  /></li>
        <li><div>Weight: </div><input type="text" name="weight" value="<?php echo h($_POST['weight'] ?? '62.5');?>"  /></li>
        <li><div>Country: </div>
        <input type="radio" name="country" value="japan" <?php if (isset($_POST['country']) && $_POST['country']=='japan') echo 'checked="checked"'; ?> />Japan<br />
        <input type="radio" name="country" value="other" <?php if (isset($_POST['country']) && $_POST['country']=='other') echo 'checked="checked"'; ?> />Other<br /></li>
        <li><div>Comment: </div><textarea name="comment"><?php echo h($_POST['comment'] ?? 'Write comment');?></textarea>
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

    // One call validates everything. Validated values are removed from
    // $inputs (the engine unsets them by reference). An unknown GET/POST key
    // is an error; every header goes through its own spec or $other_header.
    $result = validate($ctx, $inputs, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
    $status = $ctx->getStatus();
    $partial_result = $ctx->getValidated();
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
<?php echo $status ? 'Validation Success' :  'Validation failed'; ?>
<br />
<hr />
<br />
<h1>INPUTS</h1><br />
<?php echo htmlspecialchars(print_r(['get' => $_GET, 'post' => $_POST, 'header' => $_SERVER], true)); ?>
<br />
<h1>UNVALIDATED INPUTS</h1>
NOTE: validate() removes every value it validated, so whatever is left here
failed validation. You should always <b>validate every input as strictly as
possible</b>.
<br />
<?php echo htmlspecialchars(print_r($inputs, true)); ?>
<br />
<h1>VALIDATED RESULTS</h1>
TIP: HTTP headers are awkward inputs because you can't be certain which
headers any given client will send.

The recommended pattern is:
1) declare the headers you expect ("always-present" ones) with strict specs,
2) give the header VALIDATE_ARRAY an "undeclared" spec that loosely
   validates every other header.

This way, downstream code <b>only ever sees strictly validated values for
the inputs it needs</b>, while every other input still gets validated.
<br />
<h4> Validation result: (Empty when validation error)</h4>
<?php echo htmlspecialchars(print_r($result, true)); ?>
<h4> Validated Values: (May contain partial validation results when failure, exactly the same as above when success.)</h4>
<?php echo htmlspecialchars(print_r($partial_result, true)); ?>
<?php
endif;
?>
        </pre>
    </div>
</form>
</body>
</html>
