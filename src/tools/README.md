# What is this?

These simple PHP scripts are for generating Validate PHP spec and validate inputs
automatically.

## How to use

There are 3 steps.

  1. Add 'input_logger.php' to php.ini's 'auto_prepend_file', and log application inputs. (Make sure enough requests are logged.)
  1. Generate spec by executing 'input_analyzer.php'. e.g. php input_analyzer.php
  1. Add 'input_validator.php' to php.ini's 'auto_prepend_file'. (Replace 'input_logger.php' to 'input_validator.php'.)

Then validation is performed. By default, input logs and spec.php/stat.php are logged in '/var/tmp/validate'.
Alternatively, you may require_once() 'input_logger.php' and 'input_validator.php'.

*IMPORTANT:* By default, input_validator.php will not raise error for validation errors, but
logs errors by error_log().

## Status and Limitations

Since it is impossible to determine precise input value specification, it cannot
generate strict validation spec. However, it is useful because Validate PHP and
generated spec does not allow

 * Broken UTF-8 encoding
 * Symbols unless symbol is learned
 * Too long inputs

Validation SPEC optimization - work in progress. Optimization is poor. Followings are TODOs.

 * Default validation rule that excludes control chars.
 Optional parameters detection. 10 extra parameters are allowed by default currently.
 * Data type detection. (int/float/bool)
 * Parameter hint definition support.
 * It does not raise exception by default.
