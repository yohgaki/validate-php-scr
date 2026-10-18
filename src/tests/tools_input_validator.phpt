--TEST--
tools/input_validator.php - a request without a spec for its METHOD:PATH fails
--SKIPIF--
<?php
require_once __DIR__.'/bootstrap.php';
if (!class_exists("Validate")) die("skip");
?>
--INI--
error_reporting=-1
date.timezone=UTC
--FILE--
<?php
require_once __DIR__.'/bootstrap.php';

function accept_any($ctx, &$result, $input)
{
    $result = $input;
    return true;
}
// Spec directory as input_analyzer.php leaves it (mode 0700, owned by us).
// GET:/index.php takes an empty $_GET and anything else.
$dir = sys_get_temp_dir().'/validate-tools-test-'.getmypid();
mkdir($dir, 0700);
file_put_contents($dir.'/spec.php', '<?php $spec = '.var_export([
    'GET:/index.php' => [VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY_KEY_ALNUM, ['min'=>0, 'max'=>4,
        'undeclared' => [VALIDATE_CALLBACK, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>0, 'callback'=>'accept_any']]], [
        '_GET' => [VALIDATE_ARRAY, VALIDATE_FLAG_NONE, ['min'=>0, 'max'=>0], []],
    ]],
], true).';');
putenv('VALIDATE_TOOLS_DIR='.$dir);
ini_set('error_log', $dir.'/error.log');

// Each run of the validator, as auto_prepend_file runs it for a request.
// $showKey = false hides the logged METHOD:PATH where parse_url() differs
// between PHP versions.
function run($method, $uri, $showKey = true)
{
    global $dir;
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    unset($GLOBALS['_validate_errors_']);
    @unlink($dir.'/error.log');
    // The tool is a closure run on include; copy it so it runs every time.
    $tool = $dir.'/input_validator_'.md5($method.$uri).'.php';
    $code = file_get_contents(__DIR__.'/../tools/input_validator.php');
    file_put_contents($tool, str_replace('__DIR__', var_export(realpath(__DIR__.'/../tools'), true), $code));
    include $tool;
    unlink($tool);
    $log = file_exists($dir.'/error.log') ? trim(preg_replace('/^\[[^]]*\] /m', '', file_get_contents($dir.'/error.log'))) : '';
    if (isset($GLOBALS['_validate_errors_'])) {
        $errors = $GLOBALS['_validate_errors_'];
        $result = $errors[0]['error']['ROOT'][0]['message'];
    } else {
        $result = 'no errors';
    }
    echo json_encode($method.' '.$uri), ': ', $result, "\n",
         '  log: ', $log === '' ? '(none)' : ($showKey ? $log : preg_replace('/^Validate: \S+ /', 'Validate: (key) ', $log)), "\n";
}
run('GET', '/index.php');            // Known endpoint, valid input (the spec ignores the rest).
run('GET', '/index.php/x');          // PATH_INFO spelling of a known path.
run('GET', '//index.php', false);    // A host for parse_url() of PHP 5.4.7+, a path before.
run('POST', '/index.php');           // Known path, unknown method.
run("G\x01T", '/index.php');          // Control char in the logged key.

unlink($dir.'/spec.php');
@unlink($dir.'/error.log');
rmdir($dir);
?>
--EXPECT--
"GET \/index.php": no errors
  log: (none)
"GET \/index.php\/x": No spec for this METHOD:PATH.
  log: Validate: GET:/index.php/x No spec for this METHOD:PATH.
"GET \/\/index.php": No spec for this METHOD:PATH.
  log: Validate: (key) No spec for this METHOD:PATH.
"POST \/index.php": No spec for this METHOD:PATH.
  log: Validate: POST:/index.php No spec for this METHOD:PATH.
"G\u0001T \/index.php": No spec for this METHOD:PATH.
  log: Validate: G\001T:/index.php No spec for this METHOD:PATH.
