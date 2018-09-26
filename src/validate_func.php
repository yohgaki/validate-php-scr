<?php
/**
 * Simple yet flexible, powerful and reasonably fast input validator
 * that provides basic validation framework for fancy/complex validations.
 *
 * Name space is not used intentionally. This function is
 * designed to be compatible with "validate" PHP module
 * written by C.
 *
 * This code is trying to keep common structure/name with C module.
 * Therefore, this code is not aim to have optimal OO design, but
 * optimal Procedural design that has full access to module globals.
 *
 * PHP Version 7.0 or up
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
 * Initialize validate object(it may be resource in C module)
 *
 * @param mixed $root_name Root variable name. Root variable name is unknown to program.
 *
 * @return array The context for Validate.
 */
function validate_init($root_name = 'ROOT')
{
    assert(is_string($root_name));
    $ctx = new Validate($root_name);
    return $ctx;
}


/**
 * This function expect correct $specs array for maximum efficiency.
 *
 * @param array    $ctx  Validate context.
 * @param mixed    $inputs    Scalar or array values. Validated elements are removed.
 * @param array    $specs     Spec array.
 * @param int      $func_opts Bit function flags controls function behaviors.
 *
 * @return mixed Validated values.
 */
function validate(&$ctx, &$inputs, $specs, $func_opts = VALIDATE_OPT_CHECK_SPEC)
{
    if (!(is_null($ctx) || ($ctx instanceof Validate))) {
        trigger_error('1st parameter is not a Validate context.', E_USER_ERROR);
        return false;
    }
    if (!is_array($specs)) {
        trigger_error('Spec must be array.', E_USER_ERROR);
        return false;
    } elseif (($func_opts & VALIDATE_OPT_CHECK_SPEC)
         && !validate_spec($specs, $r, $tmp)) {
        print_r($tmp->getSystemErrors());
        trigger_error('Invalid validation spec detected. Fix spec errors first.', E_USER_ERROR);
        return false;
    }
    if (!is_int($func_opts)) {
        trigger_error('Function option must be int.', E_USER_ERROR);
        return false;
    }

    //assert(validate_spec($specs));

    if (!$ctx) {
        $ctx = new Validate();
    }

    $ctx->params_checked = true;
    $validated = $ctx->validate($inputs, $specs, $func_opts);

    return $validated;
}


/**
 * Get validation status
 *
 * @param array $ctx  The context.
 */
function validate_get_status($ctx)
{
    assert($ctx instanceof Validate);
    return $ctx->getStatus();
}


/**
 * Set error level.
 *
 * @param array $ctx  The context.
 * @param int   $level     Error level. E_USER_*
 */
function validate_set_error_level($ctx, $level)
{
    assert($ctx instanceof Validate);
    $ctx->setErrorLevel($level);
}


/**
 * This function provides user land validation error handling.
 * This function should be used with "callback" validator.
 *
 * @param array  $ctx  The context.
 * @param string $message   Error message.
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
 * This function provides user land validation error handling.
 * This function should be used with "callback" validator.
 *
 * @param array  $ctx  The context.
 * @param string $message   Error message.
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
 * This function provides user land validation error handling.
 * This function should be used with "callback" validator.
 *
 * @param array  $ctx  The context.
 * @param string $message   Error message.
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
 * Get system error messages.
 *
 * @param $validate Validate object
 *
 * @return array
 */
function validate_get_system_errors($ctx)
{
    assert($ctx instanceof Validate);
    return $ctx->getSystemErrors();
}


/**
 * Get user defined error messages.
 *
 * @return array
 */
function validate_get_user_errors($ctx)
{
    assert($ctx instanceof Validate);
    return $ctx->getUserErrors();
}


/**
 * Set error logger function. To use this logger,
 * $func_opts should have VALIDATE_OPT_LOG_ERROR.
 * Default logger function is trigger_error().
 *
 * @param array    $ctx    Validate context.
 * @param callable $logger_func Logger function must accept a string parameter for error message.
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
 * Check validate() function's input data specification array. ($specs)
 *
 * Specs must have following structure:
$specs =
[
    'POST' => [
        VALIDATE_ARRAY, // int Validator ID
        VALIDATE_FLAG_NONE, // int Validator Flags (Bit Mask)
        ['min' => 0, 'max' => 20], // array Validator options. (string keys only)
        [
            'scalar_param' => [
                VALIDATE_STRING, // Use Validator type int constant for ID
                $flags, // Validator flags. e.g. VALIDATE_STRING_ALNUM | VALIDATE_STRING_LF
                $options, // Validator options array. e.g. ['min' => 0, 'max' => 125]
            ],
            'array_param' => [ // Array parameter is allowed as nested spec.
                VALIDATE_ARRAY,
                $flags,
                $options,
                $params => [
                    'scalar_param' => [
                        VALIDATE_BOOL, // Define scalar param spec here
                        $flags,
                        $options,
                    ],
                ],
            ],
        ],
    ],
];
 *
 * @param array $specs       Validation spec array.
 * @param array $unvalidated Optional. Sets not validated specs.
 * @param array $ctx    Optional context.
 *
 * @return bool TRUE for success, FALSE otherwise.
 */
function validate_spec($specs, &$unvalidated = null, &$ctx = null)
{
    if (!is_array($specs)) {
        trigger_error('1st parameter must be validation spec array.', E_USER_ERROR);
        return false;
    }
    if (is_null($ctx)) {
        $ctx = new Validate;
    } elseif (!($ctx instanceof Validate)) {
        trigger_error('3rd parameter must be instance of Validate object.', E_USER_ERROR);
        return false;
    }
    $ctx->params_checked = true;
    $ret = $ctx->validateSpec($specs, $unvalidated, $ctx);
    $ctx->params_checked = false;
    return $ret;
}
