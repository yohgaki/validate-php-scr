<?php
/**
 * Validate constants.
 *
 * Defines every VALIDATE_* constant used by the framework:
 *   - Validator type IDs (VALIDATE_INT, VALIDATE_STRING, ...)
 *   - Per-type flags (VALIDATE_<TYPE>_*) carried in spec[VALIDATE_FLAGS]
 *   - Global behavior flags (VALIDATE_FLAG_*) shared by every validator
 *   - validate() function-level options (VALIDATE_OPT_*)
 *   - Spec array offset constants (VALIDATE_ID, VALIDATE_FLAGS, ...)
 *
 * Mirrors ext/validate/validate_private.h in the planned C extension —
 * keep both in sync when adding new constants.
 */


/* Validator type constants — used as spec[VALIDATE_ID]. */
define('VALIDATE_INVALID', 0); // Internal sentinel; never set by users.
define('VALIDATE_NULL', 1);
define('VALIDATE_BOOL', 2);
define('VALIDATE_INT', 3);
define('VALIDATE_FLOAT', 4);
define('VALIDATE_STRING', 5);
define('VALIDATE_ARRAY', 6);
define('VALIDATE_CALLBACK', 7);
define('VALIDATE_REGEXP', 8);
define('VALIDATE_RESOURCE', 9);
define('VALIDATE_OBJECT', 10);
define('VALIDATE_MULTI', 11); /* Validate one scalar against multiple specs (see VALIDATE_MULTI_AND/OR). */
define('VALIDATE_LAST', VALIDATE_MULTI); // Internal: highest valid type ID, used to bound spec validation loops.
/* Special validators (internal — users should not pass these as a spec ID) */
define('VALIDATE_REJECT', 20); // Internal: produced by VALIDATE_FLAG_REJECT when a rejected input is present.
define('VALIDATE_UNDEFINED', 21); // Internal: produced by VALIDATE_FLAG_UNDEFINED when input is absent.
define('VALIDATE_UNVALIDATED', 22); // Internal: marker for inputs left unvalidated (only with VALIDATE_OPT_UNVALIDATED).


/****************************************************************/

/*
 * Per-type flags are stored in spec[VALIDATE_FLAGS] and only have meaning for the
 * matching validator type. Each block ends with *_LAST_BIT, the bit count used
 * internally to detect unknown flags.
 */

/* VALIDATE_NULL flags */
define('VALIDATE_NULL_AS_STRING', 1 << 0); /* Treat empty string ('') as null and return it as a string. */
define('VALIDATE_NULL_LAST_BIT', 1); /* Used internally — bit count for this type's flags. */

/* VALIDATE_BOOL flags — pick which textual forms are accepted as boolean. */
define('VALIDATE_BOOL_AS_STRING', 1 << 0); /* Return the validated value as the original string instead of bool. */
define('VALIDATE_BOOL_01', 1 << 1); /* Accept "1" and "0". */
define('VALIDATE_BOOL_TF', 1 << 2); /* Accept "t" and "f". */
define('VALIDATE_BOOL_TRUE_FALSE', 1 << 3); /* Accept "true" and "false". */
define('VALIDATE_BOOL_ON_OFF', 1 << 4); /* Accept "on" and "off". */
define('VALIDATE_BOOL_YES_NO', 1 << 5); /* Accept "yes" and "no". */
define('VALIDATE_BOOL_LAST_BIT', 6); /* Used internally — bit count for this type's flags. */
/* Note: For VALIDATE_BOOL, VALIDATE_FLAG_UNDEFINED / VALIDATE_FLAG_EMPTY make
   the validator yield FALSE (rather than null) when the input is absent/empty. */

