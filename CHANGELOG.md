# Changelog

## Unreleased

A second review of the design and the code (2026-10-18). Every finding was
reproduced on every branch, on PHP 5.3, 5.6, 7.0, 7.4, 8.0 and 8.7-dev.

### Security

* The spec check (on by default) deleted sub-specs from the caller's spec
  through PHP references: a sub-spec array held by a reference, e.g. after
  `foreach ($spec[VALIDATE_PARAMS] as &$s)`, lost its checked entries. Valid
  input then failed, and with `VALIDATE_OPT_UNVALIDATED` the input of the
  deleted specs passed unvalidated (the broken spec was then remembered as
  checked). `validate_spec()` with `$unvalidated` did the same. The check now
  strips sub-specs from its own copy only.
* `validate()` called by a callback with the same context, as the docs allow,
  reset the outer call: its status, result, path and MULTI_OR silent mode.
  An invalid value of the outer call then passed, and inside a
  `VALIDATE_MULTI_OR` alternative every later error of the call was silenced
  (`'id' => 'DROP TABLE'` passed with exceptions on). The nested call now
  saves the outer call's state and restores it, also when an exception
  leaves; a failed nested call fails the value whose callback (filter,
  key_callback, ...) made it ("Validate: validate() called by a callback with
  this context failed.").
* The "All of specs are failed." error of `VALIDATE_MULTI_OR` was handled with
  the last alternative's spec: `VALIDATE_FLAG_PASSTHRU` on it dropped the
  failure (status true, no exception), `VALIDATE_FLAG_WARNING` / `_NOTICE`
  suppressed the exception, and its `error_message` was recorded. The error is
  the MULTI spec's own now.
* `VALIDATE_ARRAY` accepted objects implementing `ArrayAccess` and `Countable`
  (`ArrayObject`, ...), which was neither documented nor tested, and skipped
  the undeclared-key check for them: an undeclared key passed with exceptions
  on. Only PHP arrays are accepted now.
* Exception messages and the PHP errors of `VALIDATE_OPT_RAISE_ERROR` held up
  to 2048 bytes of the input value as received (passwords into logs, CR / LF
  into log lines). They name the parameter path and the error only; the error
  record (`getError()`, `validate_get_system_errors()`) keeps the value. The
  "Unvalidated value remains." message gives the number of undeclared keys;
  their names are the record's `value`.
* `src/tools/input_validator.php` let a request through unvalidated when its
  `METHOD:PATH` had no spec, so another spelling of a known path
  (`/index.php/x`, `//index.php`) bypassed it. That is a failure now, logged
  and stored in `$GLOBALS['_validate_errors_']`, or thrown with `$exception`
  on. The logged key is escaped.

### Changed — behavior changes that can affect existing code

* Exception messages: no `val: '...'` part, see Security.
* `VALIDATE_INT` accepts a float only when it equals an integer exactly, and
  validates its exact digits. It accepted non-integer floats rounded by the
  `precision` INI setting (`2.9999999999999996` became `3`) and rejected them
  with `precision=17`. `INF` / `NAN` and non-integer floats fail with
  "VALIDATE_INT: Float value is not an integer."; `-0.0` is `0`.
* `VALIDATE_STRING` / `VALIDATE_REGEXP` reject a float whose string form
  (`precision` INI setting) is not the same float: `0.30000000000000004` was
  validated as `"0.3"`, a value never input.
* `VALIDATE_INT_AS_STRING` returns the integer string without a `+` sign
  (`"+5"` yields `"5"`), as for int input and with `values`.
  `VALIDATE_FLAG_RAW` still returns `"+5"`.
* A string validator's `values` match returns a string for int input (`1`
  yields `"1"`), as without `values`.
