<?php
//namespace Validate;

/**
 * Simple yet flexible, powerful and reasonably fast input validator
 * that provides basic validation framework form simple to fancy/complex
 * validations.
 *
 * Name space is not used intentionally. This function is
 * designed to be compatible with "validate" PHP module
 * written by C.
 * i.e. Intentionally NOT OO nor PHP optimized.
 * e.g. Validate State could be private class, but in C module there is
 *      not much merit make it a private class. In C module, code is
 *      easier/shorter and faster without class. Encapsulation by C
 *      style is good enough.
 *
 * This code is intentionally optimized for C module.
 * Therefore, please do not send OO optimized PR.
 *
 *
 * Callback function signatures:
 *
 * VALIDATE_CALLBACK "callback" option.
 * "callback" is used to validate input values by your PHP script.
 *
 *   bool function(&$validated, $value, $context)
 *     $validated - Validated value.
 *     $value     - Input value
 *     $context  - context created by validate_init() and/or validate(). Users should not touch this.
 *   Return value:
 *     Must return true for successful validation, false otherwise.
 *   Note:
 *     Only VALIDATE_CALLBACK validator can have "callback" option.
 *     You can call validate() inside from VALIDATE_CALLBACK function. Make sure you pass correct $context variable.
 *
 *
 * Validator "filter" option.
 * Changes input value for pre filtering input. e.g. trim() string.
 * VALIDATE_FLAG_UNDEFINED*, VALIDATE_FLAG_EMPTY*, VALIDATE_REJECT is checked, but "min" and "max" options are not.
 *
 *   bool function(&$input, &$error)
 *     $input - The input value. Modify $input if you need to do something for it.
 *     $error - Error message. Set error message string when error. Leave it null otherwise.
 *   Return value:
 *     Must return true for successful validation, false otherwise.
 *   Note:
 *     All validators can have "filter" option.
 *
 *
 * Array of scalars "key_callback" option.
 * Checks array key value. Input values' array key can have dangerous chars!!
 *   bool function($key, &$error)
 *     $key - The key value.
 *     $error - Error message. Set error message string when error. Leave it null otherwise.
 *   Return value:
 *     Must return true for successful validation, false otherwise.
 *   Note:
 *     All validators can have "key_callback" option with VALIDATE_FLAG_ARRAY flag.
 *
 *
 * PHP Version 7.0 or up
 *
 * @category Validation
 * @package  Validate
 * @author   Yasuo Ohgaki <yohgaki@ohgaki.net>
 * @license  MIT https://github.com/yohgaki/validate-php-scr/blob/master/LICENSE
 * @link     https://github.com/yohgaki/validate-php-scr Validate PHP Script Version
 */

require_once __DIR__.'/validate_defs.php';


/**
 * Exceptions
 */
class ValidateInvalidSpecException extends InvalidArgumentException
{
}
class ValidateInvalidValueException extends InvalidArgumentException
{
}

/**
 * Code is trying to keep common structure/name with C module.
 * Users are not supposed to use this class directly!
 */
class Validate
{
    /**
     * validate() / validate_spec() params checked by procedural API.
     */
    public $validate_params_checked;
    public $spec_params_checked;

    /**
     * This is stack array and keeps track current element name.
     *
     * @var array $currentElem is used to keep track current element for recursive calls.
     */
    private $currentElem;

    /**
     * Current processing element context.
     *
     * @var Validate
     */
    private $context; // Validate object
    private $context_vars;

    /**
     * Error level for VALIDATE_OPT_RAISE_ERROR
     * E_USER_ERROR is too severe for debugging.
     */
    private $error_level;

    /**
     * Keeps track system error messages.
     *
     * @var array $errors System error messages.
     */
    private $errors;

    /**
     * Keeps track system warning messages.
     *
     * @var array $warnings System warning messages.
     */
    private $warnings;

    /**
     * Keeps track system warning messages.
     *
     * @var array $notices System warning messages.
     */
    private $notices;

    /**
     * Keeps track user error messages.
     *
     * @var array $userErrors User defined error message. i.e. validate_error($ctx, $message), $options['error_message']
     */
    private $userErrors;

    /**
     * Keeps track user warning messages.
     *
     * @var array $userWarnings User defined warning message. i.e. validate_warning($ctx, $message)
     */
    private $userWarnings;

    /**
     * Keeps track user notice messages.
     *
     * @var array $userNotices User defined warning message. i.e. validate_warning($ctx, $message)
     */
    private $userNotices;

    /**
     * Keeps track validated result. It contains values as far as Validate is checked.
     * i.e. Partial validation result.
     *
     * @var mixed Validated result.
     */
    private $validated;

    /**
     * Keeps track validation status.
     *
     * @var bool $status Validation status
     */
    private $status;

    /**
     * validate() or validateSepc()
     *
     * @var bool
     */
    private $value_validation;

    /**
     * Logger function
     *
     * @var callable $loggerFunction
     */
    private $loggerFunction = null;


    /************** public methods ****************/

    /**
     * Validate constructor
     *
     * @param string $root_name Root variable name.
     *
     * @return object
     */
    public function __construct($root_name = 'ROOT')
    {
        assert(is_string($root_name));

        $this->validate_params_checked = false;
        $this->status = null;
        $this->validated = null;
        $this->error_level = E_USER_ERROR;
        $this->errors = array();
        $this->warnings = array();
        $this->notices = array();
        $this->userErrors = array();
        $this->userWarnings = array();
        $this->userNotices = array();

        $this->currentElem = array();
        array_push($this->currentElem, $root_name); // Root variable name is unknown to program.

        $this->setContext(
            $this, // Register itself
            $root_name, // Param name
            null, // Root variable defined or not is unknown
            null, // Input is unknown
            null, // Spec is unknown
            null // $func_opt is unknwon
        );
    }


    /**
     * Set root variable name
     *
     * @param string $name Root variable name.
     *
     * @return null
     */
    public function setRootName($name)
    {
        assert(is_string($name));
        $this->__construct($name);
    }


    /**
     * Get currently working parameter name
     *
     * @return string This could be int also.
     */
    public function getCurrentParam()
    {
        assert(is_array($this->currentElem));
        return end($this->currentElem);
    }


    /**
     * Set current context for validator.
     * This function is exposed for procedural API.
     *
     * NOTE: NOT FOR USERS. This is public only for procedural API.
     *
     * @return null
     */
    public function setContext($validate, $param, $isset, $orig_value, $spec, $func_opts)
    {
        assert($validate instanceof Validate);
        assert(is_string($param) || is_int($param) || is_null($param));
        //assert(isset($value) || is_null($value));
        assert(is_bool($isset) || is_null($isset));
        assert(is_array($spec) || is_null($spec));
        // assert($this->validateSpec($spec));
        assert(is_null($func_opts) || is_int($func_opts) && !($func_opts & VALIDATE_OPT_UPPER));

        $this->context = $validate;
        $validate->context_vars = [
            'param'     => $param,
            'orig_value'=> $orig_value, // The original value to validator
            // 'value'    => null, // Cannot set value raised errors here
            'defined'   => $isset,
            'spec'      => $spec,
            'func_opts' => $func_opts,
        ];
    }


    /**
     * Set value caused validation error.
     * Original value is set by setContext(), but error value can differ.
     * i.e. "default" value and "filter".
     *
     * NOTE: NOT FOR USERS. This is public only for procedural API.
     *
     * @return null
     */
    public function setContextErrorValue($value)
    {
        $this->context_vars['value'] = $value;
    }

    /**
     * Validate inputs and removes validated values from $inputs.
     * So $validated contains validated values and $inputs has unvalidated value.
     *
     * @param mixed $inputs    Scalar or array values.
     * @param array $specs     Spec array.
     * @param int   $func_opts Bit function flags controls function behaviors.
     *
     * @return mixed Validated values.
     */
    public function validate(&$inputs, $specs, $func_opts = VALIDATE_OPT_CHECK_SPEC)
    {
        if (!$this->validate_params_checked) {
            if (($func_opts & VALIDATE_OPT_CHECK_SPEC)
                && !$this->validateSpec($specs)) {
                $err = $this->getSystemErrors();
                if (!empty($err['warning'])) {
                    $cnt = count($err['waraning']);
                    trigger_error('Validation spec problem detected. Check spec errors. Hint: validate_get_errors($ctx); '.
                                  'Warnings: '.$cnt, E_USER_WARNING);
                }
                if (!empty($err['error'])) {
                    $cnt = count($err['error']);
                    trigger_error('Invalid validation spec detected. Fix spec errors first. Hint: validate_get_errors($ctx);'.
                                  'Errors: '.$cnt, E_USER_ERROR);
                }
                return false;
            } elseif (!is_array($specs)) {
                trigger_error('Spec must be array.', E_USER_ERROR);
                return false;
            }
            if (!is_int($func_opts)) {
                trigger_error('Function option must be int.', E_USER_ERROR);
                return false;
            }
        }
        $this->validate_params_checked = false;

        // assert($this->validateSpec($specs));
        assert(is_int($func_opts));
        assert(!($func_opts & VALIDATE_OPT_UPPER));

        $scalar_validation = ($specs[VALIDATE_ID] !== VALIDATE_ARRAY || !($specs[VALIDATE_FLAGS] & VALIDATE_FLAG_ARRAY));
        // Successful status for each validate() call.
        // Previous errors are stored $this->errors array when object is used multiple times.
        $this->status = true;
        $this->validated = null;
        $this->value_validation = true;
        $ret = $this->validateImpl($this->validated, $inputs, $specs, $func_opts);
        assert(is_bool($ret));
        $this->status = $ret;

        if (!($func_opts & VALIDATE_OPT_UNVALIDATED) && !$scalar_validation && is_array($inputs)) {
            if (count($inputs)) {
                $this->internalError(
                    [
                        'message' => 'Validate: Unvalidated value remains.',
                        'value' => $inputs,
                    ],
                    // internalError() is supposed to be used by validator. Set invalid here.
                    [VALIDATE_UNVALIDATED, VALIDATE_FLAG_NONE, []],
                    $func_opts
                );
            }
        }

        if ($this->status) {
            return $this->validated;
        }
        return null;
    }


    /**
     * Validate validate()'s input spec array.
     *
     * @param array    $spec        Validate's value specification.
     * @param mixed    $unvalidated Optional. Sets unvalidated specs.
     * @param Validate $ctx    Optional. Instance of Validate object.
     *
     * @return bool TRUE for success, FALSE otherwise.
     */
    public function validateSpec($spec, &$unvalidated = null, $ctx = null)
    {
        if (!$this->spec_params_checked) {
            if (!is_array($spec)) {
                $this->spec_params_checked = false;
                trigger_error('1st parameter must be validation spec array.', E_USER_ERROR);
                return false;
            }
            if (is_null($ctx)) {
                $ctx = new Validate;
            } elseif (!($ctx instanceof Validate)) {
                $this->spec_params_checked = false;
                trigger_error('3rd parameter must be instance of Validate object.', E_USER_ERROR);
                return false;
            }
        }
        $this->spec_params_checked = false;

        $unvalidated = $spec;
        $ctx->status = true;
        $this->value_validation = false;
        $ctx->validateSpecImpl($unvalidated);
        // if (!$this->status) {
        //     trigger_error('SPEC Validation is failed! Check spec and result.', E_USER_WARNING);
        // }
        return $ctx->status;
    }


    /**
     * Get validation result including partial results.
     *
     * @return mixed Validated results.
     */
    public function getValidated()
    {
        return $this->validated;
    }


    /**
     * Get validation status
     *
     * @return bool TRUE for success.
     */
    public function getStatus()
    {
        assert(is_null($this->status) || is_bool($this->status));
        return $this->status;
    }

    /**
     * Get context
     *
     * @return array Context array
     */
    public function getContext()
    {
        assert(is_array($this->context));
        return $this->context;
    }


    /**
     * Set VALIDATE_OPT_RAISE_ERROR error level.
     * E_USER_ERROR is too severe for debugging.
     *
     * @param int E_USER_*
     */
    public function setErrorLevel($level)
    {
        assert($level === E_USER_ERROR || $level === E_USER_WARNING || $level === E_USER_NOTICE);
        $this->error_level = $level;
    }


    /**
     * Get system error messages.
     * If Validate object is used multiple validate() calls,
     * all error are stored.
     *
     * @return array System errors.
     */
    public function getSystemErrors()
    {
        return $this->getErrorAndWarning(E_ERROR);
    }


    /**
     * Get user error messages.
     * If Validate object is used multiple validate() calls,
     * all error are stored.
     *
     * @return array
     */
    public function getUserErrors()
    {
        return $this->getErrorAndWarning(E_USER_ERROR);
    }


    /**
     * Set logger function. It should accept a string parameter for error message.
     *
     * @param callable $func Logger function. function void my_logger(Validate $ctx, array $error)
     *                       $error contains full error info as array.
     *
     * @return null
     */
    public function setLoggerFunction($func)
    {
        if (!is_callable($func)) {
            throw new InvalidArgumentException('Parameter is not callable.');
        }
        //TODO Use Reflection
        $this->loggerFunction = $func;
    }


    /**
     * Handle external validation errors as system error.
     *
     * @param string   $error     Error message.
     *
     * @return null
     */
    public function error($message)
    {
        assert(is_string($message));
        $this->errorImpl($message, E_USER_ERROR);
    }


    /**
     * Handle external validation warnings as system warning.
     *
     * @param string   $error     Error message.
     *
     * @return null
     */
    public function warning($message)
    {
        assert(is_string($message));
        $this->errorImpl($message, E_USER_WARNING);
    }


    /**
     * Handle external validation warnings as system warning.
     *
     * @param string   $error     Error message.
     *
     * @return null
     */
    public function notice($message)
    {
        assert(is_string($message));
        $this->errorImpl($message, E_USER_NOTICE);
    }


    /************** private methods - validator helpers ****************/

    /**
     * Check scalar.
     *
     * @return bool
     */
    private function checkScalar($val, $specs, $func_opts)
    {
        assert(is_array($specs) && is_int($specs[VALIDATE_ID]));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        $id = $specs[VALIDATE_ID];
        if (is_bool($val)) {
            switch ($id) {
                case VALIDATE_INT:
                case VALIDATE_FLOAT:
                case VALIDATE_STRING:
                case VALIDATE_REGEXP:
                    $vname = $this->getValidatorName($id);
                    $this->internalError(
                        [
                            'message' => $vname.': Bool value cannot be treated as valid value for this validator.',
                            'value' => $val,
                        ],
                        $specs,
                        $func_opts
                    );
                    return false;
                break;
                default:
            }
        }

        if (is_scalar($val) || is_null($val)) {
            return true;
        }
        if (($specs[VALIDATE_ID] & VALIDATE_INT) && extension_loaded('GMP') && $val instanceof GMP) {
            return true;
        }

        $vname = $this->getValidatorName($id);
        $this->internalError(
            [
                'message' => $vname.': Array or object parameter is passed for scalar.',
                'value' => $val,
            ],
            $specs,
            $func_opts
        );
        return false;
    }


    /**
     * Validator function map.
     * private property is exposed by var_dump(), so hide this.
     *
     * @param int $id Validator ID
     *
     * @return string Validator name string
     */
    private function getValidator($id)
    {
        assert(is_int($id));

        $validators = [
            /* Normal Validators */
            VALIDATE_NULL      => 'validateNull',
            VALIDATE_INT       => 'validateInt',
            VALIDATE_FLOAT     => 'validateFloat',
            VALIDATE_BOOL      => 'validateBool',
            VALIDATE_STRING    => 'validateString',
            VALIDATE_ARRAY     => 'validateArray',
            VALIDATE_REGEXP    => 'validateRegexp',
            VALIDATE_CALLBACK  => 'validateCallback',
            VALIDATE_RESOURCE  => 'validateResource',
            VALIDATE_OBJECT    => 'validateObject',
            // VALIDATE_MULTI     => 'validateMulti', // This is not a function
            /* Special Validators */
            VALIDATE_INVALID   => 'validateInvalid',
            VALIDATE_REJECT    => 'validateReject',
            VALIDATE_UNDEFINED => 'validateUndefined',
        ];
        return $validators[$id];
    }


    /**
     * Get validator name.
     *
     * @return string
     */
    private function getValidatorName($id)
    {
        assert(is_int($id));

        $map = [
            VALIDATE_NULL => 'VALIDATE_NULL',
            VALIDATE_BOOL => 'VALIDATE_BOOL',
            VALIDATE_FLOAT => 'VALIDATE_FLOAT',
            VALIDATE_INT => 'VALIDATE_INT',
            VALIDATE_STRING => 'VALIDATE_STRING',
            VALIDATE_REGEXP => 'VALIDATE_REGEXP',
            VALIDATE_CALLBACK => 'VALIDATE_CALLBACK',
            VALIDATE_MULTI => 'VALIDATE_MULTI',
            VALIDATE_RESOURCE => 'VALIDATE_RESOURCE',
            VALIDATE_OBJECT => 'VALIDATE_OBJECT',
            /* Special validation checks unvalidated result */
            VALIDATE_UNVALIDATED => 'UNVALIDATED',
        ];

        if (!isset($map[$id])) {
            return false;
        }
        return $map[$id];
    }


