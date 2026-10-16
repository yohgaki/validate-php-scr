# How to test

"run-tests.php" (borrowed from PHP's own test suite) is in this directory.
Execute tests from here like:

```bash
$ cd src
$ php run-tests.php -p $(which php) --show-diff tests
```

**Note: Some distribution's, e.g. Fedora, PHP CLI binary does not have
$_ENV at all. You need to pass PHP binary location by "-p" flag.**
