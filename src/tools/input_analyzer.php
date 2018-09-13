#!/usr/bin/env php
<?php
/**
 * Simple input analyzer.
 *
 * Execute script, "php input_analyzer.php /path/to/log_dir"
 */
require_once __DIR__.'/../Validate.php';

$spec_path = '/var/tmp/validate';

define('VALIDATE_VALUES', 'values');
define('VALIDATE_TYPES', 'types');
define('VALIDATE_COUNT', 'count');
define('VALIDATE_MAX_VALUES', 999); // Max number of values keep in log analysis.
define('VALIDATE_ASCII_MAP', 'ascii_map');

$log_dir = $argv[1] ?? $spec_path ;
$stat_file = $log_dir . '/stat.php';
$spec_file = $log_dir . '/spec.php';
if (!is_dir($log_dir)) {
    usage($argv);
    trigger_error('Please specify logger dir', E_USER_ERROR);
}

// Check output spec file path
if (!touch($spec_file)) {
    trigger_error('Failed to create spec file', E_USER_ERROR);
    exit(-1);
}

// Get stat file
if (!touch($stat_file)) {
    trigger_error('Failed to read or create stat file', E_USER_ERROR);
    exit(-2);
}
if (empty($stat)) {
    $stat = array();
}

$stat = analyze_log($log_dir) ;
create_stat($stat_file, $stat);
optimize_stat($stat);
create_spec($spec_file, $stat);

echo 'Done. Check generated spec file: '. $spec_file . "\n";
/* end main */

/* functions */

/**
 * Usage
 */
function usage($argv) {
    echo "
Usage: {$argv[0]} [/path/to/log_dir]
'/path/to/log_dir' is optional. Default: '/var/tmp/validate'

";
}


/* Log Aanalyzer */

/**
 * Log analyzer wrapper
 */
function analyze_log($log_dir) {
    $logs = glob($log_dir.'/*-log.php');
    if (empty($logs)) {
        trigger_error('Empty logs. Check log directory: '. $log_dir);
        return false;
    }

    $stat = array();
    foreach ($logs as $log) {
        include($log);
        assert(is_array($inputs));
        assert(isset($inputs['_SERVER']['REQUEST_URI']));
        $uri = parse_url($inputs['_SERVER']['REQUEST_URI'], PHP_URL_PATH);
        $method = $inputs['_SERVER']['REQUEST_METHOD'] ?? '';
        analyze_log_impl($stat[$method. ':' .$uri], $inputs);
    }

    return $stat;
}


/**
 * Recursively analyze input data.
 */