/* VALIDATE_INT flags */
define('VALIDATE_INT_AS_STRING', 1 << 0); /* Accept the input only as a numeric string; return it without type conversion. */
define('VALIDATE_INT_BIT', 1 << 1); /* TODO: accept binary literals (e.g. "0b1010"). Not implemented yet. */
define('VALIDATE_INT_OCTAL', 1 << 2); /* TODO: accept octal literals (e.g. "0755"). Not implemented yet. */
define('VALIDATE_INT_HEX', 1 << 3); /* TODO: accept hexadecimal literals (e.g. "0xff"). Not implemented yet. */
define('VALIDATE_INT_POSITIVE_SIGN', 1 << 4); /* Allow an explicit leading '+' sign. */
define('VALIDATE_INT_NEGATIVE_SIGN', 1 << 5); /* Allow a leading '-' sign (negative values). */
define('VALIDATE_INT_LAST_BIT', 6); /* Used internally — bit count for this type's flags. */

/* VALIDATE_FLOAT flags */
define('VALIDATE_FLOAT_AS_STRING', 1 << 0); /* Accept the input only as a numeric string; return it without type conversion. */
define('VALIDATE_FLOAT_FRACTION', 1 << 1); /* TODO: require/allow a fractional part. Not implemented yet. */
define('VALIDATE_FLOAT_THOUSAND', 1 << 2); /* TODO: allow thousand separators (e.g. "1,234.56"). Not implemented yet. */
define('VALIDATE_FLOAT_SCIENTIFIC', 1 << 3); /* Allow scientific notation (e.g. "1.2e3"). */
define('VALIDATE_FLOAT_POSITIVE_SIGN', 1 << 4); /* Allow an explicit leading '+' sign. */
define('VALIDATE_FLOAT_NEGATIVE_SIGN', 1 << 5); /* Allow a leading '-' sign (negative values). */
define('VALIDATE_FLOAT_LAST_BIT', 6); /* Used internally — bit count for this type's flags. */

/*
 * VALIDATE_STRING flags — whitelist of character classes that are permitted.
 * String validation rejects every character by default; each flag opens up
 * one class. Use the 'ascii' option to also allow specific extra characters.
 */
define('VALIDATE_STRING_SPACE', 1 << 0); /* Allow ASCII space (0x20). */
define('VALIDATE_STRING_DIGIT', 1 << 1); /* Allow ASCII digits 0-9. */
define('VALIDATE_STRING_TAB', 1 << 2); /* Allow tab (0x09). */
define('VALIDATE_STRING_LF', 1 << 3); /* Allow line feed (0x0A). */
define('VALIDATE_STRING_CR', 1 << 4); /* Allow carriage return (0x0D). */
define('VALIDATE_STRING_CRLF_MIXED', 1 << 5); // WARNING: allows lone CR or LF mixed with CRLF — accepts broken line endings.
define('VALIDATE_STRING_CRLF', (VALIDATE_STRING_LF | VALIDATE_STRING_CR)); /* Shortcut: allow both CR and LF (use for full CRLF support). */
define('VALIDATE_STRING_LOWER_ALPHA', 1 << 6); /* Allow ASCII a-z. */
define('VALIDATE_STRING_UPPER_ALPHA', 1 << 7); /* Allow ASCII A-Z. */
define('VALIDATE_STRING_ALPHA', (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA)); /* Shortcut: both alpha cases. */
define('VALIDATE_STRING_ALNUM', (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT)); /* Shortcut: letters + digits. */
define('VALIDATE_STRING_SYMBOL', 1 << 8); // WARNING: Dangerous — opens up all printable ASCII symbols. Prefer the 'ascii' option to whitelist a few.
define('VALIDATE_STRING_MB', 1 << 9); // Allow multibyte (UTF-8) characters.
define('VALIDATE_STRING_BINARY', 1 << 10); // WARNING: Dangerous — allows arbitrary binary bytes including NUL.
define('VALIDATE_STRING_RFC3454_C', 1 << 11); // Allow Unicode control characters from RFC 3454 table C; ASCII control chars are still gated by the flags above.
define('VALIDATE_STRING_RFC3454_D', 1 << 12); // Allow Unicode bidirectional characters from RFC 3454 table D; ASCII control chars are still gated by the flags above.
define('VALIDATE_STRING_LAST_BIT', 13); /* Used internally — bit count for this type's flags. */