* `VALIDATE_OPT_ERROR_FULL`: when a node has its own errors and an int-keyed
  child with errors, the child takes its key and the node's error moves to
  the next int key. It was a fatal error ("[] operator not supported for
  strings") or wrote the child's errors into the node's error record, e.g.
  after two calls on one context.
* The spec check reports more:
  * `VALIDATE_MULTI` sub-specs that are not a non-empty array (error; it
    warned "foreach() argument must be of type array|object" or passed).
  * An empty `values` (error; `VALIDATE_STRING` then accepted `''` with PHP
    warnings).
  * `values` of `VALIDATE_REGEXP` must be `[string => bool]` like
    `VALIDATE_STRING`'s; `VALIDATE_CALLBACK`'s are no longer required to have
    int keys.
  * Options a validator does not use: `values`, `ascii`, `regexp`,
    `resource`, `class`, and `INF` / `-INF` / `length` / `decimal` /
    `thousand` on string validators (warnings).
  * Flag bits 32 - 63 on 64-bit PHP (warning; they were not checked).
* The RFC 3454 error names the table: "Unicode char prohibited by RFC 3454
  table C.1.2 (non-ASCII space) detected. code point: U+3000" instead of
  "Unicode CNTRL char detected.", which was wrong for spaces such as U+3000
  (ideographic space) and U+00A0. The rejected code points are the same.

### Fixed

* `VALIDATE_OPT_UPPER` (`0xfffffe00`) and `VALIDATE_FLAGS_UPPER`
  (`0xffff0000`) were floats on 32-bit PHP: on PHP 8.1+ every `validate()`
  call raised E_DEPRECATED (9 times for one int, 23 for an array of two
  values, measured on PHP 8.3 i386) and 61 of 71 tests failed. They are
  `~` masks of every bit above the used ones now, ints on every platform; on
  64-bit PHP option bits 32 - 63 are rejected too.
* `VALIDATE_INT_AS_STRING` with int input compared a string bound beyond
  `PHP_INT_MAX` as a float: `PHP_INT_MAX` passed `min` `"9223372036854775808"`.
* `VALIDATE_OPT_KEEP_INPUTS` did not protect a sub-array the caller shared
  through a PHP reference: the `filter` result was written to it.
* `validate_spec()` raised a PHP warning for the option key `''`.
* A `validate()` that left by an exception left its path on the context, so
  later calls on it reported wrong parameter paths.

### Added

* `composer.json` autoloads `src/validate_func.php` (the procedural API,
  `Validate` and the constants). `$basicTypes` is a variable:
  `require` `src/lib/basic_types.php` where it is used.
* CI: a 32-bit PHP job (the official `php` image for `linux/386`) on GitHub
  Actions and GitLab CI. Two tests no longer depend on the integer width.
* CI: PHP_CodeSniffer 3.13.6 from PHPCSStandards, checked against its SHA-256
  digest, instead of the unpinned, abandoned 3.7.2 phar of squizlabs.github.io.
* Tests: `validate_regressions2.phpt` (this review) and
  `tools_input_validator.phpt`.

## 1.0.x01 - 2026-10-18: `v1.0.501` (`php-5.x`), `v1.0.701` (`php-7.x`), `v1.0.801` (`php-8.x`)

Fix release of 1.0. A review of the code against its documentation (2026-10-17).

### Changed — behavior changes that can affect existing code

* A failed element of a `VALIDATE_FLAG_ARRAY` array is left out of the result
  (`getValidated()` had `null` for it; with `VALIDATE_FLAG_PASSTHRU` the
  result had the failed value).
* `VALIDATE_FLAG_RAW` wins over `VALIDATE_BOOL_AS_STRING`, as it does for
  `VALIDATE_INT` / `VALIDATE_FLOAT`: `"YES"` stays `"YES"` (was `"yes"`).

### Fixed

* `VALIDATE_FLAG_PASSTHRU` did not do what the docs say. A `validate_error()`
  from a callback was recorded and threw `ValidateInvalidValueException`, and a
  value whose callback returned false stayed in the result. Both failures are
  now dropped and the value is left out of the result.
* `validate_error()` from a callback that returned true set the status to
  false, but the value stayed in `getValidated()` and was removed from
  `$inputs`. It now fails the value like a false return.
* `VALIDATE_INT` with `values` ignored `VALIDATE_INT_POSITIVE_SIGN`: `"+5"`
  failed. The sign rules are those of the range check now: `+` with
  `VALIDATE_INT_POSITIVE_SIGN`, `-` with `VALIDATE_INT_NEGATIVE_SIGN` or when
  negative values are valid (a negative key in `values`, as a negative `min`
  allows it).
* `validate_spec()` dropped a `VALIDATE_ARRAY` node with a broken option or
  child from `$unvalidated`, which is meant to keep the broken parts.
* `php-7.x` / `php-5.x`: README, `src/README.md`, the CI files and the 1.0
  entry said the `src/run-tests.php` of these branches has no `--show-diff`.
  It has (`--show-[all|php|skip|clean|exp|diff|out]`); the CI uses it now
  instead of printing `tests/*.diff` itself.
* The spec check contradicted the engine:
  * It warned that `VALIDATE_FLAG_EMPTY_TO_DEFAULT` has no effect on
    `VALIDATE_ARRAY`, which applied it to array parameters. It is accepted
    now (a missing `default` is a spec error) and also applies to a top-level
    `VALIDATE_ARRAY`.
  * It warned that every `VALIDATE_MULTI` option has no effect, but `filter`
    is applied. `filter` (which must be callable) and `_` options are
    accepted now; others are still warned about.
  * It warned "No value can be accepted" for `VALIDATE_BOOL` without format
    flags, which accepts bool values (`$basicTypes['bool']`). It is a notice
    saying so now.
* Documentation that contradicted the code, in REFERENCE.md, README.md,
  `src/tools/README.md`, the comments of `validate_defs.php`, `Validate.php`,
  `validate_func.php` and `basic_types.php`, and example 01. Among others:
  * `VALIDATE_NULL_AS_STRING`, `VALIDATE_BOOL_AS_STRING`, `VALIDATE_STRING_CRLF`
    (CR and LF only as pairs), `*_RFC3454_D` (no effect), `VALIDATE_FLAG_EMPTY`
    with `VALIDATE_FLAG_ARRAY`, the `-` implied by negative values, `values`
    skipping the char and encoding checks, and `error_message` with
    `VALIDATE_FLAG_WARNING` / `_NOTICE`.
  * The `validate_spec()` example in `validate_func.php` was not a valid spec,
    and the REFERENCE spec template had `$params =>` as an array key.
  * README Example #5 is `src/examples/95-example.php` again (it had drifted:
    no headers), and its status is 1.0 instead of "Pre Alpha". The "6 times
    slower" figure for the RFC 3454 check had no measurement behind it and is
    gone.
  * `src/tools/README.md`: the owner check needs the posix extension, and
    `stat.php` keeps up to 999 sample values per parameter.
  * `basic_types.php`: the placeholder length of the encoded-form specs, the
    session id comment (PHP warns about an invalid id instead of stripping
    characters), the `sqlident127` comment (MySQL allows 64 chars) and the
    DNS lookups of `fqdn` / `active_url`.

