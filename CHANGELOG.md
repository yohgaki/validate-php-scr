# Changelog

## Unreleased

Design review (2026-10-16, commit d622eb4) and its follow-ups (2026-10-16):
`basic_types.php` format fixes, the `VALIDATE_ARRAY` `undeclared` option,
example/tool fixes, and remembering checked specs. Branches for older PHP
versions and fixes for PHP 8.6 / 8.7.0-dev (2026-10-17).

### Branch `php-5.x`

* Created from `php-7.x` (2026-10-17) for PHP 5.3 - 5.6. Differences from
  `master`:
  * PHP 5.3 syntax in all sources, tests, examples, tools and the code of
    README / REFERENCE: `array()` for `[]`, `isset() ? :` for `??`, no
    `(new Validate)->` one-liner, `call_user_func()` /
    `call_user_func_array()` for callbacks (PHP 5.3 cannot call
    `array($class, $method)` as `$f()`), no `finally`, `\u{}` or
    `foreach (... as list())` in tests.
  * `json` checks the input with `$jsonCheck` only: `json_decode()` of PHP 5
    accepts `TRUE`, `+1`, `07`, `[1.]`, lone `\ud800` and control characters
    in strings, and reports `[1]]` as a state mismatch.
  * `VALIDATE_INT` accepts the GMP resources of PHP 5.3 - 5.5.
  * As in `php-7.x`: the internal class constants of `Validate` have no
    visibility, and `Validate::pregLastErrorMsg()` replaces
    `preg_last_error_msg()`.
  * `src/run-tests.php` is the one of PHP 5.5.38 (no `--show-diff`; CI sets
    `REPORT_EXIT_STATUS=1`, without which it exits 0 on failures).
  * Tests: PHP 5 `assert()` warnings, the PHP 5 uncaught-exception message,
    a float printer for `var_export()` (PHP 5 prints `1.0` as `1`),
    `pack('H*')` for `hex2bin()` (PHP 5.4).
  * Tools: `mt_rand()` when `random_bytes()` is missing; `PHP_INT_MIN` is
    defined when missing.
  * GitHub Actions tests PHP 5.3 - 5.6; `.gitlab-ci.yml` tests PHP 5.5 and
    5.6 (current Docker cannot pull the `php:5.3` / `php:5.4` images, which
    use manifest schema 1); `composer.json` requires `php` `^5.3`.
* Checked on PHP 5.3.29 and 5.4.45 (built from source in Docker) and 5.5.38
  and 5.6.40 (official Docker images), all with BCMath and GMP: all tests
  pass; the outputs of `dev/basic_types_corpus.php`, of the examples and of
  the `src/tools` flow (logger, analyzer, validator) equal `master`'s on
  PHP 8.5.11; `dev/fuzz_inputs.php` gives the same results as `master` on
  PHP 8.6.0RC3 for 120,000 JSON inputs and on PHP 8.5.11 for 80,000 URLs
  (seeds as in the `json` / `url` entries below).

### Changed — behavior changes that can affect existing code

