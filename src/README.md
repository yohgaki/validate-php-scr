# How to test

"run-tests.php" (borrowed from PHP's own test suite) is in this directory.
Execute tests from here like:

```bash
$ cd src
$ php run-tests.php -p $(which php) -q tests
```

This is the run-tests.php of PHP 7.4.33 (`php-7.x` branch). It has no
`--show-diff`; see `tests/*.diff` of failed tests.

**Note: Some distribution's, e.g. Fedora, PHP CLI binary does not have
$_ENV at all. You need to pass PHP binary location by "-p" flag.**