/*
 * VALIDATE_CALLBACK flags — aliases of the VALIDATE_STRING_* flags.
 * They control which characters the engine pre-validates *before* the user
 * callback runs, so the callback can assume the input is already whitelisted.
 */
define('VALIDATE_CALLBACK_SPACE', VALIDATE_STRING_SPACE);
define('VALIDATE_CALLBACK_DIGIT', VALIDATE_STRING_DIGIT);
define('VALIDATE_CALLBACK_TAB', VALIDATE_STRING_TAB);
define('VALIDATE_CALLBACK_LF', VALIDATE_STRING_LF);
define('VALIDATE_CALLBACK_CR', VALIDATE_STRING_CR);
define('VALIDATE_CALLBACK_CRLF_MIXED', VALIDATE_STRING_CRLF_MIXED); // WARNING: This option allows broken CR/LF sequence
define('VALIDATE_CALLBACK_CRLF', (VALIDATE_STRING_LF | VALIDATE_STRING_CR));
define('VALIDATE_CALLBACK_LOWER_ALPHA', VALIDATE_STRING_LOWER_ALPHA);
define('VALIDATE_CALLBACK_UPPER_ALPHA', VALIDATE_STRING_UPPER_ALPHA);
define('VALIDATE_CALLBACK_ALPHA', (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA));
define('VALIDATE_CALLBACK_ALNUM', (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT));
define('VALIDATE_CALLBACK_SYMBOL', VALIDATE_STRING_SYMBOL); // WARNING: Dangerous option
define('VALIDATE_CALLBACK_MB', VALIDATE_STRING_MB); // Allow MultiByte chars
define('VALIDATE_CALLBACK_BINARY', VALIDATE_STRING_BINARY); // WARNING: Dangerous option
define('VALIDATE_CALLBACK_RFC3454_C', VALIDATE_STRING_RFC3454_C); // Allow Unicode CNTRL char. ASCII code CNTRL is treated by above flags.
define('VALIDATE_CALLBACK_RFC3454_D', VALIDATE_STRING_RFC3454_D); // Allow Unicode CNTRL char. ASCII code CNTRL is treated by above flags.
define('VALIDATE_CALLBACK_LAST_BIT', VALIDATE_STRING_LAST_BIT); /* Used internally — bit count for this type's flags. */

/*
 * VALIDATE_REGEXP flags — aliases of the VALIDATE_STRING_* flags.
 * They control which characters the engine pre-validates *before* the regexp
 * is applied, so the pattern can assume the input is already whitelisted.
 */
define('VALIDATE_REGEXP_SPACE', VALIDATE_STRING_SPACE);
define('VALIDATE_REGEXP_DIGIT', VALIDATE_STRING_DIGIT);
define('VALIDATE_REGEXP_TAB', VALIDATE_STRING_TAB);
define('VALIDATE_REGEXP_LF', VALIDATE_STRING_LF);
define('VALIDATE_REGEXP_CR', VALIDATE_STRING_CR);
define('VALIDATE_REGEXP_CRLF_MIXED', VALIDATE_STRING_CRLF_MIXED); // WARNING: This option allows broken CR/LF sequence
define('VALIDATE_REGEXP_CRLF', (VALIDATE_STRING_LF | VALIDATE_STRING_CR));
define('VALIDATE_REGEXP_LOWER_ALPHA', VALIDATE_STRING_LOWER_ALPHA);
define('VALIDATE_REGEXP_UPPER_ALPHA', VALIDATE_STRING_UPPER_ALPHA);
define('VALIDATE_REGEXP_ALPHA', (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA));
define('VALIDATE_REGEXP_ALNUM', (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT));
define('VALIDATE_REGEXP_SYMBOL', VALIDATE_STRING_SYMBOL); // WARNING: Dangerous option
define('VALIDATE_REGEXP_MB', VALIDATE_STRING_MB); // Allow MultiByte chars
define('VALIDATE_REGEXP_BINARY', VALIDATE_STRING_BINARY); // WARNING: Dangerous option
define('VALIDATE_REGEXP_RFC3454_C', VALIDATE_STRING_RFC3454_C); // Allow Unicode CNTRL char. ASCII code CNTRL is treated by above flags.
define('VALIDATE_REGEXP_RFC3454_D', VALIDATE_STRING_RFC3454_D); // Allow Unicode CNTRL char. ASCII code CNTRL is treated by above flags.
define('VALIDATE_REGEXP_LAST_BIT', VALIDATE_STRING_LAST_BIT); /* Used internally — bit count for this type's flags. */