* **Undeclared input keys are errors.** Every `VALIDATE_ARRAY` level now reports
  input keys that its spec does not declare ("Validate: Unvalidated value
  remains. Undeclared keys: ..."), as `validate_defs.php` and README already
  promised. Before, the check only ran for a top-level `VALIDATE_ARRAY` that also
  carried `VALIDATE_FLAG_ARRAY`, so extra keys were silently accepted.
  Multi-stage validation must pass `VALIDATE_OPT_UNVALIDATED` to every stage
  except the last (see example 01); to validate keys that cannot be listed in
  advance, such as HTTP headers, use the new `undeclared` option instead
  (examples 94, 95, 99-web).
* **Exceptions.** Value failures throw `ValidateInvalidValueException`, spec
  errors found by `VALIDATE_OPT_CHECK_SPEC` throw `ValidateInvalidSpecException`
  — both extend `InvalidArgumentException`, so existing `catch` blocks still
  work. `Validate::validate()` (OO entry) now throws like `validate()` instead
  of triggering `E_USER_ERROR`, and `validate()` no longer `print_r()`s spec
  errors to the output.
* **`VALIDATE_MULTI_OR`** tries alternatives speculatively: a failing
  alternative no longer throws (before, the first failing alternative threw
  unless exceptions were disabled) and no longer leaves its errors in the
  context.
* **NULL / presence.** A key holding `null` is *present* (`array_key_exists()`),
  so `VALIDATE_FLAG_NULL` works for array parameters and elements and yields
  `null`; `VALIDATE_FLAG_EMPTY` also accepts `null`. An absent
  `VALIDATE_FLAG_REJECT` parameter is no longer a "required parameter" error.
* **Integer strings** must be `0` or digits without leading zeros: `"-"`, `"+"`,
  `"-0"` and `"007"` are now rejected with `VALIDATE_INT_AS_STRING` too.
* `validate_warning()` / `validate_notice()` from a callback no longer fail
  validation; only errors do.
* `VALIDATE_OPT_RAISE_ERROR` defaults to `E_USER_WARNING` (PHP 8.4 deprecated
  `trigger_error(E_USER_ERROR)`); `validate_set_error_level()` throws for
  levels other than `E_USER_ERROR` / `E_USER_WARNING` / `E_USER_NOTICE`.
* `VALIDATE_OPT_KEEP_INPUTS` leaves the caller's array completely untouched
  (no injected `default`, no `filter` result).
* `alimit` counts elements across all nesting levels (`VALIDATE_FLAG_ARRAY_RECURSIVE`).
* `VALIDATE_STRING_BINARY` returns a string; a validated `VALIDATE_ARRAY`
  yields an array (`[]` when empty) instead of `null`.
* `VALIDATE_INT_AS_STRING` returns a string also for int input (int input used
  to come back as int), matching `VALIDATE_FLOAT_AS_STRING`. Affects
  `uint64` / `int128` / `uint128` and, on 32-bit builds, `uint32` / `int53` /
  `uint53` / `int64`.
* `basic_types.php`: `url` / `active_url` accept only `http` / `https`;
  `ipv4` / `cidr4` reject leading zeros; `ipv6` uses
  `filter_var(FILTER_FLAG_IPV6)` (accepts `::` and IPv4-embedded forms);
  `hostname` is one RFC 1123 label; header specs trim only SP/HTAB; DNS checks
  query absolute names for explicit record types instead of `DNS_ANY`.
* `basic_types.php`: `base64*`, `base64url*` and `urle*` are `VALIDATE_REGEXP`
  specs (were `VALIDATE_STRING`) so they can check where `=` and `%` appear.
* `basic_types.php` `url` / `active_url` accept the same URLs on every PHP
  version. `FILTER_VALIDATE_URL` changed in PHP 7.0, 7.3 and 8.2; on PHP 8.0 /
  8.1 it accepted host names ending with `-` (`http://example-/`) and invalid
  userinfo with an `[IPv6]` host (`http://a@b@[::1]/`). Before PHP 8.2 the
  specs apply the PHP 8.2+ rules (`php_filter_validate_url()` and
  `php_url_parse_ex2()` of php-src) themselves, and `active_url` resolves the
  host found by them. 80,000 URLs from `dev/fuzz_inputs.php gen url` (seeds
  11 and 12, 40,000 each) give the same results on PHP 8.0 / 8.1 as with
  `filter_var()` on PHP 8.5.11.
* `basic_types.php` `json`: the error message is fixed per `json_last_error()`
  code (e.g. "Invalid JSON: Syntax error") instead of `json_last_error_msg()`,
  whose text depends on the PHP version (8.6 appends "near location 1:2").
  PHP < 8.6 reports an unterminated string (`["abc`) as a control character
  error and 8.6+ as a syntax error; on PHP < 8.6 the spec tells them apart
  with the PHP 8.6+ rules, so every PHP version reports a syntax error.
  Objects are decoded as arrays, so keys a PHP object cannot hold
  (`{"\u0000a":1}`) are valid JSON. 120,000 inputs from
  `dev/fuzz_inputs.php gen json` (seeds 1 - 4, 30,000 each) give the same
  results on PHP 8.0 / 8.5 as on PHP 8.6.0RC3.

### Fixed

* `VALIDATE_REGEXP` cast the input to a string before checking its type: an
  array raised "Array to string conversion" and an object a fatal `Error`.
  Both are now reported as "Array or object parameter is passed for scalar."
* PHP 8.6 deprecated `is_double()`: every float input to `VALIDATE_FLOAT`
  raised a deprecation notice there; it uses `is_float()`.
* DEL (0x7F) with `VALIDATE_STRING_MB` triggered "Report this error" (a fatal
  `E_USER_ERROR`); it is now an illegal ASCII control char.
* A UTF-8 string `"0"` failed the `_MB` encoding check.
* A rejected Unicode control char still returned success from the string
  validator (it could pass through `VALIDATE_MULTI_OR`).
* The `unicode` option was rejected by `validate_spec()` and ignored together
  with `VALIDATE_STRING_RFC3454_C`.
* `VALIDATE_INT` `values` rejected every string input.
* `VALIDATE_BOOL` with `VALIDATE_FLAG_EMPTY_TO_DEFAULT` failed at top level.
* `VALIDATE_FLAG_ARRAY`: `null` / object input threw `TypeError`; an
  `EMPTY_TO_DEFAULT` default array was never validated; nested arrays without
  `VALIDATE_FLAG_ARRAY_RECURSIVE` were still traversed.
* Strict CRLF mode read past the end of a string ending in CR (PHP warning).
* Error messages for unserializable values (closures) threw instead of reporting.
* `VALIDATE_INT` spec `min`/`max` such as `'1-2'` made `bccomp()` throw.
* `validate_spec()`: inverted `key_callback` warning, undefined-index warnings
  for a lone `amin`/`amax`, `VALIDATE_UNVALIDATED` accepted as a spec ID,
  `VALIDATE_FLOAT` `min`/`max` not checked for being numeric.
* `VALIDATE_FLAG_PASSTHRU` was documented but never applied.
* `basic_types.php`: 32-bit builds wrote `VALIDATE_INT_AS_STRING` into the
  options slot; `fqdn` / `json` threw `TypeError` on array input; `json`
  rejected LF-only line breaks.
* `basic_types.php` format checks:
  * `base64*` / `base64url*` accepted `=` anywhere (`====`, `a=b`) and
    lengths no encoder produces; `=` is now only the padding of the last
    4-char group (padding stays optional).
  * `urle*` accepted `%` without two hex digits (`%zz`, a trailing `%`).
  * `time` / `datetime` rejected fractional seconds, e.g. JavaScript
    `toISOString()` output (`2025-01-02T03:04:05.678Z`); 1-9 digits are
    accepted after the seconds.
  * `domain` rejected IDNA A-label TLDs (`example.xn--p1ai`).
  * `mac_address` accepted mixed separators (`00:1A-2B:3C:4D:5E`).
  * `ulid` accepted values above 128 bits (first char `8`-`Z`).
  * `sessid` required 32-128 chars; it now takes the `session.sid_length`
    range 22-256.
* tools: `input_validator.php` loaded `validate_func.php` relative to the
  working directory and validated against the whole spec map; the analyzer
  enabled DIGIT/ALPHA flags for characters never seen, added backslashes to
  `ascii`, and crashed on mixed input types or an empty log directory.
* examples: OR-inverted deny-list regexp, `'\t'` in single quotes, email
  callbacks without `@`, `$_FILES` `full_path` (PHP 8.1+), status checks that
  only looked at the last `validate()` call.
* examples: 95 rejected a `Host` header with a port (`example.com:8080`) or an
  IP address, and used `apache_request_headers()`, whose names are all lower
  case over HTTP/2 (`host` did not match the declared `Host`); it now builds
  the headers from `$_SERVER` on every SAPI, including `Content-Type` /
  `Content-Length`. 99-web validated the whole `$_SERVER` as headers and
  printed it — server and environment variables included — to the page; it
  now takes only the request headers.

### Added

* Branches `php-8.x` (PHP 8.x), `php-7.x` (PHP 7.0 - 7.4) and `php-5.x` (PHP
  5.3 - 5.6) with the same features as `master`, which is the development
  branch: changes go to `master` first and are ported to the other branches.
  The GitHub Actions matrix of `master` adds setup-php's `nightly` (next PHP
  release, 8.6) and `master` (php-src master, 8.7.0-dev) builds; see README
  "Branches". Every branch's matrix also runs the tests without BCMath and
  GMP on its newest PHP version, where `validate_compare_bigint.phpt` tests
  the pure PHP big-integer comparison (it is skipped while GMP is loaded),
  and the jobs fail unless the extensions are loaded or disabled as
  requested. `.gitlab-ci.yml` installs `libgmp-dev` from archive.debian.org
  when the image's Debian release is no longer on the mirrors.
  `dev/basic_types_corpus.php` prints the results of every `$basicTypes` spec
  for tricky inputs, and `dev/fuzz_inputs.php` generates inputs for the
  `json` and `url` specs and validates them, to compare a branch with
  `master`.