    /**
     * Replace empty string to defined default value
     *
     * @return bool
     */
    private function validateEmptyToDefault(&$value, $id, $flags, $options, $func_opts = VALIDATE_OPT_DISABLE_EXCEPTION)
    {
        //$value could be anything
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if (($flags & VALIDATE_FLAG_EMPTY_TO_DEFAULT) && ($value === null || $value === '')) {
            if (isset($options['default'])) {
                $value = $options['default'];
                return true;
            }
            $value = null;
            $this->internalError(
                [
                    'message' => 'Invalid validation spec: empty value cannot be used without proper "default" value.',
                    'value' => null,
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false;
        }
        return true;
    }


    /**
     * Handles special cases for validate()
     *
     * Process special/tricky logic here.
     *
     * @return bool  4 patterns. 0:OK. 1:ignore. 2:error No undefined flag. 3: error Rejected.
     */
    private function validateRejectAndUndefined(&$validated, &$inputs, $param, $id, $flags, $options, $func_opts)
    {
        assert(is_array($inputs));
        assert(is_scalar($param));
        assert(is_int($id));
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if (isset($inputs[$param]) && $flags & VALIDATE_FLAG_REJECT) {
            // Do something for rejected values can be harmful.
            // Rejected parameters should not be defined.
            $vname = $this->getValidatorName($id);
            $this->internalError(
                [
                    'message' => $vname.': Rejected by flag.',
                    'value' => $inputs[$param],
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false; // 3: Error - rejected
        }

        return $this->validateUndefined($inputs, isset($inputs[$param]), $param, $id, $flags, $options, $func_opts);
    }


    /**
     * User should not call this method.
     * Use validate() wrapper function instead.
     *
     * @param mixed $validated    Validated values. Bool/Int/Float types may be converted to native types.
     * @param mixed $inputs    Input values.
     * @param array $specs     Validation specification array
     * @param int   $func_opts Bit mask function options.
     *
     * @return bool  Return true for successful validation, false otherwise.
     */
    private function validateImpl(&$validated, &$inputs, $specs, $func_opts)
    {
        //assert(is_array($inputs));
        assert(is_array($specs));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        assert(is_int($specs[VALIDATE_ID])); // Validator ID
        assert($specs[VALIDATE_ID] > VALIDATE_INVALID && $specs[VALIDATE_ID] <= VALIDATE_LAST);
        assert(is_int($specs[VALIDATE_FLAGS])); // Validator flags
        assert(is_array($specs[VALIDATE_OPTIONS])); // Validator options
        assert(!isset($specs[VALIDATE_PARAMS]) || is_array($specs[VALIDATE_PARAMS]));

        $id = $specs[VALIDATE_ID];
        $flags = $specs[VALIDATE_FLAGS];
        $options = $specs[VALIDATE_OPTIONS];
        $params = $specs[VALIDATE_PARAMS] ?? null;

        switch ($id) {
            case VALIDATE_BOOL:
            case VALIDATE_NULL:
            case VALIDATE_MULTI:
            case VALIDATE_REJECT:
            case VALIDATE_UNDEFINED:
            case VALIDATE_RESOURCE:
            case VALIDATE_OBJECT:
                break;
            default:
                $min = $options['min'] ?? 0;
                $max = $options['max'] ?? 0;
        }

        // Need to set context here since this could be called out side from main loop
        $this->setContext(
            $this,
            $this->context_vars['param'], // Can use parent call context here
            $this->context_vars['defined'],
            $inputs,
            $specs,
            $func_opts
        );

        // Multiple specs for a element
        if ($id === VALIDATE_MULTI) {
            return $this->validateMulti($validated, $inputs, $specs, $func_opts);
        }

        // Single value spec
        if ($id !== VALIDATE_ARRAY) {
            return $this->validateScalar($validated, $inputs, $id, $flags, $options, $func_opts);
        }

        // Array value spec
        if (!is_array($params)) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_ARRAY: Non array parameter option is passed for VALIDATE_ARRAY. '.
                                 'Missing 4th(params) option required for VALIDATE_ARRAY.',
                    'value' => null,
                ],
                $specs,
                $func_opts
            );
            return false;
        }
        if (!(is_array($inputs) || $inputs instanceof Countable)) {
            $this->internalError(
                [
                   'message' => 'VALIDATE_ARRAY: Input value is not array. ',
                   'value' => $inputs
                ],
                $specs,
                $func_opts
            );
            return false;
        }
        $cnt = count($inputs);
        if ($min > $cnt || $max < $cnt) {
            $this->internalError(
                [
                   'message' => 'VALIDATE_ARRAY: Count out of rage. '.
                                'min: '. $min .' max: '. $max . ' count '. $cnt,
                   'value' => null
                ],
                $specs,
                $func_opts
            );
            return false;
        }

        foreach ($params as $param => $spec) {
            assert(is_int($spec[VALIDATE_ID])); // Validator ID
            assert(is_int($spec[VALIDATE_FLAGS])); // Validator flags
            assert(is_array($spec[VALIDATE_OPTIONS])); // Validator options
            assert($spec[VALIDATE_ID] !== VALIDATE_ARRAY || is_array($spec[VALIDATE_PARAMS])); // In case of Array, must have "parameters".

            $id = $spec[VALIDATE_ID];
            $flags = $spec[VALIDATE_FLAGS];
            $options = $spec[VALIDATE_OPTIONS];

            array_push($this->currentElem, $param);
            $this->setContext(
                $this,
                $param,
                isset($inputs[$param]),
                $inputs[$param] ?? null,
                $spec,
                $func_opts
            );

            // Reject / Optional undefined parameter or not.
            if (!$this->validateRejectAndUndefined($validated, $inputs, $param, $id, $flags, $options, $func_opts)) {
                array_pop($this->currentElem);
                continue;
            }

            if (isset($inputs[$param]) && !$this->validateEmptyToDefault($inputs[$param], $id, $flags, $options)) {
                array_pop($this->currentElem);
                continue;
            }

            $status = $this->validateImpl($validated[$param], $inputs[$param], $spec, $func_opts);

            // Remove validated elements
            if ($status === true && !($func_opts & VALIDATE_OPT_KEEP_INPUTS)) {
                if ($spec[VALIDATE_ID] === VALIDATE_ARRAY && is_array($inputs[$param]) && !count($inputs[$param])) {
                    unset($inputs[$param]);
                } elseif ($spec[VALIDATE_ID] !== VALIDATE_ARRAY) {
                    unset($inputs[$param]);
                }
            }
            // Do not set unvalidated except array.
            if ($status === false && !is_array($validated[$param])) {
                unset($validated[$param]);
            }
            array_pop($this->currentElem);
        }
        return $this->status;
    }


    /************** private methods - validators ****************/


    /**
     * Apply filter before validation
     *
     * @return bool
     */
    private function validateApplyFilter(&$input, $id, $flags, $options, $func_opts = 0)
    {
        assert(is_int($id));
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if (isset($options['filter'])) {
            assert(is_callable($options['filter']));
            // TODO Use reflection
            // Validate object is not passed.
            // When error, user should simply set $error message.
            if ($flags & VALIDATE_FLAG_ARRAY) {
                if (!is_array($input)) {
                    $vname = $this->getValidatorName($id);
                    $this->internalError(
                        [
                            'message' => $vname.' filter (VALIDATE_FLAG_ARRAY) error: Input data is not an array',
                            'value' => $input
                        ],
                        [$id, $flags, $options],
                        $func_opts
                    );
                    return false;
                }
                foreach ($input as &$val) {
                    $val = $options['filter']($this->context, $val, $error);
                    if ($error) {
                        assert(is_string($error));
                        $vname = $this->getValidatorName($id);
                        $this->internalError(
                            [
                                'message' => $vname.' filter (VALIDATE_FLAG_ARRAY) error: '. addslashes($error),
                                'value' => $input
                            ],
                            [$id, $flags, $options],
                            $func_opts
                        );
                        return false;
                    }
                }
            } else {
                $input = $options['filter']($this->context, $input, $error);
                if ($error) {
                    assert(is_string($error));
                    $vname = $this->getValidatorName($id);
                    $this->internalError(
                        [
                            'message' => $vname.' filter error: '. addslashes($error),
                            'value' => $input
                        ],
                        [$id, $flags, $options],
                        $func_opts
                    );
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Validate a value by multiple specs
     */
    private function validateMulti(&$validated, &$inputs, $specs, $func_opts)
    {
        assert(is_array($specs));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));
        assert(($specs[VALIDATE_FLAGS] & VALIDATE_MULTI_OR) || ($specs[VALIDATE_FLAGS] & VALIDATE_MULTI_AND));
        assert(is_array($specs[VALIDATE_SPECS])); // Array of specs
        assert(validate_spec($specs));

        $id = VALIDATE_MULTI;
        $flags = $specs[VALIDATE_FLAGS];
        $options = $specs[VALIDATE_OPTIONS];
        $multiple_specs = $specs[VALIDATE_SPECS];

        // Rejected parameter check
        if ($this->validateReject($validated, $inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }

        // Accept null or not
        if (!$this->validateAcceptNull($validated, $inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }

        // Apply filter if any.
        if (!$this->validateApplyFilter($inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }

        // Use AND for broken spec. Spec should be validated already, though.
        $multi_and = true;
        if ($flags & VALIDATE_MULTI_OR) {
            $multi_and = false;
        }

        // Check specs count just in case user didn't validate specs
        if (!is_array($multiple_specs) || !count($multiple_specs)) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_MULTI: Invalid spec.',
                    'value' => $inputs,
                ],
                $specs,
                $func_opts
            );
            return false;
        }

        $this->setContextErrorValue($inputs);
        $ret = false;
        if ($multi_and) {
            foreach ($multiple_specs as $spec) {
                $tmp_inputs = $inputs; // Inputs may be changed by filter, etc.
                $ret = $this->validateImpl($tmp_result, $tmp_inputs, $spec, $func_opts);
                if ($ret === false) {
                    break;
                }
            }
        } else {
            $orig_status = $this->status;
            foreach ($multiple_specs as $spec) {
                $spec[VALIDATE_FLAGS] = $spec[VALIDATE_FLAGS] | VALIDATE_FLAG_PASSTHRU; // Disable logging and errors with OR.
                $tmp_inputs = $inputs; // Inputs may be changed by filter, etc.
                $ret = $this->validateImpl($tmp_result, $tmp_inputs, $spec, $func_opts);
                if ($ret === true) {
                    break;
                }
            }
            // Restore original status.
            if ($orig_status === true && $ret === true) {
                $this->status = true;
            }
        }

        if ($ret === true) {
            $validated = $tmp_result;
        } else {
            $validated = null;
        }
        if ($ret === false && !$multi_and) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_MULTI: All of specs are failed. '.
                                 'Note: OR will not log failed validations.',
                    'value' => $inputs
                ],
                $specs,
                $func_opts
            );
            return false;
        }

        return $ret;
    }

