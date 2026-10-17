# How to test

"run-tests.php" (borrowed from PHP's own test suite) is in this directory.
Execute tests from here like:

```bash
$ cd src
$ REPORT_EXIT_STATUS=1 php run-tests.php -p $(which php) -q --show-diff tests
```

This is the run-tests.php of PHP 5.5.38 (`php-5.x` branch). `-q` skips its
questions; `--show-diff` prints the diff of a failed test. It exits with a
failure status only when `REPORT_EXIT_STATUS=1` is set.

**Note: Some distribution's, e.g. Fedora, PHP CLI binary does not have
$_ENV at all. You need to pass PHP binary location by "-p" flag.**
