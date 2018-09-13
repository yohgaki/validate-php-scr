<?php
/**
 * Validate constants
 * From ext/validate/validate_private.h
 */


/* Validator type constants */
define('VALIDATE_INVALID',   0); // Not for users to use.
define('VALIDATE_NULL',      1);
define('VALIDATE_BOOL',      2);
define('VALIDATE_INT',       3);
define('VALIDATE_FLOAT',     4);
define('VALIDATE_STRING',    5);
define('VALIDATE_ARRAY',     6);
define('VALIDATE_CALLBACK',  7);
define('VALIDATE_REGEXP',    8);
define('VALIDATE_RESOURCE',  9);
define('VALIDATE_OBJECT',    10);
define('VALIDATE_MULTI',     11); /* Validate scalar with array of specs */
define('VALIDATE_LAST',      VALIDATE_MULTI); // Not for users to use. Only for spec validation that indicates the last validator
/* Special validators */
define('VALIDATE_REJECT',    20); // Not for users to use. Use reject flag for this.
define('VALIDATE_UNDEFINED', 21); // Not for users to use. Use reject flag for this.
define('VALIDATE_UNVALIDATED', 22); // Not for users to use. Use reject flag for this.


/****************************************************************/

/* VALIDATE_NULL flags */
define('VALIDATE_NULL_AS_STRING',          1 << 0); /* Validate as string (''), return as is (string) */
define('VALIDATE_NULL_LAST_BIT', 1); /* Used internally */

/* VALIDATE_BOOL flags */
define('VALIDATE_BOOL_AS_STRING',          1 << 0); /* Return validated value as is (string) */
define('VALIDATE_BOOL_01',                 1 << 1); /* "1" and "0" */
define('VALIDATE_BOOL_TF',                 1 << 2); /* "t" and "f" */
define('VALIDATE_BOOL_TRUE_FALSE',         1 << 3); /* "true" and "false" */
define('VALIDATE_BOOL_ON_OFF',             1 << 4); /* "on" and "off" */
define('VALIDATE_BOOL_YES_NO',             1 << 5); /* "yes" and "no" */
define('VALIDATE_BOOL_LAST_BIT', 6); /* Used internally */
/* VALIDATE_FLAG_UNDEFINED/EMPTY result in FALSE */

/* VALIDATE_INT flags */
define('VALIDATE_INT_AS_STRING',           1 << 0); /* Validate as int string. No type conversion. */
define('VALIDATE_INT_BIT',                 1 << 1); //TODO Implement this
define('VALIDATE_INT_OCTAL',               1 << 2); //TODO Implement this
define('VALIDATE_INT_HEX',                 1 << 3); //TODO Implement this
define('VALIDATE_INT_POSITIVE_SIGN',       1 << 4);
define('VALIDATE_INT_NEGATIVE_SIGN',       1 << 5);
define('VALIDATE_INT_LAST_BIT', 6); /* Used internally */

/* VALIDATE_FLOAT flags */
define('VALIDATE_FLOAT_AS_STRING',         1 << 0);
define('VALIDATE_FLOAT_FRACTION',          1 << 1); //TODO Implement this
define('VALIDATE_FLOAT_THOUSAND',          1 << 2); //TODO Implement this
define('VALIDATE_FLOAT_SCIENTIFIC',        1 << 3);
define('VALIDATE_FLOAT_POSITIVE_SIGN',     1 << 4);
define('VALIDATE_FLOAT_NEGATIVE_SIGN',     1 << 5);
define('VALIDATE_FLOAT_LAST_BIT', 6); /* Used internally */

/* VALIDATE_STRING flags */
define('VALIDATE_STRING_SPACE',            1 << 0);
define('VALIDATE_STRING_DIGIT',            1 << 1);
define('VALIDATE_STRING_TAB',              1 << 2);
define('VALIDATE_STRING_LF',               1 << 3);
define('VALIDATE_STRING_CR',               1 << 4);
define('VALIDATE_STRING_CRLF_MIXED',       1 << 5); // WARNING: This option allows broken CR/LF sequence
define('VALIDATE_STRING_CRLF',             (VALIDATE_STRING_LF | VALIDATE_STRING_CR));
define('VALIDATE_STRING_LOWER_ALPHA',      1 << 6);
define('VALIDATE_STRING_UPPER_ALPHA',      1 << 7);
define('VALIDATE_STRING_ALPHA',            (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA));
define('VALIDATE_STRING_ALNUM',            (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT));
define('VALIDATE_STRING_SYMBOL',           1 << 8); // WARNING: Dangerous option
define('VALIDATE_STRING_MB',               1 << 9); // Allow MultiByte chars
define('VALIDATE_STRING_BINARY',           1 << 10); // WARNING: Dangerous option
define('VALIDATE_STRING_RFC3454_C',        1 << 11); // Allow Unicode CNTRL char. ASCII code CNTRL is treated by above flags.
define('VALIDATE_STRING_RFC3454_D',        1 << 12); // Allow Unicode CNTRL char. ASCII code CNTRL is treated by above flags.
define('VALIDATE_STRING_LAST_BIT', 13); /* Used internally */