    /**
     * Validate array of scalars
     *
     * @return bool
     */
    private function validateScalarArray(&$validated, $id, $cnt, $alimit, $key_callback, &$inputs, $flags, $options, $func_opts)
    {
        assert(is_int($id)); // validator id
        assert(is_int($cnt)); // Apply count
        assert(is_callable($key_callback));
        //assert(is_array($inputs) || empty($inputs)); input could be anything
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        assert(is_int($options['amin']) && $options['amin'] >= 0); // Array elements min
        assert(is_int($options['amax'])); // Array elements max
        assert($options['amin'] <= $options['amax']);
        assert(!isset($options['alimit']) || $options['alimit'] > $options['amax']);

        // VALIDATE_FLAG_EMPTY flag and "default" option. Empty array will not set "default".
        if ($inputs === '' || $inputs === null) {
            if ($flags & VALIDATE_FLAG_EMPTY) {
                // Empty value is explicitly allowed.
                $validated = $inputs;
                return true;
            }
            if ($flags & VALIDATE_FLAG_EMPTY_TO_DEFAULT) {
                assert(isset($options["default"]));
                $validated = $options["default"];
            }
        }

        // TODO Not a optimal way to get validator name here
        $vname = $this->getValidatorName($id);
        if (is_scalar($inputs)) {
            $this->internalError(
                [
                    'message' => $vname.': Array of scalars validation. Scalar value is not allowed.',
                    'value' => $inputs
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false;
        }

        // TODO Not a optimal way to get validator, amin and amax here
        $validator = $this->getValidator($id);
        $amin = $options['amin'];
        $amax = $options['amax'];
        $num = count($inputs);

        if ($num < $amin || $num > $amax) {
            $this->internalError(
                [
                    'message' => $vname.' array: Number of elements is out of range. '.
                                 'amin: "'. $amin. '" amax: '. $amax .'"',
                    'value' => $inputs
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false;
        }

        $status = true;
        foreach ($inputs as $key => $val) {
            if ($cnt++ > $alimit) {
                $this->internalError(
                    [
                        'message' => $vname. ': Array validation. Number of elements exceed limit: "'.$alimit.'". '.
                                     'Hint: you may want to set "alimit" option to allow larger array.',
                        'value' => 'N/A',
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (!$key_callback($this->context, $key)) {
                $status = false;
                $this->internalError(
                    [
                        'message' => $vname. ': Array validation. Array parameter has invalid key format. '.
                                     'Hint: you may want VALIDATE_FLAG_ARRAY_KEY_ALNUM flag or "key_callback" option.',
                        'value' => 'key:'.$key,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                continue; // TODO Decide continue or return for error
            }
            if (is_array($val)) {
                if (!($flags & VALIDATE_FLAG_ARRAY_RECURSIVE)) {
                    $this->internalError(
                        [
                            'message' => $vname. ': Array validation. Nested array is not allowed by VALIDATE_FLAG_ARRAY_RECURSIVE.',
                            'value' => 'key:'.$key,
                        ],
                        [$id, $flags, $options],
                        $func_opts
                    );
                }
                $ret = $this->validateScalarArray($validated[$key], $id, $cnt, $alimit, $key_callback, $inputs[$key], $flags, $options, $func_opts);
                if ($ret === false) {
                    break;
                }
                continue;
            }
            if (true === $this->$validator($validated[$key], $val, $flags, $options, $func_opts)) {
                if (!($func_opts & VALIDATE_OPT_KEEP_INPUTS)) {
                    unset($inputs[$key]);
                }
            } else {
                $status = false;
            }
        }

        return $status;
    }


    /**
     * Validate scalar
     *
     * @return bool
     */
    private function validateScalar(&$validated, &$inputs, $id, $flags, $options, $func_opts)
    {
        assert(is_int($id));
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        // Rejected parameter check
        if ($this->validateReject($validated, $inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }

        // Accept null or not
        if (!$this->validateAcceptNull($validated, $inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }

        // Apply filter if any.
        if (!$this->validateApplyFilter($inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }

        // Set original value
        $this->setContextErrorValue($inputs);
        if ($flags & VALIDATE_FLAG_ARRAY) {
            assert(is_int($options['amax']));
            // Inputs is array of scalars.
            $cnt = 0;
            $alimit = $options['alimit'] ?? $options['amax'];
            $key_callback = $this->validateScalarArrayGetKeyCallback($flags, $options);
            $ret = $this->validateScalarArray($validated, $id, $cnt, $alimit, $key_callback, $inputs, $flags, $options, $func_opts);
        } else {
            // Inputs is scalar or object
            $validator = $this->getValidator($id);
            $ret = $this->$validator($validated, $inputs, $flags, $options, $func_opts);
        }
        return $ret;
    }


    /**
     * Key array key validation callback.
     *
     * @return callable Always return callable if user validates spec at development time.
     */
    private function validateScalarArrayGetKeyCallback($flags, $options)
    {
        assert(is_int($flags));
        assert(is_array($options));

        if (isset($options['key_callback'])) {
            // User defined key validator
            assert(is_callable($options['key_callback']));
            // TODO Use Reflection
            $key_callback = $options['key_callback'];
        } else {
            // Predefined array key validator
            if ($flags & VALIDATE_FLAG_ARRAY_KEY_ALNUM) {
                $key_callback= function ($ctx, $key) {
                    if (strlen($key) > 64) {
                        // By default, keys longer than 64 chars are not allowed.
                        // If users have trouble with this restriction, use user defined key validator.
                        return false;
                    }
                    $chars = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';
                    if (strlen($key) === strspn($key, $chars)) {
                        return true;
                    }
                    return false;
                };
            } else {
                $key_callback = function ($ctx, $key) {
                    if (strlen($key) > 32) {
                        // By default, keys longer than 32 digits are not allowed.
                        // If users have trouble with this restriction, use user defined key validator.
                        return false;
                    }
                    if (is_int($key) || (strlen($key) === strspn($key, '1234567890_-'))) {
                        return true;
                    }
                    return false;
                };
            }
        }
        return $key_callback;
    }

    /**
     * Special validator - Invalid validator
     *
     * @return bool
     */
    private function validateInvalid(&$validated, $value, $id, $flags, $options, $func_opts)
    {
        // Special validator. Should not be called.
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        $this->internalError(
            [
                'message' => 'VALIDATE_INVALID: This validator should not be called. ',
                'value' => $value,
            ],
            [$id, $flags, $options],
            $func_opts
        );
        return false;
    }


    /**
     * Special validator - Reject validator.
     *
     * Rejecting values are controlled by VALIDATE_FLAG_REJECT, not parameter definition.
     *
     * @return bool
     */
    private function validateReject(&$validated, $value, $id, $flags, $options, $func_opts)
    {
        // $value does not matter for this validator
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if ($flags & VALIDATE_FLAG_REJECT) {
            $vname = $this->getValidatorName($id);
            $this->internalError(
                [
                    'message' => $vname.': Rejected by flag.',
                    'value' => $value,
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return true;
        }
        return false;
    }


    /**
     * Accept null or not. Only null validator accepts null always.
     *
     * @return bool
     */
    private function validateAcceptNull($validated, $inputs, $id, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if ($id !== VALIDATE_NULL && is_null($inputs) && !($flags & VALIDATE_FLAG_NULL)) {
            $vname = $this->getValidatorName($id);
            $this->internalError(
                [
                    'message' => $vname.': NULL input is rejected by default.',
                    'value' => null,
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false;
        }
        return true;
    }


    /**
     * Special validator Undefined value validator.
     *
     * Undefined values are controlled by VALIDATE_FLAG_UNDEFINED /
     * VALIDATE_FLAG_UNDEFINED_TO_DEFAULT flags.
     * All validators may have undefined flags to control undefined values.
     *
     * Supported Flags: N/A
     * Supported Options:
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
     */
    private function validateUndefined(&$inputs, $defined, $param, $id, $flags, $options, $func_opts)
    {
        // This function may modify $inputs to handle "undefined" (not existing) input
        assert(is_array($inputs));
        assert(is_bool($defined)); // defined/undefined indicator
        assert(is_string($param) || is_int($param));
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if (!$defined) { // false === undefined value
            if ($flags & VALIDATE_FLAG_UNDEFINED_TO_DEFAULT) {
                assert(isset($options['default']));
                $inputs[$param] = $options['default'];
                return true; // OK
            } elseif ($flags & VALIDATE_FLAG_UNDEFINED) {
                return false; // 1:Ignore
            }
            $this->internalError(
                [
                    'message' => 'Undefined parameter: Required parameter is not defined.',
                    'value' => null
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false; // 2:Error - undefined not allowed
        }
        return true; // OK
    }


    /**
     * Special validator - Dummy Array validator
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "min" - Minimum elements.
     * "max" - Maximum elements.
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
     */
    private function validateArray(&$validated, $value, $flags, $options, $func_opts)
    {
        // validate() handles this.
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        $this->internalError(
            [
                'message' => 'VALIDATE_ARRAY: This validator should not be called.',
                'value' => $value,
            ],
            [VALIDATE_ARRAY, $flags, $options],
            $func_opts
        );
        return false;
    }


    /** Resource validator
     *
     * Supported Flags: See validate_defs.php
     */
    private function validateResource(&$validated, $value, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_string($options['resource'])); // Resource name string
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if (!is_resource($value)) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_RESOURCE: Not a resource. Type: '.gettype($value).' ',
                    'value' => $value,
                ],
                [VALIDATE_RESOURCE, $flags, $options],
                $func_opts
            );
            return false;
        }
        $rname = @get_resource_type($value);
        if ($rname === null) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_RESOURCE: Failed to get resource type. ',
                    'value' => $value,
                ],
                [VALIDATE_RESOURCE, $flags, $options],
                $func_opts
            );
            return false;
        }
        if ($rname !== $options['resource']) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_RESOURCE: Resource type does not match. '.
                    'Returned: \''.$rname.'\' Expected: \''.$options['resource'].'\'',
                    'value' => $value,
                ],
                [VALIDATE_RESOURCE, $flags, $options],
                $func_opts
            );
            return false;
        }

        $validated = $value;
        return true;
    }


    /**
     * Null validator
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
     */
    private function validateNull(&$validated, $value, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if (!$this->checkScalar($value, [VALIDATE_NULL, $flags, $options], $func_opts)) {
            return false;
        }

        if (!is_null($value) && $value !== '') {
            $this->internalError(
                [
                    'message' => 'VALIDATE_NULL: Non null value.',
                    'value' => $value,
                ],
                [VALIDATE_NULL, $flags, $options],
                $func_opts
            );
            return false; // This return value is exception. Other validators returns "null" for error.
        }

        if ($flags & VALIDATE_NULL_AS_STRING) {
            $validated = '';
        } else {
            $validated = null;
        }

        if ($flags & VALIDATE_FLAG_RAW) {
            $validated = $value;
        }
        return true;
    }


    /**
     * Int validator
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "min" - Minimum length.
     * "max" - Maximum length.
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
     */
    private function validateInt(&$validated, $value, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));
        assert(empty($options['values']) || is_array($options['values']));

        $values = $options['values'] ?? null;
        // Exact values match
        if ($values) {
            if (!is_int($value) || strlen($value) !== strspn($value, '-1234567890')) {
                $this->internalError(
                    [
                        'message' => 'VALIDATE_INT: Failed option "values" match. Value is not integer.',
                        'value' => $value,
                    ],
                    [VALIDATE_INT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (empty($values[$value])) {
                $this->internalError(
                    [
                        'message' => 'VALIDATE_INT: Failed to match defined option "values".',
                        'value' => $value,
                    ],
                    [VALIDATE_INT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            $validated = $value;
            return true;
        }

        assert(is_numeric($options['min']) || is_int($options['min']));
        assert(is_numeric($options['max']) || is_int($options['max']));
        assert($options['min'] <= $options['max']
               || bccomp($options['min'], $options['max']) === -1
               || bccomp($options['min'], $options['max']) === 0);

        // Normal validation
        if (extension_loaded('gmp') && $value instanceof GMP) {
            $value = gmp_strval($value);
        }

        if (!$this->checkScalar($value, [VALIDATE_INT, $flags, $options], $func_opts)) {
            return false;
        }

        $min = $options['min'];
        $max = $options['max'];

        if (is_int($value) && $value >= $min && $value <= $max) {
            $validated = $value;
            return true;
        }

        if (!is_scalar($value)) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_INT: Not a scalar.',
                    'value' => $value,
                ],
                [VALIDATE_INT, $flags, $options],
                $func_opts
            );
            return false;
        }

        if (($flags & VALIDATE_FLAG_EMPTY) && $value === '') {
            if ($flags & VALIDATE_INT_AS_STRING) {
                $validated = '';
            } else {
                $validated = null;
            }
            return true;
        }

        if (!$this->validateEmptyToDefault($value, VALIDATE_INT, $flags, $options)) {
            return false;
        }

        $ret = (string)$value;
        $len = strlen($ret);
        $lead_num = '1234567890';
        if ($flags & VALIDATE_INT_POSITIVE_SIGN) {
            $lead_num .= '+';
        }
        if ($flags & VALIDATE_INT_NEGATIVE_SIGN || substr($min, 0, 1) === '-') {
            $lead_num .= '-';
        }
        if (strspn($ret, $lead_num, 0, 1) !== 1 || strspn($ret, '1234567890', 1) !== ($len-1)) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_INT: Invalid int string.',
                    'value' => addslashes($ret),
                ],
                [VALIDATE_INT, $flags, $options],
                $func_opts
            );
            return false;
        }

        // Int type conversion is problematic with large values, especially on 32 bit platforms.
        if ($flags & VALIDATE_INT_AS_STRING) {
            if (bccomp($ret, $min) === -1 || bccomp($ret, $max) === 1) {
                $this->internalError(
                    [
                        'message' => 'VALIDATE_INT: Out of defined range. min: "'. $min .'" max: "'. $max .'"',
                        'value' => $ret,
                    ],
                    [VALIDATE_INT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if ($flags & VALIDATE_FLAG_RAW) {
                $validated = $value;
                return true;
            }
            assert(is_string($ret));
            $validated = $ret;
            return true;
        }

        $ret = (int)$value;
        if ((string)$ret !== (string)$value) {
            // Integer overflows and value is converted to float.
            $this->internalError(
                [
                    'message' => 'VALIDATE_INT: Overflow or malformed input. min: "'. $min.'" max: "'.$max.'"',
                    'value' => $value,
                ],
                [VALIDATE_INT, $flags, $options],
                $func_opts
            );
            return false;
        }

        if ($ret < $min || $ret > $max) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_INT: Out of defined range. min: "'. $min.'" max: "'.$max.'"',
                    'value' => $ret,
                ],
                [VALIDATE_INT, $flags, $options],
                $func_opts
            );
            return false;
        }

        assert(is_int($ret));
        if ($flags & VALIDATE_FLAG_RAW) {
            $validated = $value;
        } else {
            $validated = $ret; // Return int type value
        }
        return true;
    }


    /**
     * Bool validator
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
     */
    private function validateBool(&$validated, $value, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if (!$this->checkScalar($value, [VALIDATE_BOOL, $flags, $options], $func_opts)) {
            return false;
        }

        if (is_bool($value)) {
            $validated = $value;
            return true;
        }

        if (($flags & VALIDATE_FLAG_EMPTY) && $value === '') {
            if ($flags & VALIDATE_BOOL_AS_STRING) {
                $validated = '';
            } else {
                $validated = null;
            }
            return true;
        }

        $ret = (string)$value;
        if (!$this->validateEmptyToDefault($ret, VALIDATE_BOOL, $flags, $options)) {
            return false;
        }

        $len = strlen($ret);
        if ($len > 5) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_BOOL: Invalid input.',
                    'value' => $ret,
                ],
                [VALIDATE_BOOL, $flags, $options],
                $func_opts
            );
            return false;
        }
        if (!$len) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_BOOL: Empty input.',
                    'value' => $ret,
                ],
                [VALIDATE_BOOL, $flags, $options],
                $func_opts
            );
            return false;
        }

        $ret = null;
        $str = '';
        if (($flags & VALIDATE_BOOL_YES_NO)) {
            if (strncasecmp($value, 'yes', 5) === 0) {
                $str = 'yes';
                $ret = true;
            } elseif (strncasecmp($value, 'no', 5) === 0) {
                $str = 'no';
                $ret = false;
            }
        }
        if (($flags & VALIDATE_BOOL_ON_OFF)) {
            if (strncasecmp($value, 'on', 5) === 0) {
                $str = 'on';
                $ret = true;
            } elseif (strncasecmp($value, 'off', 5) === 0) {
                $str = 'off';
                $ret = false;
            }
        }
        if (($flags & VALIDATE_BOOL_TRUE_FALSE)) {
            if (strncasecmp($value, 'true', 5) === 0) {
                $str = 'true';
                $ret = true;
            } elseif (strncasecmp($value, 'false', 5) === 0) {
                $str = 'false';
                $ret = false;
            }
        }
        if (($flags & VALIDATE_BOOL_TF)) {
            if ($value === 't' || $value === 'T') {
                $str = 't';
                $ret = true;
            } elseif ($value === 'f' || $value === 'F') {
                $str = 'f';
                $ret = false;
            }
        }
        if (($flags & VALIDATE_BOOL_01)) {
            if ($value === '1' || $value === 1) {
                $str = '1';
                $ret = true;
            } elseif ($value === '0' || $value === 0) {
                $str = '0';
                $ret = false;
            }
        }
        if ($ret === null) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_BOOL: Invalid bool.',
                    'value' => $value,
                ],
                [VALIDATE_BOOL, $flags, $options],
                $func_opts
            );
            return false;
        }
        assert(is_bool($ret) || is_null($ret));
        if ($flags & VALIDATE_BOOL_AS_STRING) {
            $validated = $str;
            return true;
        }

        if ($flags & VALIDATE_FLAG_RAW) {
            $validated = $value;
        } else {
            $validated = $ret;
        }
        return true;
    }


    /**
     * Float validator
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "min" - Minimum length.
     * "max" - Maximum length.
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * “INF“ - Optional. Bool value. Allow INF as "max" value.
     * "-INF" - Optional. Bool value. Allow -INF as "min" value.
     * "decimal" - Optional. String value. Decimal char. TODO: Not implemented, yet.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
     */
    private function validateFloat(&$validated, $value, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        assert(isset($options['min']) && is_numeric($options['min']));
        assert(isset($options['max']) && is_numeric($options['max']));
        assert($options['min'] <= $options['max']);
        assert(empty($options['decimal']) || (is_string($options['decimal']) && strlen($options['decimal']) === 1));
        assert(empty($options['INF']) || is_bool($options['INF']));
        assert(empty($options['-INF']) || is_bool($options['-INF']));

        if (!$this->checkScalar($value, [VALIDATE_FLOAT, $flags, $options], $func_opts)) {
            return false;
        }

        $min = $options['min'];
        $max = $options['max'];
        $pinf = $options['INF'] ?? false;
        $ninf = $options['-INF'] ?? false;

        if (is_double($value)) {
            if ($value === NAN) {
                $this->internalError(
                    [
                        'message' => 'VALIDATE_FLOAT: NAN value is not allowed.',
                        'value' => NAN,
                    ],
                    [VALIDATE_FLOAT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (!$pinf && $value === INF) {
                $this->internalError(
                    [
                        'message' => 'VALIDATE_FLOAT: INF value is not allowed.',
                        'value' => INF
                    ],
                    [VALIDATE_FLOAT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (!$ninf && $value === -INF) {
                $this->internalError(
                    [
                        'message' => 'VALIDATE_FLOAT: -INF value is not allowed.',
                        'value' => -INF,
                    ],
                    [VALIDATE_FLOAT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if ($value < $min || $value > $max) {
                $this->internalError(
                    [
                        'message' => 'VALIDATE_FLOAT: Value is out of range. min: "'.$min.'" max: "'.$max.'"',
                        'value' => addslashes($ret),
                    ],
                    [VALIDATE_FLOAT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            $validated = $value;
            return false;
        }

        // By default max 'length' of float value is 32
        $length = $options['length'] ?? 32;
        if (strlen($length) > $length) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_FLOAT: Float value is too long. length: \''.$length.'\''.
                                 'Hint: set "length" option to allow longer length.',
                    'value' => null,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }

        if (($flags & VALIDATE_FLAG_EMPTY) && $value === '') {
            if ($flags & VALIDATE_FLOAT_AS_STRING) {
                $validated = '';
            } else {
                $validated = null;
            }
            return true;
        }

        $ret_str = (string)$value;
        if (!$this->validateEmptyToDefault($ret, VALIDATE_FLOAT, $flags, $options)) {
            return false;
        }

        if ($ret_str === '') {
            $this->internalError(
                [
                    'message' => 'VALIDATE_FLOAT: Empty input.',
                    'value' => null,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }

        // TODO: Implementation differs from C
        if (!is_numeric($ret_str)) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_FLOAT: Invalid float format.',
                    'value' => $ret_str,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }

        // Setup lead sign symbol
        $lead = '';
        if ($flags & VALIDATE_FLOAT_POSITIVE_SIGN) {
            $lead = '+';
        }
        if ($flags & VALIDATE_FLOAT_NEGATIVE_SIGN || $min < 0) {
            $lead = empty($lead) ? '-' : $lead . '\\-';
        }
        $lead = empty($lead) ? '' : '['.$lead.']?';

        $chk = true;
        if (!preg_match('/^'.$lead.'(?:0|[1-9]\\d*)(?:\\.\\d+)?$/', $ret_str)) {
            $chk = false;
        }
        if ($flags & VALIDATE_FLOAT_SCIENTIFIC) {
            if (preg_match('/^'.$lead.'(?:0|[1-9]\\d*)(?:\\.\\d+)?(?:[eE][+\\-]?\\d+)?$/', $ret_str)) {
                $chk = true;
            } else {
                $chk = false;
            }
        }

        if (!$chk) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_FLOAT: Invalid float format.',
                    'value' => $ret_str,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }

        $ret = (float)$value;
        if ($ret === NAN || $ret === INF || $ret === -INF) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_FLOAT: Invalid float value.',
                    'value' => $ret,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }
        if ($ret < $min || $ret > $max) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_FLOAT: Value is out of range. min: "'.$min.'" max: "'.$max.'"',
                    'value' => $ret,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }

        if ($flags & VALIDATE_FLAG_RAW) {
            $validated = $value;
        } elseif ($flags & VALIDATE_FLOAT_AS_STRING) {
            // TODO Validate float as string is not really a string validation.
            $validated = $ret_str;
        } else {
            $validated = $ret;
        }
        return true;
    }


    /**
     * String validator
     *
     * By default, string validator do not allow any chars. Specify chars by flags and option.
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "min" - Minimum length.
     * "max" - Maximum length.
     * "filter"   - Optional. Filter callback before validation. Use this for normalization.
     * "ascii"    - Optional. Chars allowed like strspn().
     * "encoding" - Optional. htmlspecialchars() supported encoding string.
     *              No multibyte chars are allowed by default. You also need
     *              VALIDATE_STRING_MB flag to enable multibyte chars.
     *              If "encoding" is omitted, "UTF-8" is used by default.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
     */
    private function validateString(&$validated, $value, $flags, $options, $func_opts, $id = VALIDATE_STRING)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));
        assert(empty($options['values']) || is_array($options['values']));

        $values = $options['values'] ?? null;
        $vname = $this->getValidatorName($id);
        // Exact values match
        if ($values) {
            if (is_array($value)) {
                $this->internalError(
                    [
                        'message' => $vname .': Array is passed for option "values" validation.',
                        'value' => $value,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (empty($values[$value])) {
                $this->internalError(
                    [
                        'message' => $vname .': Failed to match defined option "values".',
                        'value' => $value,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
            $validated = $value;
            return true;
        }

        assert(isset($options['min']) && is_int($options['min']));
        assert(isset($options['max']) && is_int($options['max']));
        assert($options['min'] <= $options['max']);
        assert(empty($options['encoding']) || is_string($options['encoding']));

        // Normal string validation
        if (!$this->checkScalar($value, [$id, $flags, $options], $func_opts)) {
            return false;
        }

        $ret = (string)$value;
        if (($flags & VALIDATE_FLAG_EMPTY) && $ret === '') {
            $validated = '';
            return true;
        }

        if (!$this->validateEmptyToDefault($ret, $id, $flags, $options)) {
            return false;
        }

        $min = $options['min'];
        $max = $options['max'];
        $len = strlen($ret);
        if ($len < $min || $len > $max) {
            $this->internalError(
                [
                    'message' => $vname .': Length is out of range. min: "'.$min.'" max: "'.$max.'"',
                    'value' => $ret,
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false;
        }

        // Short circuit length 0 string
        if ($len === 0) {
            $validated = '';
            return true;
        }

        if ($flags & VALIDATE_STRING_BINARY) {
            $validated = $value;
            return true;
        }

        $multibyte = ($flags & VALIDATE_STRING_MB);
        if ($multibyte) {
            $encoding = $options['encoding'] ?? 'UTF-8';
            // Use htmlspecialchars() validation to avoid mbstring dependency.
            if (!htmlspecialchars($ret, ENT_QUOTES, $encoding)) {
                $this->internalError(
                    [
                        'message' => $vname .': Invalid '.$encoding.' encoding.',
                        'value' => $ret,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
        }

        // Create map
        // Static maps didn't speedup, so keep loops.
        $map = array_fill(0, 128, false);
        if ($flags & VALIDATE_STRING_SPACE) {
            $map[ord(" ")] = true;
        }
        if ($flags & VALIDATE_STRING_TAB) {
            $map[ord("\t")] = true;
        }
        if ($flags & (VALIDATE_STRING_LF | VALIDATE_STRING_CRLF_MIXED)) {
            $map[ord("\n")] = true;
        }
        if ($flags & (VALIDATE_STRING_CR | VALIDATE_STRING_CRLF_MIXED)) {
            $map[ord("\r")] = true;
        }
        if ($flags & VALIDATE_STRING_DIGIT) {
            $max = ord('9');
            for ($i=ord('0'); $i <= $max; $i++) {
                $map[$i] = true;
            }
        }
        if ($flags & VALIDATE_STRING_LOWER_ALPHA) {
            $max = ord('z');
            for ($i=ord('a'); $i <= $max; $i++) {
                $map[$i] = true;
            }
        }
        if ($flags & VALIDATE_STRING_UPPER_ALPHA) {
            $max = ord('Z');
            for ($i=ord('A'); $i <= $max; $i++) {
                $map[$i] = true;
            }
        }
        if ($flags & VALIDATE_STRING_SYMBOL) {
            $max = ord('/');
            for ($i=ord('!'); $i <= $max; $i++) {
                $map[$i] = true;
            }
            $max = ord('@');
            for ($i=ord(':'); $i <= $max; $i++) {
                $map[$i] = true;
            }
            $max = ord('`');
            for ($i=ord('['); $i <= $max; $i++) {
                $map[$i] = true;
            }
            $max = ord('~');
            for ($i=ord('{'); $i <= $max; $i++) {
                $map[$i] = true;
            }
        }
        if (isset($options['ascii'])) {
            assert(is_string($options['ascii']));
            $chr = $options['ascii'];
            $len = strlen($chr);
            for ($i=0; $i < $len; $i++) {
                assert(ord($chr{$i}) < 128);
                $map[ord($chr{$i})] = true;
            }
        }

        // Custom unicode validation char map array. [unicode_value => true/false, ...]; true: allowed, false: disallowed.
        assert(!isset($options['unicode']) || is_array($options['unicode']));
        $unimap = $options['unicode'] ?? array();

        // Check chars
        $len = strlen($ret);
        $crlf = (($flags & VALIDATE_STRING_CR)
                  && ($flags & VALIDATE_STRING_LF)
                  && !($flags & VALIDATE_STRING_CRLF_MIXED));
        for ($i=0; $i < $len; $i++) {
            $c = $ret{$i};
            // UTF-8 multibyte chars should be safe by encoding check.
            if (ord($c) >= 127) {
                // At this point, UTF-8 encoding is validated already.
                if ($multibyte) {
                    if (!($flags & VALIDATE_STRING_RFC3454_C)
                        && (empty($options['encoding']) || !strncasecmp('UTF-8', $options['encoding'], 5))) {
                        $invalid_cp = $this->validateStringUnicodeCntrl($i, $value, $unimap, $id, $flags, $options, $func_opts);
                        if ($invalid_cp === null) {
                            continue;
                        }
                        $this->internalError(
                            [
                                'message' => $vname .': Unicode CNTRL char detected.',
                                'value' => $ret,
                            ],
                            [$id, $flags, $options],
                            $func_opts
                        );
                    }
                    continue;
                }
                $this->internalError(
                    [
                        'message' => $vname .': Multibyte char detected.',
                        'value' => $ret,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
            // CRLF needs special handling
            if ($crlf) {
                if ($c === "\n") {
                    $this->internalError(
                        [
                            'message' => $vname .': Invalid LF detected.',
                            'value' => $ret,
                        ],
                        [$id, $flags, $options],
                        $func_opts
                    );
                    return false;
                }
                if ($c === "\r") {
                    $c = $ret{++$i};
                    if ($c !== "\n") {
                        $this->internalError(
                            [
                                'message' => $vname .': Invalid CR/LF detected.',
                                'value' => $ret,
                            ],
                            [$id, $flags, $options],
                            $func_opts
                        );
                        return false;
                    }
                }
            }
            // Others
            if (!$map[ord($c)]) {
                $this->internalError(
                    [
                        'message' => $vname .': Illegal char detected. ord: "'
                                     .ord($c).'" chr: "'.addslashes($c).'"',
                        'value' => $ret,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
        }

        assert(is_string($ret));
        if ($flags & VALIDATE_FLAG_RAW) {
            $validated = $value;
        } else {
            $validated = $ret;
        }
        return true;
    }


    /**
     * String validator helper that detects unicode control chars.
     * https://www.ietf.org/rfc/rfc3454.txt C. Prohibition tables
     */
    private function validateStringUnicodeCntrl(&$idx, $value, $unimap, $id, $flags, $options, $func_opts)
    {
        assert(is_int($idx));
        assert(is_string($value));
        assert((ord($value{$idx}) & 0x80));
        assert(is_array($unimap));
        assert(is_int($id));
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts));

        $len = 0;
        $fb = ord($value{$idx});
        $c = $fb;
        for ($i = 0; $i < 6; $i++) {
            if ($c & 0x80) {
                $len++;
            } else {
                break;
            }
            $c = $c << 1;
        }

        switch ($len) {
            case 2:
                $b1 = $fb & 0b00011111;
                $b2 = ord($value{$idx+1}) & 0b00111111;
                $cp = ($b1 << 6) | ($b2);
                break;
            case 3:
                $b1 = $fb & 0b00001111;
                $b2 = ord($value{$idx+1}) & 0b00111111;
                $b3 = ord($value{$idx+2}) & 0b00111111;
                $cp = ($b1 << 12) | ($b2 << 6) | ($b3);
                break;
            case 4:
                $b1 = $fb & 0b00000111;
                $b2 = ord($value{$idx+1}) & 0b00111111;
                $b3 = ord($value{$idx+2}) & 0b00111111;
                $b4 = ord($value{$idx+3}) & 0b00111111;
                $cp = ($b1 << 18) | ($b2 << 12) | ($b3 << 6) | ($b4);
                break;
            default:
                trigger_error('Report this error', E_USER_ERROR); // Validated UTF-8. Shouldn't fail.
        }

        $idx += $len-1;

        if ($cp > 0x10FFFF) {
            $vname = $this->getValidatorName($id);
            $this->internalError(
                [
                    'message' => $vname.': Detected invalid unicode exceeds max value (0x10FFFF).',
                    'value' => $cp,
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return $cp;
        }
        if (isset($unimap[$cp])) {
            if ($unimap[$cp] === true) {
                return null;
            } else {
                $vname = $this->getValidatorName($id);
                $this->internalError(
                    [
                        'message' => $vname.': Detected invalid unicode defined by $option["unicode"].',
                        'value' => $cp,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return $cp;
            }
        }

        $ret = null;
        if (!($flags & VALIDATE_STRING_RFC3454_C)) {
            do {
                // C 1.1 - ASCII Space is ignored here.
                // C 1.2
                if ($cp == 0x0000A0)  { $ret = $cp; break; }
                if ($cp == 0x001680)  { $ret = $cp; break; }
                if ($cp == 0x002000)  { $ret = $cp; break; }
                if ($cp == 0x002001)  { $ret = $cp; break; }
                if ($cp == 0x002002)  { $ret = $cp; break; }
                if ($cp == 0x002003)  { $ret = $cp; break; }
                if ($cp == 0x002004)  { $ret = $cp; break; }
                if ($cp == 0x002005)  { $ret = $cp; break; }
                if ($cp == 0x002006)  { $ret = $cp; break; }
                if ($cp == 0x002007)  { $ret = $cp; break; }
                if ($cp == 0x002008)  { $ret = $cp; break; }
                if ($cp == 0x002009)  { $ret = $cp; break; }
                if ($cp == 0x00200A)  { $ret = $cp; break; }
                if ($cp == 0x00200B)  { $ret = $cp; break; }
                if ($cp == 0x00202F)  { $ret = $cp; break; }
                if ($cp == 0x00205F)  { $ret = $cp; break; }
                if ($cp == 0x003000)  { $ret = $cp; break; }
                // C 2.1 ｰ ASCII cntrls are ignored here
                // C 2.2
                if ($cp >= 0x000080 && $cp <= 0x00009F)  { $ret = $cp; break; }
                if ($cp == 0x0006DD)  { $ret = $cp; break; }
                if ($cp == 0x00070F)  { $ret = $cp; break; }
                if ($cp == 0x00180E)  { $ret = $cp; break; }
                if ($cp == 0x00200C)  { $ret = $cp; break; }
                if ($cp == 0x00200D)  { $ret = $cp; break; }
                if ($cp == 0x002028)  { $ret = $cp; break; }
                if ($cp == 0x002029)  { $ret = $cp; break; }
                if ($cp == 0x002060)  { $ret = $cp; break; }
                if ($cp == 0x002061)  { $ret = $cp; break; }
                if ($cp == 0x002062)  { $ret = $cp; break; }
                if ($cp == 0x002063)  { $ret = $cp; break; }
                if ($cp >= 0x00206A && $cp <= 0x00206F)  { $ret = $cp; break; }
                if ($cp == 0x00FEFF)  { $ret = $cp; break; }
                if ($cp >= 0x00FFF9 && $cp <= 0x00FFFC)  { $ret = $cp; break; }
                if ($cp >= 0x01D173 && $cp <= 0x01D17A)  { $ret = $cp; break; }
                // C 3
                if ($cp >= 0x00E000 && $cp <= 0x00F8FF)  { $ret = $cp; break; }
                if ($cp >= 0x0F0000 && $cp <= 0x0FFFFD)  { $ret = $cp; break; }
                if ($cp >= 0x100000 && $cp <= 0x10FFFD)  { $ret = $cp; break; }
                // C 4
                if ($cp >= 0x00FDD0 && $cp <= 0x00FDEF)  { $ret = $cp; break; }
                if ($cp >= 0x00FFFE && $cp <= 0x00FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x01FFFE && $cp <= 0x01FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x02FFFE && $cp <= 0x02FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x03FFFE && $cp <= 0x03FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x04FFFE && $cp <= 0x04FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x05FFFE && $cp <= 0x05FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x06FFFE && $cp <= 0x06FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x07FFFE && $cp <= 0x07FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x08FFFE && $cp <= 0x08FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x09FFFE && $cp <= 0x09FFFF)  { $ret = $cp; break; }
                if ($cp >= 0x0AFFFE && $cp <= 0x0AFFFF)  { $ret = $cp; break; }
                if ($cp >= 0x0BFFFE && $cp <= 0x0BFFFF)  { $ret = $cp; break; }
                if ($cp >= 0x0CFFFE && $cp <= 0x0CFFFF)  { $ret = $cp; break; }
                if ($cp >= 0x0DFFFE && $cp <= 0x0DFFFF)  { $ret = $cp; break; }
                if ($cp >= 0x0EFFFE && $cp <= 0x0EFFFF)  { $ret = $cp; break; }
                if ($cp >= 0x0FFFFE && $cp <= 0x0FFFFF)  { $ret = $cp; break; }
                if ($cp >= 0x10FFFE && $cp <= 0x10FFFF)  { $ret = $cp; break; }
                // C 5
                if ($cp >= 0x00D800 && $cp <= 0x00DFFF)  { $ret = $cp; break; }
                // C 6
                if ($cp == 0x00FFF9)  { $ret = $cp; break; }
                if ($cp == 0x00FFFA)  { $ret = $cp; break; }
                if ($cp == 0x00FFFB)  { $ret = $cp; break; }
                if ($cp == 0x00FFFC)  { $ret = $cp; break; }
                if ($cp == 0x00FFFD)  { $ret = $cp; break; }
                // C 7
                if ($cp >= 0x002FF0 && $cp <= 0x002FFB)  { $ret = $cp; break; }
                // C 8
                if ($cp == 0x000340)  { $ret = $cp; break; }
                if ($cp == 0x000341)  { $ret = $cp; break; }
                if ($cp == 0x00200E)  { $ret = $cp; break; }
                if ($cp == 0x00200F)  { $ret = $cp; break; }
                if ($cp == 0x00202A)  { $ret = $cp; break; }
                if ($cp == 0x00202B)  { $ret = $cp; break; }
                if ($cp == 0x00202C)  { $ret = $cp; break; }
                if ($cp == 0x00202D)  { $ret = $cp; break; }
                if ($cp == 0x00202E)  { $ret = $cp; break; }
                if ($cp == 0x00206A)  { $ret = $cp; break; }
                if ($cp == 0x00206B)  { $ret = $cp; break; }
                if ($cp == 0x00206C)  { $ret = $cp; break; }
                if ($cp == 0x00206D)  { $ret = $cp; break; }
                if ($cp == 0x00206E)  { $ret = $cp; break; }
                if ($cp == 0x00206F)  { $ret = $cp; break; }
                // C 9
                if ($cp == 0x0E0001)  { $ret = $cp; break; }
                if ($cp >= 0x0E0020 && $cp <= 0x0E007F)  { $ret = $cp; break; }
            } while (0);
        }

        /*
        if (!($flags & VALIDATE_STRING_RFC3454_D)) {
            do {
                // D 1
                if ($cp == 0x0005BE)  { $ret = $cp; break; }
                if ($cp == 0x0005C0)  { $ret = $cp; break; }
                if ($cp == 0x0005C3)  { $ret = $cp; break; }
                if ($cp >= 0x0005D0 && $cp <= 0x0005EA)  { $ret = $cp; break; }
                if ($cp >= 0x0005F0 && $cp <= 0x0005F4)  { $ret = $cp; break; }
                if ($cp == 0x00061B)  { $ret = $cp; break; }
                if ($cp == 0x00061F)  { $ret = $cp; break; }
                if ($cp >= 0x000621 && $cp <= 0x00063A)  { $ret = $cp; break; }
                if ($cp >= 0x000640 && $cp <= 0x00064A)  { $ret = $cp; break; }
                if ($cp >= 0x00066D && $cp <= 0x00066F)  { $ret = $cp; break; }
                if ($cp >= 0x000671 && $cp <= 0x0006D5)  { $ret = $cp; break; }
                if ($cp == 0x0006DD)  { $ret = $cp; break; }
                if ($cp >= 0x0006E5 && $cp <= 0x0006E6)  { $ret = $cp; break; }
                if ($cp >= 0x0006FA && $cp <= 0x0006FE)  { $ret = $cp; break; }
                if ($cp >= 0x000700 && $cp <= 0x00070D)  { $ret = $cp; break; }
                if ($cp == 0x000710)  { $ret = $cp; break; }
                if ($cp >= 0x000712 && $cp <= 0x00072C)  { $ret = $cp; break; }
                if ($cp >= 0x000780 && $cp <= 0x0007A5)  { $ret = $cp; break; }
                if ($cp == 0x0007B1)  { $ret = $cp; break; }
                if ($cp == 0x00200F)  { $ret = $cp; break; }
                if ($cp == 0x00FB1D)  { $ret = $cp; break; }
                if ($cp >= 0x00FB1F && $cp <= 0x00FB28)  { $ret = $cp; break; }
                if ($cp >= 0x00FB2A && $cp <= 0x00FB36)  { $ret = $cp; break; }
                if ($cp >= 0x00FB38 && $cp <= 0x00FB3C)  { $ret = $cp; break; }
                if ($cp == 0x00FB3E)  { $ret = $cp; break; }
                if ($cp >= 0x00FB40 && $cp <= 0x00FB41)  { $ret = $cp; break; }
                if ($cp >= 0x00FB43 && $cp <= 0x00FB44)  { $ret = $cp; break; }
                if ($cp >= 0x00FB46 && $cp <= 0x00FBB1)  { $ret = $cp; break; }
                if ($cp >= 0x00FBD3 && $cp <= 0x00FD3D)  { $ret = $cp; break; }
                if ($cp >= 0x00FD50 && $cp <= 0x00FD8F)  { $ret = $cp; break; }
                if ($cp >= 0x00FD92 && $cp <= 0x00FDC7)  { $ret = $cp; break; }
                if ($cp >= 0x00FDF0 && $cp <= 0x00FDFC)  { $ret = $cp; break; }
                if ($cp >= 0x00FE70 && $cp <= 0x00FE74)  { $ret = $cp; break; }
                if ($cp >= 0x00FE76 && $cp <= 0x00FEFC)  { $ret = $cp; break; }
                // D 2
                if ($cp >= 0x000041 && $cp <= 0x00005A)  { $ret = $cp; break; }
                if ($cp >= 0x000061 && $cp <= 0x00007A)  { $ret = $cp; break; }
                if ($cp == 0x0000AA)  { $ret = $cp; break; }
                if ($cp == 0x0000B5)  { $ret = $cp; break; }
                if ($cp == 0x0000BA)  { $ret = $cp; break; }
                if ($cp >= 0x0000C0 && $cp <= 0x0000D6)  { $ret = $cp; break; }
                if ($cp >= 0x0000D8 && $cp <= 0x0000F6)  { $ret = $cp; break; }
                if ($cp >= 0x0000F8 && $cp <= 0x000220)  { $ret = $cp; break; }
                if ($cp >= 0x000222 && $cp <= 0x000233)  { $ret = $cp; break; }
                if ($cp >= 0x000250 && $cp <= 0x0002AD)  { $ret = $cp; break; }
                if ($cp >= 0x0002B0 && $cp <= 0x0002B8)  { $ret = $cp; break; }
                if ($cp >= 0x0002BB && $cp <= 0x0002C1)  { $ret = $cp; break; }
                if ($cp >= 0x0002D0 && $cp <= 0x0002D1)  { $ret = $cp; break; }
                if ($cp >= 0x0002E0 && $cp <= 0x0002E4)  { $ret = $cp; break; }
                if ($cp == 0x0002EE)  { $ret = $cp; break; }
                if ($cp == 0x00037A)  { $ret = $cp; break; }
                if ($cp == 0x000386)  { $ret = $cp; break; }
                if ($cp >= 0x000388 && $cp <= 0x00038A)  { $ret = $cp; break; }
                if ($cp == 0x00038C)  { $ret = $cp; break; }
                if ($cp >= 0x00038E && $cp <= 0x0003A1)  { $ret = $cp; break; }
                if ($cp >= 0x0003A3 && $cp <= 0x0003CE)  { $ret = $cp; break; }
                if ($cp >= 0x0003D0 && $cp <= 0x0003F5)  { $ret = $cp; break; }
                if ($cp >= 0x000400 && $cp <= 0x000482)  { $ret = $cp; break; }
                if ($cp >= 0x00048A && $cp <= 0x0004CE)  { $ret = $cp; break; }
                if ($cp >= 0x0004D0 && $cp <= 0x0004F5)  { $ret = $cp; break; }
                if ($cp >= 0x0004F8 && $cp <= 0x0004F9)  { $ret = $cp; break; }
                if ($cp >= 0x000500 && $cp <= 0x00050F)  { $ret = $cp; break; }
                if ($cp >= 0x000531 && $cp <= 0x000556)  { $ret = $cp; break; }
                if ($cp >= 0x000559 && $cp <= 0x00055F)  { $ret = $cp; break; }
                if ($cp >= 0x000561 && $cp <= 0x000587)  { $ret = $cp; break; }
                if ($cp == 0x000589)  { $ret = $cp; break; }
                if ($cp == 0x000903)  { $ret = $cp; break; }
                if ($cp >= 0x000905 && $cp <= 0x000939)  { $ret = $cp; break; }
                if ($cp >= 0x00093D && $cp <= 0x000940)  { $ret = $cp; break; }
                if ($cp >= 0x000949 && $cp <= 0x00094C)  { $ret = $cp; break; }
                if ($cp == 0x000950)  { $ret = $cp; break; }
                if ($cp >= 0x000958 && $cp <= 0x000961)  { $ret = $cp; break; }
                if ($cp >= 0x000964 && $cp <= 0x000970)  { $ret = $cp; break; }
                if ($cp >= 0x000982 && $cp <= 0x000983)  { $ret = $cp; break; }
                if ($cp >= 0x000985 && $cp <= 0x00098C)  { $ret = $cp; break; }
                if ($cp >= 0x00098F && $cp <= 0x000990)  { $ret = $cp; break; }
                if ($cp >= 0x000993 && $cp <= 0x0009A8)  { $ret = $cp; break; }
                if ($cp >= 0x0009AA && $cp <= 0x0009B0)  { $ret = $cp; break; }
                if ($cp == 0x0009B2)  { $ret = $cp; break; }
                if ($cp >= 0x0009B6 && $cp <= 0x0009B9)  { $ret = $cp; break; }
                if ($cp >= 0x0009BE && $cp <= 0x0009C0)  { $ret = $cp; break; }
                if ($cp >= 0x0009C7 && $cp <= 0x0009C8)  { $ret = $cp; break; }
                if ($cp >= 0x0009CB && $cp <= 0x0009CC)  { $ret = $cp; break; }
                if ($cp == 0x0009D7)  { $ret = $cp; break; }
                if ($cp >= 0x0009DC && $cp <= 0x0009DD)  { $ret = $cp; break; }
                if ($cp >= 0x0009DF && $cp <= 0x0009E1)  { $ret = $cp; break; }
                if ($cp >= 0x0009E6 && $cp <= 0x0009F1)  { $ret = $cp; break; }
                if ($cp >= 0x0009F4 && $cp <= 0x0009FA)  { $ret = $cp; break; }
                if ($cp >= 0x000A05 && $cp <= 0x000A0A)  { $ret = $cp; break; }
                if ($cp >= 0x000A0F && $cp <= 0x000A10)  { $ret = $cp; break; }
                if ($cp >= 0x000A13 && $cp <= 0x000A28)  { $ret = $cp; break; }
                if ($cp >= 0x000A2A && $cp <= 0x000A30)  { $ret = $cp; break; }
                if ($cp >= 0x000A32 && $cp <= 0x000A33)  { $ret = $cp; break; }
                if ($cp >= 0x000A35 && $cp <= 0x000A36)  { $ret = $cp; break; }
                if ($cp >= 0x000A38 && $cp <= 0x000A39)  { $ret = $cp; break; }
                if ($cp >= 0x000A3E && $cp <= 0x000A40)  { $ret = $cp; break; }
                if ($cp >= 0x000A59 && $cp <= 0x000A5C)  { $ret = $cp; break; }
                if ($cp == 0x000A5E)  { $ret = $cp; break; }
                if ($cp >= 0x000A66 && $cp <= 0x000A6F)  { $ret = $cp; break; }
                if ($cp >= 0x000A72 && $cp <= 0x000A74)  { $ret = $cp; break; }
                if ($cp == 0x000A83)  { $ret = $cp; break; }
                if ($cp >= 0x000A85 && $cp <= 0x000A8B)  { $ret = $cp; break; }
                if ($cp == 0x000A8D)  { $ret = $cp; break; }
                if ($cp >= 0x000A8F && $cp <= 0x000A91)  { $ret = $cp; break; }
                if ($cp >= 0x000A93 && $cp <= 0x000AA8)  { $ret = $cp; break; }
                if ($cp >= 0x000AAA && $cp <= 0x000AB0)  { $ret = $cp; break; }
                if ($cp >= 0x000AB2 && $cp <= 0x000AB3)  { $ret = $cp; break; }
                if ($cp >= 0x000AB5 && $cp <= 0x000AB9)  { $ret = $cp; break; }
                if ($cp >= 0x000ABD && $cp <= 0x000AC0)  { $ret = $cp; break; }
                if ($cp == 0x000AC9)  { $ret = $cp; break; }
                if ($cp >= 0x000ACB && $cp <= 0x000ACC)  { $ret = $cp; break; }
                if ($cp == 0x000AD0)  { $ret = $cp; break; }
                if ($cp == 0x000AE0)  { $ret = $cp; break; }
                if ($cp >= 0x000AE6 && $cp <= 0x000AEF)  { $ret = $cp; break; }
                if ($cp >= 0x000B02 && $cp <= 0x000B03)  { $ret = $cp; break; }
                if ($cp >= 0x000B05 && $cp <= 0x000B0C)  { $ret = $cp; break; }
                if ($cp >= 0x000B0F && $cp <= 0x000B10)  { $ret = $cp; break; }
                if ($cp >= 0x000B13 && $cp <= 0x000B28)  { $ret = $cp; break; }
                if ($cp >= 0x000B2A && $cp <= 0x000B30)  { $ret = $cp; break; }
                if ($cp >= 0x000B32 && $cp <= 0x000B33)  { $ret = $cp; break; }
                if ($cp >= 0x000B36 && $cp <= 0x000B39)  { $ret = $cp; break; }
                if ($cp >= 0x000B3D && $cp <= 0x000B3E)  { $ret = $cp; break; }
                if ($cp == 0x000B40)  { $ret = $cp; break; }
                if ($cp >= 0x000B47 && $cp <= 0x000B48)  { $ret = $cp; break; }
                if ($cp >= 0x000B4B && $cp <= 0x000B4C)  { $ret = $cp; break; }
                if ($cp == 0x000B57)  { $ret = $cp; break; }
                if ($cp >= 0x000B5C && $cp <= 0x000B5D)  { $ret = $cp; break; }
                if ($cp >= 0x000B5F && $cp <= 0x000B61)  { $ret = $cp; break; }
                if ($cp >= 0x000B66 && $cp <= 0x000B70)  { $ret = $cp; break; }
                if ($cp == 0x000B83)  { $ret = $cp; break; }
                if ($cp >= 0x000B85 && $cp <= 0x000B8A)  { $ret = $cp; break; }
                if ($cp >= 0x000B8E && $cp <= 0x000B90)  { $ret = $cp; break; }
                if ($cp >= 0x000B92 && $cp <= 0x000B95)  { $ret = $cp; break; }
                if ($cp >= 0x000B99 && $cp <= 0x000B9A)  { $ret = $cp; break; }
                if ($cp == 0x000B9C)  { $ret = $cp; break; }
                if ($cp >= 0x000B9E && $cp <= 0x000B9F)  { $ret = $cp; break; }
                if ($cp >= 0x000BA3 && $cp <= 0x000BA4)  { $ret = $cp; break; }
                if ($cp >= 0x000BA8 && $cp <= 0x000BAA)  { $ret = $cp; break; }
                if ($cp >= 0x000BAE && $cp <= 0x000BB5)  { $ret = $cp; break; }
                if ($cp >= 0x000BB7 && $cp <= 0x000BB9)  { $ret = $cp; break; }
                if ($cp >= 0x000BBE && $cp <= 0x000BBF)  { $ret = $cp; break; }
                if ($cp >= 0x000BC1 && $cp <= 0x000BC2)  { $ret = $cp; break; }
                if ($cp >= 0x000BC6 && $cp <= 0x000BC8)  { $ret = $cp; break; }
                if ($cp >= 0x000BCA && $cp <= 0x000BCC)  { $ret = $cp; break; }
                if ($cp == 0x000BD7)  { $ret = $cp; break; }
                if ($cp >= 0x000BE7 && $cp <= 0x000BF2)  { $ret = $cp; break; }
                if ($cp >= 0x000C01 && $cp <= 0x000C03)  { $ret = $cp; break; }
                if ($cp >= 0x000C05 && $cp <= 0x000C0C)  { $ret = $cp; break; }
                if ($cp >= 0x000C0E && $cp <= 0x000C10)  { $ret = $cp; break; }
                if ($cp >= 0x000C12 && $cp <= 0x000C28)  { $ret = $cp; break; }
                if ($cp >= 0x000C2A && $cp <= 0x000C33)  { $ret = $cp; break; }
                if ($cp >= 0x000C35 && $cp <= 0x000C39)  { $ret = $cp; break; }
                if ($cp >= 0x000C41 && $cp <= 0x000C44)  { $ret = $cp; break; }
                if ($cp >= 0x000C60 && $cp <= 0x000C61)  { $ret = $cp; break; }
                if ($cp >= 0x000C66 && $cp <= 0x000C6F)  { $ret = $cp; break; }
                if ($cp >= 0x000C82 && $cp <= 0x000C83)  { $ret = $cp; break; }
                if ($cp >= 0x000C85 && $cp <= 0x000C8C)  { $ret = $cp; break; }
                if ($cp >= 0x000C8E && $cp <= 0x000C90)  { $ret = $cp; break; }
                if ($cp >= 0x000C92 && $cp <= 0x000CA8)  { $ret = $cp; break; }
                if ($cp >= 0x000CAA && $cp <= 0x000CB3)  { $ret = $cp; break; }
                if ($cp >= 0x000CB5 && $cp <= 0x000CB9)  { $ret = $cp; break; }
                if ($cp == 0x000CBE)  { $ret = $cp; break; }
                if ($cp >= 0x000CC0 && $cp <= 0x000CC4)  { $ret = $cp; break; }
                if ($cp >= 0x000CC7 && $cp <= 0x000CC8)  { $ret = $cp; break; }
                if ($cp >= 0x000CCA && $cp <= 0x000CCB)  { $ret = $cp; break; }
                if ($cp >= 0x000CD5 && $cp <= 0x000CD6)  { $ret = $cp; break; }
                if ($cp == 0x000CDE)  { $ret = $cp; break; }
                if ($cp >= 0x000CE0 && $cp <= 0x000CE1)  { $ret = $cp; break; }
                if ($cp >= 0x000CE6 && $cp <= 0x000CEF)  { $ret = $cp; break; }
                if ($cp >= 0x000D02 && $cp <= 0x000D03)  { $ret = $cp; break; }
                if ($cp >= 0x000D05 && $cp <= 0x000D0C)  { $ret = $cp; break; }
                if ($cp >= 0x000D0E && $cp <= 0x000D10)  { $ret = $cp; break; }
                if ($cp >= 0x000D12 && $cp <= 0x000D28)  { $ret = $cp; break; }
                if ($cp >= 0x000D2A && $cp <= 0x000D39)  { $ret = $cp; break; }
                if ($cp >= 0x000D3E && $cp <= 0x000D40)  { $ret = $cp; break; }
                if ($cp >= 0x000D46 && $cp <= 0x000D48)  { $ret = $cp; break; }
                if ($cp >= 0x000D4A && $cp <= 0x000D4C)  { $ret = $cp; break; }
                if ($cp == 0x000D57)  { $ret = $cp; break; }
                if ($cp >= 0x000D60 && $cp <= 0x000D61)  { $ret = $cp; break; }
                if ($cp >= 0x000D66 && $cp <= 0x000D6F)  { $ret = $cp; break; }
                if ($cp >= 0x000D82 && $cp <= 0x000D83)  { $ret = $cp; break; }
                if ($cp >= 0x000D85 && $cp <= 0x000D96)  { $ret = $cp; break; }
                if ($cp >= 0x000D9A && $cp <= 0x000DB1)  { $ret = $cp; break; }
                if ($cp >= 0x000DB3 && $cp <= 0x000DBB)  { $ret = $cp; break; }
                if ($cp == 0x000DBD)  { $ret = $cp; break; }
                if ($cp >= 0x000DC0 && $cp <= 0x000DC6)  { $ret = $cp; break; }
                if ($cp >= 0x000DCF && $cp <= 0x000DD1)  { $ret = $cp; break; }
                if ($cp >= 0x000DD8 && $cp <= 0x000DDF)  { $ret = $cp; break; }
                if ($cp >= 0x000DF2 && $cp <= 0x000DF4)  { $ret = $cp; break; }
                if ($cp >= 0x000E01 && $cp <= 0x000E30)  { $ret = $cp; break; }
                if ($cp >= 0x000E32 && $cp <= 0x000E33)  { $ret = $cp; break; }
                if ($cp >= 0x000E40 && $cp <= 0x000E46)  { $ret = $cp; break; }
                if ($cp >= 0x000E4F && $cp <= 0x000E5B)  { $ret = $cp; break; }
                if ($cp >= 0x000E81 && $cp <= 0x000E82)  { $ret = $cp; break; }
                if ($cp == 0x000E84)  { $ret = $cp; break; }
                if ($cp >= 0x000E87 && $cp <= 0x000E88)  { $ret = $cp; break; }
                if ($cp == 0x000E8A)  { $ret = $cp; break; }
                if ($cp == 0x000E8D)  { $ret = $cp; break; }
                if ($cp >= 0x000E94 && $cp <= 0x000E97)  { $ret = $cp; break; }
                if ($cp >= 0x000E99 && $cp <= 0x000E9F)  { $ret = $cp; break; }
                if ($cp >= 0x000EA1 && $cp <= 0x000EA3)  { $ret = $cp; break; }
                if ($cp == 0x000EA5)  { $ret = $cp; break; }
                if ($cp == 0x000EA7)  { $ret = $cp; break; }
                if ($cp >= 0x000EAA && $cp <= 0x000EAB)  { $ret = $cp; break; }
                if ($cp >= 0x000EAD && $cp <= 0x000EB0)  { $ret = $cp; break; }
                if ($cp >= 0x000EB2 && $cp <= 0x000EB3)  { $ret = $cp; break; }
                if ($cp == 0x000EBD)  { $ret = $cp; break; }
                if ($cp >= 0x000EC0 && $cp <= 0x000EC4)  { $ret = $cp; break; }
                if ($cp == 0x000EC6)  { $ret = $cp; break; }
                if ($cp >= 0x000ED0 && $cp <= 0x000ED9)  { $ret = $cp; break; }
                if ($cp >= 0x000EDC && $cp <= 0x000EDD)  { $ret = $cp; break; }
                if ($cp >= 0x000F00 && $cp <= 0x000F17)  { $ret = $cp; break; }
                if ($cp >= 0x000F1A && $cp <= 0x000F34)  { $ret = $cp; break; }
                if ($cp == 0x000F36)  { $ret = $cp; break; }
                if ($cp == 0x000F38)  { $ret = $cp; break; }
                if ($cp >= 0x000F3E && $cp <= 0x000F47)  { $ret = $cp; break; }
                if ($cp >= 0x000F49 && $cp <= 0x000F6A)  { $ret = $cp; break; }
                if ($cp == 0x000F7F)  { $ret = $cp; break; }
                if ($cp == 0x000F85)  { $ret = $cp; break; }
                if ($cp >= 0x000F88 && $cp <= 0x000F8B)  { $ret = $cp; break; }
                if ($cp >= 0x000FBE && $cp <= 0x000FC5)  { $ret = $cp; break; }
                if ($cp >= 0x000FC7 && $cp <= 0x000FCC)  { $ret = $cp; break; }
                if ($cp == 0x000FCF)  { $ret = $cp; break; }
                if ($cp >= 0x001000 && $cp <= 0x001021)  { $ret = $cp; break; }
                if ($cp >= 0x001023 && $cp <= 0x001027)  { $ret = $cp; break; }
                if ($cp >= 0x001029 && $cp <= 0x00102A)  { $ret = $cp; break; }
                if ($cp == 0x00102C)  { $ret = $cp; break; }
                if ($cp == 0x001031)  { $ret = $cp; break; }
                if ($cp == 0x001038)  { $ret = $cp; break; }
                if ($cp >= 0x001040 && $cp <= 0x001057)  { $ret = $cp; break; }
                if ($cp >= 0x0010A0 && $cp <= 0x0010C5)  { $ret = $cp; break; }
                if ($cp >= 0x0010D0 && $cp <= 0x0010F8)  { $ret = $cp; break; }
                if ($cp == 0x0010FB)  { $ret = $cp; break; }
                if ($cp >= 0x001100 && $cp <= 0x001159)  { $ret = $cp; break; }
                if ($cp >= 0x00115F && $cp <= 0x0011A2)  { $ret = $cp; break; }
                if ($cp >= 0x0011A8 && $cp <= 0x0011F9)  { $ret = $cp; break; }
                if ($cp >= 0x001200 && $cp <= 0x001206)  { $ret = $cp; break; }
                if ($cp >= 0x001208 && $cp <= 0x001246)  { $ret = $cp; break; }
                if ($cp == 0x001248)  { $ret = $cp; break; }
                if ($cp >= 0x00124A && $cp <= 0x00124D)  { $ret = $cp; break; }
                if ($cp >= 0x001250 && $cp <= 0x001256)  { $ret = $cp; break; }
                if ($cp == 0x001258)  { $ret = $cp; break; }
                if ($cp >= 0x00125A && $cp <= 0x00125D)  { $ret = $cp; break; }
                if ($cp >= 0x001260 && $cp <= 0x001286)  { $ret = $cp; break; }
                if ($cp == 0x001288)  { $ret = $cp; break; }
                if ($cp >= 0x00128A && $cp <= 0x00128D)  { $ret = $cp; break; }
                if ($cp >= 0x001290 && $cp <= 0x0012AE)  { $ret = $cp; break; }
                if ($cp == 0x0012B0)  { $ret = $cp; break; }
                if ($cp >= 0x0012B2 && $cp <= 0x0012B5)  { $ret = $cp; break; }
                if ($cp >= 0x0012B8 && $cp <= 0x0012BE)  { $ret = $cp; break; }
                if ($cp == 0x0012C0)  { $ret = $cp; break; }
                if ($cp >= 0x0012C2 && $cp <= 0x0012C5)  { $ret = $cp; break; }
                if ($cp >= 0x0012C8 && $cp <= 0x0012CE)  { $ret = $cp; break; }
                if ($cp >= 0x0012D0 && $cp <= 0x0012D6)  { $ret = $cp; break; }
                if ($cp >= 0x0012D8 && $cp <= 0x0012EE)  { $ret = $cp; break; }
                if ($cp >= 0x0012F0 && $cp <= 0x00130E)  { $ret = $cp; break; }
                if ($cp == 0x001310)  { $ret = $cp; break; }
                if ($cp >= 0x001312 && $cp <= 0x001315)  { $ret = $cp; break; }
                if ($cp >= 0x001318 && $cp <= 0x00131E)  { $ret = $cp; break; }
                if ($cp >= 0x001320 && $cp <= 0x001346)  { $ret = $cp; break; }
                if ($cp >= 0x001348 && $cp <= 0x00135A)  { $ret = $cp; break; }
                if ($cp >= 0x001361 && $cp <= 0x00137C)  { $ret = $cp; break; }
                if ($cp >= 0x0013A0 && $cp <= 0x0013F4)  { $ret = $cp; break; }
                if ($cp >= 0x001401 && $cp <= 0x001676)  { $ret = $cp; break; }
                if ($cp >= 0x001681 && $cp <= 0x00169A)  { $ret = $cp; break; }
                if ($cp >= 0x0016A0 && $cp <= 0x0016F0)  { $ret = $cp; break; }
                if ($cp >= 0x001700 && $cp <= 0x00170C)  { $ret = $cp; break; }
                if ($cp >= 0x00170E && $cp <= 0x001711)  { $ret = $cp; break; }
                if ($cp >= 0x001720 && $cp <= 0x001731)  { $ret = $cp; break; }
                if ($cp >= 0x001735 && $cp <= 0x001736)  { $ret = $cp; break; }
                if ($cp >= 0x001740 && $cp <= 0x001751)  { $ret = $cp; break; }
                if ($cp >= 0x001760 && $cp <= 0x00176C)  { $ret = $cp; break; }
                if ($cp >= 0x00176E && $cp <= 0x001770)  { $ret = $cp; break; }
                if ($cp >= 0x001780 && $cp <= 0x0017B6)  { $ret = $cp; break; }
                if ($cp >= 0x0017BE && $cp <= 0x0017C5)  { $ret = $cp; break; }
                if ($cp >= 0x0017C7 && $cp <= 0x0017C8)  { $ret = $cp; break; }
                if ($cp >= 0x0017D4 && $cp <= 0x0017DA)  { $ret = $cp; break; }
                if ($cp == 0x0017DC)  { $ret = $cp; break; }
                if ($cp >= 0x0017E0 && $cp <= 0x0017E9)  { $ret = $cp; break; }
                if ($cp >= 0x001810 && $cp <= 0x001819)  { $ret = $cp; break; }
                if ($cp >= 0x001820 && $cp <= 0x001877)  { $ret = $cp; break; }
                if ($cp >= 0x001880 && $cp <= 0x0018A8)  { $ret = $cp; break; }
                if ($cp >= 0x001E00 && $cp <= 0x001E9B)  { $ret = $cp; break; }
                if ($cp >= 0x001EA0 && $cp <= 0x001EF9)  { $ret = $cp; break; }
                if ($cp >= 0x001F00 && $cp <= 0x001F15)  { $ret = $cp; break; }
                if ($cp >= 0x001F18 && $cp <= 0x001F1D)  { $ret = $cp; break; }
                if ($cp >= 0x001F20 && $cp <= 0x001F45)  { $ret = $cp; break; }
                if ($cp >= 0x001F48 && $cp <= 0x001F4D)  { $ret = $cp; break; }
                if ($cp >= 0x001F50 && $cp <= 0x001F57)  { $ret = $cp; break; }
                if ($cp == 0x001F59)  { $ret = $cp; break; }
                if ($cp == 0x001F5B)  { $ret = $cp; break; }
                if ($cp == 0x001F5D)  { $ret = $cp; break; }
                if ($cp >= 0x001F5F && $cp <= 0x001F7D)  { $ret = $cp; break; }
                if ($cp >= 0x001F80 && $cp <= 0x001FB4)  { $ret = $cp; break; }
                if ($cp >= 0x001FB6 && $cp <= 0x001FBC)  { $ret = $cp; break; }
                if ($cp == 0x001FBE)  { $ret = $cp; break; }
                if ($cp >= 0x001FC2 && $cp <= 0x001FC4)  { $ret = $cp; break; }
                if ($cp >= 0x001FC6 && $cp <= 0x001FCC)  { $ret = $cp; break; }
                if ($cp >= 0x001FD0 && $cp <= 0x001FD3)  { $ret = $cp; break; }
                if ($cp >= 0x001FD6 && $cp <= 0x001FDB)  { $ret = $cp; break; }
                if ($cp >= 0x001FE0 && $cp <= 0x001FEC)  { $ret = $cp; break; }
                if ($cp >= 0x001FF2 && $cp <= 0x001FF4)  { $ret = $cp; break; }
                if ($cp >= 0x001FF6 && $cp <= 0x001FFC)  { $ret = $cp; break; }
                if ($cp == 0x00200E)  { $ret = $cp; break; }
                if ($cp == 0x002071)  { $ret = $cp; break; }
                if ($cp == 0x00207F)  { $ret = $cp; break; }
                if ($cp == 0x002102)  { $ret = $cp; break; }
                if ($cp == 0x002107)  { $ret = $cp; break; }
                if ($cp >= 0x00210A && $cp <= 0x002113)  { $ret = $cp; break; }
                if ($cp == 0x002115)  { $ret = $cp; break; }
                if ($cp >= 0x002119 && $cp <= 0x00211D)  { $ret = $cp; break; }
                if ($cp == 0x002124)  { $ret = $cp; break; }
                if ($cp == 0x002126)  { $ret = $cp; break; }
                if ($cp == 0x002128)  { $ret = $cp; break; }
                if ($cp >= 0x00212A && $cp <= 0x00212D)  { $ret = $cp; break; }
                if ($cp >= 0x00212F && $cp <= 0x002131)  { $ret = $cp; break; }
                if ($cp >= 0x002133 && $cp <= 0x002139)  { $ret = $cp; break; }
                if ($cp >= 0x00213D && $cp <= 0x00213F)  { $ret = $cp; break; }
                if ($cp >= 0x002145 && $cp <= 0x002149)  { $ret = $cp; break; }
                if ($cp >= 0x002160 && $cp <= 0x002183)  { $ret = $cp; break; }
                if ($cp >= 0x002336 && $cp <= 0x00237A)  { $ret = $cp; break; }
                if ($cp == 0x002395)  { $ret = $cp; break; }
                if ($cp >= 0x00249C && $cp <= 0x0024E9)  { $ret = $cp; break; }
                if ($cp >= 0x003005 && $cp <= 0x003007)  { $ret = $cp; break; }
                if ($cp >= 0x003021 && $cp <= 0x003029)  { $ret = $cp; break; }
                if ($cp >= 0x003031 && $cp <= 0x003035)  { $ret = $cp; break; }
                if ($cp >= 0x003038 && $cp <= 0x00303C)  { $ret = $cp; break; }
                if ($cp >= 0x003041 && $cp <= 0x003096)  { $ret = $cp; break; }
                if ($cp >= 0x00309D && $cp <= 0x00309F)  { $ret = $cp; break; }
                if ($cp >= 0x0030A1 && $cp <= 0x0030FA)  { $ret = $cp; break; }
                if ($cp >= 0x0030FC && $cp <= 0x0030FF)  { $ret = $cp; break; }
                if ($cp >= 0x003105 && $cp <= 0x00312C)  { $ret = $cp; break; }
                if ($cp >= 0x003131 && $cp <= 0x00318E)  { $ret = $cp; break; }
                if ($cp >= 0x003190 && $cp <= 0x0031B7)  { $ret = $cp; break; }
                if ($cp >= 0x0031F0 && $cp <= 0x00321C)  { $ret = $cp; break; }
                if ($cp >= 0x003220 && $cp <= 0x003243)  { $ret = $cp; break; }
                if ($cp >= 0x003260 && $cp <= 0x00327B)  { $ret = $cp; break; }
                if ($cp >= 0x00327F && $cp <= 0x0032B0)  { $ret = $cp; break; }
                if ($cp >= 0x0032C0 && $cp <= 0x0032CB)  { $ret = $cp; break; }
                if ($cp >= 0x0032D0 && $cp <= 0x0032FE)  { $ret = $cp; break; }
                if ($cp >= 0x003300 && $cp <= 0x003376)  { $ret = $cp; break; }
                if ($cp >= 0x00337B && $cp <= 0x0033DD)  { $ret = $cp; break; }
                if ($cp >= 0x0033E0 && $cp <= 0x0033FE)  { $ret = $cp; break; }
                if ($cp >= 0x003400 && $cp <= 0x004DB5)  { $ret = $cp; break; }
                if ($cp >= 0x004E00 && $cp <= 0x009FA5)  { $ret = $cp; break; }
                if ($cp >= 0x00A000 && $cp <= 0x00A48C)  { $ret = $cp; break; }
                if ($cp >= 0x00AC00 && $cp <= 0x00D7A3)  { $ret = $cp; break; }
                if ($cp >= 0x00D800 && $cp <= 0x00FA2D)  { $ret = $cp; break; }
                if ($cp >= 0x00FA30 && $cp <= 0x00FA6A)  { $ret = $cp; break; }
                if ($cp >= 0x00FB00 && $cp <= 0x00FB06)  { $ret = $cp; break; }
                if ($cp >= 0x00FB13 && $cp <= 0x00FB17)  { $ret = $cp; break; }
                if ($cp >= 0x00FF21 && $cp <= 0x00FF3A)  { $ret = $cp; break; }
                if ($cp >= 0x00FF41 && $cp <= 0x00FF5A)  { $ret = $cp; break; }
                if ($cp >= 0x00FF66 && $cp <= 0x00FFBE)  { $ret = $cp; break; }
                if ($cp >= 0x00FFC2 && $cp <= 0x00FFC7)  { $ret = $cp; break; }
                if ($cp >= 0x00FFCA && $cp <= 0x00FFCF)  { $ret = $cp; break; }
                if ($cp >= 0x00FFD2 && $cp <= 0x00FFD7)  { $ret = $cp; break; }
                if ($cp >= 0x00FFDA && $cp <= 0x00FFDC)  { $ret = $cp; break; }
                if ($cp >= 0x010300 && $cp <= 0x01031E)  { $ret = $cp; break; }
                if ($cp >= 0x010320 && $cp <= 0x010323)  { $ret = $cp; break; }
                if ($cp >= 0x010330 && $cp <= 0x01034A)  { $ret = $cp; break; }
                if ($cp >= 0x010400 && $cp <= 0x010425)  { $ret = $cp; break; }
                if ($cp >= 0x010428 && $cp <= 0x01044D)  { $ret = $cp; break; }
                if ($cp >= 0x01D000 && $cp <= 0x01D0F5)  { $ret = $cp; break; }
                if ($cp >= 0x01D100 && $cp <= 0x01D126)  { $ret = $cp; break; }
                if ($cp >= 0x01D12A && $cp <= 0x01D166)  { $ret = $cp; break; }
                if ($cp >= 0x01D16A && $cp <= 0x01D172)  { $ret = $cp; break; }
                if ($cp >= 0x01D183 && $cp <= 0x01D184)  { $ret = $cp; break; }
                if ($cp >= 0x01D18C && $cp <= 0x01D1A9)  { $ret = $cp; break; }
                if ($cp >= 0x01D1AE && $cp <= 0x01D1DD)  { $ret = $cp; break; }
                if ($cp >= 0x01D400 && $cp <= 0x01D454)  { $ret = $cp; break; }
                if ($cp >= 0x01D456 && $cp <= 0x01D49C)  { $ret = $cp; break; }
                if ($cp >= 0x01D49E && $cp <= 0x01D49F)  { $ret = $cp; break; }
                if ($cp == 0x01D4A2)  { $ret = $cp; break; }
                if ($cp >= 0x01D4A5 && $cp <= 0x01D4A6)  { $ret = $cp; break; }
                if ($cp >= 0x01D4A9 && $cp <= 0x01D4AC)  { $ret = $cp; break; }
                if ($cp >= 0x01D4AE && $cp <= 0x01D4B9)  { $ret = $cp; break; }
                if ($cp == 0x01D4BB)  { $ret = $cp; break; }
                if ($cp >= 0x01D4BD && $cp <= 0x01D4C0)  { $ret = $cp; break; }
                if ($cp >= 0x01D4C2 && $cp <= 0x01D4C3)  { $ret = $cp; break; }
                if ($cp >= 0x01D4C5 && $cp <= 0x01D505)  { $ret = $cp; break; }
                if ($cp >= 0x01D507 && $cp <= 0x01D50A)  { $ret = $cp; break; }
                if ($cp >= 0x01D50D && $cp <= 0x01D514)  { $ret = $cp; break; }
                if ($cp >= 0x01D516 && $cp <= 0x01D51C)  { $ret = $cp; break; }
                if ($cp >= 0x01D51E && $cp <= 0x01D539)  { $ret = $cp; break; }
                if ($cp >= 0x01D53B && $cp <= 0x01D53E)  { $ret = $cp; break; }
                if ($cp >= 0x01D540 && $cp <= 0x01D544)  { $ret = $cp; break; }
                if ($cp == 0x01D546)  { $ret = $cp; break; }
                if ($cp >= 0x01D54A && $cp <= 0x01D550)  { $ret = $cp; break; }
                if ($cp >= 0x01D552 && $cp <= 0x01D6A3)  { $ret = $cp; break; }
                if ($cp >= 0x01D6A8 && $cp <= 0x01D7C9)  { $ret = $cp; break; }
                if ($cp >= 0x020000 && $cp <= 0x02A6D6)  { $ret = $cp; break; }
                if ($cp >= 0x02F800 && $cp <= 0x02FA1D)  { $ret = $cp; break; }
                if ($cp >= 0x0F0000 && $cp <= 0x0FFFFD)  { $ret = $cp; break; }
                if ($cp >= 0x100000 && $cp <= 0x10FFFD)  { $ret = $cp; break; }
            } while(0);
        }
        */
        if ($ret !== null) {
            $vname = $this->getValidatorName($id);
            $this->internalError(
                [
                    'message' => $vname.': Detected invalid unicode.',
                    'value' => $ret,
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return $cp;
        }
        return null;
    }


    /**
     * Regexp string validator (PCRE)
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "min" - Minimum length.
     * "max" - Maximum length.
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
     */
    private function validateRegexp(&$validated, $value, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        assert(isset($options['min']) && is_int($options['min']));
        assert(isset($options['max']) && is_int($options['max']));
        assert($options['min'] <= $options['max']);
        assert(isset($options['regexp']));

        $min = $options['min'];
        $max = $options['max'];
        $regexp = $options['regexp'];
        $ret = (string)$value;

        // Validate as String first
        if (!$this->validateString($ret, $value, $flags, $options, $func_opts, VALIDATE_REGEXP)) {
            return false;
        }

        if (!preg_match($regexp, $ret)) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_REGEXP: Failed to match.',
                    'value' => $ret,
                ],
                [VALIDATE_REGEXP, $flags, $options],
                $func_opts
            );
            return false;
        }
        assert(is_string($ret));
        if ($flags & VALIDATE_FLAG_RAW) {
            $validated = $value;
        } else {
            $validated = $ret;
        }
        return true;
    }


    /**
     * Callback validator which calls specified callback function for validation.
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "min" - Minimum length.
     * "max" - Maximum length.
     * "callback" - Callback function that perform validation.
     *              It must have following parameters:
     *               &$validated - Although Validate is not a filter, but it may change. The result.
     *               $value - Value to be validated.
     *               $ctx - Validator context.
     *              Return value:
     *               Return value can be any thing, but strongly recommends not to sanitize input.
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return mixed
     */
    private function validateCallback(&$validated, $value, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        assert(isset($options['min']) && is_int($options['min']));
        assert(isset($options['max']) && is_int($options['max']));
        assert($options['min'] <= $options['max']);
        assert($options['callback'] && is_callable($options['callback']));

        if (is_string($value)) {
            // Validate as String first
            if (!$this->validateString($validated, $value, $flags, $options, $func_opts, VALIDATE_CALLBACK)) {
                return false;
            }
        }

        $tmp = $options['callback']($this->context, $validated, $value);

        if (!$tmp) {
            $msg = is_bool($tmp) ? 'Returned false.' : 'NULL is returned. There must be bug in callback.';
            $this->internalError(
                [
                    'message' => 'VALIDATE_CALLBACK: '.$msg,
                    'value' => $value,
                ],
                [VALIDATE_CALLBACK, $flags, $options],
                $func_opts
            );
            return false;
        }
        if ($flags & VALIDATE_FLAG_RAW) {
            $validated = $value;
        }
        return true;
    }


    /**
     * Object validator which calls specified method as validator.
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "class" - Class name of object.
     * ”callback" - Callback method.
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * "default" - Default value. Default value is subject to be validated also.
     */
    private function validateObject(&$validated, $value, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));
        assert(is_string($options['callback']));

        if (!is_object($value)) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_OBJECT: Value is not an object. Type: \''.gettype($value).'\'',
                    'value' => $value,
                ],
                [VALIDATE_CALLBACK, $flags, $options],
                $func_opts
            );
            return false;
        }
        $class = get_class($value);
        if (!is_callable(array($class, $options['callback']))) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_OBJECT: Callback is not callable or does not exist. Callback: \''.$options['callback'].'\'',
                    'value' => $value,
                ],
                [VALIDATE_CALLBACK, $flags, $options],
                $func_opts
            );
            return false;
        }
        $callback = $options['callback'];
        $ret = $value->$callback($this->context);
        assert(is_bool($ret));
        if ($ret !== true) {
            $this->internalError(
                [
                    'message' => 'VALIDATE_OBJECT: Object validation failed.',
                    'value' => $value,
                ],
                [VALIDATE_CALLBACK, $flags, $options],
                $func_opts
            );
            return false;
        }
        $validated = $value;
        return true;
    }


    /************** private methods - error ****************/


    /**
     * Handle external validation errors.
     *
     * @param string   $error     Error message.
     * @param int      $type      Error type.
     *
     * @return null
     */
    private function errorImpl($message, $type)
    {
        assert(is_string($message));

        assert(isset($this->context_vars['value']) || is_null($this->context_vars['value']));
        //assert($this->validateSpec($this->context_vars['spec']));
        assert(is_int($this->context_vars['func_opts']) && !($this->context_vars['func_opts'] & VALIDATE_OPT_UPPER));

        $this->status = false;
        $param = end($this->currentElem);
        $error = [
            'type'       => $type,
            'param'      => $this->currentElem,
            'defined'    => $this->context_vars['defined'],
            'message'    => $message,
            'spec'       => $this->context_vars['spec'],
            'func_opts'  => $this->context_vars['func_opts'],
            'value'      => $this->context_vars['value'],
            'orig_value' => $this->context_vars['orig_value'],
        ];

        if (!is_scalar($this->context_vars['value'])) {
            $value = serialize($this->context_vars['value']);
        } else {
            $value = $this->context_vars['value'];
        }

        $error_msg = 'param: \''.join('=>', $this->currentElem).'\' '.
                     'error: \''. $message .'\' val: \''. substr($value, 0, 2048) .'\'';

        // System errors are stored always.
        if ($type === E_ERROR) {
            $this->errorImplStore($this->errors, $error);
        } elseif ($type === E_WARNING) {
            $this->errorImplStore($this->warnings, $error);
        } elseif ($type === E_NOTICE) {
            $this->errorImplStore($this->notices, $error);
        }

        // Set error type flags setting controls ERROR / EXCEPTION.
        // Warning would not raise ERROR nor EXCEPTION.
        $fatal = !($type & (E_USER_WARNING | E_USER_NOTICE))
                 && !($this->context_vars['spec'][1] & (VALIDATE_FLAG_WARNING | VALIDATE_FLAG_NOTICE));
        $user_error_msg = $this->context_vars['spec'][2]['error_message'] ?? null;

        // Store error messages
        // User "error_message" defined in spec option is stored only once.
        // User validate_error()/validate_warning() error messages can be
        // stored as many times as users want.
        switch ($type) {
            // Following 3 are set by $flags and $options["error_message"]
            case E_ERROR:
                if ($user_error_msg) {
                    $this->errorImplStore($this->userErrors, $user_error_msg);
                }
                break;
            case E_WARNING:
                if ($user_error_msg) {
                    $this->errorImplStore($this->userWarnings, $user_error_msg);
                }
                break;
            case E_NOTICE:
                if ($user_error_msg) {
                    $this->errorImplStore($this->userNotices, $user_error_msg);
                }
                break;
            // Following 3 are set by function/method. e.g. validate_error()
            case E_USER_ERROR:
                $this->errorImplStore($this->userErrors, $message);
                break;
            case E_USER_WARNING:
                $this->errorImplStore($this->userWarnings, $message);
                break;
            case E_USER_NOTICE:
                $this->errorImplStore($this->userNotices, $message);
                break;
            default:
                trigger_error('Cannot mix error level. e.g. E_WARNING | E_NOTICE', E_USER_ERROR);
        }

        // Logger
        if ($this->context_vars['func_opts'] & VALIDATE_OPT_LOG_ERROR) {
            if ($this->loggerFunction) {
                ($this->loggerFunction)($this, $error);
            } else {
                trigger_error('Validate logger function is not registered', E_USER_WARNING);
            }
        }

        // Error
        if ($fatal && $this->context_vars['func_opts'] & VALIDATE_OPT_RAISE_ERROR) {
            switch ($type) {
                case E_ERROR:
                case E_USER_ERROR:
                    trigger_error($error_msg, $this->error_level);
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                    trigger_error($error_msg, E_USER_WARNING);
                    break;
                case E_NOTICE:
                case E_USER_NOTICE:
                    trigger_error($error_msg, E_USER_WARNING);
                    break;
                default:
                    trigger_error('Report this error.', E_USER_ERROR);
            }
        }

        // Exception
        if ($fatal && !($this->context_vars['func_opts'] & VALIDATE_OPT_DISABLE_EXCEPTION)) {
            throw new InvalidArgumentException($error_msg);
        }
    }


    /**
     * Store errors as array.
     */
    private function errorImplStore(&$storage, $error)
    {
        assert(is_array($storage));
        // System errors are array. User errors are string.
        assert(is_array($error) || is_string($error));
        assert(is_array($this->context_vars['spec']));
        assert(is_int($this->context_vars['func_opts']));

        $mode = $this->context_vars['func_opts'] & (VALIDATE_OPT_ERROR_FULL|VALIDATE_OPT_ERROR_PARAM|VALIDATE_OPT_ERROR_SQUASH);
        if (!$mode) {
            $mode = VALIDATE_OPT_ERROR_PARAM;
        }

        if (is_string($error)) {
            foreach ($this->context_vars['spec'][2] as $opt => $opt_val) {
                if (is_scalar($opt_val)) {
                    $error = str_replace('{{'.$opt.'}}', $opt_val, $error);
                }
            }
        }

        if ($mode & VALIDATE_OPT_ERROR_FULL) {
            $storage = $storage;
            foreach($this->currentElem as $el) {
                if (!isset($storage[$el])) {
                    $storage[$el] = array();
                }
                $storage = &$storage[$el];
            }
            assert(is_array($storage));
            $storage[] = $error;
        } elseif ($mode & VALIDATE_OPT_ERROR_PARAM) {
            $storage[end($this->currentElem)][] = $error;
        } else {
            $storage[join('=>', $this->currentElem)][] = $error;
        }
    }


    /**
     * Handle internal validation errors.
     *
     * @param array $error     Error message. ['message'=>$msg, 'param'=>$parm, 'value'=>$val]
     * @param array $spec      Validator spec array
     * @param int   $func_opts Validator function bit mask options.
     *
     * @return null
     */
    private function internalError($error, $spec, $func_opts, $type = E_ERROR)
    {
        assert(is_array($error));
        assert(is_string($error['message']));
        assert(is_null($error['value']) || isset($error['value']));
        assert(is_array($spec));
        // $spec could be array of specs for a var. i.e. is_array($spec[VALIDATE_ID])
        assert(!isset($spec[VALIDATE_ID]) || is_array($spec[VALIDATE_ID]) || (is_int($spec[VALIDATE_ID]) && is_int($spec[VALIDATE_FLAGS]) && is_array($spec[VALIDATE_OPTIONS])));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));
        assert($type === E_ERROR || $type === E_WARNING || $type === E_NOTICE);

        if ($spec[VALIDATE_FLAGS] & VALIDATE_FLAG_NOTICE) {
            $type = E_NOTICE;
        }
        if ($spec[VALIDATE_FLAGS] & VALIDATE_FLAG_WARNING) {
            $type = E_WARNING;
        }

        $this->setContextErrorValue($error['value']);
        $this->errorImpl($error['message'], $type);
    }


    /**
     * Handle internal validation warnings.
     *
     * @param array $error     Error message. ['message'=>$msg, 'param'=>$parm, 'value'=>$val]
     * @param array $spec      Validator spec array
     * @param int   $func_opts Validator function bit mask options.
     *
     * @return null
     */
    private function internalWarning($error, $spec, $func_opts)
    {
        assert(is_array($error));
        assert(is_string($error['message']));
        assert(is_null($error['value']) || isset($error['value']));
        assert(is_array($spec));
        // $spec could be array of specs for a var. i.e. is_array($spec[VALIDATE_ID])
        assert(!isset($spec[VALIDATE_ID]) || is_array($spec[VALIDATE_ID]) || (is_int($spec[VALIDATE_ID]) && is_int($spec[VALIDATE_FLAGS]) && is_array($spec[VALIDATE_OPTIONS])));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        $this->setContextErrorValue($error['value']);
        $this->errorImpl($error['message'], E_WARNING);
    }


    /**
     * Handle internal validation notices.
     *
     * @param array $error     Error message. ['message'=>$msg, 'param'=>$parm, 'value'=>$val]
     * @param array $spec      Validator spec array
     * @param int   $func_opts Validator function bit mask options.
     *
     * @return null
     */
    private function internalNotice($error, $spec, $func_opts)
    {
        assert(is_array($error));
        assert(is_string($error['message']));
        assert(is_null($error['value']) || isset($error['value']));
        assert(is_array($spec));
        // $spec could be array of specs for a var. i.e. is_array($spec[VALIDATE_ID])
        assert(!isset($spec[VALIDATE_ID]) || is_array($spec[VALIDATE_ID]) || (is_int($spec[VALIDATE_ID]) && is_int($spec[VALIDATE_FLAGS]) && is_array($spec[VALIDATE_OPTIONS])));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        $this->setContextErrorValue($error['value']);
        $this->errorImpl($error['message'], E_NOTICE);
    }


    /**
     * Get error and warnings
     *
     * @return array
     */
    private function getErrorAndWarning($type)
    {
        $ret = array();
        switch ($type) {
            case E_USER_ERROR:
                $ret['error']   = $this->userErrors ?? [];
                $ret['warning'] = $this->userWarnings ?? [];
                $ret['notice']  = $this->userNotices ?? [];
                break;
            case E_ERROR:
                $ret['error']   = $this->errors ?? [];
                $ret['warning'] = $this->warnings ?? [];
                $ret['notice']  = $this->notices ?? [];
                break;
            default:
                trigger_error('Report this error.', E_USER_ERROR);
        }
        return $ret;
    }


    /************** private methods - spec validation ****************/


    /**
     * Validate validate()'s input spec array.
     *
     * @param array $spec Validation spec array.
     *
     * @return bool TRUE for success, FALSE otherwise.
     */
    private function validateSpecImpl(&$spec)
    {
        if (!is_array($spec)) {
            $this->specError(
                [
                    'message' => 'Validation spec must be array.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        }

        // Validator ID
        if (!isset($spec[VALIDATE_ID])) {
            $this->specError(
                [
                    'message' =>'Validator ID(int) is missing.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        } elseif (!is_int($spec[VALIDATE_ID]) || $spec[VALIDATE_ID] <= VALIDATE_INVALID || ($spec[VALIDATE_ID] > VALIDATE_LAST && $spec[VALIDATE_ID] !== VALIDATE_UNVALIDATED)) {
            $this->specError(
                [
                    'message' => 'Validator ID must be valid int.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        }
        // Flags
        if (!isset($spec[VALIDATE_FLAGS])) {
            $this->specError(
                [
                    'message' => 'Validator Flag(int) is missing.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
        } elseif (!is_int($spec[VALIDATE_FLAGS])) {
            $this->specError(
                [
                    'message' => 'Validator Flag must be valid int.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
        }
        // Options
        if (!isset($spec[VALIDATE_OPTIONS])) {
            $this->specError(
                [
                    'message' => 'Validator Options(array) is missing.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        } elseif (!is_array($spec[VALIDATE_OPTIONS])) {
            $this->specError(
                [
                    'message' => 'Validation option must be array.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        }


        // Special validation validates unvalidated result(value).
        // Unvalidated result is checked after validateImpl().
        // Spec check is called from inside Validate, then this is used.
        if ($spec[VALIDATE_ID] === VALIDATE_UNVALIDATED) {
            // No check is needed.
            return true;
        }

        // Multi specs
        if ($spec[VALIDATE_ID] === VALIDATE_MULTI) {
            $multi_spec_ret = true;
            if (count($spec) !== 4) {
                $this->specError(
                    [
                        'message' => 'VALIDATE_MULTI requires 4 elements.',
                        'spec'    => $spec,
                        'flags'   => 'String flags are not available for VALIDATE_MULTI',
                    ]
                );
                return false;
            }
            if (!isset($spec[VALIDATE_PARAMS])) {
                $this->specError(
                    [
                        'message' => 'VALIDATE_MULTI spec parameters ($spec[VALIDATE_PARAMS]) is missing.',
                        'spec'    => $spec,
                        'flags'   => 'String flags are not available for VALIDATE_MULTI',
                    ]
                );
                $multi_spec_ret = false;
            }
            if (($spec[VALIDATE_FLAGS] & (VALIDATE_MULTI_AND) && ($spec[VALIDATE_FLAGS] & VALIDATE_MULTI_OR))) {
                $this->specError(
                    [
                        'message' => 'VALIDATE_MULTI requires either VALIDATE_MULTI_AND or VALIDATE_MULTI_OR flag. Both flags are set.',
                        'spec'    => $spec,
                        'flags'   => 'String flags are not available for VALIDATE_MULTI',
                    ]
                );
                $multi_spec_ret = false;
            }
            if (!($spec[VALIDATE_FLAGS] & (VALIDATE_MULTI_AND | VALIDATE_MULTI_OR))) {
                $this->specError(
                    [
                        'message' => 'VALIDATE_MULTI requires either VALIDATE_MULTI_AND or VALIDATE_MULTI_OR flag. Flag is missing.',
                        'spec'    => $spec,
                        'flags'   => 'String flags are not available for VALIDATE_MULTI',
                    ]
                );
                $multi_spec_ret =  false;
            }
            if (($spec[VALIDATE_FLAGS] & ~(VALIDATE_MULTI_AND | VALIDATE_MULTI_OR))) {
                $this->specError(
                    [
                        'message' => 'VALIDATE_MULTI Flags other than VALIDATE_MULTI_AND and VALIDATE_MULTI_OR are set.',
                        'spec'    => $spec,
                        'flags'   => 'String flags are not available for VALIDATE_MULTI',
                    ]
                );
                $multi_spec_ret =  false;
            }
            if (count($spec[VALIDATE_OPTIONS])) {
                $this->specWarning(
                    [
                        'message' => 'VALIDATE_MULTI has options that has no effect.',
                        'spec'    => $spec,
                        'flags'   => 'String flags are not available for VALIDATE_MULTI',
                    ]
                );
            }
            if ($spec[VALIDATE_FLAGS] & VALIDATE_FLAG_ARRAY) {
                $this->specError(
                    [
                        'message' => 'VALIDATE_MULTI cannot have VALIDATE_FLAG_ARRAY flag.',
                        'spec'    => $spec,
                        'flags'   => 'String flags are not available for VALIDATE_MULTI'
                    ]
                );
                $multi_spec_ret = false;
            }

            foreach ($spec[VALIDATE_PARAMS] as $k => $s) {
                $tmp = $this->validateSpecImpl($s);
                if ($tmp === true) {
                    unset($spec[VALIDATE_PARAMS][$k]);
                } else {
                    $multi_spec_ret = false;
                }
            }
            return $multi_spec_ret;
        }

        // Scalar / Resource / Object spec
        if ($spec[VALIDATE_ID] !== VALIDATE_ARRAY) {
            return $this->validateScalarSpec($spec);
        }

        // Array spec
        if (!isset($spec[VALIDATE_PARAMS])) {
            $this->specError(
                [
                    'message' => 'Array parameters ($spec[VALIDATE_PARAMS]) is missing.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        }
        if (!is_array($spec[VALIDATE_PARAMS])) {
            $this->specError(
                [
                    'message' => 'Array parameters ($spec[VALIDATE_PARAMS]) must be array.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        }

        $options = $spec[VALIDATE_OPTIONS];
        if (!isset($options['min']) || !isset($options['max'])) {
            $this->specError(
                [
                    'message' => 'Array parameter must have "min" and "max" options.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        } elseif ($options['min'] < 0 || $options['max'] < $options['min']) {
            $this->specError(
                [
                    'message' => 'Array must have valid min and max. min: "'.$options['min'].'" max:"'.$options['max'].'"',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        }

        $params = &$spec[VALIDATE_PARAMS];
        foreach ($params as $key => $val) {
            array_push($this->currentElem, $key);
            if ($this->validateSpecImpl($params[$key])) {
                unset($params[$key]);
            }
            array_pop($this->currentElem);
        }

        return true;
    }


    /**
     * Check useless flag bits are set.
     *
     * @return bool
     */
    private function validateScalarSpecFlags(&$flags_array, $flags, $start, $end)
    {
        assert(is_array($flags_array));
        assert(is_int($flags));
        assert(is_int($start));
        assert(is_int($end));

        $ret = true;
        for ($idx = $start; $idx < $end; $idx++) {
            $bit = 1 << $idx;
            if (($flags & $bit)) {
                $flags_array[] = '__INVALID_BIT__('.$idx.')';
                $ret = false;
            }
        }
        return $ret;
    }


    /**
     * Spec validation errors
     *
     * @param array $spec Validator spec array
     *
     * @return bool TRUE for success.
     */
    private function validateScalarSpec($spec)
    {
        if (!is_array($spec)) {
            $this->specError(
                [
                    'message' => 'Spec must be array.',
                    'spec'    => $spec,
                    'flags'   => ''
                ]
            );
            return false;
        }

        $vname = $this->getValidatorName($spec[VALIDATE_ID]);
        if (!$vname) {
            $this->specError(
                [
                    'message' => $vname.': Spec must have valid validator ID as 1st array element.',
                    'spec'    => $spec,
                    'flags'   => ''
                ]
            );
            return false;
        }

        if (count($spec) < 3) {
            $this->specError(
                [
                    'message' => $vname.': Too few elements. Spec must have 3 elements.',
                    'spec'    => $spec,
                    'flags'   => ''
                ]
            );
            return false;
        } elseif (count($spec) > 3) {
            $this->specWarning(
                [
                    'message' => $vname.': Too many elements. Spec must have 3 elements.',
                    'spec'    => $spec,
                    'flags'   => ''
                ]
            );
        }

        $ret = true;
        $flags = $spec[VALIDATE_FLAGS];
        $options = $spec[VALIDATE_OPTIONS];

        // Build flags used.
        $f = array();
        $max_bit = 16;
        $ret = false;
        switch ($spec[VALIDATE_ID]) {
            case VALIDATE_STRING: // REGEXP / CALLBACK shares the same flags
            case VALIDATE_REGEXP:
            case VALIDATE_CALLBACK:
                if ($flags & VALIDATE_STRING_SPACE) $f[] = 'VALIDATE_STRING_SPACE';
                if ($flags & VALIDATE_STRING_DIGIT) $f[] = 'VALIDATE_STRING_DIGIT';
                if ($flags & VALIDATE_STRING_TAB) $f[] = 'VALIDATE_STRING_TAB';
                if ($flags & VALIDATE_STRING_LF) $f[] = 'VALIDATE_STRING_LF';
                if ($flags & VALIDATE_STRING_CR) $f[] = 'VALIDATE_STRING_CR';
                if ($flags & VALIDATE_STRING_CRLF_MIXED) $f[] = 'VALIDATE_STRING_CRLF_MIXED';
                if ($flags & VALIDATE_STRING_CRLF) $f[] = 'VALIDATE_STRING_CRLF';
                if ($flags & VALIDATE_STRING_LOWER_ALPHA) $f[] = 'VALIDATE_STRING_LOWER_ALPHA';
                if ($flags & VALIDATE_STRING_UPPER_ALPHA) $f[] = 'VALIDATE_STRING_UPPER_ALPHA';
                if ($flags & VALIDATE_STRING_ALPHA) $f[] = 'VALIDATE_STRING_ALPHA';
                if ($flags & VALIDATE_STRING_ALNUM) $f[] = 'VALIDATE_STRING_ALNUM';
                if ($flags & VALIDATE_STRING_SYMBOL) $f[] = 'VALIDATE_STRING_SYMBOL';
                if ($flags & VALIDATE_STRING_MB) $f[] = 'VALIDATE_STRING_MB';
                if ($flags & VALIDATE_STRING_BINARY) $f[] = 'VALIDATE_STRING_BINARY';
                if ($flags & VALIDATE_STRING_RFC3454_D) $f[] = 'VALIDATE_STRING_RFC3454_D';
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_STRING_LAST_BIT, $max_bit);
                break;
            case VALIDATE_INT:
                if ($flags & VALIDATE_INT_AS_STRING) $f[] = 'VALIDATE_INT_AS_STRING';
                if ($flags & VALIDATE_INT_POSITIVE_SIGN) $f[] = 'VALIDATE_INT_POSITIVE_SIGN';
                if ($flags & VALIDATE_INT_NEGATIVE_SIGN) $f[] = 'VALIDATE_INT_NEGATIVE_SIGN';
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_INT_LAST_BIT, $max_bit);
                break;
            case VALIDATE_FLOAT:
                if ($flags & VALIDATE_FLOAT_AS_STRING) $f[] = 'VALIDATE_FLOAT_AS_STRING';
                if ($flags & VALIDATE_FLOAT_FRACTION) $f[] = 'VALIDATE_FLOAT_AS_FRACTION';
                if ($flags & VALIDATE_FLOAT_SCIENTIFIC) $f[] = 'VALIDATE_FLOAT_SCIENTIFIC';
                if ($flags & VALIDATE_FLOAT_POSITIVE_SIGN) $f[] = 'VALIDATE_FLOAT_POSITIVE_SIGN';
                if ($flags & VALIDATE_FLOAT_NEGATIVE_SIGN) $f[] = 'VALIDATE_FLOAT_NEGATIVE_SIGN';
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_FLOAT_LAST_BIT, $max_bit);
                break;
            case VALIDATE_NULL:
                if ($flags & VALIDATE_NULL_AS_STRING) $f[] = 'VALIDATE_NULL_AS_STRING';
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_NULL_LAST_BIT, $max_bit);
                break;
            case VALIDATE_BOOL:
                if ($flags & VALIDATE_BOOL_AS_STRING) $f[] = 'VALIDATE_BOOL_AS_STRING';
                if ($flags & VALIDATE_BOOL_01) $f[] = 'VALIDATE_BOOL_01';
                if ($flags & VALIDATE_BOOL_TF) $f[] = 'VALIDATE_BOOL_TF';
                if ($flags & VALIDATE_BOOL_TRUE_FALSE) $f[] = 'VALIDATE_BOOL_TRUE_FALSE';
                if ($flags & VALIDATE_BOOL_ON_OFF) $f[] = 'VALIDATE_BOOL_ON_OFF';
                if ($flags & VALIDATE_BOOL_YES_NO) $f[] = 'VALIDATE_BOOL_YES_NO';
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_BOOL_LAST_BIT, $max_bit);
                break;
            case VALIDATE_RESOURCE:
            case VALIDATE_OBJECT:
                $ret = true;
                break;
            default:
                trigger_error('Report this error.', E_USER_ERROR);
        }
        if (!$ret) {
            $this->specWarning(
                [
                    'message' => $vname.' has invalid flag for this validator.',
                    'spec'    => $spec,
                    'flags'   => join(' | ', $f),
                ]
            );
        }

        if ($flags & VALIDATE_FLAG_RAW) $f[] = 'VALIDATE_FLAG_RAW';
        if ($flags & VALIDATE_FLAG_REJECT) $f[] = 'VALIDATE_FLAG_REJECT';
        if ($flags & VALIDATE_FLAG_UNDEFINED) $f[] = 'VALIDATE_FLAG_UNDEFINED';
        if ($flags & VALIDATE_FLAG_UNDEFINED_TO_DEFAULT) $f[] = 'VALIDATE_FLAG_UNDEFINED_TO_DEFAULT';
        if ($flags & VALIDATE_FLAG_EMPTY) $f[] = 'VALIDATE_FLAG_EMPTY';
        if ($flags & VALIDATE_FLAG_EMPTY_TO_DEFAULT) $f[] = 'VALIDATE_FLAG_EMPTY_TO_DEFAULT';
        if ($flags & VALIDATE_FLAG_ARRAY) $f[] = 'VALIDATE_FLAG_ARRAY';
        if ($flags & VALIDATE_FLAG_ARRAY_KEY_ALNUM) $f[] = 'VALIDATE_FLAG_ARRAY_KEY_ALNUM';
        if ($flags & VALIDATE_FLAG_WARNING) $f[] = 'VALIDATE_FLAG_WARNING';
        if ($flags & VALIDATE_FLAG_PASSTHRU) $f[] = 'VALIDATE_FLAG_PASSTHRU';
        $tmp = $this->validateScalarSpecFlags($f, $flags, VALIDATE_FLAG_LAST_BIT, 32);
        $str_flags = join(' | ', $f);
        if (!$tmp) {
            $this->specWarning(
                [
                    'message' => $vname.' has invalid flag in common flags.',
                    'spec'    => $spec,
                    'flags'   => $str_flags
                ]
            );
        }

        // Check known options
        $known_options = [
            'min', 'max', // INT / FLOAT range, STRING / REGEXP / CALLBACK value length.
            'amax', 'amin', 'alimit', // Number of array elements.
            'key_callback', // Array key validation callback for VALIDATE_FLAG_ARRAY.
            'INF', '-INF', 'length', // FLOAT option.
            'encoding', 'ascii', 'unicode', // STRING / REGEXP / CALLBACK option.
            'values', // INT / STRING option. (REGEXP / CALLBACK may use, but not use with them)
            'regexp', // REGEXP option.
            'default', // default value for VALIDATE_FLAG_EMPTY_TO_DEFAULT and VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
            'filter', // Filter callback. All validators may have filter.
            'callback', // CALLBACK validator callback.
            'error_message', // Errors message.
        ];
        $cnt = 0;
        foreach ($known_options as $known) {
            if (isset($options[$known])) {
                $cnt++;
                if ($known === 'default') {
                    if ($spec[VALIDATE_ID] === VALIDATE_CALLBACK) {
                        // Callback may have any default value
                        continue;
                    }
                    if (($flags & VALIDATE_FLAG_ARRAY) && !is_array($options[$known]) && !is_callable($options[$known])) {
                        $this->specError(
                            [
                                'message' => $vname.' has invalid "default" option. "default" value must be array with VALIDATE_FLAG_ARRAY.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                    if ($spec[VALIDATE_ID] === VALIDATE_OBJECT && !(is_object($options[$known]) && is_callable($optoin[$known]))) {
                        $this->specError(
                            [
                                'message' => 'VALIDATE_OBJECT has invalid "default" option. "default" value must '.
                                             'be object or callable returns object for VALIDATE_OBJECT.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                    if ($spec[VALIDATE_ID] === VALIDATE_RESOURCE) {
                        $this->specError(
                            [
                                'message' => $vname.' has invalid "'.$known.'" option. "default" is not allowed for '.$vname.'.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
                if ($known !== 'default' && $known !== 'values' && !is_scalar($options[$known]) && !is_callable($options[$known])) {
                    $this->specError(
                        [
                            'message' => $vname.' has invalid "'.$known.'" option. Option value must be scalar or callable returns proper default value.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
            }
        }
        // Count user custom options by '_' prefix or integer key.
        foreach ($options as $key => $uv) {
            if (is_string($key) && $key{0} === '_') {
                $cnt++;
            }
        }
        if ($cnt !== count($options)) {
            $this->specWarning(
                [
                    'message' => $vname.' has unknown spec option and/or invalid spec option.',
                    'spec'    => $spec,
                    'flags'   => $str_flags
                ]
            );
        }

        // Check callbacks
        $callbacks = ['filter', 'callback', 'key_callback'];
        // TODO Call them and check it executes at least.

        // min/max check
        switch ($spec[VALIDATE_ID]) {
            case VALIDATE_STRING:
            case VALIDATE_REGEXP:
            case VALIDATE_CALLBACK:
                if (!isset($options['values'])) {
                    if (!(isset($options['min']) && isset($options['max']))) {
                        $this->specError(
                            [
                                'message' => $vname.' "min" and/or "max" is missing.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    } elseif ($options['min'] < 0 || $options['max'] < $options['min']) {
                        $this->specError(
                            [
                                'message' => $vname.' must have valid "min" and "max" options. min: "'
                                            .$options['min'].'" max:"'.$options['max'].'"',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
                break;
            case VALIDATE_INT:
                if (!isset($options['values'])) {
                    if (!(isset($options['min']) && isset($options['max']))) {
                        $this->specError(
                            [
                                'message' => $vname.' "min" and/or "max" is missing.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    } elseif (bccomp($options['min'], $options['max']) === 1) {
                        $this->specError(
                            [
                                'message' => $vname.' must have valid "min" and "max" options. min: "'
                                            .$options['min'].'" max:"'.$options['max'].'"',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
                break;
            case VALIDATE_FLOAT:
                if (!(isset($options['min']) && isset($options['max']))) {
                    $this->specError(
                        [
                            'message' => $vname.' "min" and/or "max" is missing.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                } elseif (!($flags & VALIDATE_FLOAT_AS_STRING)) {
                    if ($options['min'] > $options['max']) {
                        $this->specError(
                            [
                                'message' => $vname.' must have valid "min" and "max" options. min: "'
                                            .$options['min'].'" max:"'.$options['max'].'"',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
                break;
            case VALIDATE_NULL:
            case VALIDATE_BOOL:
            case VALIDATE_RESOURCE:
            case VALIDATE_OBJECT:
                if (isset($options['min']) || isset($options['min'])) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has "min" and/or "max" options that have no effect.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                break;
            default:
                trigger_error('Report this error.', E_USER_ERROR);
        }

        // encoding check
        switch ($spec[VALIDATE_ID]) {
            case VALIDATE_STRING:
            case VALIDATE_REGEXP:
            case VALIDATE_CALLBACK:
                if (isset($options['encoding']) && !($flags & VALIDATE_STRING_MB)) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has "encoding" option, but '.$vname.'_MB flag is not set.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                /*
                if (!isset($options['encoding']) && ($flags & VALIDATE_STRING_MB)) {
                    $this->specWarning([
                        'message' => 'NOTICE: '.$vname.' has '.$vname.'_MB, but "encoding" option is not set.',
                        'spec'    => $spec,
                        'flags'   => $str_flags]);
                }
                */
                break;
            case VALIDATE_INT:
            case VALIDATE_FLOAT:
            case VALIDATE_NULL:
            case VALIDATE_BOOL:
            case VALIDATE_RESOURCE:
            case VALIDATE_OBJECT:
                if (isset($options['encoding'])) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has "encoding" option that has no effect',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                break;
            default:
                trigger_error('Report this error.', E_USER_ERROR);
        }

        // default check
        switch ($spec[VALIDATE_ID]) {
            case VALIDATE_STRING:
            case VALIDATE_REGEXP:
            case VALIDATE_CALLBACK:
            case VALIDATE_INT:
            case VALIDATE_FLOAT:
            case VALIDATE_NULL:
            case VALIDATE_BOOL:
            case VALIDATE_RESOURCE:
            case VALIDATE_OBJECT:
                if (isset($options['default'])
                    && !($flags & (VALIDATE_FLAG_EMPTY_TO_DEFAULT | VALIDATE_FLAG_UNDEFINED_TO_DEFAULT))) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has "default" option, but VALIDATE_FLAGS_EMPTY_TO_DEFAULT '
                                        .'and/or VALIDATE_UNDEFINED_TO_DEFAULT is not set.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                break;
            default:
                trigger_error('Report this error.', E_USER_ERROR);
        }

        // callback check
        switch ($spec[VALIDATE_ID]) {
            case VALIDATE_STRING:
            case VALIDATE_REGEXP:
                break;
            case VALIDATE_CALLBACK:
                if (!isset($options['callback'])) {
                    $this->specError(
                        [
                            'message' => $vname.' has no "callback" option.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                if (!is_callable($options['callback'])) {
                    $this->specError(
                        [
                            'message' => $vname.' "callback" option is not callable.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                break;
            case VALIDATE_OBJECT:
                if (!isset($options['callback'])) {
                    $this->specError(
                        [
                            'message' => $vname.' has no "callback" option.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                // Callable check can only be done at runtime. i.e. Needs object.
                break;
            case VALIDATE_INT:
            case VALIDATE_FLOAT:
            case VALIDATE_NULL:
            case VALIDATE_BOOL:
            case VALIDATE_RESOURCE:
            if (isset($options['callback'])) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has "callback" option that has no effect.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                break;
            default:
                trigger_error('Report this error.', E_USER_ERROR);
        }

        // amin/amax/key_callback check
        switch ($spec[VALIDATE_ID]) {
            case VALIDATE_STRING:
            case VALIDATE_REGEXP:
            case VALIDATE_CALLBACK:
            case VALIDATE_INT:
            case VALIDATE_FLOAT:
            case VALIDATE_NULL:
            case VALIDATE_BOOL:
            case VALIDATE_RESOURCE:
            case VALIDATE_OBJECT:
                if (($flags & VALIDATE_FLAG_ARRAY) && (!isset($options['amin']) || !isset($options['amax']))) {
                    $this->specError(
                        [
                            'message' => $vname.' has VALIDATE_FLAG_ARRAY flag, but "amin" and/or "amax" option is missing.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                if (isset($options['amin']) || isset($options['amax'])) {
                    if (!is_int($options['amin']) || !is_int($options['amax'])) {
                        $this->specError(
                            [
                                'message' => $vname.'. "amin" and "amax" options must be int.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                    if ($options['amin'] >= $options['amax']) {
                        $this->specError(
                            [
                                'message'=> $vname.'. "amin" option is larger than "amax" option.',
                                'spec'   => $options,
                                'flags'  => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
                if (isset($options['key_callback']) && !($flags & VALIDATE_FLAG_ARRAY)) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has no VALIDATE_FLAG_ARRAY flag, but "key_callback" option is defined.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if (isset($options['key_callback']) && !($flags & VALIDATE_FLAG_ARRAY_KEY_ALNUM)) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has VALIDATE_FLAG_ARRAY_KEY_ALNUM flag, but "key_callback" option is defined.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if (!($flags & VALIDATE_FLAG_ARRAY) && ($flags & VALIDATE_FLAG_ARRAY_KEY_ALNUM)) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has VALIDATE_FLAG_ARRAY_KEY_ALNUM flag, but VALIDATE_FLAG_ARRAY is not defined.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                break;
            default:
                trigger_error('Report this error.', E_USER_ERROR);
        }

        // VALIDATE_*_BINARY / VALIDATE_FLAG_RAW / VALIDATE_*_SYMBOL / VALIDATE_*_CRLF_MIXED
        switch ($spec[VALIDATE_ID]) {
            case VALIDATE_STRING:
            case VALIDATE_REGEXP:
            case VALIDATE_CALLBACK:
                if (($flags & VALIDATE_STRING_BINARY)) {
                    $this->specNotice(
                        [
                            'message' => $vname.' has dangerous '.$vname.'_BINARY flag.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if (($flags & VALIDATE_FLAG_RAW)) {
                    $this->specNotice(
                        [
                            'message' => $vname.' has dangerous VALIDATE_FLAG_RAW flag.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if (($flags & VALIDATE_STRING_SYMBOL)) {
                    $this->specNotice(
                        [
                            'message' => $vname.' has dangerous '.$vname.'_SYMBOL flag.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if (($flags & VALIDATE_STRING_CRLF_MIXED)) {
                    $this->specNotice(
                        [
                            'message' => $vname.' has dangerous '.$vname.'_CRLF_MIXED flag.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                break;
            case VALIDATE_INT:
            case VALIDATE_FLOAT:
            case VALIDATE_NULL:
            case VALIDATE_BOOL:
                if (($flags & VALIDATE_STRING_BINARY)) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has '.$vname.'_BINARY flag that has no effect.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if (($flags & VALIDATE_FLAG_RAW)) {
                    $this->specNotice(
                        [
                            'message' => $vname.' has dangerous VALIDATE_FLAG_RAW flag.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                break;
            case VALIDATE_RESOURCE:
            case VALIDATE_OBJECT:
                break;
            default:
                trigger_error('Report this error.', E_USER_ERROR);
        }

        // Misc checks
        switch ($spec[VALIDATE_ID]) {
            case VALIDATE_STRING:
                if (isset($options['values'])) {
                    if (!is_array($options['values'])) {
                        $this->specError(
                            [
                                'message' => $vname.' "values" option is not an array.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                        break;
                    }
                    foreach ($options['values'] as $k => $v) {
                        if (!is_bool($v)) {
                            $this->specError(
                                [
                                    'message' => $vname.' "values" option must have bool value.',
                                    'spec'    => $spec,
                                    'flags'   => $str_flags
                                ]
                            );
                            $ret = false;
                            break;
                        }
                    }
                }
                break;
            case VALIDATE_REGEXP:
                if (!(isset($options['regexp'])) || !is_string($options['regexp'])) {
                    $this->specError(
                        [
                            'message' => $vname.' has no "regexp" option or invalid value.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                break;
            case VALIDATE_CALLBACK:
            case VALIDATE_INT:
                if (isset($options['values'])) {
                    if  (!is_array($options['values'])) {
                        $this->specError(
                            [
                                'message' => $vname.' "values" option is not an array.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                        break;
                    }
                    foreach ($options['values'] as $k => $v) {
                        if (!is_bool($v)) {
                            $this->specError(
                                [
                                    'message' => $vname.' "values" option must have bool value.',
                                    'spec'    => $spec,
                                    'flags'   => $str_flags
                                ]
                            );
                            $ret = false;
                            break;
                        }
                        if (strlen($k) !== strspn($k, '-1234567890')) {
                            $this->specError(
                                [
                                    'message' => $vname.' "values" option key must be integer.',
                                    'spec'    => $spec,
                                    'flags'   => $str_flags
                                ]
                            );
                            $ret = false;
                            break;
                        }
                    }
                }
                break;
            case VALIDATE_FLOAT:
            case VALIDATE_NULL:
            case VALIDATE_BOOL:
                break;
            case VALIDATE_RESOURCE:
                if (!isset($spec[VALIDATE_OPTIONS]['resource'])) {
                    $this->specError(
                        [
                            'message' => $vname.' has no $spec["resource"] option.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                } else if (!is_string($spec[VALIDATE_OPTIONS]['resource']) || $spec[VALIDATE_OPTIONS]['resource'] === '') {
                    $this->specError(
                        [
                            'message' => $vname.' must have string $spec["resource"] option.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                break;
            case VALIDATE_OBJECT:
                break;
            default:
                trigger_error('Report this error.', E_USER_ERROR);
        }

        return $ret;
    }


    /**
     * Spec validation errors
     *
     * @param array $error Error message ['message'=>$msg, 'spec'=>$val]
     *
     * @return null
     */
    private function specError($error)
    {
        assert(is_array($error));
        assert(isset($error['message']));
        assert(isset($error['spec']) || is_null($error['spec']));

        $error['param'] = join('=>', $this->currentElem);
        $this->errors[] = $error;
        $this->status = false;
    }


    /**
     * Spec validation warning
     *
     * @param array $error Warning message ['message'=>$msg, 'spec'=>$val]
     *
     * @return null
     */
    private function specWarning($error)
    {
        assert(is_array($error));
        assert(isset($error['message']));
        assert(isset($error['spec']) || is_null($error['spec']));

        $error['param'] = join('=>', $this->currentElem);
        $this->warnings[] = $error;
        // Warnings should not change status
        //$this->status = false;
    }


    /**
     * Spec validation notice
     *
     * @param array $error Notice message ['message'=>$msg, 'spec'=>$val]
     *
     * @return null
     */
    private function specNotice($error)
    {
        assert(is_array($error));
        assert(isset($error['message']));
        assert(isset($error['spec']) || is_null($error['spec']));

        $error['param'] = join('=>', $this->currentElem);
        $this->notices[] = $error;
        // Notices should not change status
        //$this->status = false;
    }
}