* `VALIDATE_OBJECT` `class` option (instanceof check), as `validate_defs.php`
  already described.
* `VALIDATE_ARRAY` `undeclared` option: a spec applied to every input key that
  `VALIDATE_PARAMS` does not declare, with the keys checked by
  `VALIDATE_FLAG_ARRAY_KEY_ALNUM` / `key_callback` of the `VALIDATE_ARRAY`
  spec. Known and unknown keys (e.g. HTTP headers) are validated in one
  `validate()` call; examples 94, 95 and 99-web use it instead of two-stage
  validation. `validate_spec()` checks the `undeclared` spec and warns about
  unknown `VALIDATE_ARRAY` options.
* `basic_types.php`: `timezone_all_with_bc` also accepts backward-compatible
  zone names (`DateTimeZone::ALL_WITH_BC`, Laravel `timezone:all_with_bc`),
  e.g. `US/Eastern` or `Asia/Calcutta`, which browsers still report.
  `timezone` keeps accepting canonical names only. Only names in PHP's
  timezone database are valid: PHP of Debian / Ubuntu uses the system
  database, which has the backward-compatible names only with the
  `tzdata-legacy` package (Debian 13, Ubuntu 24.04 and later). README
  "Recommended" asks to install it on such servers, and
  `basic_types_laravel.phpt` expects the names to pass only where PHP knows
  them.