/* VALIDATE_CALLBACK */
define('VALIDATE_CALLBACK_SPACE',          VALIDATE_STRING_SPACE);
define('VALIDATE_CALLBACK_DIGIT',          VALIDATE_STRING_DIGIT);
define('VALIDATE_CALLBACK_TAB',            VALIDATE_STRING_TAB);
define('VALIDATE_CALLBACK_LF',             VALIDATE_STRING_LF);
define('VALIDATE_CALLBACK_CR',             VALIDATE_STRING_CR);
define('VALIDATE_CALLBACK_CRLF_MIXED',     VALIDATE_STRING_CRLF_MIXED); // WARNING: This option allows broken CR/LF sequence
define('VALIDATE_CALLBACK_CRLF',           (VALIDATE_STRING_LF | VALIDATE_STRING_CR));
define('VALIDATE_CALLBACK_LOWER_ALPHA',    VALIDATE_STRING_LOWER_ALPHA);
define('VALIDATE_CALLBACK_UPPER_ALPHA',    VALIDATE_STRING_UPPER_ALPHA);
define('VALIDATE_CALLBACK_ALPHA',          (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA));
define('VALIDATE_CALLBACK_ALNUM',          (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT));
define('VALIDATE_CALLBACK_SYMBOL',         VALIDATE_STRING_SYMBOL); // WARNING: Dangerous option
define('VALIDATE_CALLBACK_MB',             VALIDATE_STRING_MB); // Allow MultiByte chars
define('VALIDATE_CALLBACK_BINARY',         VALIDATE_STRING_BINARY); // WARNING: Dangerous option
define('VALIDATE_CALLBACK_RFC3454_C',      VALIDATE_STRING_RFC3454_C); // Allow Unicode CNTRL char. ASCII code CNTRL is treated by above flags.
define('VALIDATE_CALLBACK_LAST_BIT', VALIDATE_STRING_LAST_BIT); /* Used internally */

/* VALIDATE_REGEXP */
define('VALIDATE_REGEXP_SPACE',            VALIDATE_STRING_SPACE);
define('VALIDATE_REGEXP_DIGIT',            VALIDATE_STRING_DIGIT);
define('VALIDATE_REGEXP_TAB',              VALIDATE_STRING_TAB);
define('VALIDATE_REGEXP_LF',               VALIDATE_STRING_LF);
define('VALIDATE_REGEXP_CR',               VALIDATE_STRING_CR);
define('VALIDATE_REGEXP_CRLF_MIXED',       VALIDATE_STRING_CRLF_MIXED); // WARNING: This option allows broken CR/LF sequence
define('VALIDATE_REGEXP_CRLF',             (VALIDATE_STRING_LF | VALIDATE_STRING_CR));
define('VALIDATE_REGEXP_LOWER_ALPHA',      VALIDATE_STRING_LOWER_ALPHA);
define('VALIDATE_REGEXP_UPPER_ALPHA',      VALIDATE_STRING_UPPER_ALPHA);
define('VALIDATE_REGEXP_ALPHA',            (VALIDATE_STRING_LOWER_ALPHA | VALIDATE_STRING_UPPER_ALPHA));
define('VALIDATE_REGEXP_ALNUM',            (VALIDATE_STRING_ALPHA | VALIDATE_STRING_DIGIT));
define('VALIDATE_REGEXP_SYMBOL',           VALIDATE_STRING_SYMBOL); // WARNING: Dangerous option
define('VALIDATE_REGEXP_MB',               VALIDATE_STRING_MB); // Allow MultiByte chars
define('VALIDATE_REGEXP_BINARY',           VALIDATE_STRING_BINARY); // WARNING: Dangerous option
define('VALIDATE_REGEXP_RFC3454_C',        VALIDATE_STRING_RFC3454_C); // Allow Unicode CNTRL char. ASCII code CNTRL is treated by above flags.
define('VALIDATE_REGEXP_LAST_BIT', VALIDATE_STRING_LAST_BIT); /* Used internally */

/* VALIDATE_ARRAY */
/* No validator flag */

/* VALIDATE_RESOURCE */
/* No validator flag */

/* VALIDATE_OBJECT */
/* No validator flag */

/* VALIDATE_SCALAR_ARRAY */
define('VALIDATE_MULTI_AND',               1 << 0); /* Validate a value by multiple specs with AND condition. i.e All validations must success. */
define('VALIDATE_MULTI_OR',                1 << 1); /* Validate a value by multiple specs with OR condition. i.e One of validation must success. */
define('VALIDATE_MULTI_LAST_BIT', 2); /* Used internally */


