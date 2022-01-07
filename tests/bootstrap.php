<?php

declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;
use Migrations\TestSuite\Migrator;

/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

require dirname(__DIR__) . '/config/bootstrap.php';

$_SERVER['PHP_SELF'] = '/';

Configure::write('App.fullBaseUrl', 'http://localhost');

// DebugKit skips settings these connection config if PHP SAPI is CLI / PHPDBG.
// But since PagesControllerTest is run with debug enabled and DebugKit is loaded
// in application, without setting up these config DebugKit errors out.
ConnectionManager::setConfig('test_debug_kit', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\Sqlite',
    'database' => TMP . 'debug_kit.sqlite',
    'encoding' => 'utf8',
    'cacheMetadata' => true,
    'quoteIdentifiers' => false,
]);

ConnectionManager::alias('test_debug_kit', 'debug_kit');

// Fixate sessionid early on, as php7.2+
// does not allow the sessionid to be set after stdout
// has been written to.
session_id('cli');

// Load db schema from mysql.sql and run migrations
// super hacky way to skip migrations
if (!in_array('skip-migrations', $_SERVER['argv'])) {
    // TODO: Removing mysql.sql and relying only in migrations would be ideal
    // in the meantime, `'skip' => ['*']`, prevents migrations from droping already created tables
    (new SchemaLoader())->loadSqlFiles('./INSTALL/mysql.sql', 'test');
    $migrator = new Migrator();
    $migrator->runMany([
        ['connection' => 'test', 'skip' => ['*']],
        ['plugin' => 'Tags', 'connection' => 'test', 'skip' => ['*']],
        ['plugin' => 'ADmad/SocialAuth', 'connection' => 'test', 'skip' => ['*']]
    ]);
}else{
    echo "[ * ] Skipping migrations ...\n";
}
