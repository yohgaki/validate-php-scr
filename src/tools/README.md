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

The input logs, `stat.php` and `spec.php` live in one directory:

| Setting | Used by | Default | Meaning |
|---|---|---|---|
| `VALIDATE_TOOLS_DIR` (environment variable) | all three scripts | `/var/tmp/validate` | Log / spec directory. `input_analyzer.php` also takes it as its first argument, which wins over the variable. |

The directory is created with mode 0700 and log files with mode 0600. Every
script refuses to use the directory unless it is not a symlink, is not
group/world writable and (when the posix extension is loaded) is owned by the
current user (`*-log.php` and `spec.php` are `include()`d, so a directory
another user can write to would mean code execution). Logs contain every
request body (passwords, cookies) in cleartext, and `stat.php` keeps up to 999
distinct sample values per parameter; `input_analyzer.php` writes `stat.php`
and `spec.php` with the process umask, so the 0700 directory is what protects
them. Run the logger only in a controlled environment.

If `auto_prepend_file` is not available (e.g. shared hosting), `require_once`
the scripts manually from a bootstrap file instead.

*IMPORTANT:* `input_validator.php` does NOT throw on validation failure by
default. Errors are sent to `error_log()` (parameter paths and messages only,
no input values) and stashed in `$GLOBALS['_validate_errors_']`. Flip the
`$exception` switch inside the script to make failures throw
`ValidateInvalidValueException`. Input keys that the generated spec does not
declare are validation errors, and so is a request whose `METHOD:PATH` has no
spec: an endpoint that was not logged, or another spelling of a logged path
(`/index.php/x`, `//index.php`). Such a request is reported like any other
failure ("No spec for this METHOD:PATH."); before 2026-10-18 it passed
unvalidated.

## Status and Limitations

Inferring an input spec from observed traffic is necessarily approximate — a
generated spec is a lower bound (rules every recorded request satisfies),
not a true specification. It still gives a meaningful safety net, because
Validate PHP rejects:

 * Broken UTF-8 encoding
 * Any symbol that did not appear during logging
 * Parameters that did not appear during logging
 * Strings longer than a length bucket chosen from the recorded maximum:
   up to 50 bytes seen -> max 100, 51-300 -> max 1024, longer -> max 262144

Spec optimization is still rough. Outstanding TODOs:

 * Add a default rule that excludes control characters.
 * Detect which parameters are optional. Today every parameter seen in the
   logs is required (no VALIDATE_FLAG_UNDEFINED), so a request that omits one
   fails; the generated array 'max' only adds a headroom of 10 to the count.
 * Detect numeric inputs as int/float/bool instead of treating them as strings.
 * Honor parameter hints from key names (email, password, sessid, ...).
 * Switch the default to raising exceptions on validation failure.
