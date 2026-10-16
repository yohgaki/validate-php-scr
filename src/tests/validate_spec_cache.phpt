--TEST--
validate(): specs that passed VALIDATE_OPT_CHECK_SPEC are remembered
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

// The cache is internal; read it through reflection.
function cache_state()
{
    $rp = new ReflectionProperty('Validate', 'checkedSpecs');
    if (PHP_VERSION_ID < 80100) {
        $rp->setAccessible(true);
    }
    $state = [];
    foreach ($rp->getValue() as $entry) {
        $state[] = $entry[1] ? 'trusted' : 'untrusted';
    }
    return count($state).' ['.implode(', ', $state).']';
}

function clear_cache()
{
    $rp = new ReflectionProperty('Validate', 'checkedSpecs');
    if (PHP_VERSION_ID < 80100) {
        $rp->setAccessible(true);
    }
    $rp->setValue(null, []);
}

// validate() with the default VALIDATE_OPT_CHECK_SPEC; prints the outcome.
function check($label, $input, $spec)
{
    $ctx = null;
    try {
        $r = validate($ctx, $input, $spec);
        echo "$label: ok ", var_export($r, true);
    } catch (ValidateInvalidSpecException $e) {
        echo "$label: ValidateInvalidSpecException";
    }
    echo ' / cache ', cache_state(), "\n";
}

$int = [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => 10]];

echo "**** first use, reuse, hit ****\n";
check('1st', '5', $int);
check('2nd', '6', $int);
check('3rd', '7', $int);
$copy = $int;
check('equal copy', '8', $copy);
clear_cache();
$ctx = null;
$in = '9';
validate($ctx, $in, $int, VALIDATE_OPT_NONE);
echo 'VALIDATE_OPT_NONE / cache ', cache_state(), "\n";
check('after clear', '9', $int);
check('again', '9', $int);

echo "\n**** a changed spec is checked again ****\n";
$bad = $int;
$bad[VALIDATE_OPTIONS]['max'] = 'x';
check('changed copy', '5', $bad);
check('changed copy again', '5', $bad);

echo "\n**** PHP references inside a spec ****\n";
clear_cache();
$opts = ['min' => 0, 'max' => 10];
$ref = [VALIDATE_INT, VALIDATE_FLAG_NONE, &$opts];
check('1st', '5', $ref);
$opts['max'] = 'x';
check('changed via reference while untrusted', '5', $ref);
$opts['max'] = 10;
check('restored', '5', $ref);
check('reused', '5', $ref);
$opts['max'] = 'x';
check('changed via reference while trusted', '5', $ref);
$opts['max'] = 10;
check('restored again', '5', $ref);

echo "\n**** objects match by instance ****\n";
clear_cache();
$make = function () {
    return [VALIDATE_CALLBACK, VALIDATE_CALLBACK_DIGIT, ['min' => 1, 'max' => 2,
        'callback' => function ($ctx, &$result, $input) {
            $result = (int)$input;
            return true;
        }]];
};
$cb = $make();
check('1st', '5', $cb);
check('2nd', '5', $cb);
check('new closure instance', '5', $make());

echo "\n**** recursive array stays untrusted ****\n";
clear_cache();
$rec = [1];
$rec[] = &$rec;
$recSpec = [VALIDATE_CALLBACK, VALIDATE_CALLBACK_DIGIT, ['min' => 1, 'max' => 2, 'default' => $rec,
    'callback' => function ($ctx, &$result, $input) {
        $result = $input;
        return true;
    }]];
check('1st', '5', $recSpec);
check('2nd', '5', $recSpec);
check('3rd', '5', $recSpec);

echo "\n**** bounded size ****\n";
clear_cache();
for ($i = 0; $i < 70; $i++) {
    $ctx = null;
    $in = '1';
    validate($ctx, $in, [VALIDATE_INT, VALIDATE_FLAG_NONE, ['min' => 0, 'max' => $i + 1]]);
}
echo 'after 70 specs: ', cache_state() === '64 ['.implode(', ', array_fill(0, 64, 'untrusted')).']' ? '64 untrusted' : cache_state(), "\n";

echo "\n**** validate_spec() always checks ****\n";
clear_cache();
$warn = [VALIDATE_ARRAY, VALIDATE_FLAG_ARRAY_KEY_ALNUM, ['min' => 0, 'max' => 1], []];
check('1st', [], $warn);
check('2nd', [], $warn);
$ctx = null;
$unvalidated = null;
var_dump(validate_spec($warn, $unvalidated, $ctx));
echo count(validate_get_system_errors($ctx)['warning']), " warning(s)\n";
?>
--EXPECT--
**** first use, reuse, hit ****
1st: ok 5 / cache 1 [untrusted]
2nd: ok 6 / cache 1 [trusted]
3rd: ok 7 / cache 1 [trusted]
equal copy: ok 8 / cache 1 [trusted]
VALIDATE_OPT_NONE / cache 0 []
after clear: ok 9 / cache 1 [untrusted]
again: ok 9 / cache 1 [trusted]

**** a changed spec is checked again ****
changed copy: ValidateInvalidSpecException / cache 1 [trusted]
changed copy again: ValidateInvalidSpecException / cache 1 [trusted]

**** PHP references inside a spec ****
1st: ok 5 / cache 1 [untrusted]
changed via reference while untrusted: ValidateInvalidSpecException / cache 1 [untrusted]
restored: ok 5 / cache 1 [trusted]
reused: ok 5 / cache 1 [trusted]
changed via reference while trusted: ValidateInvalidSpecException / cache 1 [trusted]
restored again: ok 5 / cache 1 [trusted]

**** objects match by instance ****
1st: ok 5 / cache 1 [untrusted]
2nd: ok 5 / cache 1 [trusted]
new closure instance: ok 5 / cache 2 [trusted, untrusted]

**** recursive array stays untrusted ****
1st: ok '5' / cache 1 [untrusted]
2nd: ok '5' / cache 1 [untrusted]
3rd: ok '5' / cache 1 [untrusted]

**** bounded size ****
after 70 specs: 64 untrusted

**** validate_spec() always checks ****
1st: ok array (
) / cache 1 [untrusted]
2nd: ok array (
) / cache 1 [trusted]
bool(true)
1 warning(s)