* `basic_types.php`: `http_host` for the HTTP `Host` header — a host name
  (one label such as `localhost` is fine), IPv4 address or `[IPv6]` address
  with an optional port 1-65535. Examples 95 and 99-web use it.
* `validate()` remembers specs that passed the `VALIDATE_OPT_CHECK_SPEC` check
  (up to 64 per process, i.e. per request on PHP-FPM / mod_php) and skips the
  check for an identical spec — same values, following PHP references, and
  same object instances. A spec is checked on its first and second use (the
  second check guards against changes through PHP references), then trusted
  as a reference-free copy; a changed spec is checked again. `validate_spec()`
  always checks. Per-call time of `validate()` with the check, before → after:

  | Spec / input | Repeated calls | First use | Without the check |
  |---|---|---|---|
  | `95-example.php` spec and CLI sample inputs | 64.2 → 28.0 µs | 63.8 → 65.1 µs | 26.5 µs |
  | `$basicTypes['uint32']`, `'12345'` | 3.27 → 1.23 µs | 3.29 → 3.34 µs | 1.1 µs |

  Measured in the `php:8.5-cli` Docker image (PHP 8.5.11, no Xdebug, CLI
  OPcache off) with `-d zend.assertions=-1`, before = commit 01dedde: per-call
  time of a `hrtime()`-timed loop of 3,000 (95) / 30,000 (`uint32`) calls,
  best of 5 loops per run, minimum of 9 runs. "First use" empties the cache
  (through reflection) before every call. The machine was under load, so only
  the minima are meaningful.
* `ValidateInvalidSpecException::getContext()`,
  `ValidateInvalidValueException::getError()`.
* `VALIDATE_TOOLS_DIR` environment variable for `src/tools` (see
  `src/tools/README.md`); the tools refuse directories other users can write to.
* `src/tests/validate_regressions.phpt`, `src/tests/basic_types_formats.phpt`,
  `src/tests/validate_array_undeclared.phpt`, `src/tests/validate_spec_cache.phpt`;
  JSON error messages (`validate_basic_types_more.phpt`), PCRE runtime
  errors (`validate_regexp.phpt`) and the PHP 8.2+ URL rules
  (`basic_types_formats.phpt`); PHP 8.5 in both CI matrices.

### Removed / internal

* The 700-line `if` chain for RFC 3454 table C became a 36-range table searched
  by bisection (verified identical for every code point U+0080..U+10FFFF). The
  commented-out RFC 3454 table D code was removed; `VALIDATE_STRING_RFC3454_D`
  is documented as reserved (no effect).
* The per-key body of the `VALIDATE_ARRAY` loop is `validateParam()`, shared
  by declared keys and keys validated by `undeclared`.
* tools: removed the key-name hint heuristics from `input_analyzer.php`; they
  ran after `stat.php` was written and never reached the generated spec.
  Honoring key-name hints stays on the `src/tools/README.md` TODO list.
* String validation skips whitelisted runs with `strspn()` instead of building
  a 128-entry map per value.
* Removed dead internals (`reportWarning()`, `reportNotice()`,
  `validateInvalid()`, `validateUndefined()`, the public
  `validate_params_checked` / `spec_params_checked` handshake properties).
* `src/Validate.php`: 5,154 → 4,563 lines (`wc -l src/Validate.php` at 997dbd7
  vs. d622eb4).
