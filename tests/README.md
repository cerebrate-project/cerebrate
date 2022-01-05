# Testing
Add a test database to your `config/app_local.php` config file and set `debug` mode to `true`.
```php
'debug' => true,
'Datasources' => [
    'default' => [
        ...
    ],
    /*
        * The test connection is used during the test suite.
        */
    'test' => [
        'host' => 'localhost',
        'username' => 'cerebrate',
        'password' => 'cerebrate',
        'database' => 'cerebrate_test',
    ],
],
```

## Runing the tests

```
$ composer install
$ vendor/bin/phpunit
PHPUnit 8.5.22 by Sebastian Bergmann and contributors.

.....                                     5 / 5 (100%)

Time: 11.61 seconds, Memory: 26.00 MB

OK (5 tests, 15 assertions)
```

Running a specific suite:
```
$ vendor/bin/phpunit --testsuite=api
```
Available suites:
* `app`: runs all test suites
* `api`: runs only api tests
* `controller`: runs only controller tests
* _to be continued ..._

By default the database is re-generated before running the test suite, to skip this step and speed up the test run use the `-d skip-migrations` option:
```
$ vendor/bin/phpunit -d skip-migrations
```