/* General validator behavior flags */
//TODO: Implement changes in C module
define('VALIDATE_FLAG_NONE',                     0); /* No option flags */
define('VALIDATE_FLAG_RAW',                      1 << 16);  /* WARNING: Return RAW value. This could be dangerous. Consider this as debug feature. */
define('VALIDATE_FLAG_REJECT',                   1 << 17);  /* Reject if parameter is defined. */
define('VALIDATE_FLAG_UNDEFINED',                1 << 18);  /* Input is undefined and allow undefined, set empty null. '' when *_AS_STRING flag) */
define('VALIDATE_FLAG_UNDEFINED_TO_DEFAULT',     1 << 19);  /* Input is undefined, set default */
define('VALIDATE_FLAG_EMPTY',                    1 << 20);  /* Input is empty ('' or null), set null. '' when *_AS_STRING flag */
define('VALIDATE_FLAG_EMPTY_TO_DEFAULT',         1 << 21);  /* Input is empty ('' or null), set default */
define('VALIDATE_FLAG_NULL',                     1 << 22);  /* Accept NULL as valid input */
define('VALIDATE_FLAG_ARRAY',                    1 << 23);  /* Require array of defined scalars. i.e. script.php?val[]=1&val[]=2 */
define('VALIDATE_FLAG_ARRAY_RECURSIVE',          1 << 24);  /* Allow nested array. */
define('VALIDATE_FLAG_ARRAY_KEY_ALNUM',          1 << 25);  /* Allow alnum + '_' keys. By default, only INT is allowed for keys. Use "callback" option for custom key validation. */
define('VALIDATE_FLAG_WARNING',                  1 << 26);  /* WARNING: Dangerous option! Make validation error a warning error. i.e. Do not raise EXCEPTION / ERROR at all. */
define('VALIDATE_FLAG_NOTICE',                   1 << 27);  /* WARNING: Dangerous option! Make validation error a notice error. i.e. Do not raise EXCEPTION / ERROR at all. */
define('VALIDATE_FLAG_PASSTHRU',                 1 << 28);  /* WARNING: Dangerous option! Disables validation error & warning and logging. i.e. Behave as if there is no error/warning/notice. */
define('VALIDATE_FLAG_LAST_BIT', 29); /* Used internally */

// Flag Bit Mask
define('VALIDATE_FLAGS_LOWER',                   0x0000ffff); /* No use for users. */
define('VALIDATE_FLAGS_UPPER',                   0xffff0000); /* No use for users. */


/**************************************************************************/

//TODO: Script version only supports exception
/* validate() function behavior options */
define('VALIDATE_OPT_NONE',                     0);      // No options.
define('VALIDATE_OPT_CHECK_SPEC',               1 << 0); // DEFAULT. Check spec by validate_spec(). Disable this check for production use.
define('VALIDATE_OPT_DISABLE_EXCEPTION',        1 << 1); // InvalidArgumentException is raised by default.
define('VALIDATE_OPT_UNVALIDATED',              1 << 2); // Allow unvalidated values after validation. Unvalidated values are not allowed by default.
define('VALIDATE_OPT_KEEP_INPUTS',              1 << 3); // Keep input values. It removes(unset) validated values by default.
define('VALIDATE_OPT_RAISE_ERROR',              1 << 4); // Enable PHP errors. Useful to see what kind of errors happens with entire inputs.
define('VALIDATE_OPT_LOG_ERROR',                1 << 5); // Enable user logger by "logger" option function. Use validate_set_logger_function() to register logger.
define('VALIDATE_OPT_ERROR_FULL',               1 << 6); // Enable user logger by "logger" option function. Use validate_set_logger_function() to register logger.
define('VALIDATE_OPT_ERROR_PARAM',              1 << 7); // Enable user logger by "logger" option function. Use validate_set_logger_function() to register logger.
define('VALIDATE_OPT_ERROR_SQUASH',             1 << 8); // Enable user logger by "logger" option function. Use validate_set_logger_function() to register logger.
define('VALIDATE_OPT_LAST_BIT', 9); /* Used internally */

// Opt Bit Mask
define('VALIDATE_OPT_UPPER',                    0xfffffe00); /* No use for users. */


/**************************************************************************/
/* Validate Spec Array Offsets */
define('VALIDATE_ID',      0); // Validator ID
define('VALIDATE_FLAGS',   1); // Validator Flags
define('VALIDATE_OPTIONS', 2); // Validator Options
define('VALIDATE_PARAMS',  3); // Array validator parameters
define('VALIDATE_SPECS',   3); // Multi spec validation specs