/* VALIDATE_ARRAY — no per-type flags; configure with the global VALIDATE_FLAG_* flags and the 'amin'/'amax' options. */

/* VALIDATE_RESOURCE — no per-type flags; configure via the 'restype' option. */

/* VALIDATE_OBJECT — no per-type flags; configure via the 'class' option. */

/* VALIDATE_MULTI flags — combine multiple sub-specs against a single scalar. */
define('VALIDATE_MULTI_AND', 1 << 0); /* All sub-specs must pass. */
define('VALIDATE_MULTI_OR', 1 << 1); /* At least one sub-spec must pass. */
define('VALIDATE_MULTI_LAST_BIT', 2); /* Used internally — bit count for this type's flags. */


/*
 * General (cross-type) validator behavior flags.
 *
 * These occupy the upper 16 bits of spec[VALIDATE_FLAGS] so they don't collide
 * with the per-type flags above (which live in the lower 16 bits).
 */
//TODO: Implement changes in C module
define('VALIDATE_FLAG_NONE', 0); /* No flags set. */
define('VALIDATE_FLAG_RAW', 1 << 16);  /* WARNING: Return the raw input without conversion. Debug-only — bypasses type normalization. */
define('VALIDATE_FLAG_REJECT', 1 << 17);  /* Treat the parameter as forbidden — validation fails if it is present. */
define('VALIDATE_FLAG_UNDEFINED', 1 << 18);  /* Allow absent input; yield null (or '' when the *_AS_STRING flag is also set). */
define('VALIDATE_FLAG_UNDEFINED_TO_DEFAULT', 1 << 19);  /* When input is absent, substitute the 'default' option value. */
define('VALIDATE_FLAG_EMPTY', 1 << 20);  /* Allow empty input ('' or null); yield null (or '' with *_AS_STRING). */
define('VALIDATE_FLAG_EMPTY_TO_DEFAULT', 1 << 21);  /* When input is empty, substitute the 'default' option value. */
define('VALIDATE_FLAG_NULL', 1 << 22);  /* Accept the actual NULL value as valid input. */
define('VALIDATE_FLAG_ARRAY', 1 << 23);  /* Require an array of scalars (e.g. script.php?val[]=1&val[]=2); each element is validated by the same spec. */
define('VALIDATE_FLAG_ARRAY_RECURSIVE', 1 << 24);  /* When combined with VALIDATE_FLAG_ARRAY, also allow nested arrays. */
define('VALIDATE_FLAG_ARRAY_KEY_ALNUM', 1 << 25);  /* Allow alnum + '_' string keys. Without this, only int keys are accepted. Use the 'key_callback' option for custom rules. */
define('VALIDATE_FLAG_WARNING', 1 << 26);  /* WARNING: Dangerous — downgrade a validation failure to an E_WARNING instead of raising an exception. */
define('VALIDATE_FLAG_NOTICE', 1 << 27);  /* WARNING: Dangerous — downgrade a validation failure to an E_NOTICE instead of raising an exception. */
define('VALIDATE_FLAG_PASSTHRU', 1 << 28);  /* WARNING: Dangerous — silently accept invalid input (no exception, no error, no logging). */
define('VALIDATE_FLAG_LAST_BIT', 29); /* Used internally — highest bit position in use. */

