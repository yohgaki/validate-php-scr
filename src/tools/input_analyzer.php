#!/usr/bin/env php
<?php
/**
 * Spec generator: consumes the per-request logs written by input_logger.php
 * and emits a Validate PHP spec covering the observed traffic.
 *
 * Pipeline:
 *   1. analyze_log()      — walk every *-log.php, fold each request into $stat
 *   2. create_stat()      — dump the raw $stat array to stat.php (debug aid)
 *   3. optimize_stat()    — turn observation counters into final flag/range values
 *   4. create_spec()      — translate $stat into spec.php for input_validator.php
 *
 * Usage: php input_analyzer.php [/path/to/log_dir]
 *        log_dir defaults to $VALIDATE_TOOLS_DIR, else /var/tmp/validate.
 *        The directory must be owned by the current user and must not be
 *        group/world writable: every *-log.php in it is include()d.
 */
require_once __DIR__.'/../Validate.php';
if (!defined('PHP_INT_MIN')) {
    define('PHP_INT_MIN', -PHP_INT_MAX - 1); // PHP 7.0+ constant
}

$spec_path = rtrim(getenv('VALIDATE_TOOLS_DIR') ?: '/var/tmp/validate', '/');

// Stat-array keys live in their own namespace so they cannot collide with
// VALIDATE_ID / VALIDATE_FLAGS / VALIDATE_OPTIONS used by real specs.
define('VALIDATE_VALUES', 'values');
define('VALIDATE_TYPES', 'types');
define('VALIDATE_COUNT', 'count');
define('VALIDATE_MAX_VALUES', 999); // Distinct sample values kept per node (caps memory growth).
define('VALIDATE_ASCII_MAP', 'ascii_map');

$log_dir = rtrim(isset($argv[1]) ? $argv[1] : $spec_path, '/');
$stat_file = $log_dir . '/stat.php';
$spec_file = $log_dir . '/spec.php';
if (!is_dir($log_dir)) {
    usage($argv);
    fail('Please specify logger dir', 1);
}
$st = stat($log_dir);
if (is_link($log_dir) || ($st['mode'] & 0022)
    || (function_exists('posix_geteuid') && $st['uid'] !== posix_geteuid())) {
    fail('Unsafe log dir (must be owned by this user and not group/world writable): '.$log_dir, 1);
}

// Check output spec file path
if (!touch($spec_file)) {
    fail('Failed to create spec file', 2);
}

// Get stat file
if (!touch($stat_file)) {
    fail('Failed to read or create stat file', 3);
}

$stat = analyze_log($log_dir);
if ($stat === false) {
    fail('Empty logs. Check log directory: '. $log_dir, 4);
}
create_stat($stat_file, $stat);
optimize_stat($stat);
create_spec($spec_file, $stat);

echo 'Done. Check generated spec file: '. $spec_file . "\n";
/* end main */

/* functions */

/**
 * Print an error and exit. (trigger_error(E_USER_ERROR) is deprecated in PHP 8.4.)
 */
function fail($message, $code)
{
    fwrite(STDERR, $message."\n");
    exit($code);
}


/**
 * Usage
 */
function usage($argv)
{
    echo "
Usage: {$argv[0]} [/path/to/log_dir]
'/path/to/log_dir' is optional. Default: '/var/tmp/validate'

";
}


/* Log Analyzer */

/**
 * Log analyzer wrapper
 */
function analyze_log($log_dir)
{
    $logs = glob($log_dir.'/*-log.php');
    if (empty($logs)) {
        return false;
    }

    $stat = array();
    foreach ($logs as $log) {
        include($log);
        assert(is_array($inputs));
        assert(isset($inputs['_SERVER']['REQUEST_URI']));
        $uri = parse_url($inputs['_SERVER']['REQUEST_URI'], PHP_URL_PATH);
        $method = isset($inputs['_SERVER']['REQUEST_METHOD']) ? $inputs['_SERVER']['REQUEST_METHOD'] : '';
        analyze_log_impl($stat[$method. ':' .$uri], $inputs);
    }

    return $stat;
}


/**
 * Walk one request's input tree, folding it into the running $stat node.
 * Tracks min/max element count, then descends into each child key.
 */
