<?php

/**
 * The directory in which your application specific resources are located. The application directory must contain the
 * `bootstrap.php` file.
 */
$application = 'application';

/**
 * The directory in which your modules are located.
 */
$modules = 'modules';

/**
 * The directory in which the framework resources are located. The system directory must contain the `classes/KO7.php`
 * file.
 */
$system = 'system';

/**
 * The directory in which the framework public files are located. The public directory contains for example the
 * `index.php` and `.htaccess` files.
 */
$public = 'public';

/**
 * @var string The default extension of resource files. If you change this, all resources must be renamed to use the
 *     new extension.
 */
define('EXT', '.php');

/**
 * @var string Set the path to the document root. This assumes that this file is stored 2 levels below the DOCROOT, if
 *     you move this bootstrap file somewhere else then you'll need to modify this value to compensate.
 */
define('DOCROOT', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);

/**
 * Set the PHP error reporting level. If you set this in php.ini, you remove this.
 *
 * @link https://www.php.net/manual/errorfunc.configuration#ini.error-reporting
 */
error_reporting(E_ALL);

/**
 * End of standard configuration! Changing any of the code below should only be attempted by those with a working
 * knowledge of framework internals.
 */

// Make the application relative to the document's root
if (! is_dir($application) && is_dir(DOCROOT . $application)) {
    $application = DOCROOT . $application;
}

// Make the modules relative to the docroot
if (! is_dir($modules) && is_dir(DOCROOT . $modules)) {
    $modules = DOCROOT . $modules;
}

// Make the system relative to the docroot
if (! is_dir($system) && is_dir(DOCROOT . $system)) {
    $system = DOCROOT . $system;
}

// Make the public relative to the docroot
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

if (! defined('KO7_START_TIME')) {
    /** @var float Start time of the application, used for profiling */
    define('KO7_START_TIME', microtime(true));
}

if (! defined('KO7_START_MEMORY')) {
    /** @var int Memory usage at the start of the application, used for profiling */
    define('KO7_START_MEMORY', memory_get_usage(true));
}

// Bootstrap the application
require(APPPATH . 'bootstrap' . EXT);

// Disable output buffering
$ob_len = ob_get_length();
if ($ob_len !== false) {
    // flush_end on an empty buffer causes headers to be sent. Only flush if needed.
    if ($ob_len > 0) {
        ob_end_flush();
    } else {
        ob_end_clean();
    }
}

// Enable all modules we can find
$modules = new FilesystemIterator(
    MODPATH,
    FilesystemIterator::KEY_AS_FILENAME
    | FilesystemIterator::CURRENT_AS_PATHNAME
    | FilesystemIterator::SKIP_DOTS
);
try {
    KO7::modules(iterator_to_array($modules));
} catch (KO7_Exception $e) {
    die($e->getTraceAsString());
}
unset($modules);