## 1.0 - 2026-10-17: `v1.0.500` (`php-5.x`), `v1.0.700` (`php-7.x`), `v1.0.800` (`php-8.x`)

Changes since `v0.8.0`. Design review (2026-10-16, commit d622eb4) and its
follow-ups (2026-10-16): `basic_types.php` format fixes, the `VALIDATE_ARRAY`
`undeclared` option, example/tool fixes, and remembering checked specs.
Branches for older PHP versions and fixes for PHP 8.6 / 8.7.0-dev
(2026-10-17). Character encoding validation with mbstring (2026-10-17).

### Security

* **Character encodings were validated with an output function, and only in
  the encodings it knows.** Since the first version, the `_MB` encoding check
  called `htmlspecialchars()` — an output escaping function — and treated an
  empty result as invalid. README recommended mbstring for "more supported
  encodings and better encoding checks", but no code used it (the
  recommendation was removed from README in d622eb4 instead of being
  implemented). As a result:
  * An `encoding` that `htmlspecialchars()` does not know (`ISO-2022-JP`,
    `UTF-16`, `EUC-KR`, `GB18030`, a misspelling, or `UTF8`, which the code
    treated as UTF-8) raised a PHP warning on every validation and was checked
    as UTF-8; on PHP 5.3 as ISO-8859-1, so any bytes passed.
  * Single-byte encodings (`ISO-8859-1`, ...) and every encoding other than
    UTF-8 skipped the RFC 3454 table C check and the `unicode` option: C1
    controls such as ISO-8859-1 0x85 (U+0085) passed.
  * Shift_JIS / BIG5 chars whose second byte is in the ASCII range were
    checked byte by byte against the ASCII whitelist (SJIS 0x835C U+30BD was
    rejected unless `\` was allowed).
  * `urle*` / `htmle*` of `basic_types.php` checked only the encoded form:
    `%FF%FE` (invalid UTF-8), `%E2%80%AE` (U+202E) and `%00` passed.

  Now mbstring is required, and `mb_check_encoding()` validates the string in
  its `encoding`: any character encoding mbstring knows, default UTF-8. The
  string is then converted to UTF-8 and every char is checked (flags,
  `ascii`, RFC 3454 table C, `unicode`). Unknown names and mbstring's names
  that are not character encodings (`BASE64`, `Quoted-Printable`,
  `HTML-ENTITIES`, `8bit`, ...) are spec errors and fail validation when the
  spec check is off. UTF-8 forms of values beyond U+10FFFF, which mbstring of
  PHP 5.3 accepts, are rejected on every PHP version. See README "Character
  Encoding Validation".
* **`mbstring.func_overload` defeated the string checks** (PHP 7 / 5
  branches). With its string functions bit (2), `strlen()` counts chars, so
  `min` / `max` were compared with a char count and the char check stopped
  early: `VALIDATE_STRING_ALNUM | VALIDATE_STRING_MB` accepted
  four U+3042 followed by `<x>` on PHP 7.4 and 5.3. `new Validate` now throws
  `RuntimeException` when it is set.

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
  * As in `php-7.x`: `Validate::isCharEncoding()` detects a name mbstring
    does not know by its warning (PHP 8 throws `ValueError`), and
    `new Validate` refuses `mbstring.func_overload` string functions
    (`validate_func_overload.phpt`).
* Checked on PHP 5.3.29 and 5.4.45 (built from source in Docker) and 5.5.38
  and 5.6.40 (official Docker images), all with BCMath and GMP: all tests
  pass; the outputs of `dev/basic_types_corpus.php`, of the examples and of
  the `src/tools` flow (logger, analyzer, validator) equal `master`'s on
  PHP 8.5.11; `dev/fuzz_inputs.php` gives the same results as `master` on
  PHP 8.6.0RC3 for 120,000 JSON inputs and on PHP 8.5.11 for 80,000 URLs
  (seeds as in the `json` / `url` entries below).

### Changed — behavior changes that can affect existing code

* **mbstring is required.** `new Validate`, `validate_init()`, `validate()`
  and `validate_spec()` throw `RuntimeException` without it; `composer.json`
  requires `ext-mbstring`.
* **`encoding` takes mbstring's names.** Names only `htmlspecialchars()` knew
  are spec errors now: `MacRoman`, `BIG5-HKSCS` and the code page numbers
  `866`, `932`, `936`, `950`, `1251`, `1252` (use `CP866`, `CP932`, `CP936`,
  `CP950`, `Windows-1251`, `Windows-1252`). Strings in an encoding other than
  UTF-8 are checked after conversion to UTF-8, so RFC 3454 table C and the
  `unicode` option apply to them too.
* `basic_types.php` `urle*` / `htmle*` validate the decoded text (the new
  `transfer_encoding` option): it must be valid UTF-8 without control chars
  (`htmle*` allows TAB and LF) or RFC 3454 table C code points. `htmle*`
  requires the form `htmlspecialchars($s, ENT_QUOTES)` produces: a raw `&`
  outside `&amp;` `&lt;` `&gt;` `&quot;` `&apos;` or a numeric reference fails.

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
* `basic_types.php`: `base64*` and `base64url*` are `VALIDATE_REGEXP` specs
  (were `VALIDATE_STRING`) so they can check where `=` appears. `urle*` are
  `VALIDATE_STRING` specs with `transfer_encoding` `percent`, which checks
  where `%` appears.
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
* `composer.json` required `ext-bcmath`, though without BCMath and GMP big
  integers are compared in pure PHP (tested by the CI). `ext-bcmath` moved to
  `suggest`.
* The error record of an invalid `VALIDATE_INT` string held the value escaped
  by `addslashes()`; it holds the input as received, like every other
  validator. Escaping belongs to the code that outputs the record.
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

* Release tags per branch with one version line: `vMAJOR.MINOR.PATCH`, the
  hundreds digit of PATCH naming the branch (`5xx` `php-5.x`, `7xx`
  `php-7.x`, `8xx` `php-8.x`), so `^1.0` installs the right branch on every
  PHP version (README "Releases").
* `transfer_encoding` option of the string validators: `quoted-printable`
  (RFC 2045), `percent` (`urlencode()` / `rawurlencode()` output), `base64` /
  `base64url` (RFC 4648) and `html` (`htmlspecialchars($s, ENT_QUOTES)`
  output). The encoded form is checked strictly, then the decoded text is
  validated (`encoding`, flags, RFC 3454 table C, `unicode`). mbstring cannot
  do this: `mb_check_encoding($s, 'Quoted-Printable')` is false even for valid
  input on PHP 8.0 and older and true even for `=ZZ` on PHP 8.1 and later
  (deprecated since 8.2), and `quoted_printable_decode()` passes `=ZZ` through.
* `dev/mb_check.php` measures what mbstring of a PHP version accepts (UTF-8
  against the RFC 3629 grammar, and the encodings known to differ between
  versions); README "Character Encoding Validation" lists the results.
* Branches `php-8.x` (PHP 8.x), `php-7.x` (PHP 7.0 - 7.4) and `php-5.x` (PHP
  5.3 - 5.6) with the same features as `master`, which is the development
  branch: changes go to `master` first and are ported to the other branches.
  The GitHub Actions matrix of `master` adds setup-php's `nightly` (next PHP
  release, 8.6) and `master` (php-src master, 8.7.0-dev) builds; see README
  "Branches". Every branch's matrix also runs the tests without BCMath and
  GMP on its newest PHP version, where `validate_compare_bigint.phpt` tests
  the pure PHP big-integer comparison (it is skipped while GMP is loaded),
  and the jobs fail unless the extensions are loaded or disabled as
  requested; `.gitlab-ci.yml` does the same with the official `php` images.
  `.gitlab-ci.yml` installs `libgmp-dev` from archive.debian.org when the
  image's Debian release is no longer on the mirrors.
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