// Bit masks used internally to split per-type flags (lower 16 bits) from general flags (upper 16 bits).
define('VALIDATE_FLAGS_LOWER', 0x0000ffff); /* Internal — mask for per-type flags. */
define('VALIDATE_FLAGS_UPPER', 0xffff0000); /* Internal — mask for general VALIDATE_FLAG_* flags. */


/**************************************************************************/

/*
 * validate() function-level options.
 *
 * These are passed as the 4th argument of validate() and control runtime
 * behavior of the call itself rather than the per-field validation rules.
 *
 * NOTE: The script version currently only supports the exception code path;
 * the C module will extend support for the warning/notice/passthru modes.
 */
define('VALIDATE_OPT_NONE', 0);      // No options.
define('VALIDATE_OPT_CHECK_SPEC', 1 << 0); // DEFAULT-ON. Validate the spec array itself via validate_spec(). Disable in production for speed.
define('VALIDATE_OPT_DISABLE_EXCEPTION', 1 << 1); // Suppress the InvalidArgumentException raised on validation failure; the call returns null instead.
define('VALIDATE_OPT_UNVALIDATED', 1 << 2); // Permit leftover unvalidated keys in $inputs after validation (by default they cause an error).
define('VALIDATE_OPT_KEEP_INPUTS', 1 << 3); // Preserve $inputs as-is. By default validate() unsets validated keys so the caller can detect leftovers.
define('VALIDATE_OPT_RAISE_ERROR', 1 << 4); // Raise PHP errors for failures in addition to recording them — useful for surfacing all errors in a single pass.
define('VALIDATE_OPT_LOG_ERROR', 1 << 5); // Call the user-supplied logger (see validate_set_logger_function()) for each error.
/*
 * Error storage layout — these three are mutually exclusive. They control how
 * errors recorded in the context are keyed:
 *   FULL:   nested array mirroring the spec tree (good for showing per-field errors in deep forms)
 *   PARAM:  flat array keyed by the leaf parameter name (default behavior; matches most form UIs)
 *   SQUASH: flat array keyed by the joined dotted path "a=>b=>c" (good for logging)
 */
define('VALIDATE_OPT_ERROR_FULL', 1 << 6);
define('VALIDATE_OPT_ERROR_PARAM', 1 << 7);
define('VALIDATE_OPT_ERROR_SQUASH', 1 << 8);
define('VALIDATE_OPT_LAST_BIT', 9); /* Used internally — highest bit position in use. */

// Bit mask covering unused option bits; used internally to detect unknown options.
define('VALIDATE_OPT_UPPER', 0xfffffe00); /* Internal — reserved bits not exposed to users. */


/**************************************************************************/
/*
 * Spec array offsets.
 *
 * Every validation spec is a plain PHP array of up to 4 elements:
 *   $spec[VALIDATE_ID]      => int     validator type (VALIDATE_INT, VALIDATE_STRING, ...)
 *   $spec[VALIDATE_FLAGS]   => int     bitfield of per-type + general flags
 *   $spec[VALIDATE_OPTIONS] => array   options ('min', 'max', 'ascii', 'default', ...)
 *   $spec[VALIDATE_PARAMS]  => array   sub-specs (only for VALIDATE_ARRAY)
 *   $spec[VALIDATE_SPECS]   => array   sub-specs (only for VALIDATE_MULTI — same slot as PARAMS)
 */
define('VALIDATE_ID', 0);      // Validator type ID slot.
define('VALIDATE_FLAGS', 1);   // Flag bitfield slot.
define('VALIDATE_OPTIONS', 2); // Options array slot.
define('VALIDATE_PARAMS', 3);  // Sub-specs slot for VALIDATE_ARRAY.
define('VALIDATE_SPECS', 3);   // Sub-specs slot for VALIDATE_MULTI (same offset as PARAMS).
