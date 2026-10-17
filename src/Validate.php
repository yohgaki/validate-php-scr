<?php
//namespace Validate;

/**
 * Simple yet flexible, powerful and reasonably fast input validator that
 * provides a basic validation framework for everything from simple to
 * complex validations.
 *
 * Namespaces are intentionally NOT used. This class is designed to be
 * source-compatible with the planned "validate" PHP C extension; the
 * structure, naming, and procedural-flavored shape make the future port
 * trivial. As a consequence:
 *   - The code is intentionally not OO-optimized. Validate is a single
 *     class because PHP needs one, but the C version will keep the same
 *     state in a struct and skip the class entirely.
 *   - Please do not submit OO-style refactor PRs.
 *
 * ============================================================
 * Callback signatures used by spec options
 * ============================================================
 *
 *   VALIDATE_CALLBACK "callback" option
 *   ----------------------------------
 *   Run user PHP code to validate a value. For string input the engine has
 *   already enforced flag-based character whitelisting and min/max before
 *   the callback runs; any other input type (int, array, null with
 *   VALIDATE_FLAG_NULL, ...) reaches the callback unchecked.
 *
 *     function (Validate $ctx, mixed &$result, mixed $input): bool
 *       $ctx    - validation context (created by validate_init() / validate()).
 *       $result - assign here to publish the validated (possibly normalized) value.
 *       $input  - the input value (after the "filter" option).
 *     Returns true on success, false on failure. Calling validate_error()
 *     also fails the value; validate_warning() / validate_notice() do not.
 *     Inside VALIDATE_MULTI_OR alternatives messages are not recorded.
 *
 *     A callback MAY call validate() recursively — pass the same $ctx.
 *
 *
 *   "filter" option
 *   ---------------
 *   Pre-process the input before the validator examines it (e.g. trim() a
 *   string). VALIDATE_FLAG_REJECT, VALIDATE_FLAG_UNDEFINED*,
 *   VALIDATE_FLAG_EMPTY_TO_DEFAULT and the NULL check are already applied
 *   when the filter runs; VALIDATE_FLAG_EMPTY, 'min' and 'max' apply to the
 *   filtered value.
 *
 *     function (Validate $ctx, mixed $input, string &$error): mixed
 *       $ctx   - validation context.
 *       $input - the input value (read-only here; return the modified value).
 *       $error - assign a string to flag a filter failure; leave null otherwise.
 *     Returns the (possibly modified) value. Available on every validator.
 *
 *
 *   "key_callback" option
 *   ---------------------
 *   Validate the keys of an array-of-scalars input (VALIDATE_FLAG_ARRAY), or
 *   the keys a VALIDATE_ARRAY validates with its "undeclared" option.
 *   Untrusted input arrays can carry dangerous characters in their keys, so
 *   a key check is often as important as a value check.
 *
 *     function (Validate $ctx, mixed $key): bool
 *       $ctx - validation context.
 *       $key - the array key under inspection.
 *     Returns true to accept the key, false to reject the element.
 *
 *
 * PHP Version 8.0 or higher.
 *
 * @category Validation
 * @package  Validate
 * @author   Yasuo Ohgaki <yohgaki@ohgaki.net>
 * @license  MIT https://github.com/yohgaki/validate-php-scr/blob/master/LICENSE
 * @link     https://github.com/yohgaki/validate-php-scr Validate PHP Script Version
 */

require_once __DIR__.'/validate_defs.php';


/**
 * Exception thrown by validate() when VALIDATE_OPT_CHECK_SPEC finds a
 * structural problem in the spec (wrong type ID, missing min/max, malformed
 * sub-spec, ...). Subclass of InvalidArgumentException so existing handlers
 * continue to work.
 *
 * getContext() returns the Validate context that checked the spec; read the
 * full list with validate_get_system_errors($e->getContext()).
 */
class ValidateInvalidSpecException extends InvalidArgumentException
{
    private $specContext;

    public function __construct($message, $specContext = null)
    {
        parent::__construct($message);
        $this->specContext = $specContext;
    }

    /**
     * @return Validate|null Context holding every spec error/warning/notice.
     */
    public function getContext()
    {
        return $this->specContext;
    }
}

/**
 * Exception thrown when validate() rejects an input value at runtime. Raised
 * unless VALIDATE_OPT_DISABLE_EXCEPTION is set on the call. Subclass of
 * InvalidArgumentException so existing handlers continue to work.
 *
 * getError() returns the structured error record (the same shape as the
 * entries of validate_get_system_errors()).
 */
class ValidateInvalidValueException extends InvalidArgumentException
{
    private $error;

    public function __construct($message, $error = null)
    {
        parent::__construct($message);
        $this->error = $error;
    }

    /**
     * @return array|null Structured error record.
     */
    public function getError()
    {
        return $this->error;
    }
}

/**
 * Validate engine — implements every rule that the procedural API delegates to.
 *
 * Internal layout deliberately mirrors what the planned C extension will use:
 * the same private fields, the same recursion shape, the same option keys.
 * Application code should prefer the procedural API (validate(),
 * validate_spec(), validate_error(), ...) and treat this class as opaque.
 */
class Validate
{
    // Internal constants. They are private on master; PHP 7.0 has no class
    // constant visibility, so do not use them from outside this class.

    /* checkParamPresence() results. */
    const PRESENCE_VALIDATE = 1; // Validate the input value.
    const PRESENCE_DEFAULT = 2;  // Validate the "default" option instead.
    const PRESENCE_SKIP = 3;     // Nothing to validate; not an error.
    const PRESENCE_FAIL = 4;     // Error already reported.

    /* Max entries of $checkedSpecs (oldest dropped first). */
    const CHECKED_SPECS_MAX = 64;

    /**
     * Specs that passed the VALIDATE_OPT_CHECK_SPEC check in this process
     * (one PHP request on per-request SAPIs), so validate() can skip checking
     * an identical spec again — the script version's stand-in for the C
     * extension's compiled specs. Oldest first; each entry is [spec, trusted]:
     *
     *  - untrusted: the caller's array as first checked. It may contain PHP
     *    references whose targets can change later, so a match only marks
     *    the spec as reused: it is checked again, then stored trusted.
     *  - trusted: a reference-free copy (copySpecValues()), which nothing
     *    outside can modify. A spec identical (===) to it, i.e. with the same
     *    values now and the same object instances, skips the check.
     *
     * Only successful checks are stored. A spec check is a pure function of
     * the spec except for is_callable(), which never turns false once true.
     *
     * @var array
     */
    private static $checkedSpecs = [];

    /**
     * Stack of element names representing the path from the root to the value
     * currently being validated (e.g. ['ROOT', 'POST', 'address', 'zip']).
     * Pushed before recursion, popped after. Used to build error paths and
     * to key error storage under VALIDATE_OPT_ERROR_FULL/SQUASH.
     */
    private $currentElem;

    /**
     * Current per-call context. $context is the active Validate instance
     * (this == $context except during nested calls). $context_vars carries
     * scratch state across helpers within a single validate() invocation —
     * the current spec, the func_opts bitmask, and so on.
     */
    private $context;
    private $context_vars;

    /**
     * PHP error level used when VALIDATE_OPT_RAISE_ERROR is set.
     * Defaults to E_USER_WARNING — adjust via setErrorLevel(). (PHP 8.4
     * deprecated passing E_USER_ERROR to trigger_error().)
     */
    private $error_level;

    /*
     * Error buckets — system vs user.
     *
     * System messages come from the engine itself (broken types, length
     * violations, illegal characters); they are meant for logging, not for
     * showing to end users.
     *
     * User messages come from spec 'error_message' options or
     * validate_error/_warning/_notice() calls in user callbacks; they are
     * safe to show in form UIs.
     */

    /** @var array $errors      System-side error messages. */
    private $errors;
    /** @var array $warnings    System-side warning messages. */
    private $warnings;
    /** @var array $notices     System-side notice messages. */
    private $notices;

    /** @var array $userErrors  User-facing errors (spec 'error_message' / validate_error()). */
    private $userErrors;
    /** @var array $userWarnings User-facing warnings (validate_warning()). */
    private $userWarnings;
    /** @var array $userNotices User-facing notices (validate_notice()). */
    private $userNotices;

    /**
     * Running validation result. Holds every value validated so far, so the
     * caller can inspect partial results even when later fields fail.
     *
     * @var mixed
     */
    private $validated;

    /**
     * Overall pass/fail status of the most recent validation call.
     * null = validate() has not been called yet.
     *
     * @var bool|null
     */
    private $status;

    /**
     * Speculative-evaluation depth. While > 0 (VALIDATE_MULTI_OR alternatives
     * are being tried) dispatchError() only records that a failure happened:
     * nothing is stored, logged, raised or thrown, and $status is untouched.
     * $silentFailed is the per-alternative failure marker.
     *
     * @var int
     */
    private $silent = 0;
    private $silentFailed = false;

    /**
     * Optional user logger callback, registered via setLoggerFunction().
     * Invoked once per error when VALIDATE_OPT_LOG_ERROR is set on the
     * validate() call. Falls back to trigger_error() when unset.
     *
     * @var callable|null
     */
    private $loggerFunction = null;


    /************** public methods ****************/

