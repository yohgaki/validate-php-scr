<?php
/**
 * Simple yet flexible, powerful, and reasonably fast input validator that
 * provides a basic validation framework for complex validations.
 *
 * Namespaces are intentionally NOT used. These procedural wrappers mirror
 * the API of the planned "validate" PHP C extension; keeping the same
 * structure/naming makes the future port to C easier.
 *
 * The code optimizes for a procedural shape (with full access to module
 * globals) rather than an idiomatic OO design.
 *
 * PHP Version 8.0 or higher.
 *
 * https://github.com/yohgaki/validate-php-scr
 *
 * @package  Validate
 * @category Validation
 * @author   Yasuo Ohgaki <yohgaki@ohgaki.net>
 * @license  MIT https://github.com/yohgaki/validate-php-scr/blob/master/LICENSE
 */

require_once __DIR__.'/Validate.php';


/**
 * Initialize a Validate context. (Will be a resource handle in the future C module.)
 *
 * @param string $root_name Root variable name shown in error reports.
 *
 * @return Validate Fresh Validate context object.
 */
function validate_init($root_name = 'ROOT')
{
    assert(is_string($root_name));
    $ctx = new Validate($root_name);
    return $ctx;
}


/**
 * Validate inputs against a spec.
 *
 * Primary entry point of the framework. By default this also checks the spec
 * format itself (VALIDATE_OPT_CHECK_SPEC) — leave that on while developing,
 * then drop it in production for speed once the specs are known to be correct.
 *
 * $inputs is passed by reference: keys that pass validation are unset, so
 * after the call $inputs holds only the leftover (unvalidated) input. This
 * lets a caller validate a request in multiple stages or detect unexpected
 * extra parameters. Pass VALIDATE_OPT_KEEP_INPUTS to keep $inputs untouched.
 *
 * @param Validate|null $ctx       Validate context. If null, a fresh one is created and assigned back.
 * @param mixed         $inputs    Scalar or array of inputs. Validated elements are removed by reference.
 * @param array         $specs     Spec array. See validate_spec() for the format.
 * @param int           $func_opts Bitmask of VALIDATE_OPT_* flags controlling function behavior.
 *
 * @return mixed Validated value(s), or null on failure when exceptions are disabled.
 */
function validate(&$ctx, &$inputs, $specs, $func_opts = VALIDATE_OPT_CHECK_SPEC)
{
    if (!(is_null($ctx) || ($ctx instanceof Validate))) {
        throw new InvalidArgumentException('1st parameter is not a Validate context.');
    }
    if (!is_array($specs)) {
        throw new InvalidArgumentException('Spec must be array.');
    } elseif (($func_opts & VALIDATE_OPT_CHECK_SPEC)
         && !validate_spec($specs, $unvalidatedSpecs, $specCheckCtx)) {
        print_r($specCheckCtx->getSystemErrors());
        throw new InvalidArgumentException('Invalid validation spec detected. Fix spec errors first.');
    }
    if (!is_int($func_opts)) {
        throw new InvalidArgumentException('Function option must be int.');
    }

    //assert(validate_spec($specs));

    if (!$ctx) {
        $ctx = new Validate();
    }

    $ctx->validate_params_checked = true;
    $validated = $ctx->validate($inputs, $specs, $func_opts);

    return $validated;
}


/**
 * Get the overall validation status of a context.
 *
 * @param Validate $ctx The Validate context.
 *
 * @return bool|null True if all validations passed, false on failure,
 *                   null if validate() has not been called yet.
 */
function validate_get_status($ctx)
{
    assert($ctx instanceof Validate);
    return $ctx->getStatus();
}


/**
 * Set the PHP error level used when a hard validation error is dispatched.
 *
 * @param Validate $ctx   The Validate context.
 * @param int      $level Error level constant. One of E_USER_ERROR / E_USER_WARNING / E_USER_NOTICE.
 *
 * @return null
 */
function validate_set_error_level($ctx, $level)
{
    assert($ctx instanceof Validate);
    $ctx->setErrorLevel($level);
}


/**
 * Report a user-land validation error. Intended for use from "callback" validators.
 *
 * @param Validate $ctx     The Validate context.
 * @param string   $message Error message to record.
 *
 * @return null
 */
function validate_error($ctx, $message)
{
    assert($ctx instanceof Validate);
    assert(is_string($message));

    $ctx->error($message);
}


