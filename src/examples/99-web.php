<?php
/**
 * Somewhat realistic wep app input validation example.
 * e.g. Start CLI web server like "php -S 127.0.0.1:8888", then
 * access http://127.0.0.1:8888/00-validate-web.php with your browser.
 *
 * All web apps must validate all inputs including HTTP headers.
 */

// Define validate(), etc.
require_once __DIR__.'/../validate_func.php';

// Load parameter spec definitions
require_once __DIR__.'/99-web-specs.php';

// Now you can combine above predefined parameter specs to a request validation spec.
$spec1 = [
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
        'header' => [ // Headers are validated by multi phase validations.
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

// 2nd validation for headers. Just check RFC conformance w/o UTF chars.
$tmp = $B['header1024'];
$tmp[VALIDATE_FLAGS] |= VALIDATE_FLAG_ARRAY | VALIDATE_FLAG_ARRAY_KEY_ALNUM; // Validate headers as array.
$tmp[VALIDATE_OPTIONS]['amin'] = 10; // At least 10 headers.
$tmp[VALIDATE_OPTIONS]['amax'] = 50;  // At most 50 headers.
$spec2 = $tmp;

?>
<html>
<head>
    <title>Somewhat realistic wep app input validation example.</title>
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
        This is an form validation example, but it also validates any other inputs for
        apps. i.e. HTTP headers, GET parameters. Try adding "?debug=1" to query(URL/GET) parameter
        and more than 3 parameters.
    </p>
    <p>
        <ul>
        <li><a href="https://github.com/yohgaki/validate-php-scr">Validate PHP - Script Version</a></li>
        <li><a href="https://github.com/yohgaki/validate-php-scr/blob/master/src/examples/00-validate-web.php">Source Code</a></li>
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
    // Unset some sensitive vars so that this can be exposed to internet.
    unset($_SERVER['DOCUMENT_ROOT'], $_SERVER['SERVER_SOFTWARE'], $_SERVER['SCRIPT_FILENAME'], $_SERVER['CONTEXT_DOCUMENT_ROOT']);

    // You should validate ALL inputs. i.e. $_POST/$_GET/$_COOKIE/$_FILES/$_SERVER or apache_request_headers().
    $inputs = ['get' => $_GET, 'post' => $_POST, 'header' => $_SERVER];

    // Let's validate them with multiple validations.
    // At first, validate as script as possible.
    $result1 =validate($ctx, $inputs, $spec1, VALIDATE_OPT_DISABLE_EXCEPTION);
    $status1 = $ctx->getStatus();
    $partial_result1 = $ctx->getValidated();
    // Secondary, validate remaining headers with loose spec.
    $result2 =validate($ctx, $inputs['header'], $spec2, VALIDATE_OPT_DISABLE_EXCEPTION);
    $status2 = $ctx->getStatus();
    $partial_result2 = $ctx->getValidated();
    // OO API one liner
    //$result = (new Validate)->validate($inputs, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
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
NOTE: You should try to <b>validate all inputs as strict as possible</b>.
This example is bad because it does not validate "submit" and "GET"
parameters.
<br />
<?php echo htmlspecialchars(print_r($inputs, true)); ?>
<br />
<h1>VALIDATED RESULTS</h1>
TIP: HTTP headers are most problematic inputs because you cannot be
sure what HTTP headers will be set.

Since validate() removes validated inputs, you can perform strict
validation for "Headers always exist" by preceding spec. Then, you may
validate the rest by non strict validation spec applied later at once.

Or simply ignore unneeded headers at 1st validate call, then check the
rest by 2nd validate() call.

Latter is my recommendation because you are better off if "<b>You only have
strictly validated inputs that are needed for the processing code, and
validate all inputs</b>".
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
