# Validate PHP Reference

Validate PHP is input data validation framework that provides features required for strict input data validations.

Validate PHP is designed to handle both string inputs and natively typed inputs.

## Table of Contents

   * [Validate PHP Reference](#validate-php-reference)
      * [Functions](#functions)
         * [validate_init()](#validate_init)
         * [validate()](#validate)
         * [validate_set_error_level()](#validate_set_error_level)
         * [validate_error()](#validate_error)
         * [validate_warning()](#validate_warning)
         * [validate_notice()](#validate_notice)
         * [validate_get_system_errors()](#validate_get_system_errors)
         * [validate_get_user_errors()](#validate_get_user_errors)
         * [validate_set_logger_function()](#validate_set_logger_function)
         * [validate_spec()](#validate_spec)
      * [Validation SPEC](#validation-spec)
      * [Validator IDs](#validator-ids)
      * [General validator flags](#general-validator-flags)
      * [VALIDATOR specific flags](#validator-specific-flags)
         * [VALIDATE_NULL flags](#validate_null-flags)
         * [VALIDATE_BOOL flags](#validate_bool-flags)
         * [VALIDATE_INT flags](#validate_int-flags)
         * [VALIDATE_FLOAT flags](#validate_float-flags)
         * [VALIDATE_STRING flags](#validate_string-flags)
         * [VALIDATE_CALLBACK flags](#validate_callback-flags)
         * [VALIDATE_REGEXP flags](#validate_regexp-flags)
         * [VALIDATE_MULTI flags](#validate_multi-flags)
      * [Validator options](#validator-options)
      * [Callbacks](#callbacks)
         * ['filter' callback](#filter-callback)
         * ['key_callback' for array of scalars](#key_callback-for-array-of-scalars)
         * ['callback' for VALIDATE_CALLBACK validator](#callback-for-validate_callback-validator)

## Functions

### validate_init()

Initialized Validate object.

```php
Validate validate([string $root_name='root'])
```

Parameters:

 * $root_name - Optional root variable name.

Return value:

 * Validate object

Description:

validate_init() initializes Validate object which is used as validation context.


### validate()

Validate variable(s) by specified validation spec.

```php
mixed validate(Validate &$ctx, mixed &$inputs, array $specs [, int $func_opts = VALIDATE_OPT_CHECK_SPEC])
```

Parameters:

 * $ctx - Optional Validate object. If it is null, Validate object is initialized internally.
 * $inputs - Variable(s) to be validated.
 * $spec - Validation spec array.
 * $func_opts - Optional function behavior options. $spec parameter definition is validated by default.

validate() validates scalar or array. Array of scalars and/or array such as $_GET/$_POST can be validated. Nested array is supported.

$func_opts bit flag options:

 * VALIDATE_OPT_CHECK_SPEC - Check $spec parameter before validation. Use this for development and debugging.
 * VALIDATE_OPT_KEEP_INPUTS - Keep $input value as is. validate() unset validated values from $input array by default.
 * VALIDATE_OPT_RAISE_ERROR - Raise PHP error. validate() does not raise PHP errors by default. Use this for debugging.
 * VALIDATE_OPT_ERROR_PARAM - Use parameter name only for error.
 * VALIDATE_OPT_ERROR_FULL - Use array for error. i.e. Errors are stored as array corresponding to $inputs.
 * VALIDATE_OPT_ERROR_SQUASH - Use squashed parameter name for error. e.g. root=>array1=>array2=>param as error input param.
 * VALIDATE_OPT_DISABLE_EXCEPTION - Disable exception. Use this for debugging.
 * VALIDATE_OPT_LOG_ERROR - Log errors by logger function set by validate_set_logger_function().



### validate_set_error_level()

Set error level when PHP error is raised. i.e. Not an exception.

```php
void validate_set_error_level(Validate $ctx, int $error_level)
```

Parameters:

 * $ctx - Validate object
 * $error_level - E_USER_* constants

Description:

Set PHP error level when "VALIDATE_OPT_DISABLE_EXCEPTION | 



### validate_error()

Raise validation PHP error or exception.

```php
void validate_error(Validate $ctx, string $error_message)
```

Parameters:

 * $ctx - Validate object
 * $error_message - Error message string

Description:

validate_error() is intended to be used in callbacks.


### validate_warning()

Raise validation PHP error or exception.

```php
void validate_warning(Validate $ctx, string $error_message)
```

Parameters:

 * $ctx - Validate object
 * $error_message - Error message string

Description:

validate_warning() is intended to be used in callbacks.


### validate_notice()

Raise validation PHP error or exception.

```php
void validate_notice(Validate $ctx, string $error_message)
```

Parameters:

 * $ctx - Validate object
 * $error_message - Error message string

Description:

validate_notice() is intended to be used in callbacks.


### validate_get_system_errors()

Get system validation error messages.

```php
array validate_get_system_errors(Validate $ctx)
```

Parameters:

 * $ctx - Validate object

Return Value:

 * System error message array

Description:

validate_get_system_errors() returns system error messages set by Validate PHP.


### validate_get_user_errors()

Get user validation error messages.

```php
array validate_get_user_errors(Validate $ctx)
```

Parameters:

 * $ctx - Validate object

Return Value:

 * User error message array

Description:

validate_get_user_errors() returns user error messages set by user. e.g. validate_errer() or 'error_message' validator option.

### validate_set_logger_function()

Set error logging function.

```php
void validate_set_logger_function(Validate $ctx, callable $logger_func)
```

Parameters:

 * $ctx - Validate object
 * $logger_func - Logger function callback

Description:

By default, Validate PHP does not log validation errors. Validation error logging is strongly recommended.


### validate_spec()

Validate validation spec array.

```php
bool validate_spec(array $specs [, &$unvalidated = null [, &$ctx = null]])
```

Parameters:

 * $specs - Validation spec array
 * $unvalidated - Optional spec(s) that are invalid
 * $ctx - Optional Validate object

Retun Value:

 * Returns TRUE when success, FALSE otherwise.

Description:

validate() validates $spec parameter by default using validate_spec() internally. i.e.
VALIDATE_OPT_CHECK_SPEC function option flag is set by default.

## Validation SPEC

Validation SPEC format:

```php
$spec = [
    VALIDATE_ID => <int validator id>,
    VALIDATE_FLAGS => <int bit flags for this validator>,
    VALIDATE_OPTIONS => <array of this validator options>
];
```

SPEC array is processed recursively. Therefore any number of values can be validated
at once.

Constants:

 * VALIDATE_ID - 0
 * VALIDATE_FLAGS - 1
 * VALIDATE_OPTIONS - 2


Single value validation spec example:

```php
// Spec for string value which has exactly 32 alphabet chars(bytes)
$spec =
[
    VALIDATE_STRING,
    VALIDATE_FLAG_ALPHA,
    ['min' => 32, 'max' => 32],
];
```

Multiple values validation spec example:

```php
// Spec for array of values.
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
```

## Validator IDs

Validator IDs are integers.

Validatetors returns corresponding data type values. To allow and return other data type values, validator options must be specified.

Validator ID may be specified like

```php
$single_value_spec[VALIDATE_ID] = VALIDATE_STRING;
```

 * VALIDATE_NULL - Null
 * VALIDATE_BOOL - Boolean
 * VALIDATE_INT - Integer
 * VALIDATE_FLOAT - Float
 * VALIDATE_STRING - String
 * VALIDATE_REGEX - Regular Expression
 * VALIDATE_RESOURCE - Resource. e.g. Network connection resource.
 * VALIDATE_ARRAY - Array
 * VALIDATE_OBJECT - Object
 * VALIDATE_MULTI - Multiple Validators as array.


## General validator flags

Validator flags are bit flag.

Validator flags may be specified like

```php
$single_value_spec[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED;
```

 * VALIDATE_FLAG_NONE - No option flags
 * VALIDATE_FLAG_RAW - WARNING: Return RAW value. This could be dangerous. Consider this as debug feature.
 * VALIDATE_FLAG_REJECT - Reject if parameter is defined.
 * VALIDATE_FLAG_UNDEFINED - Input is undefined and allow undefined, set null. '' when *_AS_STRING flag is set.
 * VALIDATE_FLAG_UNDEFINED_TO_DEFAULT - Input is undefined, set default value specified by validator "default" option.
 * VALIDATE_FLAG_EMPTY - Input is empty ('' or null), set null. '' when *_AS_STRING flag is set.
 * VALIDATE_FLAG_EMPTY_TO_DEFAULT - Input is empty ('' or null), set default value specified by validator "default" option.
 * VALIDATE_FLAG_NULL - Accept NULL as valid input.
 * VALIDATE_FLAG_ARRAY - Require array of scalars. e.g. script.php?val[]=1&val[]=2
 * VALIDATE_FLAG_ARRAY_RECURSIVE - Allow nested array of scalars.
 * VALIDATE_FLAG_ARRAY_KEY_ALNUM - Allow alnum + '_' keys. By default, only INT is allowed for keys. Use "key_callback" option for custom key validation.
 * VALIDATE_FLAG_WARNING - WARNING: Dangerous option! Make validation error a warning error. i.e. Do not raise EXCEPTION / ERROR at all.
 * VALIDATE_FLAG_NOTICE - WARNING: Dangerous option! Make validation error a notice error. i.e. Do not raise EXCEPTION / ERROR at all.
 * VALIDATE_FLAG_PASSTHRU - WARNING: Dangerous option! Disables validation error & warning and logging. i.e. Behave as if there is no error/warning/notice.


## VALIDATOR specific flags

### VALIDATE_NULL flags

 * VALIDATE_NULL_AS_STRING - Validate '' string as NULL

### VALIDATE_BOOL flags

 * VALIDATE_BOOL_AS_STRING - Return validated value as string.
 * VALIDATE_BOOL_01 - Validate "1" and "0" as bool. Optionally return as string.
 * VALIDATE_BOOL_TF - Validate "t" and "f" (case insensitive) as bool. Optionally return as string.
 * VALIDATE_BOOL_TRUE_FALSE - Validate "true" and "false" (case insensitive) as bool. Optionally return as string.
 * VALIDATE_BOOL_ON_OFF - Validate "on" and "off" (case insensitive) as bool. Optionally return as string.
 * VALIDATE_BOOL_YES_NO - Validate "yes" and "no" (case insensitive) as bool. Optionally return as string.

### VALIDATE_INT flags

 * VALIDATE_INT_AS_STRING - Validate as int string. i.e. No type conversion.
 * VALIDATE_INT_BIT - TODO Implement this.
 * VALIDATE_INT_OCTAL - TODO Implement this.
 * VALIDATE_INT_HEX - TODO Implement this.
 * VALIDATE_INT_POSITIVE_SIGN - Allow "+" prefix.
 * VALIDATE_INT_NEGATIVE_SIGN - Allow "-" prefix.

### VALIDATE_FLOAT flags

 * VALIDATE_FLOAT_AS_STRING - Return validated value as string.
 * VALIDATE_FLOAT_FRACTION - TODO Implement this.
 * VALIDATE_FLOAT_THOUSAND - TODO Implement this.
 * VALIDATE_FLOAT_SCIENTIFIC - Allow scientific notation. e.g. 1.23e10
 * VALIDATE_FLOAT_POSITIVE_SIGN - Allow "+" prefix.
 * VALIDATE_FLOAT_NEGATIVE_SIGN - Allow "-" prefix.

### VALIDATE_STRING flags

String validator reject all chars by default. Allowed chars must be specified explicitly.

 * VALIDATE_STRING_SPACE - Allow space ' '.
 * VALIDATE_STRING_DIGIT - Allow 0-9
 * VALIDATE_STRING_TAB - Allow horizontal TAB.
 * VALIDATE_STRING_LF - Allow linefeed.
 * VALIDATE_STRING_CR' - Allow carrige return.
 * VALIDATE_STRING_CRLF_MIXED - WARNING: This option allows broken CR/LF sequence.
 * VALIDATE_STRING_CRLF - Same as (VALIDATE_STRING_LF | VALIDATE_STRING_CR), and ensures correct CR/LF sequence.
 * VALIDATE_STRING_LOWER_ALPHA - Allow lower alphabets.
 * VALIDATE_STRING_UPPER_ALPHA - Allow upper(capital) alphabets.
 * VALIDATE_STRING_ALPHA - Same as (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA)
 * VALIDATE_STRING_ALNUM - Same as (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT)
 * VALIDATE_STRING_SYMBOL - Allow cymbol chars, but not controls chars. WARNING: Dangerous option
 * VALIDATE_STRING_MB - Allow MultiByte chars. Only UTF-8 is supported.
 * VALIDATE_STRING_BINARY - Allow anything. WARNING: Dangerous option
 * VALIDATE_STRING_RFC3454_C - Allow Unicode CNTRL char. ASCII code CNTRL is handled by above flags. e.g. VALIDATE_STRING_LF.
 * VALIDATE_STRING_RFC3454_D - Allow Unicode bidirectional CNTRL char. ASCII code CNTRL is handled by above flags.

### VALIDATE_CALLBACK flags

Inputs are validated as string at first, then callback check is applied.

Callback validator accepts flags that are accepted by string validator. Constants are defined for API consistency.

 * VALIDATE_CALLBACK_SPACE - VALIDATE_STRING_SPACE
 * VALIDATE_CALLBACK_DIGIT - VALIDATE_STRING_DIGIT
 * VALIDATE_CALLBACK_TAB - VALIDATE_STRING_TAB
 * VALIDATE_CALLBACK_LF - VALIDATE_STRING_LF
 * VALIDATE_CALLBACK_CR - VALIDATE_STRING_CR
 * VALIDATE_CALLBACK_CRLF_MIXED - VALIDATE_STRING_CRLF_MIXED)
 * VALIDATE_CALLBACK_CRLF - VALIDATE_STRING_LF | VALIDATE_STRING_CR)
 * VALIDATE_CALLBACK_LOWER_ALPHA - VALIDATE_STRING_LOWER_ALPHA
 * VALIDATE_CALLBACK_UPPER_ALPHA - VALIDATE_STRING_UPPER_ALPHA
 * VALIDATE_CALLBACK_ALPHA - (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA)
 * VALIDATE_CALLBACK_ALNUM - (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT)
 * VALIDATE_CALLBACK_SYMBOL - VALIDATE_STRING_SYMBOL
 * VALIDATE_CALLBACK_MB - VALIDATE_STRING_MB
 * VALIDATE_CALLBACK_BINARY - VALIDATE_STRING_BINARY
 * VALIDATE_CALLBACK_RFC3454_C - VALIDATE_STRING_RFC3454_C
 * VALIDATE_CALLBACK_RFC3454_D  - VALIDATE_STRING_RFC3454_D

### VALIDATE_REGEXP flags

Inputs are validated as string at first, then regexp check is applied.

Regex validator accepts flags that are accepted by string validator. Constants are defined for API consistency.

 * VALIDATE_REGEXP_SPACE - VALIDATE_STRING_SPACE
 * VALIDATE_REGEXP_DIGIT - VALIDATE_STRING_DIGIT
 * VALIDATE_REGEXP_TAB - VALIDATE_STRING_TAB
 * VALIDATE_REGEXP_LF - VALIDATE_STRING_LF
 * VALIDATE_REGEXP_CR - VALIDATE_STRING_CR
 * VALIDATE_REGEXP_CRLF_MIXED - VALIDATE_STRING_CRLF_MIXED)
 * VALIDATE_REGEXP_CRLF - VALIDATE_STRING_LF | VALIDATE_STRING_CR)
 * VALIDATE_REGEXP_LOWER_ALPHA - VALIDATE_STRING_LOWER_ALPHA
 * VALIDATE_REGEXP_UPPER_ALPHA - VALIDATE_STRING_UPPER_ALPHA
 * VALIDATE_REGEXP_ALPHA - (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA)
 * VALIDATE_REGEXP_ALNUM - (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT)
 * VALIDATE_REGEXP_SYMBOL - VALIDATE_STRING_SYMBOL
 * VALIDATE_REGEXP_MB - VALIDATE_STRING_MB
 * VALIDATE_REGEXP_BINARY - VALIDATE_STRING_BINARY
 * VALIDATE_REGEXP_RFC3454_C - VALIDATE_STRING_RFC3454_C
 * VALIDATE_REGEXP_RFC3454_D  - VALIDATE_STRING_RFC3454_D

### VALIDATE_MULTI flags

This is not a type validator, but applies multiple validators for a value.

 * VALIDATE_MULTI_AND - Validate a value by multiple specs with AND condition. i.e All validations must success.
 * VALIDATE_MULTI_OR - Validate a value by multiple specs with OR condition. i.e One of validation must success.

## Validator options

Validator options are strings.

Validator options are specified like
 $single_value_spec[VALIDATE_OPTIONS]['opt_name'] = opt_value;

 * 'min' - Min value for numeric type. Min bytes for string.
 * 'max' - Max value for numeric type. Max bytes for string.
 * 'amin' - Minimum number of elements in an array.
 * 'amax' - Maximum number of elements in an array.
 * 'alimit' - Maximum number of elements in an array of scalars.
 * 'key_callback' - Array key validation callback function. By default only int key is allowed. VALIDATE_FLAG_ARRAY_KEY_ALNUM may be used for less strict array key validation.
 * 'INF' - Float validator option allows INF as valid value.
 * '-INF' - Float validator option allows -INF as valid value.
 * 'length' - Maximum chars(bytes) for float value strings. Float string could be extremely long. 'max' is used for Maximum value. Default to 32 bytes.
 * 'encoding' - Char encoding for strings. Only UTF-8 is supported currently and default to UTF-8.
 * 'ascii' - Char list for additionally allowed chars in strings.
 * 'values' - Set of valid values for string and integer. i.e. If an integer must be 1 or 2, 'values' should be [1=>true,2=>true];
 * 'regexp' - Regular expression for VALIDATE_REGEXP validator.
 * 'default' - Default values. Default values are validated by validators also.
 * 'filter' - Filter function which is applied before validation.
 * 'callback' - Callback function for VALIDATE_CALLBACK validator.
 * 'error_message' - User defined error/exception message.

VALIDATE_STRING, VALIDATE_REGEXP, VALIDATE_CALLBACK is string validators. String validations are performed before regexp or callback validations. i.e. Allowed chars must be specified by validator flags.

## Callbacks

### 'filter' callback

```php
mixed filter_callback(Validate $ctx, mixed $input, &$error)
```

'filter' callback parameters:

 * $ctx - Validate object. Don't modify this.
 * $input - Input value. $input is raw input value which can be anything.
 * &$error - Error message.

'filter' callback return value:

 * mixed - Can return any value, but returned value is validated by validator.

Example:

Following code applies trim() before validation.

```php
$vtrim = function($ctx, $input, &$error) {
    assert($ctx instanceof Validate);
    if (is_array($input)) {
        $error = 'HTTP Header Filter error: HTTP header must be string.';
        return;
    }
    return trim($input);
};

$B['header'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 0,
        'filter' => $vtrim,
    ]
];
```



### 'key_callback' for array of scalars

```php
bool key_callback(Validate $ctx, mixed $key)
```

Parameters:

 * $ctx - Validate object. Don't modify this.
 * $key - Int or string array key value.

Return Value:

 * Return TRUE for valid key, FALSE otherwise.


Description:

'key_callback' is for array key validations for array of scalars. Only integer key is allowed by default.
VALIDATE_FLAG_KEY_ALNUM flags can be used to allow alnum chars and '_'.


Example:

Validate.php uses 'key_callback' as follows:

```php
            if ($flags & VALIDATE_FLAG_ARRAY_KEY_ALNUM) {
                $key_callback= function ($ctx, $key) {
                    if (strlen($key) > 64) {
                        // By default, keys longer than 64 chars are not allowed.
                        // If users have trouble with this restriction, use user defined key validator.
                        return false;
                    }
                    $chars = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
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
                    if (is_int($key) || (strlen($key) === strspn($key, '1234567890-'))) {
                        return true;
                    }
                    return false;
                };
            }
```

### 'callback' for VALIDATE_CALLBACK validator

```php
bool callbak(Validate $ctx, mixed &$result, mixed $input)
```

Parameters:

* $ctx - Validate object. Don't modify this.
* $result - Validated value.
* $input - Input value.

Return Value:

* TRUE for successful validation, FALSE otherwise.

Description:

Update $input if you need modified value by using reference.

Usage example:

```php
$user_agent = [
    VALIDATE_CALLBACK,
    VALIDATE_CALLBACK_SPACE | VALIDATE_CALLBACK_ALNUM | VALIDATE_CALLBACK_SYMBOL | VALIDATE_CALLBACK_MB,
    ['min' => 10, 'max' => 128,
    'callback' =>
    function ($ctx, &$result, $input) {
        if (!is_string($input)) {
            validate_error($ctx, 'User-Agent validation: User-Agent must be string.');
            return false; // Make sure return false. validate_error() could be user error and return here.
        }
        $len = strlen($input);
        if ($len !== strspn($input, ' 1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ()_.,/;"')) {
            validate_error($ctx, 'User-Agent validation: Invalid char detected.');
            return false;
        }
        $result = $input;
        return true;
    }]
];
```

