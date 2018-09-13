# How to test

Get PHP source code. There is "run-tests.php" in source root.
Execute tests like:

```bash
$ /path/to/run-tests.php -p /path/to/php-cli/binary/php --show-diff tests
```

**Note: Some distribution's, e.g. Fedora, PHP CLI binary does not have
$_ENV at all. You need to pass PHP binary location by "-p" flag.**