/**
 * Report a user-land validation warning. Intended for use from "callback" validators.
 *
 * @param Validate $ctx     The Validate context.
 * @param string   $message Warning message to record.
 *
 * @return null
 */
function validate_warning($ctx, $message)
{
    assert($ctx instanceof Validate);
    assert(is_string($message));

    $ctx->warning($message);
}


/**
 * Report a user-land validation notice. Intended for use from "callback" validators.
 *
 * @param Validate $ctx     The Validate context.
 * @param string   $message Notice message to record.
 *
 * @return null
 */
function validate_notice($ctx, $message)
{
    assert($ctx instanceof Validate);
    assert(is_string($message));

    $ctx->notice($message);
}


/**
 * Get system error messages recorded by the validator itself
 * (broken types, length violations, illegal characters, etc.).
 *
 * @param Validate $ctx The Validate context.
 *
 * @return array Bucketed errors: ['error' => [...], 'warning' => [...], 'notice' => [...]].
 */
function validate_get_system_errors($ctx)
{
    assert($ctx instanceof Validate);
    return $ctx->getSystemErrors();
}


/**
 * Get user-defined messages reported via validate_error/_warning/_notice()
 * or via the 'error_message' spec option. Use these for interactive form
 * feedback while system errors stay internal.
 *
 * @param Validate $ctx The Validate context.
 *
 * @return array Bucketed messages: ['error' => [...], 'warning' => [...], 'notice' => [...]].
 */
function validate_get_user_errors($ctx)
{
    assert($ctx instanceof Validate);
    return $ctx->getUserErrors();
}


/**
 * Set the error-logger callback. The logger is only invoked when
 * VALIDATE_OPT_LOG_ERROR is set in $func_opts on the validate() call.
 * Default behavior (with no custom logger registered) is trigger_error().
 *
 * @param Validate $ctx         The Validate context.
 * @param callable $logger_func Callback taking a single string argument (the error message).
 *
 * @return null
 */
function validate_set_logger_function($ctx, $logger_func)
{
    assert($ctx instanceof Validate);
    assert(is_callable($logger_func));
    $ctx->setLoggerFunction($logger_func);
}


/**
 * Validate the structure of a spec array itself (not the input data).
 *
 * Called automatically by validate() when VALIDATE_OPT_CHECK_SPEC is set
 * (the default). Call it directly during development to surface spec errors
 * before any input is touched.
 *
 * A spec array uses integer offsets (VALIDATE_ID / VALIDATE_FLAGS /
 * VALIDATE_OPTIONS / VALIDATE_PARAMS) so it can compile to a fixed-shape
 * struct in the future C extension:
 *
 *   $specs = [
 *       'POST' => [
 *           VALIDATE_ARRAY,                  // [0] validator type
 *           VALIDATE_FLAG_NONE,              // [1] bitmask of flags
 *           ['amin' => 0, 'amax' => 20],     // [2] options (string keys)
 *           [                                // [3] sub-specs for ARRAY/MULTI
 *               'scalar_param' => [
 *                   VALIDATE_STRING,
 *                   VALIDATE_STRING_ALNUM | VALIDATE_STRING_LF,
 *                   ['min' => 0, 'max' => 125],
 *               ],
 *               'array_param' => [           // Nested arrays are allowed.
 *                   VALIDATE_ARRAY,
 *                   VALIDATE_FLAG_NONE,
 *                   [],
 *                   [
 *                       'flag' => [VALIDATE_BOOL, VALIDATE_BOOL_01, []],
 *                   ],
 *               ],
 *           ],
 *       ],
 *   ];
 *
 * @param array         $specs       Validation spec array to check.
 * @param array|null    $unvalidated Output. Receives entries from $specs that could not be checked.
 * @param Validate|null $ctx         Optional context. A fresh one is created if null.
 *
 * @return bool true if the spec is well-formed, false otherwise.
 */
function validate_spec($specs, &$unvalidated = null, &$ctx = null)
{
    if (!is_array($specs)) {
        throw new InvalidArgumentException('1st parameter must be validation spec array.');
    }
    if (is_null($ctx)) {
        $ctx = new Validate;
    } elseif (!($ctx instanceof Validate)) {
        throw new InvalidArgumentException('3rd parameter must be instance of Validate object.');
    }
    $ctx->spec_params_checked = true;
    $ret = $ctx->validateSpec($specs, $unvalidated, $ctx);
    $ctx->spec_params_checked = false;
    return $ret;
}
