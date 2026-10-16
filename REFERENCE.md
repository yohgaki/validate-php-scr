# Validate PHP Reference

Validate PHP is input data validation framework that provides features required for strict input data validations.

Validate PHP is designed to handle both string inputs and natively typed inputs.

## Table of Contents

   * [Validate PHP Reference](#validate-php-reference)
      * [Functions](#functions)
         * [validate_init()](#validate_init)
         * [validate()](#validate)
         * [validate_get_status()](#validate_get_status)
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
         * ['key_callback' for array keys](#key_callback-for-array-keys)
         * ['callback' for VALIDATE_CALLBACK validator](#callback-for-validate_callback-validator)
      * [Tips](#tips)

## Functions

### validate_init()

Initializes Validate object.

```php
Validate validate_init([string $root_name='ROOT'])
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

validate() removes validated values from $inputs (pass by reference), so $inputs holds the leftovers afterwards. Input keys that a VALIDATE_ARRAY spec does not declare are validated by its 'undeclared' option spec when it has one (see [Validator options](#validator-options)); otherwise they are validation errors ("Validate: Unvalidated value remains. Undeclared keys: ...") at every nesting level unless VALIDATE_OPT_UNVALIDATED is set. For multi-stage validation, set VALIDATE_OPT_UNVALIDATED on every stage except the last.

$func_opts bit flag options:

 * VALIDATE_OPT_NONE - No options (no spec check).
 * VALIDATE_OPT_CHECK_SPEC - Check $spec before validation (default). Spec errors throw ValidateInvalidSpecException. Use this for development and debugging; omit it in production for speed.
 * VALIDATE_OPT_UNVALIDATED - Allow input keys that a VALIDATE_ARRAY spec does not declare. They stay in $inputs. Has no effect on a VALIDATE_ARRAY with an 'undeclared' option, which validates those keys.
 * VALIDATE_OPT_KEEP_INPUTS - Keep $inputs as is (validate() works on a copy, so no unset, no "filter" result and no substituted "default" reaches the caller). validate() unsets validated values from $inputs by default.
 * VALIDATE_OPT_RAISE_ERROR - Also raise a PHP error (trigger_error()) per failure at the level set by validate_set_error_level() (default E_USER_WARNING). Use this for debugging.
 * VALIDATE_OPT_ERROR_PARAM - Key stored errors by the parameter name only (default).
 * VALIDATE_OPT_ERROR_FULL - Store errors as an array corresponding to $inputs.
 * VALIDATE_OPT_ERROR_SQUASH - Key stored errors by the squashed parameter path. e.g. ROOT=>array1=>array2=>param.
 * VALIDATE_OPT_DISABLE_EXCEPTION - Do not throw; validate() returns null on failure. Use this for business logic (form) validation.
 * VALIDATE_OPT_LOG_ERROR - Log errors by logger function set by validate_set_logger_function().

Only one of VALIDATE_OPT_ERROR_PARAM / _FULL / _SQUASH may be set.

Exceptions (all extend InvalidArgumentException, so existing handlers keep working):

 * InvalidArgumentException - Bad argument: $ctx is not a Validate, $specs is not an array, $func_opts has unknown bits or more than one error mode.
 * ValidateInvalidSpecException - VALIDATE_OPT_CHECK_SPEC found spec errors. getContext() returns the Validate object holding every spec error (validate_get_system_errors()).
 * ValidateInvalidValueException - An input failed validation (unless VALIDATE_OPT_DISABLE_EXCEPTION). getError() returns the structured error record.


### validate_get_status()

Get validation status.

```php
bool validate_get_status(Validate $ctx)
```

Parameters:

 * $ctx - Validate object.

Description:

Returns TRUE when the most recent validate() call on $ctx succeeded, FALSE otherwise, and NULL before the first call. Recorded errors accumulate across calls on the same context, but the status does not: check it after each call (or validate everything with one spec).


### validate_set_error_level()

Set the PHP error level used by VALIDATE_OPT_RAISE_ERROR. i.e. Not an exception.

```php
void validate_set_error_level(Validate $ctx, int $error_level)
```

Parameters:

 * $ctx - Validate object
 * $error_level - E_USER_WARNING (default), E_USER_NOTICE or E_USER_ERROR. Any other value throws InvalidArgumentException.

Description:

Set PHP error level for the VALIDATE_OPT_RAISE_ERROR function option. Note: PHP 8.4+ deprecates passing E_USER_ERROR to trigger_error(); use exceptions to abort instead.



### validate_error()

Record a user error. The current value fails validation: unless VALIDATE_OPT_DISABLE_EXCEPTION is set, ValidateInvalidValueException is thrown.

```php
void validate_error(Validate $ctx, string $error_message)
```

Parameters:

 * $ctx - Validate object
 * $error_message - Error message string

Description:

validate_error() is intended to be used in callbacks.


### validate_warning()

Record a user warning. It does not fail validation.

```php
void validate_warning(Validate $ctx, string $error_message)
```

Parameters:

 * $ctx - Validate object
 * $error_message - Error message string

Description:

validate_warning() is intended to be used in callbacks.


### validate_notice()

Record a user notice. It does not fail validation.

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

validate_get_user_errors() returns user error messages set by user. e.g. validate_error() or 'error_message' validator option.

### validate_set_logger_function()

Set error logging function.

```php
void validate_set_logger_function(Validate $ctx, callable $logger_func)
```

Parameters:

 * $ctx - Validate object
 * $logger_func - function (Validate $ctx, array $error): void. $error is the structured error record (same shape as validate_get_system_errors() entries).

Description:

By default, Validate PHP does not log validation errors. Validation error logging is strongly recommended. The logger is called only with the VALIDATE_OPT_LOG_ERROR function option; without a registered logger that option raises an E_USER_WARNING per error.


### validate_spec()

Validate validation spec array.

```php
bool validate_spec(array $specs [, &$unvalidated = null [, &$ctx = null]])
```

Parameters:

 * $specs - Validation spec array
 * $unvalidated - Optional output. A copy of $specs with every sub-spec that checked clean removed, i.e. what remains are the broken parts.
 * $ctx - Optional Validate object that receives spec errors/warnings/notices (read them with validate_get_system_errors($ctx)).

Return Value:

 * Returns TRUE when success, FALSE otherwise. Warnings and notices do not make it FALSE. A non-array $specs throws InvalidArgumentException.

Description:

validate() checks $spec by default (VALIDATE_OPT_CHECK_SPEC) with a separate context and throws ValidateInvalidSpecException when this returns FALSE.

## Validation SPEC

Validation SPEC format:

```php
$spec = [
    VALIDATE_ID => <int validator id>,
    VALIDATE_FLAGS => <int bit flags for this validator>,
    VALIDATE_OPTIONS => <array of this validator options>,
    // VALIDATE_ARRAY: VALIDATE_PARAMS => [key => spec, ...]
    // VALIDATE_MULTI: VALIDATE_SPECS  => [spec, ...]
];
```

SPEC array is processed recursively. Therefore any number of values can be validated
at once.

Constants:

 * VALIDATE_ID - 0
 * VALIDATE_FLAGS - 1
 * VALIDATE_OPTIONS - 2
 * VALIDATE_PARAMS - 3 (sub-specs of VALIDATE_ARRAY)
 * VALIDATE_SPECS - 3 (sub-specs of VALIDATE_MULTI)

For VALIDATE_ARRAY, 'min'/'max' bound the number of elements (declared and undeclared keys together) and every input key must be declared in VALIDATE_PARAMS or validated by the 'undeclared' option (see validate()). 'amin'/'amax' are the element-count options of VALIDATE_FLAG_ARRAY (array of scalars).

VALIDATE_ARRAY options are 'min', 'max', 'default' (with VALIDATE_FLAG_UNDEFINED_TO_DEFAULT), 'error_message', 'undeclared' and 'key_callback' (with 'undeclared'); validate_spec() warns about others except '_' prefixed user options.

```php
// Known headers strictly, every other header with a catch-all spec.
$spec = [
    VALIDATE_ARRAY,
    VALIDATE_FLAG_ARRAY_KEY_ALNUM, // Names of undeclared keys: alnum, '-' and '_'.
    ['min' => 1, 'max' => 50, 'undeclared' => $basicTypes['header512']],
    [
        'Host' => $basicTypes['http_host'],
    ]
];
```


Single value validation spec example:

```php
// Spec for string value which has exactly 32 alphabet chars(bytes)
$spec =
[
    VALIDATE_STRING,
    VALIDATE_STRING_ALPHA,
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

Validators return corresponding data type values. To allow and return other data type values, validator options must be specified.

Validator ID may be specified like

```php
$single_value_spec[VALIDATE_ID] = VALIDATE_STRING;
```

 * VALIDATE_NULL - Null
 * VALIDATE_BOOL - Boolean
 * VALIDATE_INT - Integer
 * VALIDATE_FLOAT - Float
 * VALIDATE_STRING - String
 * VALIDATE_REGEXP - Regular Expression
 * VALIDATE_CALLBACK - User callback (string input is validated like VALIDATE_STRING first)
 * VALIDATE_RESOURCE - Resource. e.g. Network connection resource.
 * VALIDATE_ARRAY - Array
 * VALIDATE_OBJECT - Object
 * VALIDATE_MULTI - Multiple Validators as array.

VALIDATE_REJECT, VALIDATE_UNDEFINED and VALIDATE_UNVALIDATED are reserved constants; they are not valid spec IDs.


## General validator flags

Validator flags are bit flag.

Validator flags may be specified like

```php
$single_value_spec[VALIDATE_FLAGS] |= VALIDATE_FLAG_UNDEFINED;
```

 * VALIDATE_FLAG_NONE - No option flags
 * VALIDATE_FLAG_RAW - WARNING: Return RAW value. This could be dangerous. Consider this as debug feature.
 * VALIDATE_FLAG_REJECT - Reject if parameter is defined (an absent parameter is fine).
 * VALIDATE_FLAG_UNDEFINED - Allow an undefined (absent) parameter. The key is left out of the result.
 * VALIDATE_FLAG_UNDEFINED_TO_DEFAULT - Input is undefined: validate the "default" option value instead.
 * VALIDATE_FLAG_EMPTY - Allow empty input. null yields null; '' yields '' for string validators, null for the others ('' when *_AS_STRING flag is set).
 * VALIDATE_FLAG_EMPTY_TO_DEFAULT - Input is empty ('' or null): validate the "default" option value instead.
 * VALIDATE_FLAG_NULL - Accept NULL as valid input (also as an array element or parameter value); yields null. VALIDATE_CALLBACK passes it to the callback.
 * VALIDATE_FLAG_ARRAY - Require array of scalars. e.g. script.php?val[]=1&val[]=2
 * VALIDATE_FLAG_ARRAY_RECURSIVE - Allow nested array of scalars.
 * VALIDATE_FLAG_ARRAY_KEY_ALNUM - Allow alnum + '-' + '_' keys (max 64 chars) for VALIDATE_FLAG_ARRAY elements, or for the keys a VALIDATE_ARRAY validates with its 'undeclared' option. By default, keys must be ints or strings of digits, '-' and '_' (max 32 chars). Use "key_callback" option for custom key validation.
 * VALIDATE_FLAG_WARNING - WARNING: Dangerous option! Record a validation failure as a system warning. i.e. Do not raise EXCEPTION / ERROR at all. The status is still FALSE.
 * VALIDATE_FLAG_NOTICE - WARNING: Dangerous option! Record a validation failure as a system notice. i.e. Do not raise EXCEPTION / ERROR at all. The status is still FALSE.
 * VALIDATE_FLAG_PASSTHRU - WARNING: Dangerous option! Drop a validation failure silently: nothing is recorded, raised, thrown or logged and the status is unchanged. The invalid value is left out of the result.

For VALIDATE_ARRAY specs only VALIDATE_FLAG_REJECT, VALIDATE_FLAG_UNDEFINED, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT, VALIDATE_FLAG_WARNING, VALIDATE_FLAG_NOTICE, VALIDATE_FLAG_PASSTHRU and, with the 'undeclared' option, VALIDATE_FLAG_ARRAY_KEY_ALNUM have an effect (validate_spec() warns about others).


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

 * VALIDATE_INT_AS_STRING - Validate as int string, i.e. no int conversion (values beyond PHP_INT_MAX keep their precision). The result is always a string, also for int input.
 * VALIDATE_INT_BIT - TODO Implement this.
 * VALIDATE_INT_OCTAL - TODO Implement this.
 * VALIDATE_INT_HEX - TODO Implement this.
 * VALIDATE_INT_POSITIVE_SIGN - Allow "+" prefix.
 * VALIDATE_INT_NEGATIVE_SIGN - Allow "-" prefix.

Integer strings must be "0" or digits without leading zeros, with an optional sign where allowed; "-0" and a lone sign are rejected (also with VALIDATE_INT_AS_STRING).

### VALIDATE_FLOAT flags

 * VALIDATE_FLOAT_AS_STRING - Accept numeric string (or int) input only; native float input is rejected. Returns the validated value as a canonical numeric string (thousand separators removed, decimal separator normalized to `.`), so the result is always `is_numeric()`.
 * VALIDATE_FLOAT_FRACTION - Require a fractional part in string input. "1.5" passes, "1" and "1." fail. Native float input always satisfies this flag (a PHP float is already a float).
 * VALIDATE_FLOAT_THOUSAND - Allow thousand separators in the integer part. Separator char comes from the `thousand` option (default `,`).
 * VALIDATE_FLOAT_SCIENTIFIC - Allow scientific notation. e.g. 1.23e10
 * VALIDATE_FLOAT_POSITIVE_SIGN - Allow "+" prefix.
 * VALIDATE_FLOAT_NEGATIVE_SIGN - Allow "-" prefix.

### VALIDATE_STRING flags

String validator reject all chars by default. Allowed chars must be specified explicitly.

 * VALIDATE_STRING_SPACE - Allow space ' '.
 * VALIDATE_STRING_DIGIT - Allow 0-9
 * VALIDATE_STRING_TAB - Allow horizontal TAB.
 * VALIDATE_STRING_LF - Allow linefeed.
 * VALIDATE_STRING_CR - Allow carriage return.
 * VALIDATE_STRING_CRLF_MIXED - WARNING: This option allows broken CR/LF sequence.
 * VALIDATE_STRING_CRLF - Same as (VALIDATE_STRING_LF | VALIDATE_STRING_CR), and ensures correct CR/LF sequence.
 * VALIDATE_STRING_LOWER_ALPHA - Allow lower alphabets.
 * VALIDATE_STRING_UPPER_ALPHA - Allow upper(capital) alphabets.
 * VALIDATE_STRING_ALPHA - Same as (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA)
 * VALIDATE_STRING_ALNUM - Same as (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT)
 * VALIDATE_STRING_SYMBOL - Allow symbol chars, but not control chars. WARNING: Dangerous option
 * VALIDATE_STRING_MB - Allow MultiByte chars. The encoding is checked ("encoding" option, default UTF-8); for UTF-8 the RFC 3454 table C code points (non-ASCII spaces/controls, private use, non-characters, ...) are rejected unless allowed.
 * VALIDATE_STRING_BINARY - Allow anything. WARNING: Dangerous option
 * VALIDATE_STRING_RFC3454_C - Allow the RFC 3454 table C code points. ASCII code CNTRL is handled by above flags. e.g. VALIDATE_STRING_LF. The "unicode" option still applies.
 * VALIDATE_STRING_RFC3454_D - Reserved. Currently has no effect.

### VALIDATE_CALLBACK flags

String inputs are validated as string at first, then callback check is applied. Other input types reach the callback unchecked.

Callback validator accepts flags that are accepted by string validator. Constants are defined for API consistency.

 * VALIDATE_CALLBACK_SPACE - VALIDATE_STRING_SPACE
 * VALIDATE_CALLBACK_DIGIT - VALIDATE_STRING_DIGIT
 * VALIDATE_CALLBACK_TAB - VALIDATE_STRING_TAB
 * VALIDATE_CALLBACK_LF - VALIDATE_STRING_LF
 * VALIDATE_CALLBACK_CR - VALIDATE_STRING_CR
 * VALIDATE_CALLBACK_CRLF_MIXED - VALIDATE_STRING_CRLF_MIXED
 * VALIDATE_CALLBACK_CRLF - VALIDATE_STRING_LF | VALIDATE_STRING_CR
 * VALIDATE_CALLBACK_LOWER_ALPHA - VALIDATE_STRING_LOWER_ALPHA
 * VALIDATE_CALLBACK_UPPER_ALPHA - VALIDATE_STRING_UPPER_ALPHA
 * VALIDATE_CALLBACK_ALPHA - (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA)
 * VALIDATE_CALLBACK_ALNUM - (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT)
 * VALIDATE_CALLBACK_SYMBOL - VALIDATE_STRING_SYMBOL
 * VALIDATE_CALLBACK_MB - VALIDATE_STRING_MB
 * VALIDATE_CALLBACK_BINARY - VALIDATE_STRING_BINARY
 * VALIDATE_CALLBACK_RFC3454_C - VALIDATE_STRING_RFC3454_C
 * VALIDATE_CALLBACK_RFC3454_D  - VALIDATE_STRING_RFC3454_D (reserved, no effect)

### VALIDATE_REGEXP flags

Inputs are validated as string at first, then regexp check is applied.

Regex validator accepts flags that are accepted by string validator. Constants are defined for API consistency.

 * VALIDATE_REGEXP_SPACE - VALIDATE_STRING_SPACE
 * VALIDATE_REGEXP_DIGIT - VALIDATE_STRING_DIGIT
 * VALIDATE_REGEXP_TAB - VALIDATE_STRING_TAB
 * VALIDATE_REGEXP_LF - VALIDATE_STRING_LF
 * VALIDATE_REGEXP_CR - VALIDATE_STRING_CR
 * VALIDATE_REGEXP_CRLF_MIXED - VALIDATE_STRING_CRLF_MIXED
 * VALIDATE_REGEXP_CRLF - VALIDATE_STRING_LF | VALIDATE_STRING_CR
 * VALIDATE_REGEXP_LOWER_ALPHA - VALIDATE_STRING_LOWER_ALPHA
 * VALIDATE_REGEXP_UPPER_ALPHA - VALIDATE_STRING_UPPER_ALPHA
 * VALIDATE_REGEXP_ALPHA - (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA)
 * VALIDATE_REGEXP_ALNUM - (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT)
 * VALIDATE_REGEXP_SYMBOL - VALIDATE_STRING_SYMBOL
 * VALIDATE_REGEXP_MB - VALIDATE_STRING_MB
 * VALIDATE_REGEXP_BINARY - VALIDATE_STRING_BINARY
 * VALIDATE_REGEXP_RFC3454_C - VALIDATE_STRING_RFC3454_C
 * VALIDATE_REGEXP_RFC3454_D  - VALIDATE_STRING_RFC3454_D (reserved, no effect)

With VALIDATE_FLAG_EMPTY an empty string is accepted without applying the pattern.

### VALIDATE_MULTI flags

This is not a type validator, but applies multiple validators for a value.

 * VALIDATE_MULTI_AND - Validate a value by multiple specs with AND condition. i.e All validations must success. The result of the last spec is returned.
 * VALIDATE_MULTI_OR - Validate a value by multiple specs with OR condition. i.e One of validation must success. Alternatives are tried in order; a failing alternative records, logs, raises and throws nothing (only "VALIDATE_MULTI: All of specs are failed." is reported when none passes), and validate_error() inside an alternative fails it.

## Validator options

Validator options can be strings, integers, arrays, or callables depending on the option.

Validator options are specified like
 $single_value_spec[VALIDATE_OPTIONS]['opt_name'] = opt_value;

 * 'min' - Min value for numeric type. Min bytes for string.
 * 'max' - Max value for numeric type. Max bytes for string.
 * 'amin' - Minimum number of elements in an array of scalars (VALIDATE_FLAG_ARRAY), per nesting level.
 * 'amax' - Maximum number of elements in an array of scalars (VALIDATE_FLAG_ARRAY), per nesting level.
 * 'alimit' - Maximum total number of elements of an array of scalars, counted across all nesting levels (VALIDATE_FLAG_ARRAY_RECURSIVE). Default: 'amax'. Must be an int not smaller than 'amax'.
 * 'key_callback' - Array key validation callback function for VALIDATE_FLAG_ARRAY elements, or for the keys a VALIDATE_ARRAY validates with 'undeclared'. By default keys must be ints or strings of digits, '-' and '_'. VALIDATE_FLAG_ARRAY_KEY_ALNUM may be used for less strict array key validation.
 * 'undeclared' - VALIDATE_ARRAY only. Spec applied to every input key that VALIDATE_PARAMS does not declare, e.g. a catch-all for unknown HTTP headers. The keys themselves are checked like VALIDATE_FLAG_ARRAY keys (VALIDATE_FLAG_ARRAY_KEY_ALNUM of the VALIDATE_ARRAY spec or 'key_callback'; by default only ints and strings of digits, '-' and '_'). Validated values are added to the result after the declared ones and removed from $inputs; 'min'/'max' of the VALIDATE_ARRAY bound the total count.
 * 'INF' - Float validator option allows INF as valid value.
 * '-INF' - Float validator option allows -INF as valid value.
 * 'decimal' - Float decimal separator (single char). Default `.`. Must not be a digit, `+`, `-`, `e` or `E` (those would corrupt parsing); such specs are rejected.
 * 'thousand' - Float thousand separator (single char). Default `,`. Only consulted when `VALIDATE_FLOAT_THOUSAND` flag is set; must differ from `decimal` and has the same character restrictions as 'decimal'.
 * 'length' - Maximum chars(bytes) for float value strings. Float string could be extremely long. 'max' is used for Maximum value. Default to 32 bytes. Note: due to a bug, this limit was not enforced before the float fraction/decimal implementation — specs that accept float strings longer than 32 bytes must now set 'length' explicitly.
 * 'encoding' - Char encoding for strings with the _MB flag (htmlspecialchars() encodings). Default UTF-8. The RFC 3454 checks and the 'unicode' option apply to UTF-8 only.
 * 'ascii' - Char list for additionally allowed chars in strings (7-bit ASCII only).
 * 'unicode' - [code point => bool] overrides for UTF-8 strings with the _MB flag: true allows, false rejects a code point (0x80-0x10FFFF).
 * 'values' - Set of valid values for string and integer. i.e. If an integer must be 1 or 2, 'values' should be [1=>true,2=>true]; Replaces 'min'/'max'. Integer strings ("1") are accepted for integer values.
 * 'regexp' - Regular expression for VALIDATE_REGEXP validator.
 * 'resource' - Required resource type name for VALIDATE_RESOURCE (get_resource_type()).
 * 'class' - Optional class/interface name for VALIDATE_OBJECT (instanceof check).
 * 'default' - Default values. Default values are validated by validators also. Must be an array with VALIDATE_FLAG_ARRAY and an object for VALIDATE_OBJECT; not allowed for VALIDATE_RESOURCE.
 * 'filter' - Filter function which is applied before validation.
 * 'callback' - Callback function for VALIDATE_CALLBACK validator; method name for VALIDATE_OBJECT (called as $value->callback($ctx), must return true).
 * 'error_message' - User defined error message, stored as a user error when the value fails. "{{option}}" placeholders expand to option values, e.g. "{{min}}".

Option keys starting with '_' are reserved for user data and ignored by validate_spec().

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
$vtrim = function ($ctx, $input, &$error) {
    assert($ctx instanceof Validate);
    if (!is_scalar($input)) {
        $error = 'HTTP Header Filter error: HTTP header must be string.';
        return null;
    }
    return trim((string)$input, " \t"); // OWS is SP / HTAB only; CR, LF, NUL stay and are rejected.
};

$basicTypes['header'] = [
    VALIDATE_STRING,
    VALIDATE_STRING_SPACE | VALIDATE_STRING_TAB | VALIDATE_STRING_SYMBOL | VALIDATE_STRING_ALNUM,
    [
        'min' => 0, 'max' => 0,
        'filter' => $vtrim,
    ]
];
```



### 'key_callback' for array keys

```php
bool key_callback(Validate $ctx, mixed $key)
```

Parameters:

 * $ctx - Validate object. Don't modify this.
 * $key - Int or string array key value.

Return Value:

 * Return TRUE for valid key, FALSE otherwise.


Description:

'key_callback' is for array key validations for array of scalars (VALIDATE_FLAG_ARRAY) and for the keys a VALIDATE_ARRAY validates with its 'undeclared' option. It replaces the built-in key checks:

 * default - int keys, or strings of digits, '-' and '_' (max 32 chars).
 * VALIDATE_FLAG_ARRAY_KEY_ALNUM - strings of alnum, '-' and '_' (max 64 chars). This flag has no effect when 'key_callback' is set.

Example:

```php
// Allow lower-case snake_case keys up to 32 chars.
$spec[VALIDATE_OPTIONS]['key_callback'] = function ($ctx, $key) {
    return is_string($key) && preg_match('/\A[a-z][a-z0-9_]{0,31}\z/', $key) === 1;
};
```

### 'callback' for VALIDATE_CALLBACK validator

```php
bool callback(Validate $ctx, mixed &$result, mixed $input)
```

Parameters:

* $ctx - Validate object. Don't modify this.
* $result - Validated value.
* $input - Input value.

Return Value:

* TRUE for successful validation, FALSE otherwise.

Description:

Assign the validated (possibly normalized) value to $result; it is pre-set to the string-validated value for string input. Calling validate_error() fails the value even when the callback returns TRUE.

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

## Tips

* "Validate PHP" has OO API, but I recommend procedural API.
* If you would like to use "Validate PHP" for logic validation that you don't want any error/exception, simply use **VALIDATE_OPT_DISABLE_EXCEPTION** function option.
* You would like to define central input parameter specifications similar to [this](https://github.com/yohgaki/validate-php-scr/blob/master/src/lib/basic_types.php). The definition file is an example definition for portable integer value handling also.

```php
<?php
require_once __DIR__.'/../validate_func.php';
// Procedural
// mixed validate(Validate $ctx, mixed &$input, array $spec [, int $flags])
// $ctx is automatically initialized.
$result = validate($ctx, $input, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
$error_message = validate_get_user_errors($ctx); // Error messages defined by you.

// OO
$ctx = new Validate;
$result = $ctx->validate($input, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
// This could be one liner if you don't need $ctx object.
$result = (new Validate)->validate($input, $spec, VALIDATE_OPT_DISABLE_EXCEPTION);
?>
```

* You can log validation errors and ignore exceptions/errors. i.e.

```php
<?php
// Procedural
$ctx = validate_init();
// Register logger simply logs errors.
validate_set_logger_function($ctx, $my_logger);
// validate() works without disturbing application.
$result = validate($ctx, $input, $spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_LOG_ERROR);
// Examine logged errors.

// OO
$ctx = new Validate;
$ctx->setLoggerFunction($my_logger);
$result = $ctx->validate($input, $spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_LOG_ERROR);
?>
```

* You are better to define rock solid whitelist input validation rule for single/individual variable, then combine them for array inputs such as GET/POST/COOKIE/FILES/HTTP headers.
* You can examine **unvalidated** inputs if you allow them.

```php
$result = validate($ctx, $input, $spec, VALIDATE_OPT_DISABLE_EXCEPTION | VALIDATE_OPT_UNVALIDATED);

// $input contains unvalidated values.
var_dump($input);
// You would validate them with more generic/loose validator.
```

* To validate such keys in the same call instead, give the VALIDATE_ARRAY an 'undeclared' spec (see [Validator options](#validator-options)).

