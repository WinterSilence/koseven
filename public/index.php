<?php

declare(strict_types=1);

// The directory in which your application specific resources are located, must contain the `bootstrap.php` file.
$application = 'application';

// The directory in which your modules are located.
$modules = 'modules';

// The directory in which the system resources are located, must contain the `classes/KO7.php` file.
$system = 'system';

// The directory in which the framework's public files are located, contains for example the `index.php`.
$public = 'public';

// The default extension of resource files. If you change this, all resources must be renamed to use the new extension.
define('EXT', '.php');

/**
 * Set the PHP error reporting level. If you set this in php.ini, you remove this.
 *
 * @link https://www.php.net/manual/errorfunc.configuration#ini.error-reporting
 */
error_reporting(E_ALL);

/**
 * End of standard configuration! Changing any of the code below should only be
 * attempted by those with a working knowledge of framework internals.
 */

// Set the full path to the docroot
define('DOCROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Make the application relative to the docroot, for symlink'd `index.php`
if (! is_dir($application) && is_dir(DOCROOT . $application)) {
    $application = DOCROOT . $application;
}

// Make the modules relative to the docroot, for symlink'd `index.php`
if (! is_dir($modules) && is_dir(DOCROOT . $modules)) {
    $modules = DOCROOT . $modules;
}

// Make the system relative to the docroot, for symlink'd `index.php`
if (! is_dir($system) && is_dir(DOCROOT . $system)) {
    $system = DOCROOT . $system;
}

// Make the public relative to the docroot, for symlink'd `index.php`
if (! is_dir($public) && is_dir(DOCROOT . $public)) {
    $public = DOCROOT . $public;
}

// Define the absolute paths for configured directories
define('APPPATH', realpath($application) . DIRECTORY_SEPARATOR);
define('MODPATH', realpath($modules) . DIRECTORY_SEPARATOR);
define('SYSPATH', realpath($system) . DIRECTORY_SEPARATOR);
define('PUBPATH', realpath($public) . DIRECTORY_SEPARATOR);

// Clean up the configuration vars
unset($application, $modules, $system, $public);

/**
 * Define the start time of the application, used for profiling.
 */
if (! defined('KO7_START_TIME')) {
    define('KO7_START_TIME', microtime(true));
}

/**
 * Define the memory usage at the start of the application, used for profiling.
 */
if (! defined('KO7_START_MEMORY')) {
    define('KO7_START_MEMORY', memory_get_usage(true));
}

// Bootstrap the application
require(APPPATH . 'bootstrap' . EXT);

// @todo replace to my CLI module
if (PHP_SAPI === 'cli') {
    // Try and load minion
    class_exists('Minion_Task') or die('Please enable the Minion module for CLI support.');
    set_exception_handler(['Minion_Exception', 'handler']);
    Minion_Task::factory(Minion_CLI::options())->execute();
} else {
    /**
     * Execute the main request. A source of the URI can be passed, eg: `$_SERVER['PATH_INFO']`.
     * If no source is specified, the URI will be automatically detected.
     */
    echo Request::factory(true, [], false)
        ->execute()
        ->send_headers(true)
        ->body();
}