function analyze_log_impl(&$stat, $inputs) {
    assert(is_array($inputs) || is_scalar($inputs) || is_null($inputs));

    if (!is_array($inputs)) {
        analyze_scalar_log($stat, $inputs);
        return;
    }

    $stat_opts = &$stat[VALIDATE_OPTIONS];
    if (empty($stat[VALIDATE_ID])) {
        $stat[VALIDATE_ID] = VALIDATE_ARRAY;
        $stat[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
        $stat_opts['lmin'] = $stat[VALIDATE_PARAMS]['lmin'] ?? PHP_INT_MAX; // Set large enough value
        $stat_opts['lmax'] = $stat[VALIDATE_PARAMS]['lmax'] ?? 0;
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


function analyze_scalar_log(&$stat, $input) {
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


function analyze_int_log(&$stat, $input) {
    assert(is_int($input));

    $stat_opts = &$stat[VALIDATE_OPTIONS];
    if (empty($stat[VALIDATE_ID])) {
        $stat[VALIDATE_ID] = VALIDATE_INT;
        $stat[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
        $stat_opts = &$stat[VALIDATE_OPTIONS];
        $stat_opts['lmin'] = $stat[VALIDATE_PARAMS]['lmin'] ?? PHP_INT_MAX; // Set large enough value
        $stat_opts['lmax'] = $stat[VALIDATE_PARAMS]['lmax'] ?? PHP_INT_MIN;
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


function analyze_float_log(&$stat, $input) {
    assert(is_float($input));

    $stat_opts = &$stat[VALIDATE_OPTIONS];
    if (empty($stat[VALIDATE_ID])) {
        $stat[VALIDATE_ID] = VALIDATE_FLOAT;
        $stat[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
        $stat_opts['lmin'] = $stat[VALIDATE_PARAMS]['lmin'] ?? INF; // Set large enough value
        $stat_opts['lmax'] = $stat[VALIDATE_PARAMS]['lmax'] ?? -INF;
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


function analyze_string_log(&$stat, $input) {
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


function analyze_string_ascii_map(&$stat, $input) {
    assert(is_string($input));

    $map = array_fill(0, 128, 0);
    $tmp = $stat[VALIDATE_ASCII_MAP] ?? array(); // Set previously found chars
    foreach($tmp as $key => $input) {
        $map[$key] = $input;
    }
    $len = strlen($input);
    for ($i = 0; $i < $len; $i++) {
        $ch = ord($input{$i});
        if ($ch >= 127) {
            $map[127]++;
        } else {
            $map[$ch]++;
        }
    }
    $stat[VALIDATE_ASCII_MAP] = $map;
}


function analyze_store_values(&$stat, $input) {
    if (is_array($input)) {
        $in = join("\b", array_keys($input));
    } else {
        $in = $input;
    }
    $type = gettype($input);

    $stat[VALIDATE_TYPES][$type] = $stat[VALIDATE_TYPES][$type] ?? 0;
    $stat[VALIDATE_VALUES][$in] = $stat[VALIDATE_VALUES][$in] ?? 0;
    $stat[VALIDATE_COUNT] = $stat[VALIDATE_COUNT] ?? 0;

    $stat[VALIDATE_TYPES][$type]++;
    $stat[VALIDATE_VALUES][$in]++;
    $stat[VALIDATE_COUNT]++;

    // Remove excessive values
    if (count($stat[VALIDATE_VALUES]) > VALIDATE_MAX_VALUES) {
        array_pop($stat[VALIDATE_VALUES]);
    }
}



/* Stat optimizer */

function optimize_stat(&$stat) {
    assert(is_array($stat));
    foreach($stat as $uri => $val) {
        optimize_stat_recursive($stat[$uri]);
    }
}


function optimize_stat_recursive(&$stat) {
    if ($stat[VALIDATE_ID] !== VALIDATE_ARRAY) {
        optimize_scalar_stat($stat);
        return;
    }
    foreach ($stat[VALIDATE_PARAMS] as $key => $val) {
        optimize_stat_hint($stat[VALIDATE_PARAMS][$key], $key);
        optimize_stat_recursive($stat[VALIDATE_PARAMS][$key]);
    }
}


function optimize_stat_hint(&$stat, $key) {
    if (preg_match('/mail/i', $key)) {
        $stat['hint']['email'] = $stat['hint']['email'] ?? 0;
        $stat['hint']['email']++;
    }
    if (preg_match('/password|passwd/i', $key)) {
        $stat['hint']['password'] = $stat['hint']['password'] ?? 0;
        $stat['hint']['password']++;
    }
    if (preg_match('/id\\z/i', $key)) {
        if (strlen($key) === strspn($key, '1234567890')) {
            $stat['hint']['int_id'] = $stat['hint']['int_id'] ?? 0;
            $stat['hint']['int_id']++;
        } elseif (preg_match('/phpsessid/i', $key)) {
            $stat['hint']['sessid'] = $stat['hint']['sessid'] ?? 0;
            $stat['hint']['sessid']++;
        } else {
            $stat['hint']['str_id'] = $stat['hint']['str_id'] ?? 0;
            $stat['hint']['str_id']++;
        }
    }
    if (preg_match('/name/i', $key)) {
        $stat['hint']['name'] = $stat['hint']['name'] ?? 0;
        $stat['hint']['name']++;
    }
    if (preg_match('/addr/i', $key)) {
        if (preg_match('/ip/i', $key) && strlen($key) === strspn($key, '1234567890.')) {
            $stat['hint']['ipv4'] = $stat['hint']['ipv4'] ?? 0;
            $stat['hint']['ipv4']++;
        } else {
            $stat['hint']['address'] = $stat['hint']['address'] ?? 0;
            $stat['hint']['address']++;
        }
    }
    if (preg_match('/key/i', $key)) {
        $stat['hint']['key'] = $stat['hint']['key'] ?? 0;
        $stat['hint']['key']++;
    }
    foreach ($stat[VALIDATE_VALUES] as $val => $cnt) {
        if (strlen($val) < 10) {
            $stat['hint']['len_10'] = $stat['hint']['len_10'] ?? 0;
            $stat['hint']['len_10']++;
        } elseif (strlen($val) < 20) {
            $stat['hint']['len_20'] = $stat['hint']['len_20'] ?? 0;
            $stat['hint']['len_20']++;
        } elseif (strlen($val) < 50) {
            $stat['hint']['len_50'] = $stat['hint']['len_50'] ?? 0;
            $stat['hint']['len_50']++;
        } elseif (strlen($val) < 100) {
            $stat['hint']['len_100'] = $stat['hint']['len_100'] ?? 0;
            $stat['hint']['len_100']++;
        } elseif (strlen($val) < 200) {
            $stat['hint']['len_200'] = $stat['hint']['len_200'] ?? 0;
            $stat['hint']['len_200']++;
        } else {
            $stat['hint']['len_200+'] = $stat['hint']['len_200+'] ?? 0;
            $stat['hint']['len_200+']++;
        }
        if (preg_match('/\\n|\\r/', $val)) {
            $stat['hint']['newline'] = $stat['hint']['newline'] ?? 0;
            $stat['hint']['newline']++;
        }
        if (preg_match('/\\A\\d+\\z/', $val)) {
            $stat['hint']['int'] = $stat['hint']['int'] ?? 0;
            $stat['hint']['int']++;
        }
        if (preg_match('/\\A\\d+\\.\\d+\\z/', $val)) {
            $stat['hint']['float'] = $stat['hint']['float'] ?? 0;
            $stat['hint']['float']++;
        }
        if (preg_match('/ /', $val)) {
            $stat['hint']['space'] = $stat['hint']['space'] ?? 0;
            $stat['hint']['space']++;
        }
        if (preg_match('/\\A[0-9a-f]+/i', $key)) {
            $stat['hint']['hex'] = $stat['hint']['hex'] ?? 0;
            $stat['hint']['hex']++;
        }
    }
 }


function optimize_scalar_stat(&$stat) {
    if (count($stat[VALIDATE_TYPES]) > 1) {
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
            trigger_error('Invalid ID: '. $stat[VALIDATE_ID]) . ' Stat: '.serialize($stat);
            $stat = null;
    }
    return;
}

/**
 * Do some optimization
 * TODO Add real optimization
 */
function optimize_int_stat(&$stat) {
    // Cannot automatically set reliable range
    $stat[VALIDATE_OPTIONS]['omin'] = PHP_INT_MIN;
    $stat[VALIDATE_OPTIONS]['omax'] = PHP_INT_MAX;
}


function optimize_float_stat(&$stat) {
    // Cannot automatically set reliable range
    $stat[VALIDATE_OPTIONS]['omin'] = -INF;
    $stat[VALIDATE_OPTIONS]['omax'] = INF;
}


function optimize_string_stat(&$stat) {
    // Cannot automatically set reliable length
    $stat[VALIDATE_OPTIONS]['omin'] = 0;
    if ($stat[VALIDATE_OPTIONS]['lmax'] > 300) {
        $stat[VALIDATE_OPTIONS]['omax'] = 1024*256;
    } else if ($stat[VALIDATE_OPTIONS]['lmax'] > 50) {
        $stat[VALIDATE_OPTIONS]['omax'] = 1024;
    } else {
        $stat[VALIDATE_OPTIONS]['omax'] = 100;
    }
    foreach($stat[VALIDATE_ASCII_MAP] as $key => $val) {
        $stat[VALIDATE_OPTIONS]['oflags'] = $stat[VALIDATE_OPTIONS]['oflags'] ?? 0;
        if ($key >= ord('0') && $key <= ord('9')) {
            $stat[VALIDATE_OPTIONS]['oflags'] |= VALIDATE_STRING_DIGIT;
        }
        if ($key >= ord('A') && $key <= ord('Z')) {
            $stat[VALIDATE_OPTIONS]['oflags'] |= VALIDATE_STRING_UPPER_ALPHA;
        }
        if ($key >= ord('a') && $key <= ord('z')) {
            $stat[VALIDATE_OPTIONS]['oflags'] |= VALIDATE_STRING_LOWER_ALPHA;
        }
        if (!empty($stat[VALIDATE_ASCII_MAP][127])) {
            $stat[VALIDATE_OPTIONS]['oflags'] |= VALIDATE_STRING_MB;
        }
    }
}


/**
 * Create stat file
 */
function create_stat($stat_file, $stat) {
    $str_stat = '<?php
 $stat =
';
    $str_stat .= var_export($stat, true);
    $str_stat .= ';';
    file_put_contents($stat_file, $str_stat);
}


/* Create SPEC */

/**
 * Create SPEC file
 */
function create_spec($spec_file, $stat) {
    $spec = array();
    // TODO Implement nicer exporter that supports "string" ID, FLAGS.
    foreach($stat as $uri => $val) {
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


function create_spec_recursive(&$spec, $stat) {
    if ($stat[VALIDATE_ID] !== VALIDATE_ARRAY) {
        return create_scalar_spec($spec, $stat);
    }

    assert($stat[VALIDATE_ID] === VALIDATE_ARRAY);
    $spec[VALIDATE_ID] = VALIDATE_ARRAY;
    $spec[VALIDATE_FLAGS] = VALIDATE_FLAG_NONE;
    $spec[VALIDATE_OPTIONS]['min'] = $stat[VALIDATE_OPTIONS]['lmin'];
    // Allow 10 more extra vars because number of elements is not reliable.
    $spec[VALIDATE_OPTIONS]['max'] = $stat[VALIDATE_OPTIONS]['lmax'] + 10;
    $params = [];
    foreach($stat[VALIDATE_PARAMS] as $key => $val) {
        $params[$key] = create_spec_recursive($stat[$key], $val);
    }
    $spec[VALIDATE_PARAMS] = $params;
    return $spec;
}

/**
 * Create spec from stat data
 */
function create_scalar_spec($spec, $stat) {
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


function create_int_spec($spec, $stat) {
    assert($stat[VALIDATE_ID] === VALIDATE_INT);
    $spec[VALIDATE_ID] = VALIDATE_INT;
    $spec[VALIDATE_FLAGS] = ($stat[VALIDATE_OPTIONS]['oflags'] ?? 0);
    $spec[VALIDATE_OPTIONS]['min'] = $stat[VALIDATE_OPTIONS]['omin'];
    $spec[VALIDATE_OPTIONS]['max'] = $stat[VALIDATE_OPTIONS]['omax'];
    return $spec;
}


function create_float_spec($spec, $stat) {
    assert($stat[VALIDATE_ID] === VALIDATE_FLOAT);
    $spec[VALIDATE_ID] = VALIDATE_FLOAT;
    $spec[VALIDATE_FLAGS] = ($stat[VALIDATE_OPTIONS]['oflags'] ?? 0);
    $spec[VALIDATE_OPTIONS]['min'] = $stat[VALIDATE_OPTIONS]['omin'];
    $spec[VALIDATE_OPTIONS]['max'] = $stat[VALIDATE_OPTIONS]['omax'];
    return $spec;
}


function create_string_spec($spec, $stat) {
    assert($stat[VALIDATE_ID] === VALIDATE_STRING);
    $spec[VALIDATE_ID] = VALIDATE_STRING;
    $spec[VALIDATE_FLAGS] = ($stat[VALIDATE_OPTIONS]['oflags'] ?? 0);
    $spec[VALIDATE_OPTIONS]['min'] = $stat[VALIDATE_OPTIONS]['omin'];
    $spec[VALIDATE_OPTIONS]['max'] = $stat[VALIDATE_OPTIONS]['omax'];
    if (!empty($stat[VALIDATE_ASCII_MAP])) {
        $spec[VALIDATE_OPTIONS]['ascii'] = '';
        foreach ($stat[VALIDATE_ASCII_MAP] as $key => $val) {
            if ($val) {
                $spec[VALIDATE_OPTIONS]['ascii'] .= addslashes(chr($key));
            }
        }
    }
    return $spec;
}