    /**
     * Validate constructor.
     *
     * @param string $root_name Label shown for the top-level value in error
     *                          reports (e.g. "ROOT", "POST", "request").
     *
     * @throws RuntimeException The mbstring extension is not loaded, or
     *                          mbstring.func_overload overloads string functions.
     */
    public function __construct($root_name = 'ROOT')
    {
        assert(is_string($root_name));

        // Character encodings are validated with mbstring.
        if (!function_exists('mb_check_encoding')) {
            throw new RuntimeException('Validate requires the mbstring extension.');
        }
        // With overloaded string functions strlen() counts chars, not bytes:
        // byte lengths and the char checks would be wrong.
        if (((int)ini_get('mbstring.func_overload')) & 2) {
            throw new RuntimeException('Validate does not work with mbstring.func_overload string functions (2).');
        }

        $this->status = null;
        $this->validated = null;
        $this->error_level = E_USER_WARNING;
        $this->errors = array();
        $this->warnings = array();
        $this->notices = array();
        $this->userErrors = array();
        $this->userWarnings = array();
        $this->userNotices = array();

        // Seed the path stack with the user-supplied root label; nested
        // values are pushed/popped during recursion.
        $this->currentElem = array();
        array_push($this->currentElem, $root_name);

        $this->setContext(
            $this,       // Self-register as the active context.
            $root_name,  // Param name for the root.
            null,        // Defined-ness of the root is not yet known.
            null,        // Input is not yet known.
            null,        // Spec is not yet known.
            null         // func_opts is not yet known.
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
     * Return the name of the parameter currently being validated — the leaf
     * of the recursion path stack ($currentElem). Numeric array indices come
     * back as int, named keys as string.
     *
     * @return string|int
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
            'value'     => $orig_value, // Value that caused an error; see setContextErrorValue()
            'defined'   => $isset,
            'spec'      => $spec ?? [VALIDATE_INVALID, VALIDATE_FLAG_NONE, []],
            'func_opts' => $func_opts ?? VALIDATE_OPT_NONE,
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
     * Validate inputs against a spec.
     *
     * $inputs is passed by reference: each key that passes validation is
     * unset by the engine, so on return $inputs contains only the leftover
     * (unvalidated) input. Pass VALIDATE_OPT_KEEP_INPUTS to keep it intact
     * (the engine then works on a private copy, so neither unset(), "filter"
     * results nor substituted "default" values reach the caller's array).
     *
     * Input keys that a VALIDATE_ARRAY spec does not declare are validated by
     * its "undeclared" option spec when it has one; otherwise they are
     * reported as "Unvalidated value remains." unless VALIDATE_OPT_UNVALIDATED
     * is set.
     *
     * With VALIDATE_OPT_CHECK_SPEC (default) the spec is checked first, unless
     * an identical spec passed before and is remembered ($checkedSpecs).
     *
     * On failure either a ValidateInvalidValueException is thrown (default)
     * or null is returned (VALIDATE_OPT_DISABLE_EXCEPTION). Partial validation
     * results remain reachable via getValidated().
     *
     * @param mixed $inputs    Scalar or array of inputs.
     * @param array $specs     Spec array. See validate_spec() for format.
     * @param int   $func_opts Bitmask of VALIDATE_OPT_* function options.
     *
     * @return mixed Validated value(s) on success, null on failure when
     *               exceptions are disabled.
     *
     * @throws InvalidArgumentException     Bad $specs / $func_opts argument.
     * @throws ValidateInvalidSpecException VALIDATE_OPT_CHECK_SPEC found spec errors.
     * @throws ValidateInvalidValueException Validation failed (unless disabled).
     */
    public function validate(&$inputs, $specs, $func_opts = VALIDATE_OPT_CHECK_SPEC)
    {
        if (!is_array($specs)) {
            throw new InvalidArgumentException('Spec must be array.');
        }
        if (!is_int($func_opts) || ($func_opts & VALIDATE_OPT_UPPER) || $func_opts < 0) {
            throw new InvalidArgumentException('Function option must be int made of VALIDATE_OPT_* bits.');
        }
        $error_modes = $func_opts & (VALIDATE_OPT_ERROR_FULL | VALIDATE_OPT_ERROR_PARAM | VALIDATE_OPT_ERROR_SQUASH);
        if ($error_modes & ($error_modes - 1)) {
            throw new InvalidArgumentException(
                'VALIDATE_OPT_ERROR_FULL, VALIDATE_OPT_ERROR_PARAM and VALIDATE_OPT_ERROR_SQUASH are mutually exclusive.'
            );
        }
        if ($func_opts & VALIDATE_OPT_CHECK_SPEC) {
            $checked = self::findCheckedSpec($specs);
            if ($checked === null || !self::$checkedSpecs[$checked][1]) {
                // Check with a separate context so spec problems never mix
                // with value errors recorded in $this.
                $specCtx = new Validate();
                if (!$specCtx->validateSpec($specs, $unvalidated_spec, $specCtx)) {
                    throw new ValidateInvalidSpecException(self::buildSpecErrorMessage($specCtx), $specCtx);
                }
                self::rememberCheckedSpec($specs, $checked);
            }
        }

        // Successful status for each validate() call.
        // Previous errors are stored $this->errors array when object is used multiple times.
        $this->status = true;
        $this->validated = null;
        $this->silent = 0;
        if ($func_opts & VALIDATE_OPT_KEEP_INPUTS) {
            $work = $inputs; // Never touch the caller's array.
            $ret = $this->runValidation($this->validated, $work, $specs, $func_opts);
        } else {
            $ret = $this->runValidation($this->validated, $inputs, $specs, $func_opts);
        }
        assert(is_bool($ret));

        if ($this->status) {
            return $this->validated;
        }
        return null;
    }


    /**
     * Find the $checkedSpecs entry identical (===) to $spec.
     *
     * @param array $spec Spec passed to validate().
     *
     * @return int|null Index into $checkedSpecs, or null when not found.
     */
    private static function findCheckedSpec($spec)
    {
        assert(is_array($spec));
        foreach (self::$checkedSpecs as $idx => $entry) {
            if ($entry[0] === $spec) {
                return $idx;
            }
        }
        return null;
    }


    /**
     * Record a spec that just passed the spec check.
     *
     * A new spec is stored untrusted as is (no copy cost for a spec used
     * once). A reused one ($idx: its untrusted entry) is replaced by a
     * trusted reference-free copy; if it cannot be copied (nested deeper
     * than any real spec, e.g. a recursive array), it stays untrusted.
     *
     * @param array    $spec Spec that passed the check.
     * @param int|null $idx  Index of its untrusted entry, null if none.
     *
     * @return null
     */
    private static function rememberCheckedSpec($spec, $idx)
    {
        assert(is_array($spec));
        assert(is_null($idx) || !self::$checkedSpecs[$idx][1]);
        if ($idx === null) {
            if (count(self::$checkedSpecs) >= self::CHECKED_SPECS_MAX) {
                array_shift(self::$checkedSpecs);
            }
            self::$checkedSpecs[] = [$spec, false];
            return;
        }
        $copy = self::copySpecValues($spec, 0);
        if ($copy !== null) {
            self::$checkedSpecs[$idx] = [$copy, true];
        }
    }


    /**
     * Deep-copy a spec value, replacing PHP references by their current
     * values. Objects (closures, ...) are kept as the same instances.
     *
     * @param mixed $value Spec or spec element.
     * @param int   $depth Current array nesting depth.
     *
     * @return mixed The copy, or null when arrays nest deeper than 64 levels.
     */
    private static function copySpecValues($value, $depth)
    {
        if (!is_array($value)) {
            return $value;
        }
        if ($depth >= 64) {
            return null;
        }
        $copy = [];
        foreach ($value as $key => $elem) {
            $elem = self::copySpecValues($elem, $depth + 1);
            if ($elem === null && is_array($value[$key])) {
                return null;
            }
            $copy[$key] = $elem;
        }
        return $copy;
    }


    /**
     * Build the exception message for a spec that failed validateSpec().
     *
     * @param Validate $specCtx Context used for the spec check.
     *
     * @return string
     */
    private static function buildSpecErrorMessage($specCtx)
    {
        $err = $specCtx->getSystemErrors();
        $msg = 'Invalid validation spec detected. Fix spec errors first. Errors: '.count($err['error'])
              .' Warnings: '.count($err['warning']).'.';
        if (!empty($err['error'])) {
            $first = reset($err['error']);
            $msg .= ' First error: param: \''.$first['param'].'\' message: \''.$first['message'].'\'.';
        }
        return $msg.' Hint: validate_spec($spec, $unvalidated, $ctx); validate_get_system_errors($ctx);';
    }


    /**
     * Verify the structure of a spec array (without applying it to any input).
     *
     * Run during validate() when VALIDATE_OPT_CHECK_SPEC is set, and exposed
     * for explicit pre-flight checks during development.
     *
     * @param array    $spec        Spec array to verify.
     * @param mixed    $unvalidated Output. Receives sub-specs that could not be checked.
     * @param Validate $ctx         Optional context that receives the spec
     *                              errors (fresh one created when null).
     *
     * @return bool true if the spec is well-formed, false otherwise.
     *
     * @throws InvalidArgumentException Bad $spec / $ctx argument.
     */
    public function validateSpec($spec, &$unvalidated = null, $ctx = null)
    {
        if (!is_array($spec)) {
            throw new InvalidArgumentException('1st parameter must be validation spec array.');
        }
        if (is_null($ctx)) {
            $ctx = new Validate;
        } elseif (!($ctx instanceof Validate)) {
            throw new InvalidArgumentException('3rd parameter must be instance of Validate object.');
        }

        $unvalidated = $spec;
        $ctx->status = true;
        $ctx->checkSpecEntry($unvalidated);
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
     * Return the overall status of the most recent validation pass.
     * Returns null when validate() has not been called yet.
     *
     * @return bool|null
     */
    public function getStatus()
    {
        assert(is_null($this->status) || is_bool($this->status));
        return $this->status;
    }

    /**
     * Return the active per-call context — the Validate instance currently
     * driving the validation pass. Equal to $this except inside nested calls.
     *
     * @return Validate
     */
    public function getContext()
    {
        assert($this->context instanceof Validate);
        return $this->context;
    }


    /**
     * Choose which PHP error level VALIDATE_OPT_RAISE_ERROR uses.
     * Default is E_USER_WARNING. E_USER_ERROR is still accepted, but PHP 8.4+
     * deprecates passing it to trigger_error(); prefer exceptions to abort.
     *
     * @param int $level E_USER_ERROR / E_USER_WARNING / E_USER_NOTICE.
     *
     * @throws InvalidArgumentException For any other level.
     */
    public function setErrorLevel($level)
    {
        if ($level !== E_USER_ERROR && $level !== E_USER_WARNING && $level !== E_USER_NOTICE) {
            throw new InvalidArgumentException('Error level must be E_USER_ERROR, E_USER_WARNING or E_USER_NOTICE.');
        }
        $this->error_level = $level;
    }


    /**
     * Return system-side errors recorded by the engine (broken types,
     * length violations, illegal characters, ...). When the same Validate
     * instance is reused across multiple validate() calls, errors from
     * every call accumulate here.
     *
     * @return array ['error' => [...], 'warning' => [...], 'notice' => [...]]
     */
    public function getSystemErrors()
    {
        return $this->collectErrors(E_ERROR);
    }


    /**
     * Return user-facing messages reported via validate_error/_warning/_notice()
     * or via the spec 'error_message' option. These are safe to surface in
     * form UIs; system errors should stay internal.
     *
     * @return array ['error' => [...], 'warning' => [...], 'notice' => [...]]
     */
    public function getUserErrors()
    {
        return $this->collectErrors(E_USER_ERROR);
    }


    /**
     * Register a logger callback used by VALIDATE_OPT_LOG_ERROR.
     * The callback is invoked once per recorded error during validate();
     * if no logger is registered the engine falls back to trigger_error().
     *
     * Signature: function (Validate $ctx, array $error): void
     *   $error is the full structured error record (message, param, value,
     *   spec, ...) — match the shape of getSystemErrors() entries.
     *
     * @param callable $func Logger callback.
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
     * Record a user-side error. Backs validate_error() — meant to be called
     * from inside a 'callback' validator to report a user-facing message.
     *
     * @param string $message Error message.
     */
    public function error($message)
    {
        assert(is_string($message));
        $this->dispatchError($message, E_USER_ERROR);
    }


    /**
     * Record a user-side warning. Backs validate_warning(). See error().
     *
     * @param string $message Warning message.
     */
    public function warning($message)
    {
        assert(is_string($message));
        $this->dispatchError($message, E_USER_WARNING);
    }


    /**
     * Record a user-side notice. Backs validate_notice(). See error().
     *
     * @param string $message Notice message.
     */
    public function notice($message)
    {
        assert(is_string($message));
        $this->dispatchError($message, E_USER_NOTICE);
    }


    /************** private methods - validator helpers ****************/

    /**
     * Compare two integers safely beyond PHP_INT_MAX.
     *
     * Returns -1, 0, or 1 like the spaceship operator. Used when an int
     * spec is in AS_STRING mode and the numeric values may overflow PHP_INT.
     * Prefers bccomp / GMP when available; otherwise falls back to a hand-
     * rolled string compare so 32-bit and 64-bit builds give identical results.
     *
     * @return int -1 if $a < $b, 0 if equal, 1 if $a > $b.
     */
    private static function compareBigInt($a, $b): int
    {
        if (is_int($a) && is_int($b)) {
            return $a <=> $b;
        }
        if (function_exists('bccomp')) {
            return bccomp((string)$a, (string)$b);
        }
        if (extension_loaded('gmp')) {
            return gmp_cmp((string)$a, (string)$b);
        }
        // Pure PHP fallback for integer string comparison
        $a = (string)$a;
        $b = (string)$b;
        $aneg = ($a !== '' && $a[0] === '-');
        $bneg = ($b !== '' && $b[0] === '-');
        // Normalize magnitudes first so "-0"/"000" compare as plain zero.
        $a = ltrim($aneg ? substr($a, 1) : $a, '0') ?: '0';
        $b = ltrim($bneg ? substr($b, 1) : $b, '0') ?: '0';
        $aneg = $aneg && $a !== '0';
        $bneg = $bneg && $b !== '0';
        if ($aneg !== $bneg) {
            return $aneg ? -1 : 1;
        }
        if ($aneg) {
            $la = strlen($a);
            $lb = strlen($b);
            if ($la !== $lb) {
                return $la < $lb ? 1 : -1;
            }
            return strcmp($b, $a) <=> 0;
        }
        $la = strlen($a);
        $lb = strlen($b);
        if ($la !== $lb) {
            return $la < $lb ? -1 : 1;
        }
        return strcmp($a, $b) <=> 0;
    }


    /**
     * Check scalar.
     *
     * @return bool
     */
    private function assertScalarType($val, $specs, $func_opts)
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
                    $validatorName = $this->getValidatorName($id);
                    $this->reportError(
                        [
                            'message' => $validatorName.': Bool value cannot be treated as valid value for this validator.',
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
        if ($id === VALIDATE_INT && $val instanceof GMP) {
            return true;
        }

        $validatorName = $this->getValidatorName($id);
        $this->reportError(
            [
                'message' => $validatorName.': Array or object parameter is passed for scalar.',
                'value' => $val,
            ],
            $specs,
            $func_opts
        );
        return false;
    }


    /**
     * Look up the method name that implements a validator type.
     *
     * Kept inside a method (rather than a class property) so it doesn't
     * leak into var_dump() output of a Validate instance.
     *
     * @param int $id Validator ID (VALIDATE_INT, VALIDATE_STRING, ...).
     *
     * @return string Method name on this class.
     */
    private function getValidator($id)
    {
        assert(is_int($id));

        static $validators = [
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
            // VALIDATE_MULTI is dispatched by runValidation() (validateMulti()).
        ];
        return $validators[$id];
    }


    /**
     * Map a validator type ID to its human-readable label (used in error
     * messages). Returns false for IDs not in the map so callers can detect
     * "unknown ID" without bailing on a missing-array-key warning.
     *
     * @param int $id Validator ID.
     *
     * @return string|false Label like "VALIDATE_INT", or false when unknown.
     */
    private function getValidatorName($id)
    {
        assert(is_int($id));

        static $map = [
            VALIDATE_NULL => 'VALIDATE_NULL',
            VALIDATE_ARRAY => 'VALIDATE_ARRAY',
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
     * Substitute the spec's 'default' option for empty input.
     *
     * Triggered when VALIDATE_FLAG_EMPTY_TO_DEFAULT is set and the value is
     * '' or null. Records a system error and returns false if the flag is
     * set without a 'default' option (the spec is broken in that case).
     *
     * @return bool true on success or when the flag does not apply; false when the spec is broken.
     */
    private function applyDefaultIfEmpty(&$value, $id, $flags, $options, $func_opts = VALIDATE_OPT_DISABLE_EXCEPTION)
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
            $this->reportError(
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
     * Decide what to do with a declared parameter before per-type validation.
     *
     * Presence is array_key_exists(), so a key holding null is *present* and
     * VALIDATE_FLAG_NULL / VALIDATE_FLAG_EMPTY* apply to it.
     *   - VALIDATE_FLAG_REJECT : present = error, absent = OK (nothing to validate).
     *   - absent + VALIDATE_FLAG_UNDEFINED_TO_DEFAULT : validate the "default" option.
     *   - absent + VALIDATE_FLAG_UNDEFINED : OK; the key is left out of the result.
     *   - absent otherwise : required-parameter error.
     *
     * @param bool  $defined Whether $param exists in the input array.
     * @param mixed $value   Input value (null when absent).
     *
     * @return int PRESENCE_VALIDATE (validate $value), PRESENCE_DEFAULT
     *             (validate the "default" option), PRESENCE_SKIP (nothing to
     *             do, not an error) or PRESENCE_FAIL (error already reported).
     */
    private function checkParamPresence($defined, $value, $id, $flags, $options, $func_opts)
    {
        assert(is_bool($defined));
        assert(is_int($id));
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if ($flags & VALIDATE_FLAG_REJECT) {
            if (!$defined) {
                return self::PRESENCE_SKIP;
            }
            // Doing anything with a rejected value can be harmful.
            // Rejected parameters must simply not be defined.
            $validatorName = $this->getValidatorName($id);
            $this->reportError(
                [
                    'message' => $validatorName.': Rejected by flag.',
                    'value' => $value,
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return self::PRESENCE_FAIL;
        }

        if ($defined) {
            return self::PRESENCE_VALIDATE;
        }
        if ($flags & VALIDATE_FLAG_UNDEFINED_TO_DEFAULT) {
            if (!isset($options['default'])) {
                $this->reportError(
                    [
                        'message' => 'Invalid validation spec: VALIDATE_FLAG_UNDEFINED_TO_DEFAULT without "default" option.',
                        'value' => null,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return self::PRESENCE_FAIL;
            }
            return self::PRESENCE_DEFAULT;
        }
        if ($flags & VALIDATE_FLAG_UNDEFINED) {
            return self::PRESENCE_SKIP;
        }
        $this->reportError(
            [
                'message' => 'Undefined parameter: Required parameter is not defined.',
                'value' => null
            ],
            [$id, $flags, $options],
            $func_opts
        );
        return self::PRESENCE_FAIL;
    }


    /**
     * Recursive core of validate(). Dispatches on the spec's validator type:
     *   - VALIDATE_MULTI  -> validateMulti() (one value vs. multiple specs)
     *   - VALIDATE_ARRAY  -> recurse into sub-specs, walk each declared key,
     *                        then validate undeclared input keys with the
     *                        "undeclared" option spec or report them
     *   - everything else -> validateScalar() for per-type rules
     *
     * Drives the $currentElem path stack so error messages and context
     * always reflect the field currently under validation. Internal — call
     * the procedural validate() / Validate::validate() wrapper instead.
     *
     * @param mixed $validated By-ref output: validated values are written here.
     *                         Native types may be normalized (string ints -> int, etc.).
     * @param mixed $inputs    By-ref input: validated keys are unset (validate()
     *                         passes a private copy under VALIDATE_OPT_KEEP_INPUTS).
     * @param array $specs     Validation spec node ([type, flags, options, sub-specs]).
     * @param int   $func_opts VALIDATE_OPT_* bitmask for this call.
     *
     * @return bool true when this node (and everything below it) is valid.
     */
    private function runValidation(&$validated, &$inputs, $specs, $func_opts)
    {
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
            $this->reportError(
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
        if (!(is_array($inputs) || ($inputs instanceof ArrayAccess && $inputs instanceof Countable))) {
            $this->reportError(
                [
                   'message' => 'VALIDATE_ARRAY: Input value is not array. ',
                   'value' => $inputs
                ],
                $specs,
                $func_opts
            );
            return false;
        }
        $min = $options['min'] ?? 0;
        $max = $options['max'] ?? 0;
        $cnt = count($inputs);
        if ($min > $cnt || $max < $cnt) {
            $this->reportError(
                [
                   'message' => 'VALIDATE_ARRAY: Count out of range. '.
                                'min: '. $min .' max: '. $max . ' count '. $cnt,
                   'value' => null
                ],
                $specs,
                $func_opts
            );
            return false;
        }

        if (!is_array($validated)) {
            $validated = []; // A validated VALIDATE_ARRAY yields an array, even when empty.
        }
        $ok = true;
        foreach ($params as $param => $spec) {
            if (!$this->validateParam($validated, $inputs, $param, $spec, $func_opts)) {
                $ok = false;
            }
        }

        // Input keys the spec does not declare. Whitelist validation means
        // they are errors unless the "undeclared" option spec validates them
        // or the caller explicitly allows leftovers (multi-stage validation).
        if (!is_array($inputs)) {
            return $ok;
        }
        $undeclared = array_diff_key($inputs, $params);
        if (!$undeclared) {
            return $ok;
        }
        if (isset($options['undeclared'])) {
            // Keys are input too: VALIDATE_FLAG_ARRAY_KEY_ALNUM / "key_callback"
            // of this VALIDATE_ARRAY spec decide which undeclared keys may exist.
            $keyValidator = $this->buildKeyValidator($flags, $options);
            foreach (array_keys($undeclared) as $param) {
                if (!$keyValidator($this->context, $param)) {
                    $this->setContext($this, end($this->currentElem), true, $inputs, $specs, $func_opts);
                    $this->reportError(
                        [
                            'message' => 'VALIDATE_ARRAY: Undeclared key has invalid format. '.
                                         'Hint: you may want VALIDATE_FLAG_ARRAY_KEY_ALNUM flag or "key_callback" option.',
                            'value' => 'key:'.$param,
                        ],
                        $specs,
                        $func_opts
                    );
                    $ok = false;
                    continue;
                }
                if (!$this->validateParam($validated, $inputs, $param, $options['undeclared'], $func_opts)) {
                    $ok = false;
                }
            }
            return $ok;
        }
        if (!($func_opts & VALIDATE_OPT_UNVALIDATED)) {
            $this->setContext($this, end($this->currentElem), true, $inputs, $specs, $func_opts);
            $this->reportError(
                [
                    'message' => 'Validate: Unvalidated value remains. Undeclared keys: \''
                                 .join('\', \'', array_keys($undeclared)).'\'',
                    'value' => array_keys($undeclared),
                ],
                $specs,
                $func_opts
            );
            $ok = false;
        }
        return $ok;
    }


    /**
     * Validate one element of a VALIDATE_ARRAY input, $inputs[$param],
     * against $spec: presence (absent / rejected / "default"), then the
     * value. A valid element is removed from $inputs (a nested array only
     * once all of its elements were consumed); a failed scalar result is
     * dropped from $validated.
     *
     * @param array $validated By-ref VALIDATE_ARRAY result; receives $validated[$param].
     * @param mixed $inputs    By-ref VALIDATE_ARRAY input.
     * @param mixed $param     Key in $inputs (declared, or undeclared under "undeclared").
     * @param array $spec      Spec for this element.
     * @param int   $func_opts VALIDATE_OPT_* bitmask for this call.
     *
     * @return bool
     */
    private function validateParam(&$validated, &$inputs, $param, $spec, $func_opts)
    {
        assert(is_array($validated));
        assert(is_int($spec[VALIDATE_ID])); // Validator ID
        assert(is_int($spec[VALIDATE_FLAGS])); // Validator flags
        assert(is_array($spec[VALIDATE_OPTIONS])); // Validator options
        assert($spec[VALIDATE_ID] !== VALIDATE_ARRAY || is_array($spec[VALIDATE_PARAMS])); // In case of Array, must have "parameters".

        $id = $spec[VALIDATE_ID];
        $flags = $spec[VALIDATE_FLAGS];
        $options = $spec[VALIDATE_OPTIONS];
        $defined = is_array($inputs) ? array_key_exists($param, $inputs) : isset($inputs[$param]);

        array_push($this->currentElem, $param);
        $this->setContext(
            $this,
            $param,
            $defined,
            $defined ? $inputs[$param] : null,
            $spec,
            $func_opts
        );

        // Rejected / optional undefined parameter or not.
        $presence = $this->checkParamPresence($defined, $defined ? $inputs[$param] : null, $id, $flags, $options, $func_opts);
        if ($presence === self::PRESENCE_VALIDATE && ($inputs[$param] === '' || $inputs[$param] === null)
            && ($flags & VALIDATE_FLAG_EMPTY_TO_DEFAULT)) {
            // Validate the "default" value; leave the caller's '' / null alone.
            $presence = self::PRESENCE_DEFAULT;
        }

        if ($presence === self::PRESENCE_VALIDATE) {
            $status = $this->runValidation($validated[$param], $inputs[$param], $spec, $func_opts);
            // Remove validated elements. A nested array is removed only
            // when every one of its elements was consumed.
            if ($status === true && !($func_opts & VALIDATE_OPT_KEEP_INPUTS)
                && ($id !== VALIDATE_ARRAY || (is_array($inputs[$param]) && !count($inputs[$param])))) {
                unset($inputs[$param]);
            }
        } elseif ($presence === self::PRESENCE_DEFAULT) {
            $default = $options['default'] ?? null;
            $status = $this->runValidation($validated[$param], $default, $spec, $func_opts);
            if ($status === true && $defined && !($func_opts & VALIDATE_OPT_KEEP_INPUTS)) {
                unset($inputs[$param]); // The empty input was consumed by "default".
            }
        } else {
            $status = ($presence === self::PRESENCE_SKIP);
        }

        // Do not set unvalidated except array.
        if ($status === false && array_key_exists($param, $validated) && !is_array($validated[$param])) {
            unset($validated[$param]);
        }
        array_pop($this->currentElem);
        return $status;
    }


    /************** private methods - validators ****************/


    /**
     * Run the spec's 'filter' option over $input before any validator looks
     * at it. When VALIDATE_FLAG_ARRAY is set, the filter is applied to each
     * element. A filter failure (filter returns with $error set) records
     * a system error and returns false so the caller can skip validation.
     *
     * @return bool true if no filter is set or the filter accepted the input.
     */
    private function applyInputFilter(&$input, $id, $flags, $options, $func_opts = 0)
    {
        assert(is_int($id));
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if (!isset($options['filter'])) {
            return true;
        }
        assert(is_callable($options['filter']));
        // When error, user should simply set $error message.
        $vname = $this->getValidatorName($id);
        if ($flags & VALIDATE_FLAG_ARRAY) {
            if (!is_array($input)) {
                $this->reportError(
                    [
                        'message' => $vname.' filter (VALIDATE_FLAG_ARRAY) error: Input data is not an array',
                        'value' => $input
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
            foreach ($input as $key => $val) {
                $error = null;
                $input[$key] = $options['filter']($this->context, $val, $error);
                if ($error) {
                    assert(is_string($error));
                    $this->reportError(
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
            return true;
        }

        $error = null;
        $input = $options['filter']($this->context, $input, $error);
        if ($error) {
            assert(is_string($error));
            $this->reportError(
                [
                    'message' => $vname.' filter error: '. addslashes($error),
                    'value' => $input
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false;
        }
        return true;
    }


    /**
     * Validate one value against multiple specs.
     *
     * VALIDATE_MULTI_AND: every sub-spec must pass; the result of the last
     * sub-spec is returned. VALIDATE_MULTI_OR: sub-specs are tried in order
     * and the first passing one wins. OR alternatives are evaluated
     * speculatively ($this->silent): a failing alternative records, logs,
     * raises and throws nothing, and does not touch the overall status — only
     * "All of specs are failed." is reported when no alternative passes.
     *
     * @return bool
     */
    private function validateMulti(&$validated, &$inputs, $specs, $func_opts)
    {
        assert(is_array($specs));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));
        assert(($specs[VALIDATE_FLAGS] & VALIDATE_MULTI_OR) || ($specs[VALIDATE_FLAGS] & VALIDATE_MULTI_AND));
        assert(is_array($specs[VALIDATE_SPECS])); // Array of specs

        $id = VALIDATE_MULTI;
        $flags = $specs[VALIDATE_FLAGS];
        $options = $specs[VALIDATE_OPTIONS];
        $subSpecs = $specs[VALIDATE_SPECS] ?? null;

        // Check specs count just in case user didn't validate specs
        if (!is_array($subSpecs) || !count($subSpecs)) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_MULTI: Invalid spec.',
                    'value' => $inputs,
                ],
                $specs,
                $func_opts
            );
            return false;
        }

        // Rejected parameter check
        if ($this->validateReject($validated, $inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }

        // Accept null or not
        if (!$this->validateAcceptNull($validated, $inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }

        // Apply filter if any. Sub-specs see the filtered value.
        $value = $inputs;
        if (!$this->applyInputFilter($value, $id, $flags, $options, $func_opts)) {
            return false;
        }

        $this->setContextErrorValue($value);
        $validated = null;
        if (!($flags & VALIDATE_MULTI_OR)) {
            // AND (also the fallback for a broken spec without AND/OR).
            $result = null;
            foreach ($subSpecs as $spec) {
                $copy = $value; // Sub-specs must not consume or alter the shared input.
                if (!$this->runValidation($result, $copy, $spec, $func_opts)) {
                    return false;
                }
            }
            $validated = $result;
            return true;
        }

        // OR
        $outerFailed = $this->silentFailed; // Support OR nested inside an OR alternative.
        foreach ($subSpecs as $spec) {
            $copy = $value;
            $result = null;
            $this->silent++;
            $this->silentFailed = false;
            $passed = $this->runValidation($result, $copy, $spec, $func_opts) && !$this->silentFailed;
            $this->silent--;
            if ($passed) {
                $this->silentFailed = $outerFailed;
                $validated = $result;
                return true;
            }
        }
        $this->silentFailed = $outerFailed;
        $this->reportError(
            [
                'message' => 'VALIDATE_MULTI: All of specs are failed. '.
                             'Note: OR will not log failed validations.',
                'value' => $value
            ],
            $specs,
            $func_opts
        );
        return false;
    }

    /**
     * Validate an array of scalars (VALIDATE_FLAG_ARRAY): every element is
     * validated by the same spec. $processedCount counts every element across
     * all nesting levels so "alimit" bounds the total work.
     *
     * @return bool
     */
    private function validateScalarArray(&$validated, $id, &$processedCount, $elementLimit, $keyValidator, &$inputs, $flags, $options, $func_opts)
    {
        assert(is_int($id)); // validator id
        assert(is_int($processedCount)); // Apply count
        assert(is_callable($keyValidator));
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        assert(is_int($options['amin']) && $options['amin'] >= 0); // Array elements min
        assert(is_int($options['amax'])); // Array elements max
        assert($options['amin'] <= $options['amax']);

        $validatorName = $this->getValidatorName($id);
        if ($inputs === '' && ($flags & VALIDATE_FLAG_EMPTY)) {
            $validated = ''; // Empty value is explicitly allowed.
            return true;
        }
        if (!is_array($inputs)) {
            $this->reportError(
                [
                    'message' => $validatorName.': Array of scalars validation. '
                                 .(is_scalar($inputs) ? 'Scalar' : 'Non-array').' value is not allowed.',
                    'value' => $inputs
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false;
        }

        $validator = $this->getValidator($id);
        $amin = $options['amin'];
        $amax = $options['amax'];
        $num = count($inputs);

        if ($num < $amin || $num > $amax) {
            $this->reportError(
                [
                    'message' => $validatorName.' array: Number of elements is out of range. '.
                                 'amin: "'. $amin. '" amax: "'. $amax .'"',
                    'value' => $inputs
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false;
        }

        // "default" replaces the whole array, never an element.
        $elementFlags = $flags & ~VALIDATE_FLAG_EMPTY_TO_DEFAULT;
        $status = true;
        foreach ($inputs as $key => $val) {
            if (++$processedCount > $elementLimit) {
                $this->reportError(
                    [
                        'message' => $validatorName. ': Array validation. Number of elements exceed limit: "'.$elementLimit.'". '.
                                     'Hint: you may want to set "alimit" option to allow larger array.',
                        'value' => 'N/A',
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (!$keyValidator($this->context, $key)) {
                $status = false;
                $this->reportError(
                    [
                        'message' => $validatorName. ': Array validation. Array parameter has invalid key format. '.
                                     'Hint: you may want VALIDATE_FLAG_ARRAY_KEY_ALNUM flag or "key_callback" option.',
                        'value' => 'key:'.$key,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                continue;
            }
            if (is_array($val)) {
                if (!($flags & VALIDATE_FLAG_ARRAY_RECURSIVE)) {
                    $status = false;
                    $this->reportError(
                        [
                            'message' => $validatorName. ': Array validation. Nested array is not allowed by VALIDATE_FLAG_ARRAY_RECURSIVE.',
                            'value' => 'key:'.$key,
                        ],
                        [$id, $flags, $options],
                        $func_opts
                    );
                    continue;
                }
                $result = $this->validateScalarArray($validated[$key], $id, $processedCount, $elementLimit, $keyValidator, $inputs[$key], $flags, $options, $func_opts);
                if ($result === false) {
                    return false;
                }
                continue;
            }
            if ($val === null && $id !== VALIDATE_NULL && $id !== VALIDATE_CALLBACK) {
                if (!$this->validateAcceptNull(null, $val, $id, $flags, $options, $func_opts)) {
                    $status = false;
                    continue;
                }
                $validated[$key] = null; // Accepted NULL is the result.
                if (!($func_opts & VALIDATE_OPT_KEEP_INPUTS)) {
                    unset($inputs[$key]);
                }
                continue;
            }
            if (true === $this->$validator($validated[$key], $val, $elementFlags, $options, $func_opts)) {
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
     * Validate a single (non-ARRAY, non-MULTI) spec node: reject flag,
     * empty -> "default", null handling, "filter", then the per-type
     * validator (once, or per element under VALIDATE_FLAG_ARRAY).
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

        // Empty input: validate the "default" value instead (the caller's
        // input is left alone).
        if (($flags & VALIDATE_FLAG_EMPTY_TO_DEFAULT) && ($inputs === '' || $inputs === null)) {
            $default = $inputs;
            if (!$this->applyDefaultIfEmpty($default, $id, $flags, $options, $func_opts)) {
                return false;
            }
            return $this->validateScalar($validated, $default, $id, $flags & ~VALIDATE_FLAG_EMPTY_TO_DEFAULT, $options, $func_opts);
        }

        // NULL is rejected unless VALIDATE_FLAG_NULL / VALIDATE_FLAG_EMPTY allow it.
        if (!$this->validateAcceptNull($validated, $inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }
        if ($inputs === null && $id !== VALIDATE_NULL && $id !== VALIDATE_CALLBACK) {
            // Accepted NULL is the result. (VALIDATE_CALLBACK receives it.)
            $validated = null;
            return true;
        }

        // Apply filter if any.
        if (!$this->applyInputFilter($inputs, $id, $flags, $options, $func_opts)) {
            return false;
        }

        // Set original value
        $this->setContextErrorValue($inputs);
        if ($flags & VALIDATE_FLAG_ARRAY) {
            assert(is_int($options['amax']));
            // Inputs is array of scalars.
            $processedCount = 0;
            $elementLimit = $options['alimit'] ?? $options['amax'];
            $keyValidator = $this->buildKeyValidator($flags, $options);
            return $this->validateScalarArray($validated, $id, $processedCount, $elementLimit, $keyValidator, $inputs, $flags, $options, $func_opts);
        }
        // Inputs is scalar or object
        $validator = $this->getValidator($id);
        return $this->$validator($validated, $inputs, $flags, $options, $func_opts);
    }


    /**
     * Build the key validator for VALIDATE_FLAG_ARRAY elements or for the
     * keys a VALIDATE_ARRAY validates with its "undeclared" option:
     * "key_callback", else VALIDATE_FLAG_ARRAY_KEY_ALNUM, else int / digit keys.
     *
     * @return callable Always return callable if user validates spec at development time.
     */
    private function buildKeyValidator($flags, $options)
    {
        assert(is_int($flags));
        assert(is_array($options));

        if (isset($options['key_callback'])) {
            // User defined key validator
            assert(is_callable($options['key_callback']));
            return $options['key_callback'];
        }
        // Predefined array key validator
        if ($flags & VALIDATE_FLAG_ARRAY_KEY_ALNUM) {
            return function ($ctx, $key) {
                $key = (string)$key;
                if (strlen($key) > 64) {
                    // By default, keys longer than 64 chars are not allowed.
                    // If users have trouble with this restriction, use user defined key validator.
                    return false;
                }
                $chars = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';
                return strlen($key) === strspn($key, $chars);
            };
        }
        return function ($ctx, $key) {
            if (is_int($key)) {
                return true;
            }
            if (strlen($key) > 32) {
                // By default, keys longer than 32 digits are not allowed.
                // If users have trouble with this restriction, use user defined key validator.
                return false;
            }
            return strlen($key) === strspn($key, '1234567890_-');
        };
    }

    /**
     * Special validator - Reject validator.
     *
     * Rejecting values are controlled by VALIDATE_FLAG_REJECT, not parameter definition.
     *
     * @return bool true when the value was rejected (error reported).
     */
    private function validateReject(&$validated, $value, $id, $flags, $options, $func_opts)
    {
        // $value does not matter for this validator
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if ($flags & VALIDATE_FLAG_REJECT) {
            $vname = $this->getValidatorName($id);
            $this->reportError(
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
     * Accept null or not. VALIDATE_NULL always accepts null; every other
     * validator needs VALIDATE_FLAG_NULL or VALIDATE_FLAG_EMPTY.
     *
     * @return bool false when null was rejected (error reported).
     */
    private function validateAcceptNull($validated, $inputs, $id, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if ($id !== VALIDATE_NULL && is_null($inputs) && !($flags & (VALIDATE_FLAG_NULL | VALIDATE_FLAG_EMPTY))) {
            $vname = $this->getValidatorName($id);
            $this->reportError(
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

        $this->reportError(
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
            $this->reportError(
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
            $this->reportError(
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
            $this->reportError(
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

        if (!$this->assertScalarType($value, [VALIDATE_NULL, $flags, $options], $func_opts)) {
            return false;
        }

        if (!is_null($value) && $value !== '') {
            $this->reportError(
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
     * Accepted string grammar (both modes): an optional sign (only when
     * permitted by VALIDATE_INT_POSITIVE_SIGN / VALIDATE_INT_NEGATIVE_SIGN or
     * a negative "min"), then "0" or a digit string without leading zeros.
     * "-0", a lone sign and leading zeros are rejected.
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "min" - Minimum value (int or integer string).
     * "max" - Maximum value (int or integer string).
     * "values" - Optional. [int => true, ...] exact-match whitelist; replaces min/max.
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

        if ($value instanceof GMP) {
            $value = gmp_strval($value);
        }
        if (!$this->assertScalarType($value, [VALIDATE_INT, $flags, $options], $func_opts)) {
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

        $values = $options['values'] ?? null;
        // Exact values match
        if ($values) {
            if (!is_int($value) && !(is_string($value) && preg_match('/\A-?(?:0|[1-9][0-9]*)\z/', $value))) {
                $this->reportError(
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
                $this->reportError(
                    [
                        'message' => 'VALIDATE_INT: Failed to match defined option "values".',
                        'value' => $value,
                    ],
                    [VALIDATE_INT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if ($flags & VALIDATE_FLAG_RAW) {
                $validated = $value;
            } elseif ($flags & VALIDATE_INT_AS_STRING) {
                $validated = (string)$value;
            } else {
                $validated = (int)$value;
            }
            return true;
        }

        assert(is_numeric($options['min']) || is_int($options['min']));
        assert(is_numeric($options['max']) || is_int($options['max']));
        assert($options['min'] <= $options['max']
               || self::compareBigInt($options['min'], $options['max']) <= 0);

        $min = $options['min'];
        $max = $options['max'];

        if (is_int($value) && $value >= $min && $value <= $max) {
            // AS_STRING always yields a string, so int input (json_decode())
            // and string input give the same result type.
            $validated = (($flags & VALIDATE_INT_AS_STRING) && !($flags & VALIDATE_FLAG_RAW))
                         ? (string)$value : $value;
            return true;
        }

        if (!is_scalar($value)) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_INT: Not a scalar.',
                    'value' => $value,
                ],
                [VALIDATE_INT, $flags, $options],
                $func_opts
            );
            return false;
        }

        $ret = (string)$value;
        $sign = '';
        if ($flags & VALIDATE_INT_POSITIVE_SIGN) {
            $sign .= '+';
        }
        if ($flags & VALIDATE_INT_NEGATIVE_SIGN || substr((string)$min, 0, 1) === '-') {
            $sign .= '\\-';
        }
        $sign = ($sign === '') ? '' : '['.$sign.']?';
        if (!preg_match('/\A'.$sign.'(?:0|[1-9][0-9]*)\z/', $ret) || $ret === '-0') {
            $this->reportError(
                [
                    'message' => 'VALIDATE_INT: Invalid int string.',
                    'value' => $ret,
                ],
                [VALIDATE_INT, $flags, $options],
                $func_opts
            );
            return false;
        }
        // An explicit '+' sign (allowed via VALIDATE_INT_POSITIVE_SIGN) is not
        // part of the canonical int string form.
        $canonical = ($ret[0] === '+') ? substr($ret, 1) : $ret;

        // Int type conversion is problematic with large values, especially on 32 bit platforms.
        if ($flags & VALIDATE_INT_AS_STRING) {
            if (self::compareBigInt($canonical, $min) < 0 || self::compareBigInt($canonical, $max) > 0) {
                $this->reportError(
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
            $validated = $ret;
            return true;
        }

        $int = (int)$canonical;
        if ((string)$int !== $canonical) {
            // Integer overflows and value is converted to float.
            $this->reportError(
                [
                    'message' => 'VALIDATE_INT: Overflow or malformed input. min: "'. $min.'" max: "'.$max.'"',
                    'value' => $value,
                ],
                [VALIDATE_INT, $flags, $options],
                $func_opts
            );
            return false;
        }

        if ($int < $min || $int > $max) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_INT: Out of defined range. min: "'. $min.'" max: "'.$max.'"',
                    'value' => $int,
                ],
                [VALIDATE_INT, $flags, $options],
                $func_opts
            );
            return false;
        }

        if ($flags & VALIDATE_FLAG_RAW) {
            $validated = $value;
        } else {
            $validated = $int; // Return int type value
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

        if (!$this->assertScalarType($value, [VALIDATE_BOOL, $flags, $options], $func_opts)) {
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
        $len = strlen($ret);
        if ($len > 5) {
            $this->reportError(
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
            $this->reportError(
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
            $this->reportError(
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
     * Float validator.
     *
     * Supported flags (see validate_defs.php):
     *   VALIDATE_FLOAT_AS_STRING     accept numeric-string (or int) input only — native doubles are
     *                                rejected — and return the value as a canonical numeric string
     *                                (thousand separators removed, decimal separator normalized to '.').
     *   VALIDATE_FLOAT_FRACTION      require a fractional part in string input (e.g. "1.5", reject
     *                                "1" or "1."). Native doubles are already floats and always satisfy it.
     *   VALIDATE_FLOAT_THOUSAND      accept thousand separators in the integer part.
     *   VALIDATE_FLOAT_SCIENTIFIC    accept scientific notation (1.2e3).
     *   VALIDATE_FLOAT_POSITIVE_SIGN allow an explicit leading '+'.
     *   VALIDATE_FLOAT_NEGATIVE_SIGN allow a leading '-' (also implied when min < 0).
     *
     * Supported options:
     *   "min"      numeric. Inclusive lower bound.
     *   "max"      numeric. Inclusive upper bound.
     *   "INF"      bool. Permit positive infinity as a valid value (min/max do not apply to it).
     *   "-INF"     bool. Permit negative infinity as a valid value (min/max do not apply to it).
     *   "decimal"  single char. Decimal separator (default '.'). Must not be a digit, '+', '-', 'e' or 'E'.
     *   "thousand" single char. Thousand separator (default ','). Only consulted when
     *              VALIDATE_FLOAT_THOUSAND is set; same char restrictions, must differ from "decimal".
     *   "length"   int. Max byte length of a string-form input (default 32).
     *   "filter"   callable. Pre-filter applied before validation.
     *   "default"  mixed. Substituted when VALIDATE_FLAG_EMPTY_TO_DEFAULT applies; itself validated.
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
        assert(empty($options['thousand']) || (is_string($options['thousand']) && strlen($options['thousand']) === 1));
        assert(empty($options['INF']) || is_bool($options['INF']));
        assert(empty($options['-INF']) || is_bool($options['-INF']));

        if (!$this->assertScalarType($value, [VALIDATE_FLOAT, $flags, $options], $func_opts)) {
            return false;
        }

        $min = $options['min'];
        $max = $options['max'];
        $pinf = $options['INF'] ?? false;
        $ninf = $options['-INF'] ?? false;
        $decimalChar = $options['decimal'] ?? '.';
        $thousandChar = $options['thousand'] ?? ',';
        // Separator sanity must hold even in production where assert() and
        // VALIDATE_OPT_CHECK_SPEC are off: a separator that collides with the
        // other one or with the float grammar (digits, signs, 'e'/'E') would
        // make the str_replace() normalization below silently corrupt values
        // (e.g. decimal ',' colliding with the default thousand ',' turns
        // "1,5" into 15.0). Fail validation instead.
        if (!is_string($decimalChar) || strlen($decimalChar) !== 1
            || strpbrk($decimalChar, VALIDATE_FLOAT_SEPARATOR_INVALID_CHARS) !== false
            || (($flags & VALIDATE_FLOAT_THOUSAND)
                && (!is_string($thousandChar) || strlen($thousandChar) !== 1
                    || $decimalChar === $thousandChar
                    || strpbrk($thousandChar, VALIDATE_FLOAT_SEPARATOR_INVALID_CHARS) !== false))) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_FLOAT: Broken spec: "decimal"/"thousand" separator must be '
                                .'a single char other than digits, "+", "-", "e" and "E", '
                                .'and must differ from each other.',
                    'value' => null,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }

        // Resolve empty input first so every later check (type dispatch,
        // length, format, range) runs on the single final candidate value.
        // (A 'default' for VALIDATE_FLAG_EMPTY_TO_DEFAULT was already
        // substituted by validateScalar().)
        if (($flags & VALIDATE_FLAG_EMPTY) && $value === '') {
            if ($flags & VALIDATE_FLOAT_AS_STRING) {
                $validated = '';
            } else {
                $validated = null;
            }
            return true;
        }
        if (is_float($value)) {
            if ($flags & VALIDATE_FLOAT_AS_STRING) {
                // AS_STRING accepts numeric strings only (see validate_defs.php);
                // converting a native double here could also produce scientific
                // notation ('1.0E-5') that would not round-trip through this
                // validator.
                $this->reportError(
                    [
                        'message' => 'VALIDATE_FLOAT: AS_STRING requires string input. Float value given.',
                        'value' => $value,
                    ],
                    [VALIDATE_FLOAT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (is_nan($value)) {
                $this->reportError(
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
                $this->reportError(
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
                $this->reportError(
                    [
                        'message' => 'VALIDATE_FLOAT: -INF value is not allowed.',
                        'value' => -INF,
                    ],
                    [VALIDATE_FLOAT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (is_infinite($value)) {
                // INF/-INF passed their option gates above; min/max (and the
                // FRACTION notation requirement) do not apply to infinities.
                $validated = $value;
                return true;
            }
            if ($value < $min || $value > $max) {
                $this->reportError(
                    [
                        'message' => 'VALIDATE_FLOAT: Value is out of range. min: "'.$min.'" max: "'.$max.'"',
                        'value' => $value,
                    ],
                    [VALIDATE_FLOAT, $flags, $options],
                    $func_opts
                );
                return false;
            }
            // FRACTION is a notation constraint on string input; a native
            // double is already a float, so it always satisfies the flag.
            // VALIDATE_FLAG_RAW needs no branch here: the raw input IS $value.
            $validated = $value;
            return true;
        }

        // String-form input. Bound the byte length cheaply before regex work.
        $length = $options['length'] ?? 32;
        $ret_str = (string)$value;
        if (strlen($ret_str) > $length) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_FLOAT: Float value is too long. length: \''.$length.'\' '.
                                 'Hint: set "length" option to allow longer length.',
                    'value' => $ret_str,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }

        if ($ret_str === '') {
            $this->reportError(
                [
                    'message' => 'VALIDATE_FLOAT: Empty input.',
                    'value' => null,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }

        // Build the format regex from flags + options. The string is parsed
        // strictly so locale leakage (whitespace, alt forms) cannot pass.
        $lead = '';
        if ($flags & VALIDATE_FLOAT_POSITIVE_SIGN) {
            $lead = '+';
        }
        if ($flags & VALIDATE_FLOAT_NEGATIVE_SIGN || $min < 0) {
            $lead = empty($lead) ? '-' : $lead . '\\-';
        }
        $lead = empty($lead) ? '' : '['.$lead.']?';

        $dec = preg_quote($decimalChar, '/');
        if ($flags & VALIDATE_FLOAT_THOUSAND) {
            $thou = preg_quote($thousandChar, '/');
            // Integer part: either "0", a grouped form (first group 1-3
            // digits, every subsequent group exactly 3, at least one
            // separator), or the ungrouped form (the C module's behaviour:
            // separator is permitted, not required). The grouped branch
            // requires '+' so it stays disjoint from the ungrouped branch.
            $int = '(?:0|[1-9]\\d{0,2}(?:'.$thou.'\\d{3})+|[1-9]\\d*)';
        } else {
            $int = '(?:0|[1-9]\\d*)';
        }
        $fracOptional = '(?:'.$dec.'\\d+)?';
        $fracRequired = $dec.'\\d+';
        $frac = ($flags & VALIDATE_FLOAT_FRACTION) ? $fracRequired : $fracOptional;
        $sci = ($flags & VALIDATE_FLOAT_SCIENTIFIC) ? '(?:[eE][+\\-]?\\d+)?' : '';

        if (!preg_match('/\A'.$lead.$int.$frac.$sci.'\z/', $ret_str)) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_FLOAT: Invalid float format.',
                    'value' => $ret_str,
                ],
                [VALIDATE_FLOAT, $flags, $options],
                $func_opts
            );
            return false;
        }

        // Normalize separators before the float cast: drop thousand chars,
        // then swap a custom decimal char back to '.' so (float) parses.
        $normalized = $ret_str;
        if ($flags & VALIDATE_FLOAT_THOUSAND) {
            $normalized = str_replace($thousandChar, '', $normalized);
        }
        if ($decimalChar !== '.') {
            $normalized = str_replace($decimalChar, '.', $normalized);
        }

        $ret = (float)$normalized;
        if (is_nan($ret) || $ret === INF || $ret === -INF) {
            $this->reportError(
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
            $this->reportError(
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
            // Return the canonical numeric string (thousand separators
            // removed, decimal separator normalized to '.') so the result is
            // always is_numeric() and safe in downstream numeric contexts.
            $validated = $normalized;
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
     * "encoding" - Optional. Character encoding of the input, any name
     *              mbstring knows ("UTF-8", "SJIS", "ISO-2022-JP", "UTF-16",
     *              ...); default "UTF-8". mb_check_encoding() validates it.
     *              Without VALIDATE_STRING_MB only ASCII chars are allowed.
     * "transfer_encoding" - Optional. "quoted-printable", "percent",
     *              "base64", "base64url" or "html": the encoded form is
     *              checked strictly and decoded, and the decoded text is
     *              validated (encoding, chars). See decodeTransferEncoding().
     * "unicode"  - Optional. [code point => bool] overrides for _MB input:
     *              true allows a code point, false rejects it. Consulted
     *              before (and also with) VALIDATE_STRING_RFC3454_C.
     * "values"   - Optional. [string => true, ...] exact-match whitelist; replaces min/max and char checks.
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

        $vname = $this->getValidatorName($id);
        if (!$this->assertScalarType($value, [$id, $flags, $options], $func_opts)) {
            return false;
        }

        $values = $options['values'] ?? null;
        // Exact values match
        if ($values) {
            if (empty($values[(string)$value])) {
                $this->reportError(
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

        $ret = (string)$value;
        if (($flags & VALIDATE_FLAG_EMPTY) && $ret === '') {
            $validated = '';
            return true;
        }

        $min = $options['min'];
        $max = $options['max'];
        $len = strlen($ret);
        if ($len < $min || $len > $max) {
            $this->reportError(
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
            $validated = ($flags & VALIDATE_FLAG_RAW) ? $value : $ret;
            return true;
        }

        // The chars are checked on $text: the input as received, decoded
        // from its "transfer_encoding" and converted to UTF-8 from its
        // "encoding". The validated value stays the input as received.
        $text = $ret;
        $transfer = $options['transfer_encoding'] ?? null;
        if ($transfer !== null && $transfer !== 'html') {
            $text = self::decodeTransferEncoding($ret, $transfer);
            if ($text === null) {
                $this->reportError(
                    [
                        'message' => $vname .': Invalid '.$transfer.' encoding.',
                        'value' => $ret,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
        }

        $multibyte = ($flags & VALIDATE_STRING_MB);
        $encoding = $options['encoding'] ?? 'UTF-8';
        if ($multibyte) {
            // Character encoding validation (mbstring). An unknown name fails
            // closed even when the spec check is disabled.
            if (!self::isCharEncoding($encoding)) {
                $this->reportError(
                    [
                        'message' => $vname .': Unsupported encoding "'.$encoding.'".',
                        'value' => $ret,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (!mb_check_encoding($text, $encoding)) {
                $this->reportError(
                    [
                        'message' => $vname .': Invalid '.$encoding.' encoding.',
                        'value' => $ret,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
            if (strcasecmp($encoding, 'UTF-8') !== 0 && strcasecmp($encoding, 'UTF8') !== 0) {
                $text = mb_convert_encoding($text, 'UTF-8', $encoding);
            }
        }
        if ($transfer === 'html') {
            // HTML character references are decoded after the conversion:
            // they name Unicode code points, not bytes of "encoding".
            $text = self::decodeHtmlReferences($text);
            if ($text === null) {
                $this->reportError(
                    [
                        'message' => $vname .': Invalid html encoding.',
                        'value' => $ret,
                    ],
                    [$id, $flags, $options],
                    $func_opts
                );
                return false;
            }
        }

        // Strict CRLF mode: CR and LF are only allowed as the "\r\n" pair.
        $crlf = (($flags & VALIDATE_STRING_CR)
                  && ($flags & VALIDATE_STRING_LF)
                  && !($flags & VALIDATE_STRING_CRLF_MIXED));
        $allowed = self::stringCharset($flags, $options['ascii'] ?? '');
        if ($crlf) {
            $allowed = str_replace(["\r", "\n"], '', $allowed);
        }

        // Custom unicode validation char map array. [unicode_value => true/false, ...]; true: allowed, false: disallowed.
        assert(!isset($options['unicode']) || is_array($options['unicode']));
        $unimap = $options['unicode'] ?? array();
        $checkUnicode = $multibyte && (!($flags & VALIDATE_STRING_RFC3454_C) || $unimap);

        // Skip runs of whitelisted ASCII with strspn(); inspect only the
        // bytes it stops at. With _MB, $text is valid UTF-8 at this point.
        $len = strlen($text);
        $i = 0;
        while (($i += strspn($text, $allowed, $i)) < $len) {
            $c = $text[$i];
            if (ord($c) >= 0x80) {
                if (!$multibyte) {
                    $this->reportError(
                        [
                            'message' => $vname .': Multibyte char detected.',
                            'value' => $ret,
                        ],
                        [$id, $flags, $options],
                        $func_opts
                    );
                    return false;
                }
                // mbstring of PHP 5.3 accepts UTF-8 forms of values beyond
                // U+10FFFF; reject them on every version.
                $cp = self::utf8CodePointAt($text, $i, $clen);
                if ($cp < 0) {
                    $this->reportError(
                        [
                            'message' => $vname .': Invalid '.$encoding.' encoding.',
                            'value' => $ret,
                        ],
                        [$id, $flags, $options],
                        $func_opts
                    );
                    return false;
                }
                if (!$checkUnicode) {
                    $i += $clen;
                    continue;
                }
                if (isset($unimap[$cp])) {
                    $allowedCp = ($unimap[$cp] === true);
                    $why = 'Detected invalid unicode defined by $option["unicode"].';
                } else {
                    $allowedCp = ($flags & VALIDATE_STRING_RFC3454_C) || !self::isRfc3454Prohibited($cp);
                    $why = 'Unicode CNTRL char detected.';
                }
                if (!$allowedCp) {
                    $this->reportError(
                        [
                            'message' => $vname .': '.$why.sprintf(' code point: U+%04X', $cp),
                            'value' => $ret,
                        ],
                        [$id, $flags, $options],
                        $func_opts
                    );
                    return false;
                }
                $i += $clen;
                continue;
            }
            if ($crlf) {
                if ($c === "\r" && ($text[$i + 1] ?? '') === "\n") {
                    $i += 2;
                    continue;
                }
                if ($c === "\n") {
                    $this->reportError(
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
                    $this->reportError(
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
            $this->reportError(
                [
                    'message' => $vname .': Illegal char detected. ord: "'.ord($c).'" chr: "'
                                 .(ord($c) >= 0x20 && ord($c) < 0x7F ? addslashes($c) : sprintf('\\x%02X', ord($c))).'"',
                    'value' => $ret,
                ],
                [$id, $flags, $options],
                $func_opts
            );
            return false;
        }

        if ($flags & VALIDATE_FLAG_RAW) {
            $validated = $value;
        } else {
            $validated = $ret;
        }
        return true;
    }


    /**
     * Build the strspn() whitelist of ASCII bytes allowed by the
     * VALIDATE_STRING_* character-class flags plus the "ascii" option.
     * Bytes >= 0x80 are never part of it (multibyte chars are handled
     * separately).
     *
     * @param int    $flags Spec flags.
     * @param string $ascii "ascii" option.
     *
     * @return string
     */
    private static function stringCharset($flags, $ascii)
    {
        assert(is_int($flags));
        assert(is_string($ascii));

        $allowed = '';
        if ($flags & VALIDATE_STRING_SPACE) {
            $allowed .= ' ';
        }
        if ($flags & VALIDATE_STRING_TAB) {
            $allowed .= "\t";
        }
        if ($flags & (VALIDATE_STRING_LF | VALIDATE_STRING_CRLF_MIXED)) {
            $allowed .= "\n";
        }
        if ($flags & (VALIDATE_STRING_CR | VALIDATE_STRING_CRLF_MIXED)) {
            $allowed .= "\r";
        }
        if ($flags & VALIDATE_STRING_DIGIT) {
            $allowed .= '0123456789';
        }
        if ($flags & VALIDATE_STRING_LOWER_ALPHA) {
            $allowed .= 'abcdefghijklmnopqrstuvwxyz';
        }
        if ($flags & VALIDATE_STRING_UPPER_ALPHA) {
            $allowed .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        if ($flags & VALIDATE_STRING_SYMBOL) {
            $allowed .= '!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~';
        }
        // Spec check guarantees "ascii" is 7-bit; drop anything else defensively.
        $allowed .= preg_replace('/[\x80-\xFF]/', '', $ascii);
        return $allowed;
    }


    /**
     * Decode the UTF-8 character starting at byte $idx of a string
     * mb_check_encoding() accepted as UTF-8.
     *
     * @param string $str  UTF-8 string.
     * @param int    $idx  Byte offset of a lead byte (>= 0x80).
     * @param int    $clen Output. Byte length of the character (2-4; 1 for
     *                     a lead byte 0xF5 - 0xFD).
     *
     * @return int Code point, or -1 for a value beyond U+10FFFF (a lead byte
     *             0xF5 - 0xFD, or 0xF4 followed by 0x90 - 0xBF), which
     *             mbstring of PHP 5.3 accepts.
     */
    private static function utf8CodePointAt($str, $idx, &$clen)
    {
        $b0 = ord($str[$idx]);
        if ($b0 >= 0xF5) {
            $clen = 1;
            return -1;
        }
        if ($b0 >= 0xF0) {
            $clen = 4;
            $cp = (($b0 & 0x07) << 18) | ((ord($str[$idx + 1]) & 0x3F) << 12)
                  | ((ord($str[$idx + 2]) & 0x3F) << 6) | (ord($str[$idx + 3]) & 0x3F);
            return $cp > 0x10FFFF ? -1 : $cp;
        }
        if ($b0 >= 0xE0) {
            $clen = 3;
            return (($b0 & 0x0F) << 12) | ((ord($str[$idx + 1]) & 0x3F) << 6) | (ord($str[$idx + 2]) & 0x3F);
        }
        $clen = 2;
        return (($b0 & 0x1F) << 6) | (ord($str[$idx + 1]) & 0x3F);
    }


    /**
     * Encode a Unicode scalar value (U+0000 - U+10FFFF, no surrogates) as
     * UTF-8.
     *
     * @param int $cp Code point.
     *
     * @return string
     */
    private static function utf8Chr($cp)
    {
        if ($cp < 0x80) {
            return chr($cp);
        }
        if ($cp < 0x800) {
            return chr(0xC0 | ($cp >> 6)) . chr(0x80 | ($cp & 0x3F));
        }
        if ($cp < 0x10000) {
            return chr(0xE0 | ($cp >> 12)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
        }
        return chr(0xF0 | ($cp >> 18)) . chr(0x80 | (($cp >> 12) & 0x3F))
               . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F));
    }


    /**
     * Whether $name is a character encoding mbstring knows (a name, MIME
     * name or alias; mbstring resolves it). mbstring also lists transfer
     * encodings and internal names - BASE64, UUENCODE, HTML-ENTITIES,
     * Quoted-Printable, 7bit, 8bit, pass, auto, wchar, byte2be, ... - which
     * are not character encodings and are rejected (decode a transfer
     * encoding with the "transfer_encoding" option).
     *
     * @param mixed $name "encoding" option.
     *
     * @return bool
     */
    private static function isCharEncoding($name)
    {
        static $known = [];

        if (!is_string($name) || $name === '') {
            return false;
        }
        $key = strtolower($name);
        if (!isset($known[$key])) {
            // Names, MIME names and aliases of the non-character
            // "encodings" of mbstring in PHP 5.3 - 8.6.
            $excluded = [
                'pass', 'none', 'auto', 'unknown', 'wchar', 'byte2be', 'byte2le', 'byte4be', 'byte4le',
                'base64', 'uuencode', 'x-uuencode', 'html-entities', 'html', 'quoted-printable', 'qprint',
                '7bit', '8bit', 'binary',
            ];
            $known[$key] = false;
            if (!in_array($key, $excluded, true)) {
                // mbstring warns about an unknown name (PHP 8 throws ValueError).
                $unknown = false;
                set_error_handler(function () use (&$unknown) {
                    $unknown = true;
                    return true;
                });
                mb_check_encoding('', $name);
                restore_error_handler();
                $known[$key] = !$unknown;
            }
        }
        return $known[$key];
    }


    /**
     * Check the syntax of a transfer-encoded string strictly and decode it
     * ("transfer_encoding" option; "html" is decodeHtmlReferences()).
     *
     * "quoted-printable" - RFC 2045 6.7: lines of at most 76 chars separated
     *                      by CRLF; printable ASCII, SPACE and TAB, but no
     *                      SPACE / TAB at the end of a line; '=' only in
     *                      "=XX" (uppercase hex digits) or as a soft line
     *                      break at the end of a line other than the last.
     * "percent"          - The output of urlencode() / rawurlencode(): RFC
     *                      3986 unreserved chars, "%XX" and '+' (a space).
     * "base64"           - RFC 4648 4. "base64url" - RFC 4648 5. '='
     *                      padding is optional but must complete the last
     *                      group; its unused bits must be zero.
     *
     * @param string $str  Encoded string.
     * @param string $type "transfer_encoding" option.
     *
     * @return string|null Decoded bytes, or null for invalid syntax.
     */
    private static function decodeTransferEncoding($str, $type)
    {
        switch ($type) {
            case 'quoted-printable':
                $lines = explode("\r\n", $str);
                $last = count($lines) - 1;
                foreach ($lines as $n => $line) {
                    if (strlen($line) > 76
                        || !preg_match('/\A(?:[\x21-\x3C\x3E-\x7E]|[\t ](?!\z)|=[0-9A-F]{2})*+(=?)\z/', $line, $m)
                        || ($m[1] === '=' && $n === $last)) {
                        return null;
                    }
                }
                return quoted_printable_decode($str);
            case 'percent':
                if (!preg_match('/\A(?:[A-Za-z0-9._~+-]|%[0-9A-Fa-f]{2})*+\z/', $str)) {
                    return null;
                }
                return urldecode($str);
            case 'base64':
            case 'base64url':
                $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
                            .($type === 'base64' ? '+/' : '-_');
                $c = ($type === 'base64') ? 'A-Za-z0-9+\/' : 'A-Za-z0-9_-';
                if (!preg_match('/\A(?:['.$c.']{4})*+(?:['.$c.']{2}(?:==)?|['.$c.']{3}=?)?\z/', $str)) {
                    return null;
                }
                $data = rtrim($str, '=');
                $rest = strlen($data) % 4; // 0, 2 or 3
                if ($rest > 0
                    && (strpos($alphabet, $data[strlen($data) - 1]) & ($rest === 2 ? 0x0F : 0x03)) !== 0) {
                    return null; // non-zero unused bits
                }
                if ($type === 'base64url') {
                    $data = strtr($data, '-_', '+/');
                }
                $decoded = base64_decode($data.str_repeat('=', (4 - $rest) % 4), true);
                return ($decoded === false) ? null : $decoded;
            default:
                return null;
        }
    }


    /**
     * Check and decode HTML character references ("transfer_encoding" =>
     * "html"): the output of htmlspecialchars($s, ENT_QUOTES). No raw '<',
     * '>', '"' or "'"; '&' only in &amp; &lt; &gt; &quot; &apos; or a
     * numeric reference (&#DDD; / &#xHHH;) to U+0001 - U+10FFFF other than
     * a surrogate.
     *
     * @param string $text Text (UTF-8 with the _MB flag).
     *
     * @return string|null Decoded UTF-8 text, or null for invalid syntax.
     */
    private static function decodeHtmlReferences($text)
    {
        if (!preg_match('/\A(?:[^&<>"\']++|&(?:amp|lt|gt|quot|apos|#[0-9]{1,7}|#[xX][0-9A-Fa-f]{1,6});)*+\z/', $text)) {
            return null;
        }
        $named = ['amp' => '&', 'lt' => '<', 'gt' => '>', 'quot' => '"', 'apos' => "'"];
        // Every '&' starts a reference ending at the next ';' (checked above).
        $parts = explode('&', $text);
        $decoded = array_shift($parts);
        foreach ($parts as $part) {
            $end = strpos($part, ';');
            $name = substr($part, 0, $end);
            if (isset($named[$name])) {
                $decoded .= $named[$name];
            } else {
                $cp = ($name[1] === 'x' || $name[1] === 'X') ? hexdec(substr($name, 2)) : (int)substr($name, 1);
                if ($cp === 0 || ($cp >= 0xD800 && $cp <= 0xDFFF) || $cp > 0x10FFFF) {
                    return null;
                }
                $decoded .= self::utf8Chr($cp);
            }
            $decoded .= substr($part, $end + 1);
        }
        return $decoded;
    }


    /**
     * Whether a code point is prohibited by RFC 3454 appendix C (tables
     * C.1.2 and C.2.2 - C.9; ASCII C.1.1 / C.2.1 are governed by the
     * VALIDATE_STRING_* flags instead).
     * https://www.ietf.org/rfc/rfc3454.txt C. Prohibition tables
     *
     * The table is a sorted list of disjoint inclusive [first, last] ranges
     * (a static array in the C extension) searched by bisection.
     *
     * @param int $cp Code point (>= 0x80).
     *
     * @return bool
     */
    private static function isRfc3454Prohibited($cp)
    {
        static $ranges = [
            [0x0080, 0x00A0],   // C.2.2 C1 controls, C.1.2 NO-BREAK SPACE
            [0x0340, 0x0341],   // C.8
            [0x06DD, 0x06DD],   // C.2.2
            [0x070F, 0x070F],   // C.2.2
            [0x1680, 0x1680],   // C.1.2
            [0x180E, 0x180E],   // C.2.2
            [0x2000, 0x200F],   // C.1.2 spaces, C.2.2 ZWNJ/ZWJ, C.8 LRM/RLM
            [0x2028, 0x202F],   // C.2.2 LS/PS, C.8 embedding controls, C.1.2 NNBSP
            [0x205F, 0x2063],   // C.1.2 MMSP, C.2.2 invisible operators
            [0x206A, 0x206F],   // C.2.2 / C.8
            [0x2FF0, 0x2FFB],   // C.7
            [0x3000, 0x3000],   // C.1.2
            [0xD800, 0xDFFF],   // C.5 surrogates
            [0xE000, 0xF8FF],   // C.3 private use
            [0xFDD0, 0xFDEF],   // C.4
            [0xFEFF, 0xFEFF],   // C.2.2
            [0xFFF9, 0xFFFF],   // C.2.2 / C.6 / C.4
            [0x1D173, 0x1D17A], // C.2.2
            [0x1FFFE, 0x1FFFF], // C.4
            [0x2FFFE, 0x2FFFF], // C.4
            [0x3FFFE, 0x3FFFF], // C.4
            [0x4FFFE, 0x4FFFF], // C.4
            [0x5FFFE, 0x5FFFF], // C.4
            [0x6FFFE, 0x6FFFF], // C.4
            [0x7FFFE, 0x7FFFF], // C.4
            [0x8FFFE, 0x8FFFF], // C.4
            [0x9FFFE, 0x9FFFF], // C.4
            [0xAFFFE, 0xAFFFF], // C.4
            [0xBFFFE, 0xBFFFF], // C.4
            [0xCFFFE, 0xCFFFF], // C.4
            [0xDFFFE, 0xDFFFF], // C.4
            [0xE0001, 0xE0001], // C.9
            [0xE0020, 0xE007F], // C.9
            [0xEFFFE, 0xEFFFF], // C.4
            [0xF0000, 0xFFFFF], // C.3 private use, C.4
            [0x100000, 0x10FFFF], // C.3 private use, C.4
        ];
        assert(is_int($cp));

        $lo = 0;
        $hi = count($ranges) - 1;
        while ($lo <= $hi) {
            $mid = ($lo + $hi) >> 1;
            if ($cp < $ranges[$mid][0]) {
                $hi = $mid - 1;
            } elseif ($cp > $ranges[$mid][1]) {
                $lo = $mid + 1;
            } else {
                return true;
            }
        }
        return false;
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

        $regexp = $options['regexp'];
        $ret = null; // Set by validateString(), which also rejects non-scalar input.

        // Validate as String first
        if (!$this->validateString($ret, $value, $flags, $options, $func_opts, VALIDATE_REGEXP)) {
            return false;
        }
        if (($flags & VALIDATE_FLAG_EMPTY) && $ret === '') {
            $validated = ''; // Allowed empty input; the pattern does not apply.
            return true;
        }

        $matched = preg_match($regexp, (string)$ret);
        if ($matched !== 1) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_REGEXP: '.($matched === false
                                 ? 'PCRE error: '.self::pregLastErrorMsg().'.' : 'Failed to match.'),
                    'value' => $ret,
                ],
                [VALIDATE_REGEXP, $flags, $options],
                $func_opts
            );
            return false;
        }
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
     * String input is first validated like VALIDATE_STRING (flags, "min",
     * "max", "ascii", ...); any other input type reaches the callback as is.
     *
     * Supported Flags: See validate_defs.php
     * Supported Options:
     * "min" - Minimum length (string input).
     * "max" - Maximum length (string input).
     * "callback" - function (Validate $ctx, mixed &$validated, mixed $value): bool
     *               $ctx       - Validator context.
     *               $validated - The result; pre-set to the string-validated value.
     *               $value     - Value to be validated.
     *              Return true to accept. Do not sanitize input here.
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
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
            $this->reportError(
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
     * "callback" - Method name. Called as $value->callback($ctx); must return true to accept.
     * "class" - Optional. Required class/interface name (instanceof check).
     * "filter" - Optional. Filter callback before validation. Use this for normalization.
     * "default" - Default value. Default value is subject to be validated also.
     *
     * @return bool
     */
    private function validateObject(&$validated, $value, $flags, $options, $func_opts)
    {
        assert(is_int($flags));
        assert(is_array($options));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));
        assert(is_string($options['callback']));

        if (!is_object($value)) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_OBJECT: Value is not an object. Type: \''.gettype($value).'\'',
                    'value' => $value,
                ],
                [VALIDATE_OBJECT, $flags, $options],
                $func_opts
            );
            return false;
        }
        if (isset($options['class']) && !($value instanceof $options['class'])) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_OBJECT: Object is not an instance of \''.$options['class'].'\'. '
                                 .'Class: \''.get_class($value).'\'',
                    'value' => $value,
                ],
                [VALIDATE_OBJECT, $flags, $options],
                $func_opts
            );
            return false;
        }
        if (!is_callable([$value, $options['callback']])) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_OBJECT: "'. get_class($value) . '" class validation callback is not callable or does not exist. Callback: \''.$options['callback'].'\'',
                    'value' => $value,
                ],
                [VALIDATE_OBJECT, $flags, $options],
                $func_opts
            );
            return false;
        }
        $callback = $options['callback'];
        $ret = $value->$callback($this->context);
        assert(is_bool($ret));
        if ($ret !== true) {
            $this->reportError(
                [
                    'message' => 'VALIDATE_OBJECT: Object validation failed.',
                    'value' => $value,
                ],
                [VALIDATE_OBJECT, $flags, $options],
                $func_opts
            );
            return false;
        }
        $validated = $value;
        return true;
    }


    /************** private methods - error ****************/


    /**
     * Record a message and act on it.
     *
     * $type selects the bucket:
     *   E_ERROR / E_WARNING / E_NOTICE                 - system message from the engine
     *                                                    (E_WARNING / E_NOTICE when the spec has
     *                                                    VALIDATE_FLAG_WARNING / VALIDATE_FLAG_NOTICE).
     *   E_USER_ERROR / E_USER_WARNING / E_USER_NOTICE  - user message (validate_error() & co.).
     *
     * Everything except E_USER_WARNING / E_USER_NOTICE is a validation
     * failure and clears $status. A failure is "fatal" (exception and, with
     * VALIDATE_OPT_RAISE_ERROR, a PHP error) unless the spec downgrades it
     * with VALIDATE_FLAG_WARNING / VALIDATE_FLAG_NOTICE.
     *
     * While VALIDATE_MULTI_OR alternatives are tried ($this->silent > 0)
     * nothing is recorded; failures only set $this->silentFailed.
     *
     * @param string $message Message text.
     * @param int    $type    One of the six levels above.
     *
     * @return null
     *
     * @throws ValidateInvalidValueException On a fatal failure unless VALIDATE_OPT_DISABLE_EXCEPTION.
     */
    private function dispatchError($message, $type)
    {
        assert(is_string($message));
        assert(in_array($type, [E_ERROR, E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE], true));
        assert(array_key_exists('value', $this->context_vars));
        assert(is_int($this->context_vars['func_opts']) && !($this->context_vars['func_opts'] & VALIDATE_OPT_UPPER));

        $failure = ($type !== E_USER_WARNING && $type !== E_USER_NOTICE);
        if ($this->silent) {
            if ($failure) {
                $this->silentFailed = true;
            }
            return;
        }
        if ($failure) {
            $this->status = false;
        }

        $func_opts = $this->context_vars['func_opts'];
        $error = [
            'type'       => $type,
            'param'      => $this->currentElem,
            'defined'    => $this->context_vars['defined'],
            'message'    => $message,
            'spec'       => $this->context_vars['spec'],
            'func_opts'  => $func_opts,
            'value'      => $this->context_vars['value'],
            'orig_value' => $this->context_vars['orig_value'],
        ];

        $error_msg = 'param: \''.join('=>', $this->currentElem).'\' '.
                     'error: \''. $message .'\' val: \''. substr(self::valueToString($this->context_vars['value']), 0, 2048) .'\'';

        // System errors are stored always.
        if ($type === E_ERROR) {
            $this->appendError($this->errors, $error);
        } elseif ($type === E_WARNING) {
            $this->appendError($this->warnings, $error);
        } elseif ($type === E_NOTICE) {
            $this->appendError($this->notices, $error);
        }

        // VALIDATE_FLAG_WARNING / VALIDATE_FLAG_NOTICE turn the failure into
        // a recorded message only: no ERROR, no EXCEPTION.
        $spec_flags = $this->context_vars['spec'][VALIDATE_FLAGS] ?? 0;
        $fatal = ($type === E_ERROR || $type === E_USER_ERROR)
                 && !($spec_flags & (VALIDATE_FLAG_WARNING | VALIDATE_FLAG_NOTICE));
        $user_error_msg = $this->context_vars['spec'][VALIDATE_OPTIONS]['error_message'] ?? null;

        // Store error messages
        // User "error_message" defined in spec option is stored only once.
        // User validate_error()/validate_warning() error messages can be
        // stored as many times as users want.
        switch ($type) {
            // Following 3 are set by $flags and $options["error_message"]
            case E_ERROR:
                if ($user_error_msg) {
                    $this->appendError($this->userErrors, $user_error_msg);
                }
                break;
            case E_WARNING:
                if ($user_error_msg) {
                    $this->appendError($this->userWarnings, $user_error_msg);
                }
                break;
            case E_NOTICE:
                if ($user_error_msg) {
                    $this->appendError($this->userNotices, $user_error_msg);
                }
                break;
            // Following 3 are set by function/method. e.g. validate_error()
            case E_USER_ERROR:
                $this->appendError($this->userErrors, $message);
                break;
            case E_USER_WARNING:
                $this->appendError($this->userWarnings, $message);
                break;
            case E_USER_NOTICE:
                $this->appendError($this->userNotices, $message);
                break;
        }

        // Logger
        if ($func_opts & VALIDATE_OPT_LOG_ERROR) {
            if ($this->loggerFunction) {
                ($this->loggerFunction)($this, $error);
            } else {
                trigger_error('Validate logger function is not registered', E_USER_WARNING);
            }
        }

        if (!$fatal) {
            return;
        }
        // Error
        if ($func_opts & VALIDATE_OPT_RAISE_ERROR) {
            trigger_error($error_msg, $this->error_level);
        }
        // Exception
        if (!($func_opts & VALIDATE_OPT_DISABLE_EXCEPTION)) {
            throw new ValidateInvalidValueException($error_msg, $error);
        }
    }


    /**
     * preg_last_error_msg() (PHP 8.0+) with the same messages on older PHP.
     *
     * @return string
     */
    private static function pregLastErrorMsg()
    {
        if (function_exists('preg_last_error_msg')) {
            return preg_last_error_msg();
        }
        switch (preg_last_error()) {
            case PREG_NO_ERROR:
                return 'No error';
            case PREG_INTERNAL_ERROR:
                return 'Internal error';
            case PREG_BACKTRACK_LIMIT_ERROR:
                return 'Backtrack limit exhausted';
            case PREG_RECURSION_LIMIT_ERROR:
                return 'Recursion limit exhausted';
            case PREG_BAD_UTF8_ERROR:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            case PREG_BAD_UTF8_OFFSET_ERROR:
                return 'The offset did not correspond to the beginning of a valid UTF-8 code point';
            case PREG_JIT_STACKLIMIT_ERROR:
                return 'JIT stack limit exhausted';
        }
        return 'Unknown error';
    }


    /**
     * Render a value for an error message without failing on values that
     * cannot be serialized (closures, ...).
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function valueToString($value)
    {
        if (is_float($value) && is_nan($value)) {
            return 'NAN'; // PHP 8.5+ warns when NAN is implicitly coerced to string.
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        if (is_resource($value)) {
            return 'resource('.get_resource_type($value).')';
        }
        try {
            return serialize($value);
        } catch (Throwable $e) {
            return is_object($value) ? 'object('.get_class($value).')' : gettype($value);
        }
    }


    /**
     * Store errors as array.
     */
    private function appendError(&$storage, $error)
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
            // "{{option}}" placeholders in user messages expand to option values.
            foreach ($this->context_vars['spec'][VALIDATE_OPTIONS] as $opt => $opt_val) {
                if (is_scalar($opt_val)) {
                    $error = str_replace('{{'.$opt.'}}', (string)$opt_val, $error);
                }
            }
        }

        if ($mode & VALIDATE_OPT_ERROR_FULL) {
            foreach ($this->currentElem as $el) {
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
     * VALIDATE_FLAG_PASSTHRU on the spec drops the failure entirely (nothing
     * recorded, raised or thrown; the status is unchanged); the validator
     * still returns false, so the value is left out of the result.
     * VALIDATE_FLAG_NOTICE / VALIDATE_FLAG_WARNING downgrade it to a
     * recorded system notice / warning.
     *
     * @param array $error     Error message. ['message'=>$msg, 'value'=>$val]
     * @param array $spec      Validator spec array
     * @param int   $func_opts Validator function bit mask options.
     *
     * @return null
     */
    private function reportError($error, $spec, $func_opts)
    {
        assert(is_array($error));
        assert(is_string($error['message']));
        assert(array_key_exists('value', $error));
        assert(is_array($spec));
        assert(is_int($spec[VALIDATE_ID]) && is_int($spec[VALIDATE_FLAGS]) && is_array($spec[VALIDATE_OPTIONS]));
        assert(is_int($func_opts) && (!($func_opts & VALIDATE_OPT_UPPER)));

        if ($spec[VALIDATE_FLAGS] & VALIDATE_FLAG_PASSTHRU) {
            return;
        }
        $type = E_ERROR;
        if ($spec[VALIDATE_FLAGS] & VALIDATE_FLAG_NOTICE) {
            $type = E_NOTICE;
        }
        if ($spec[VALIDATE_FLAGS] & VALIDATE_FLAG_WARNING) {
            $type = E_WARNING;
        }

        $this->setContextErrorValue($error['value']);
        $this->dispatchError($error['message'], $type);
    }


    /**
     * Get error and warnings
     *
     * @param int $type E_USER_ERROR for user messages, E_ERROR for system messages.
     *
     * @return array
     */
    private function collectErrors($type)
    {
        assert($type === E_USER_ERROR || $type === E_ERROR);
        if ($type === E_USER_ERROR) {
            return [
                'error'   => $this->userErrors,
                'warning' => $this->userWarnings,
                'notice'  => $this->userNotices,
            ];
        }
        return [
            'error'   => $this->errors,
            'warning' => $this->warnings,
            'notice'  => $this->notices,
        ];
    }


    /************** private methods - spec validation ****************/
    /*
     * Backs validateSpec(). Walks the spec recursively and reports every
     * structural problem it finds via specError(). The same helpers used
     * for value validation are reused to validate the type IDs/flags/
     * option types in the spec itself.
     */


    /**
     * Validate one node of the spec tree (a single [id, flags, options, sub-specs] entry).
     *
     * Reports problems through specError() and continues recursing so the
     * caller can see every error in one pass.
     *
     * @param array $spec Spec node to check (passed by reference because
     *                    successfully-checked entries are stripped to mark
     *                    progress for the caller's $unvalidated output).
     *
     * @return bool true if the node is well-formed, false otherwise.
     */
    private function checkSpecEntry(&$spec)
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
        } elseif (!is_int($spec[VALIDATE_ID]) || $spec[VALIDATE_ID] <= VALIDATE_INVALID || $spec[VALIDATE_ID] > VALIDATE_LAST) {
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
            return false;
        } elseif (!is_int($spec[VALIDATE_FLAGS])) {
            $this->specError(
                [
                    'message' => 'Validator Flag must be valid int.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
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

            // VALIDATE_PARAMS may be absent here — that case is already
            // reported as a spec error above; keep collecting other errors.
            foreach ($spec[VALIDATE_PARAMS] ?? [] as $k => $s) {
                $tmp = $this->checkSpecEntry($s);
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
        if (count($spec) > 4) {
            $this->specWarning(
                [
                    'message' => 'VALIDATE_ARRAY: Too many elements. Spec must have 4 elements.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
        }
        // Only presence / reporting flags mean something for VALIDATE_ARRAY;
        // VALIDATE_FLAG_ARRAY_KEY_ALNUM checks the keys "undeclared" validates.
        $array_flags = VALIDATE_FLAG_REJECT | VALIDATE_FLAG_UNDEFINED | VALIDATE_FLAG_UNDEFINED_TO_DEFAULT
                       | VALIDATE_FLAG_WARNING | VALIDATE_FLAG_NOTICE | VALIDATE_FLAG_PASSTHRU;
        if (isset($options['undeclared'])) {
            $array_flags |= VALIDATE_FLAG_ARRAY_KEY_ALNUM;
        }
        if ($spec[VALIDATE_FLAGS] & ~$array_flags) {
            $this->specWarning(
                [
                    'message' => 'VALIDATE_ARRAY has flags that have no effect. Only VALIDATE_FLAG_REJECT, '
                                .'VALIDATE_FLAG_UNDEFINED, VALIDATE_FLAG_UNDEFINED_TO_DEFAULT, VALIDATE_FLAG_WARNING, '
                                .'VALIDATE_FLAG_NOTICE, VALIDATE_FLAG_PASSTHRU and, with "undeclared" option, '
                                .'VALIDATE_FLAG_ARRAY_KEY_ALNUM apply.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
        }
        $known_options = ['min', 'max', 'default', 'error_message', 'undeclared', 'key_callback'];
        foreach ($options as $key => $unused) {
            if (!in_array($key, $known_options, true) && !(is_string($key) && $key !== '' && $key[0] === '_')) {
                $this->specWarning(
                    [
                        'message' => 'VALIDATE_ARRAY has unknown option "'.$key.'". Known options: "'
                                     .join('", "', $known_options).'" and "_" prefixed user options.',
                        'spec'    => $spec,
                        'flags'   => '',
                    ]
                );
            }
        }
        if (isset($options['key_callback'])) {
            if (!is_callable($options['key_callback'])) {
                $this->specError(
                    [
                        'message' => 'VALIDATE_ARRAY "key_callback" option is not callable.',
                        'spec'    => $spec,
                        'flags'   => '',
                    ]
                );
            } elseif (!isset($options['undeclared'])) {
                $this->specWarning(
                    [
                        'message' => 'VALIDATE_ARRAY has "key_callback" option that has no effect without "undeclared" option.',
                        'spec'    => $spec,
                        'flags'   => '',
                    ]
                );
            }
            if ($spec[VALIDATE_FLAGS] & VALIDATE_FLAG_ARRAY_KEY_ALNUM) {
                $this->specWarning(
                    [
                        'message' => 'VALIDATE_ARRAY has VALIDATE_FLAG_ARRAY_KEY_ALNUM flag that has no effect: "key_callback" option is defined.',
                        'spec'    => $spec,
                        'flags'   => '',
                    ]
                );
            }
        }
        if (isset($options['undeclared'])) {
            // The spec for undeclared keys. Checked on a copy: unlike
            // VALIDATE_PARAMS entries it is not stripped from $unvalidated.
            $undeclaredSpec = $options['undeclared'];
            array_push($this->currentElem, '(undeclared)');
            $this->checkSpecEntry($undeclaredSpec);
            array_pop($this->currentElem);
        }
        if (($spec[VALIDATE_FLAGS] & VALIDATE_FLAG_UNDEFINED_TO_DEFAULT) && !isset($options['default'])) {
            $this->specError(
                [
                    'message' => 'VALIDATE_ARRAY has VALIDATE_FLAG_UNDEFINED_TO_DEFAULT but "default" option is missing.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
        }
        if (!isset($options['min']) || !isset($options['max'])) {
            $this->specError(
                [
                    'message' => 'Array parameter must have "min" and "max" options.',
                    'spec'    => $spec,
                    'flags'   => '',
                ]
            );
            return false;
        } elseif (!is_int($options['min']) || !is_int($options['max'])) {
            $this->specError(
                [
                    'message' => 'Array "min" and "max" options must be int.',
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
            if ($this->checkSpecEntry($params[$key])) {
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
                if ($flags & VALIDATE_STRING_SPACE) {
                    $f[] = 'VALIDATE_STRING_SPACE';
                }
                if ($flags & VALIDATE_STRING_DIGIT) {
                    $f[] = 'VALIDATE_STRING_DIGIT';
                }
                if ($flags & VALIDATE_STRING_TAB) {
                    $f[] = 'VALIDATE_STRING_TAB';
                }
                if ($flags & VALIDATE_STRING_LF) {
                    $f[] = 'VALIDATE_STRING_LF';
                }
                if ($flags & VALIDATE_STRING_CR) {
                    $f[] = 'VALIDATE_STRING_CR';
                }
                if ($flags & VALIDATE_STRING_CRLF_MIXED) {
                    $f[] = 'VALIDATE_STRING_CRLF_MIXED';
                }
                if ($flags & VALIDATE_STRING_CRLF) {
                    $f[] = 'VALIDATE_STRING_CRLF';
                }
                if ($flags & VALIDATE_STRING_LOWER_ALPHA) {
                    $f[] = 'VALIDATE_STRING_LOWER_ALPHA';
                }
                if ($flags & VALIDATE_STRING_UPPER_ALPHA) {
                    $f[] = 'VALIDATE_STRING_UPPER_ALPHA';
                }
                if ($flags & VALIDATE_STRING_ALPHA) {
                    $f[] = 'VALIDATE_STRING_ALPHA';
                }
                if ($flags & VALIDATE_STRING_ALNUM) {
                    $f[] = 'VALIDATE_STRING_ALNUM';
                }
                if ($flags & VALIDATE_STRING_SYMBOL) {
                    $f[] = 'VALIDATE_STRING_SYMBOL';
                }
                if ($flags & VALIDATE_STRING_MB) {
                    $f[] = 'VALIDATE_STRING_MB';
                }
                if ($flags & VALIDATE_STRING_BINARY) {
                    $f[] = 'VALIDATE_STRING_BINARY';
                }
                if ($flags & VALIDATE_STRING_RFC3454_C) {
                    $f[] = 'VALIDATE_STRING_RFC3454_C';
                }
                if ($flags & VALIDATE_STRING_RFC3454_D) {
                    $f[] = 'VALIDATE_STRING_RFC3454_D';
                }
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_STRING_LAST_BIT, $max_bit);
                break;
            case VALIDATE_INT:
                if ($flags & VALIDATE_INT_AS_STRING) {
                    $f[] = 'VALIDATE_INT_AS_STRING';
                }
                if ($flags & VALIDATE_INT_POSITIVE_SIGN) {
                    $f[] = 'VALIDATE_INT_POSITIVE_SIGN';
                }
                if ($flags & VALIDATE_INT_NEGATIVE_SIGN) {
                    $f[] = 'VALIDATE_INT_NEGATIVE_SIGN';
                }
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_INT_LAST_BIT, $max_bit);
                break;
            case VALIDATE_FLOAT:
                if ($flags & VALIDATE_FLOAT_AS_STRING) {
                    $f[] = 'VALIDATE_FLOAT_AS_STRING';
                }
                if ($flags & VALIDATE_FLOAT_FRACTION) {
                    $f[] = 'VALIDATE_FLOAT_FRACTION';
                }
                if ($flags & VALIDATE_FLOAT_THOUSAND) {
                    $f[] = 'VALIDATE_FLOAT_THOUSAND';
                }
                if ($flags & VALIDATE_FLOAT_SCIENTIFIC) {
                    $f[] = 'VALIDATE_FLOAT_SCIENTIFIC';
                }
                if ($flags & VALIDATE_FLOAT_POSITIVE_SIGN) {
                    $f[] = 'VALIDATE_FLOAT_POSITIVE_SIGN';
                }
                if ($flags & VALIDATE_FLOAT_NEGATIVE_SIGN) {
                    $f[] = 'VALIDATE_FLOAT_NEGATIVE_SIGN';
                }
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_FLOAT_LAST_BIT, $max_bit);
                break;
            case VALIDATE_NULL:
                if ($flags & VALIDATE_NULL_AS_STRING) {
                    $f[] = 'VALIDATE_NULL_AS_STRING';
                }
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_NULL_LAST_BIT, $max_bit);
                break;
            case VALIDATE_BOOL:
                if ($flags & VALIDATE_BOOL_AS_STRING) {
                    $f[] = 'VALIDATE_BOOL_AS_STRING';
                }
                if ($flags & VALIDATE_BOOL_01) {
                    $f[] = 'VALIDATE_BOOL_01';
                }
                if ($flags & VALIDATE_BOOL_TF) {
                    $f[] = 'VALIDATE_BOOL_TF';
                }
                if ($flags & VALIDATE_BOOL_TRUE_FALSE) {
                    $f[] = 'VALIDATE_BOOL_TRUE_FALSE';
                }
                if ($flags & VALIDATE_BOOL_ON_OFF) {
                    $f[] = 'VALIDATE_BOOL_ON_OFF';
                }
                if ($flags & VALIDATE_BOOL_YES_NO) {
                    $f[] = 'VALIDATE_BOOL_YES_NO';
                }
                $ret = $this->validateScalarSpecFlags($f, $flags, VALIDATE_BOOL_LAST_BIT, $max_bit);
                if (!($flags & (VALIDATE_BOOL_01 | VALIDATE_BOOL_TF | VALIDATE_BOOL_TRUE_FALSE
                               | VALIDATE_BOOL_ON_OFF | VALIDATE_BOOL_YES_NO))) {
                    $this->specWarning(
                        [
                            'message' => 'VALIDATE_BOOL has no boolean format flags. No value can be accepted.',
                            'spec'    => $spec,
                            'flags'   => join(' | ', $f),
                        ]
                    );
                }
                break;
            case VALIDATE_RESOURCE:
            case VALIDATE_OBJECT:
                $ret = true;
                break;
            default:
                throw new LogicException('Validate: unexpected validator ID in spec check. Report this error.');
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

        if ($flags & VALIDATE_FLAG_RAW) {
            $f[] = 'VALIDATE_FLAG_RAW';
        }
        if ($flags & VALIDATE_FLAG_REJECT) {
            $f[] = 'VALIDATE_FLAG_REJECT';
        }
        if ($flags & VALIDATE_FLAG_UNDEFINED) {
            $f[] = 'VALIDATE_FLAG_UNDEFINED';
        }
        if ($flags & VALIDATE_FLAG_UNDEFINED_TO_DEFAULT) {
            $f[] = 'VALIDATE_FLAG_UNDEFINED_TO_DEFAULT';
        }
        if ($flags & VALIDATE_FLAG_EMPTY) {
            $f[] = 'VALIDATE_FLAG_EMPTY';
        }
        if ($flags & VALIDATE_FLAG_EMPTY_TO_DEFAULT) {
            $f[] = 'VALIDATE_FLAG_EMPTY_TO_DEFAULT';
        }
        if ($flags & VALIDATE_FLAG_NULL) {
            $f[] = 'VALIDATE_FLAG_NULL';
        }
        if ($flags & VALIDATE_FLAG_ARRAY) {
            $f[] = 'VALIDATE_FLAG_ARRAY';
        }
        if ($flags & VALIDATE_FLAG_ARRAY_RECURSIVE) {
            $f[] = 'VALIDATE_FLAG_ARRAY_RECURSIVE';
        }
        if ($flags & VALIDATE_FLAG_ARRAY_KEY_ALNUM) {
            $f[] = 'VALIDATE_FLAG_ARRAY_KEY_ALNUM';
        }
        if ($flags & VALIDATE_FLAG_WARNING) {
            $f[] = 'VALIDATE_FLAG_WARNING';
        }
        if ($flags & VALIDATE_FLAG_NOTICE) {
            $f[] = 'VALIDATE_FLAG_NOTICE';
        }
        if ($flags & VALIDATE_FLAG_PASSTHRU) {
            $f[] = 'VALIDATE_FLAG_PASSTHRU';
        }
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
            'decimal', 'thousand', // FLOAT separator chars (paired with VALIDATE_FLOAT_THOUSAND).
            'encoding', 'transfer_encoding', 'ascii', 'unicode', // STRING / REGEXP / CALLBACK option.
            'values', // INT / STRING option. (REGEXP / CALLBACK may use, but not use with them)
            'regexp', // REGEXP option.
            'resource', // RESOURCE option. Required resource type name.
            'class', // OBJECT option. Required class/interface name.
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
                    if (($flags & VALIDATE_FLAG_ARRAY) && !is_array($options[$known])) {
                        $this->specError(
                            [
                                'message' => $vname.' has invalid "default" option. "default" value must be array with VALIDATE_FLAG_ARRAY.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                    if ($spec[VALIDATE_ID] === VALIDATE_OBJECT && !is_object($options[$known])) {
                        $this->specError(
                            [
                                'message' => 'VALIDATE_OBJECT has invalid "default" option. "default" value must '.
                                             'be object for VALIDATE_OBJECT.',
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
                if ($known !== 'default' && $known !== 'values' && $known !== 'unicode'
                    && !is_scalar($options[$known]) && !is_callable($options[$known])) {
                    $this->specError(
                        [
                            'message' => $vname.' has invalid "'.$known.'" option. Option value must be scalar or callable.',
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
            if (is_string($key) && $key[0] === '_') {
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
                if (isset($options['min']) && !is_int($options['min'])) {
                    $this->specError(
                        [
                            'message' => $vname.' "min" option must be int.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                if (isset($options['max']) && !is_int($options['max'])) {
                    $this->specError(
                        [
                            'message' => $vname.' "max" option must be int.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
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
                    // compareBigInt() below requires int or plain decimal
                    // string min/max — anything else (array, float, '1e5')
                    // would make bccomp() throw instead of reporting the
                    // broken spec.
                    $int_minmax_ok = function ($v) {
                        return is_int($v) || (is_string($v) && preg_match('/\A-?[0-9]+\z/', $v));
                    };
                    if (!(isset($options['min']) && isset($options['max']))) {
                        $this->specError(
                            [
                                'message' => $vname.' "min" and/or "max" is missing.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    } elseif (!$int_minmax_ok($options['min']) || !$int_minmax_ok($options['max'])) {
                        $this->specError(
                            [
                                'message' => $vname.' "min" and "max" options must be int or integer string.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    } elseif (self::compareBigInt($options['min'], $options['max']) > 0) {
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
                } elseif (!is_numeric($options['min']) || !is_numeric($options['max'])) {
                    $this->specError(
                        [
                            'message' => $vname.' "min" and "max" options must be numeric.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                } elseif ($options['min'] > $options['max']) {
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
                break;
            case VALIDATE_NULL:
            case VALIDATE_BOOL:
            case VALIDATE_RESOURCE:
            case VALIDATE_OBJECT:
                if (isset($options['min']) || isset($options['max'])) {
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
                throw new LogicException('Validate: unexpected validator ID in spec check. Report this error.');
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
                if (isset($options['encoding']) && !self::isCharEncoding($options['encoding'])) {
                    $this->specError(
                        [
                            'message' => $vname.' "encoding" option must be a character encoding mbstring knows '
                                        .'(use "transfer_encoding" for quoted-printable, base64, ...).',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
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
                if (isset($options['INF']) || isset($options['-INF']) || isset($options['length'])
                    || isset($options['decimal']) || isset($options['thousand'])) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has "INF"/"-INF"/"length"/"decimal"/"thousand" '
                                        .'options that have no effect.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                break;
            case VALIDATE_FLOAT:
                if (isset($options['encoding'])) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has "encoding" option that has no effect',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                // Separators overlapping the float grammar (digits, signs,
                // 'e'/'E') would corrupt values during normalization, so they
                // are spec errors, not merely unusual choices.
                foreach (['decimal', 'thousand'] as $sep) {
                    if (isset($options[$sep])
                        && (!is_string($options[$sep]) || strlen($options[$sep]) !== 1
                            || strpbrk($options[$sep], VALIDATE_FLOAT_SEPARATOR_INVALID_CHARS) !== false)) {
                        $this->specError(
                            [
                                'message' => $vname.' "'.$sep.'" option must be a single character '
                                            .'other than digits, "+", "-", "e" and "E".',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
                if (isset($options['thousand']) && !($flags & VALIDATE_FLOAT_THOUSAND)) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has "thousand" option but VALIDATE_FLOAT_THOUSAND flag is not set.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if ($flags & VALIDATE_FLOAT_THOUSAND) {
                    $decimalChar = $options['decimal'] ?? '.';
                    $thousandChar = $options['thousand'] ?? ',';
                    if (is_string($decimalChar) && is_string($thousandChar)
                        && strlen($decimalChar) === 1 && strlen($thousandChar) === 1
                        && $decimalChar === $thousandChar) {
                        $this->specError(
                            [
                                'message' => $vname.' "decimal" and "thousand" options must differ. Both are "'.$decimalChar.'".',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
                if (isset($options['length']) && (!is_int($options['length']) || $options['length'] <= 0)) {
                    $this->specError(
                        [
                            'message' => $vname.' "length" option must be a positive int.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                break;
            default:
                throw new LogicException('Validate: unexpected validator ID in spec check. Report this error.');
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
                            'message' => $vname.' has "default" option, but VALIDATE_FLAG_EMPTY_TO_DEFAULT '
                                        .'and/or VALIDATE_FLAG_UNDEFINED_TO_DEFAULT is not set.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if (($flags & VALIDATE_FLAG_UNDEFINED_TO_DEFAULT) && !isset($options['default'])) {
                    $this->specError(
                        [
                            'message' => $vname.' has VALIDATE_FLAG_UNDEFINED_TO_DEFAULT but "default" option is missing.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                if (($flags & VALIDATE_FLAG_EMPTY_TO_DEFAULT) && !isset($options['default'])) {
                    $this->specError(
                        [
                            'message' => $vname.' has VALIDATE_FLAG_EMPTY_TO_DEFAULT but "default" option is missing.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                break;
            default:
                throw new LogicException('Validate: unexpected validator ID in spec check. Report this error.');
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
                } elseif (!is_callable($options['callback'])) {
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
                throw new LogicException('Validate: unexpected validator ID in spec check. Report this error.');
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
                    if (!is_int($options['amin'] ?? null) || !is_int($options['amax'] ?? null)) {
                        $this->specError(
                            [
                                'message' => $vname.'. "amin" and "amax" options must be int.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    } elseif ($options['amin'] < 0) {
                        $this->specError(
                            [
                                'message' => $vname.'. "amin" option must not be negative.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    } elseif ($options['amin'] > $options['amax']) {
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
                if (isset($options['key_callback']) && ($flags & VALIDATE_FLAG_ARRAY_KEY_ALNUM)) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has VALIDATE_FLAG_ARRAY_KEY_ALNUM flag that has no effect: "key_callback" option is defined.',
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
                if (isset($options['alimit']) && (!is_int($options['alimit']) || $options['alimit'] < ($options['amax'] ?? 0))) {
                    $this->specError(
                        [
                            'message' => $vname.' "alimit" option must be int and not smaller than "amax".',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                if (isset($options['alimit']) && !($flags & VALIDATE_FLAG_ARRAY)) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has "alimit" option but VALIDATE_FLAG_ARRAY is not set.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if (($flags & VALIDATE_FLAG_ARRAY_RECURSIVE) && !($flags & VALIDATE_FLAG_ARRAY)) {
                    $this->specWarning(
                        [
                            'message' => $vname.' has VALIDATE_FLAG_ARRAY_RECURSIVE but VALIDATE_FLAG_ARRAY is not set.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                }
                if (isset($options['filter']) && !is_callable($options['filter'])) {
                    $this->specError(
                        [
                            'message' => $vname.' "filter" option is not callable.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                if (isset($options['key_callback']) && !is_callable($options['key_callback'])) {
                    $this->specError(
                        [
                            'message' => $vname.' "key_callback" option is not callable.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                break;
            default:
                throw new LogicException('Validate: unexpected validator ID in spec check. Report this error.');
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
                throw new LogicException('Validate: unexpected validator ID in spec check. Report this error.');
        }

        // Misc checks
        switch ($spec[VALIDATE_ID]) {
            case VALIDATE_STRING:
                if (isset($options['ascii'])) {
                    if (!is_string($options['ascii'])) {
                        $this->specError(
                            [
                                'message' => $vname.' "ascii" option must be a string.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    } elseif (!preg_match('/\A[\x00-\x7F]*\z/', $options['ascii'])) {
                        $this->specError(
                            [
                                'message' => $vname.' "ascii" option must contain ASCII characters only.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
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
                if (isset($options['ascii'])) {
                    if (!is_string($options['ascii'])) {
                        $this->specError(
                            [
                                'message' => $vname.' "ascii" option must be a string.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    } elseif (!preg_match('/\A[\x00-\x7F]*\z/', $options['ascii'])) {
                        $this->specError(
                            [
                                'message' => $vname.' "ascii" option must contain ASCII characters only.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
                if (!(isset($options['regexp'])) || !is_string($options['regexp'])) {
                    $this->specError(
                        [
                            'message' => $vname.' has no "regexp" option or invalid value.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                } elseif (@preg_match($options['regexp'], '') === false) {
                    $this->specError(
                        [
                            'message' => $vname.' "regexp" option is not a valid PCRE pattern: '.$options['regexp'],
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                break;
            case VALIDATE_CALLBACK:
                if (isset($options['ascii'])) {
                    if (!is_string($options['ascii'])) {
                        $this->specError(
                            [
                                'message' => $vname.' "ascii" option must be a string.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    } elseif (!preg_match('/\A[\x00-\x7F]*\z/', $options['ascii'])) {
                        $this->specError(
                            [
                                'message' => $vname.' "ascii" option must contain ASCII characters only.',
                                'spec'    => $spec,
                                'flags'   => $str_flags
                            ]
                        );
                        $ret = false;
                    }
                }
                // fall through
            case VALIDATE_INT:
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
                        if (!is_int($k)) {
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
                } elseif (!is_string($spec[VALIDATE_OPTIONS]['resource']) || $spec[VALIDATE_OPTIONS]['resource'] === '') {
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
                if (isset($options['class']) && (!is_string($options['class']) || $options['class'] === '')) {
                    $this->specError(
                        [
                            'message' => $vname.' "class" option must be a non-empty class/interface name.',
                            'spec'    => $spec,
                            'flags'   => $str_flags
                        ]
                    );
                    $ret = false;
                }
                break;
            default:
                throw new LogicException('Validate: unexpected validator ID in spec check. Report this error.');
        }

        // "unicode" code point overrides
        if (isset($options['unicode'])) {
            $unicode_ok = is_array($options['unicode']);
            foreach ($unicode_ok ? $options['unicode'] : [] as $cp => $allow) {
                if (!is_int($cp) || $cp < 0x80 || $cp > 0x10FFFF || !is_bool($allow)) {
                    $unicode_ok = false;
                    break;
                }
            }
            if (!$unicode_ok) {
                $this->specError(
                    [
                        'message' => $vname.' "unicode" option must be an array of [code point (0x80-0x10FFFF) => bool].',
                        'spec'    => $spec,
                        'flags'   => $str_flags
                    ]
                );
                $ret = false;
            } elseif (!in_array($spec[VALIDATE_ID], [VALIDATE_STRING, VALIDATE_REGEXP, VALIDATE_CALLBACK], true)
                      || !($flags & VALIDATE_STRING_MB)) {
                $this->specWarning(
                    [
                        'message' => $vname.' has "unicode" option that has no effect. It needs a string validator with the _MB flag.',
                        'spec'    => $spec,
                        'flags'   => $str_flags
                    ]
                );
            }
        }

        // "transfer_encoding"
        if (isset($options['transfer_encoding'])) {
            if (!in_array($options['transfer_encoding'], ['quoted-printable', 'percent', 'base64', 'base64url', 'html'], true)) {
                $this->specError(
                    [
                        'message' => $vname.' "transfer_encoding" option must be "quoted-printable", "percent", '
                                    .'"base64", "base64url" or "html".',
                        'spec'    => $spec,
                        'flags'   => $str_flags
                    ]
                );
                $ret = false;
            } elseif (!in_array($spec[VALIDATE_ID], [VALIDATE_STRING, VALIDATE_REGEXP, VALIDATE_CALLBACK], true)) {
                $this->specWarning(
                    [
                        'message' => $vname.' has "transfer_encoding" option that has no effect. It needs a string validator.',
                        'spec'    => $spec,
                        'flags'   => $str_flags
                    ]
                );
            } elseif ($flags & VALIDATE_STRING_BINARY) {
                $this->specError(
                    [
                        'message' => $vname.' has "transfer_encoding" option, but the '.$vname.'_BINARY flag skips every check.',
                        'spec'    => $spec,
                        'flags'   => $str_flags
                    ]
                );
                $ret = false;
            }
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
