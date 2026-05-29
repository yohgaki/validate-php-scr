# What is this?

A small set of PHP scripts that auto-generate a Validate PHP spec from real
request traffic and apply it on every subsequent request. Useful for retrofitting
input validation onto an existing application that has no specs yet.

## How to use

Three steps:

  1. Set `input_logger.php` as `auto_prepend_file` in `php.ini` and replay
     enough representative traffic so every legitimate code path is recorded.
  2. Generate the spec by running `input_analyzer.php` against the log
     directory: `php input_analyzer.php [/path/to/log_dir]`.
  3. Swap `auto_prepend_file` from `input_logger.php` to `input_validator.php`
     so each request is validated against the generated spec.

By default the input logs, `stat.php` and `spec.php` are written under
`/var/tmp/validate/`. Override the path inside each script if you need to.
If `auto_prepend_file` is not available (e.g. shared hosting), `require_once`
the scripts manually from a bootstrap file instead.

*IMPORTANT:* `input_validator.php` does NOT throw on validation failure by
default. Errors are sent to `error_log()` and stashed in
`$GLOBALS['_validate_errors_']`. Flip the `$exception` switch inside the
script to make failures raise `InvalidArgumentException`.

## Status and Limitations

Inferring an input spec from observed traffic is necessarily approximate — a
generated spec is a lower bound (rules every recorded request satisfies),
not a true specification. It still gives a meaningful safety net, because
Validate PHP rejects:

 * Broken UTF-8 encoding
 * Any symbol that did not appear during logging
 * Inputs longer than the recorded maximum (with a small slack)

Spec optimization is still rough. Outstanding TODOs:

 * Add a default rule that excludes control characters.
 * Detect which parameters are optional (today the generator simply allows
   10 extra parameters per array as headroom).
 * Detect numeric inputs as int/float/bool instead of treating them as strings.
 * Honor parameter hints from key names (email, password, sessid, ...).
 * Switch the default to raising exceptions on validation failure.
