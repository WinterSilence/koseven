<?php

/**
 * Set KO7::$environment if a 'KOHANA_ENV' environment variable has been supplied.
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant KO7::<INVALID_ENV_NAME>"
 */
if (isset($_SERVER['KOHANA_ENV'])) {
    KO7::$environment = constant('KO7::' . strtoupper($_SERVER['KOSEVEN_ENV']));
}

/**
 * Define the start time of the application, used for profiling.
 */
if (! defined('KOHANA_START_TIME')) {
    define('KOHANA_START_TIME', KO7_START_TIME);
}

/**
 * Define the memory usage at the start of the application, used for profiling.
 */
if (! defined('KOHANA_START_MEMORY')) {
    define('KOHANA_START_MEMORY', KO7_START_MEMORY);
}