function analyze_log_impl(&$stat, $inputs)
{
    assert(is_array($inputs) || is_scalar($inputs) || is_null($inputs));

    if (!is_array($inputs)) {
        analyze_scalar_log($stat, $inputs);
        return;
    }

    $stat_opts = &$stat[VALIDATE_OPTIONS];
    if (empty($stat[VALIDATE_ID])) {
        $stat[VALIDATE_ID] = VALIDATE_ARRAY;
        $stat[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
        $stat_opts['lmin'] = isset($stat[VALIDATE_PARAMS]['lmin']) ? $stat[VALIDATE_PARAMS]['lmin'] : PHP_INT_MAX; // Set large enough value
        $stat_opts['lmax'] = isset($stat[VALIDATE_PARAMS]['lmax']) ? $stat[VALIDATE_PARAMS]['lmax'] : 0;
        $stat[VALIDATE_PARAMS] = array();
    }
    if ($stat[VALIDATE_ID] !== VALIDATE_ARRAY) {
        trigger_error('Type mismatch.');
        $stat = null;
        return null;
    }
    $stat_opts['lmin'] = $stat_opts['lmin'] < count($inputs) ? $stat_opts['lmin'] : count($inputs);
    $stat_opts['lmax'] = $stat_opts['lmax'] > count($inputs) ? $stat_opts['lmax'] : count($inputs);
    analyze_store_values($stat, $inputs);

    foreach ($inputs as $key => $input) {
        analyze_log_impl($stat[VALIDATE_PARAMS][$key], $input);
    }
}


function analyze_scalar_log(&$stat, $input)
{
    assert(is_scalar($input));
    $type = gettype($input);
    switch ($type) {
        case 'integer':
            return analyze_int_log($stat, $input);
            break;
        case 'double':
            return analyze_float_log($stat, $input);
            break;
        case 'string':
            return analyze_string_log($stat, $input);
            break;
        default:
            trigger_error('Invalid type: '. gettype($input));
    }
    return null;
}


function analyze_int_log(&$stat, $input)
{
    assert(is_int($input));

    $stat_opts = &$stat[VALIDATE_OPTIONS];
    if (empty($stat[VALIDATE_ID])) {
        $stat[VALIDATE_ID] = VALIDATE_INT;
        $stat[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
        $stat_opts = &$stat[VALIDATE_OPTIONS];
        $stat_opts['lmin'] = isset($stat[VALIDATE_PARAMS]['lmin']) ? $stat[VALIDATE_PARAMS]['lmin'] : PHP_INT_MAX; // Set large enough value
        $stat_opts['lmax'] = isset($stat[VALIDATE_PARAMS]['lmax']) ? $stat[VALIDATE_PARAMS]['lmax'] : PHP_INT_MIN;
    }
    if ($stat[VALIDATE_ID] !== VALIDATE_INT) {
        trigger_error('Type mismatch.');
        $stat = null;
        return null;
    }
    $stat_opts['lmin'] = $stat_opts['lmin'] < $input ? $stat_opts['lmin'] : $input;
    $stat_opts['lmax'] = $stat_opts['lmax'] > $input ? $stat_opts['lmax'] : $input;

    analyze_store_values($stat, $input);
    return $stat;
}


function analyze_float_log(&$stat, $input)
{
    assert(is_float($input));

    $stat_opts = &$stat[VALIDATE_OPTIONS];
    if (empty($stat[VALIDATE_ID])) {
        $stat[VALIDATE_ID] = VALIDATE_FLOAT;
        $stat[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
        $stat_opts['lmin'] = isset($stat[VALIDATE_PARAMS]['lmin']) ? $stat[VALIDATE_PARAMS]['lmin'] : INF; // Set large enough value
        $stat_opts['lmax'] = isset($stat[VALIDATE_PARAMS]['lmax']) ? $stat[VALIDATE_PARAMS]['lmax'] : -INF;
    }
    if ($stat[VALIDATE_ID] !== VALIDATE_FLOAT) {
        trigger_error('Type mismatch.');
        $stat = null;
        return null;
    }
    $stat_opts['lmin'] = $stat_opts['lmin'] < $input ? $stat_opts['lmin'] : $input;
    $stat_opts['lmax'] = $stat_opts['lmax'] > $input ? $stat_opts['lmax'] : $input;

    analyze_store_values($stat, $input);
    return $stat;
}


function analyze_string_log(&$stat, $input)
{
    assert(is_string($input));

    $stat_opts = &$stat[VALIDATE_OPTIONS];
    if (empty($stat[VALIDATE_ID])) {
        $stat[VALIDATE_ID] = VALIDATE_STRING;
        $stat[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
        $stat_opts = &$stat[VALIDATE_OPTIONS];
        $stat_opts['lmin'] = PHP_INT_MAX; // Set large enough value
        $stat_opts['lmax'] = PHP_INT_MIN;
    }
    if ($stat[VALIDATE_ID] !== VALIDATE_STRING) {
        trigger_error('Type mismatch.');
        $stat = null;
        return null;
    }
    $stat_opts['lmin'] = $stat_opts['lmin'] < strlen($input) ? $stat_opts['lmin'] : strlen($input);
    $stat_opts['lmax'] = $stat_opts['lmax'] > strlen($input) ? $stat_opts['lmax'] : strlen($input);

    analyze_string_ascii_map($stat, $input);
    analyze_store_values($stat, $input);
    return $stat;
}


/**
 * Build per-byte frequency map for a string value.
 *
 * Slot 0..126 = exact ASCII byte counts (used by optimize_string_stat() to
 *               decide which VALIDATE_STRING_* flags to enable).
 * Slot 127    = bucket for any non-ASCII byte (>= 0x7F). Presence here later
 *               triggers VALIDATE_STRING_MB.
 */
function analyze_string_ascii_map(&$stat, $input)
{
    assert(is_string($input));

    $map = array_fill(0, 128, 0);
    $tmp = isset($stat[VALIDATE_ASCII_MAP]) ? $stat[VALIDATE_ASCII_MAP] : array(); // Carry forward the counts from earlier samples.
    foreach ($tmp as $key => $val) {
        $map[$key] = $val;
    }
    $len = strlen($input);
    for ($i = 0; $i < $len; $i++) {
        $ch = ord($input[$i]);
        if ($ch >= 127) {
            $map[127]++; // Bucket every non-ASCII byte together.
        } else {
            $map[$ch]++;
        }
    }
    $stat[VALIDATE_ASCII_MAP] = $map;
}


/**
 * Record the type and value seen at this node so optimize_*_stat() can
 * later choose ranges and flags. Distinct values are capped at
 * VALIDATE_MAX_VALUES to keep memory and the dumped stat.php finite.
 */
function analyze_store_values(&$stat, $input)
{
    if (is_array($input)) {
        // For arrays, fold the key set into a single "shape" key so we still
        // count distinct array-shapes without storing every nested value.
        $in = join("\b", array_keys($input));
    } else {
        $in = $input;
    }
    $type = gettype($input);

    $stat[VALIDATE_TYPES][$type] = isset($stat[VALIDATE_TYPES][$type]) ? $stat[VALIDATE_TYPES][$type] : 0;
    $stat[VALIDATE_VALUES][$in] = isset($stat[VALIDATE_VALUES][$in]) ? $stat[VALIDATE_VALUES][$in] : 0;
    $stat[VALIDATE_COUNT] = isset($stat[VALIDATE_COUNT]) ? $stat[VALIDATE_COUNT] : 0;

    $stat[VALIDATE_TYPES][$type]++;
    $stat[VALIDATE_VALUES][$in]++;
    $stat[VALIDATE_COUNT]++;

    // Cap stored distinct values. array_pop drops an arbitrary entry, which is
    // fine: the goal is bounded memory, not a representative sample.
    if (count($stat[VALIDATE_VALUES]) > VALIDATE_MAX_VALUES) {
        array_pop($stat[VALIDATE_VALUES]);
    }
}



/* ============================================================
 * Stat optimizer
 *
 * Translates raw observation counters (lmin/lmax, ascii_map, ...) into the
 * final per-node values (omin/omax/oflags) that create_*_spec() will copy
 * into the generated spec.
 * ============================================================ */

function optimize_stat(&$stat)
{
    assert(is_array($stat));
    foreach ($stat as $uri => $val) {
        optimize_stat_recursive($stat[$uri]);
    }
}


function optimize_stat_recursive(&$stat)
{
    if (!is_array($stat) || !isset($stat[VALIDATE_ID])) {
        return; // Node dropped after a type mismatch between requests.
    }
    if ($stat[VALIDATE_ID] !== VALIDATE_ARRAY) {
        optimize_scalar_stat($stat);
        return;
    }
    foreach ($stat[VALIDATE_PARAMS] as $key => $val) {
        optimize_stat_recursive($stat[VALIDATE_PARAMS][$key]);
    }
}


function optimize_scalar_stat(&$stat)
{
    if (count(isset($stat[VALIDATE_TYPES]) ? $stat[VALIDATE_TYPES] : array()) > 1) {
        print_r($stat);
        trigger_error('Mixed types are not supported: '.join(', ', $stat[VALIDATE_TYPES]));
        $stat[VALIDATE_ID] = VALIDATE_STRING;
        return;
    }
    switch ($stat[VALIDATE_ID]) {
        case VALIDATE_INT:
            optimize_int_stat($stat);
            break;
        case VALIDATE_FLOAT:
            optimize_float_stat($stat);
            break;
        case VALIDATE_STRING:
            optimize_string_stat($stat);
            break;
        default:
            trigger_error('Invalid ID: '. $stat[VALIDATE_ID] . ' Stat: '.serialize($stat));
            $stat = null;
    }
    return;
}

/*
 * Range optimizers — pick min/max for the generated spec from the observed
 * range. Currently very conservative: ints and floats fall back to the full
 * domain because traffic alone can't safely bound them. TODO: tighten this
 * once the generator is confident the observed range is representative.
 */
function optimize_int_stat(&$stat)
{
    $stat[VALIDATE_OPTIONS]['omin'] = PHP_INT_MIN;
    $stat[VALIDATE_OPTIONS]['omax'] = PHP_INT_MAX;
}


function optimize_float_stat(&$stat)
{
    $stat[VALIDATE_OPTIONS]['omin'] = -INF;
    $stat[VALIDATE_OPTIONS]['omax'] = INF;
}


/**
 * Choose length bounds and character flags for a string node.
 *
 * Length: bucketed by the longest observed value, giving headroom over the
 * largest seen sample (a strict max would cause false rejects on natural
 * variance after deployment).
 * Flags : derived from VALIDATE_ASCII_MAP — only character classes that
 *         actually appeared in traffic are enabled, so the generated spec
 *         stays as restrictive as the data allows.
 */
function optimize_string_stat(&$stat)
{
    $stat[VALIDATE_OPTIONS]['omin'] = 0;
    if ($stat[VALIDATE_OPTIONS]['lmax'] > 300) {
        $stat[VALIDATE_OPTIONS]['omax'] = 1024*256;
    } elseif ($stat[VALIDATE_OPTIONS]['lmax'] > 50) {
        $stat[VALIDATE_OPTIONS]['omax'] = 1024;
    } else {
        $stat[VALIDATE_OPTIONS]['omax'] = 100;
    }
    $stat[VALIDATE_OPTIONS]['oflags'] = isset($stat[VALIDATE_OPTIONS]['oflags']) ? $stat[VALIDATE_OPTIONS]['oflags'] : 0;
    foreach ((isset($stat[VALIDATE_ASCII_MAP]) ? $stat[VALIDATE_ASCII_MAP] : array()) as $key => $val) {
        if (!$val) {
            continue; // Byte never seen in traffic.
        }
        if ($key >= ord('0') && $key <= ord('9')) {
            $stat[VALIDATE_OPTIONS]['oflags'] |= VALIDATE_STRING_DIGIT;
        } elseif ($key >= ord('A') && $key <= ord('Z')) {
            $stat[VALIDATE_OPTIONS]['oflags'] |= VALIDATE_STRING_UPPER_ALPHA;
        } elseif ($key >= ord('a') && $key <= ord('z')) {
            $stat[VALIDATE_OPTIONS]['oflags'] |= VALIDATE_STRING_LOWER_ALPHA;
        } elseif ($key === 127) {
            $stat[VALIDATE_OPTIONS]['oflags'] |= VALIDATE_STRING_MB; // Non-ASCII bucket.
        }
    }
}


/**
 * Persist the raw $stat tree as PHP source.
 *
 * The file is for human inspection and rerunning the optimizer; it is NOT
 * consumed at validation time (input_validator.php only loads spec.php).
 */
function create_stat($stat_file, $stat)
{
    $str_stat = '<?php
 $stat =
';
    $str_stat .= var_export($stat, true);
    $str_stat .= ';';
    file_put_contents($stat_file, $str_stat);
}


/* ============================================================
 * Spec emitter
 *
 * Translates the optimized $stat tree into a Validate PHP spec and writes
 * it to disk as PHP source. input_validator.php loads this file directly.
 * ============================================================ */

/**
 * Walk every endpoint in $stat, build a matching spec, and dump as PHP source.
 */
function create_spec($spec_file, $stat)
{
    $spec = array();
    // TODO Implement nicer exporter that supports "string" ID, FLAGS.
    foreach ($stat as $uri => $val) {
        create_spec_recursive($spec[$uri], $val);
    }
    $str_spec = '<?php
$spec =
';
    $str_spec .= var_export($spec, true);
/*
    $str_spec .= '

$EXTRA_SPECS =
    ';
    $str_spec .= var_export($extra_spec);
*/
    $str_spec .= ';';
    file_put_contents($spec_file, $str_spec);
}


function create_spec_recursive(&$spec, $stat)
{
    if (!is_array($stat) || !isset($stat[VALIDATE_ID])) {
        return null; // Node dropped after a type mismatch between requests.
    }
    if ($stat[VALIDATE_ID] !== VALIDATE_ARRAY) {
        return create_scalar_spec($spec, $stat);
    }

    assert($stat[VALIDATE_ID] === VALIDATE_ARRAY);
    $spec[VALIDATE_ID] = VALIDATE_ARRAY;
    $spec[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
    $spec[VALIDATE_OPTIONS]['min'] = $stat[VALIDATE_OPTIONS]['lmin'];
    // +10 slack on the upper element count: real traffic almost never sees
    // every optional parameter, so a strict max would cause false rejects.
    // See README TODO list — replace with proper optional-param detection.
    $spec[VALIDATE_OPTIONS]['max'] = $stat[VALIDATE_OPTIONS]['lmax'] + 10;
    $params = array();
    foreach ($stat[VALIDATE_PARAMS] as $key => $val) {
        $child = null;
        $child = create_spec_recursive($child, $val);
        if ($child !== null) {
            $params[$key] = $child;
        }
    }
    $spec[VALIDATE_PARAMS] = $params;
    return $spec;
}

/**
 * Dispatch a scalar stat node to the matching per-type spec builder.
 */
function create_scalar_spec($spec, $stat)
{
    assert($stat[VALIDATE_ID] !== VALIDATE_ARRAY);
    $type = $stat[VALIDATE_ID];
    switch ($type) {
        case VALIDATE_INT:
            return create_int_spec($spec, $stat);
            break;
        case VALIDATE_FLOAT:
            return create_float_spec($spec, $stat);
            break;
        case VALIDATE_STRING:
            return create_string_spec($spec, $stat);
            break;
        default:
            trigger_error('Invalid type: '. $type);
    }
    return null;
}


function create_int_spec($spec, $stat)
{
    assert($stat[VALIDATE_ID] === VALIDATE_INT);
    $spec[VALIDATE_ID] = VALIDATE_INT;
    $spec[VALIDATE_FLAGS] = (isset($stat[VALIDATE_OPTIONS]['oflags']) ? $stat[VALIDATE_OPTIONS]['oflags'] : 0);
    $spec[VALIDATE_OPTIONS]['min'] = $stat[VALIDATE_OPTIONS]['omin'];
    $spec[VALIDATE_OPTIONS]['max'] = $stat[VALIDATE_OPTIONS]['omax'];
    return $spec;
}


function create_float_spec($spec, $stat)
{
    assert($stat[VALIDATE_ID] === VALIDATE_FLOAT);
    $spec[VALIDATE_ID] = VALIDATE_FLOAT;
    $spec[VALIDATE_FLAGS] = (isset($stat[VALIDATE_OPTIONS]['oflags']) ? $stat[VALIDATE_OPTIONS]['oflags'] : 0);
    $spec[VALIDATE_OPTIONS]['min'] = $stat[VALIDATE_OPTIONS]['omin'];
    $spec[VALIDATE_OPTIONS]['max'] = $stat[VALIDATE_OPTIONS]['omax'];
    return $spec;
}


function create_string_spec($spec, $stat)
{
    assert($stat[VALIDATE_ID] === VALIDATE_STRING);
    $spec[VALIDATE_ID] = VALIDATE_STRING;
    $spec[VALIDATE_FLAGS] = (isset($stat[VALIDATE_OPTIONS]['oflags']) ? $stat[VALIDATE_OPTIONS]['oflags'] : 0);
    $spec[VALIDATE_OPTIONS]['min'] = $stat[VALIDATE_OPTIONS]['omin'];
    $spec[VALIDATE_OPTIONS]['max'] = $stat[VALIDATE_OPTIONS]['omax'];
    if (!empty($stat[VALIDATE_ASCII_MAP])) {
        // Raw bytes: "ascii" is a strspn()-style character list, not an
        // escaped string. Letters/digits are covered by the flags above and
        // slot 127 is the non-ASCII bucket (VALIDATE_STRING_MB).
        $spec[VALIDATE_OPTIONS]['ascii'] = '';
        foreach ($stat[VALIDATE_ASCII_MAP] as $key => $val) {
            if ($val > 0 && $key < 127 && !preg_match('/[A-Za-z0-9]/', chr($key))) {
                $spec[VALIDATE_OPTIONS]['ascii'] .= chr($key);
            }
        }
    }
    return $spec;
}
