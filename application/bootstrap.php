<?php

// ## Environment setup

// Load the core abstract class
require_once(SYSPATH . 'classes/KO7/Core' . EXT);

if (is_file(APPPATH . 'classes/KO7' . EXT)) {
    // Application extends the core
    require_once(APPPATH . 'classes/KO7' . EXT);
} else {
    // Load empty core extension
    require_once(SYSPATH . 'classes/KO7' . EXT);
}

/**
 * Set the default time zone.
 *
 * @link https://www.php.net/manual/timezones
 */
date_default_timezone_set('America/Chicago');

/**
 * Set the default locale.
 *
 * @link https://www.php.net/manual/function.setlocale
 */
setlocale(LC_ALL, 'en_US.utf-8');

/**
 * Enable the KO7 auto-loader.
 *
 * @link http://koseven.dev/guide/using.autoloading
 * @link https://www.php.net/manual/function.spl-autoload-register
 */
spl_autoload_register(['KO7', 'auto_load']);

/**
 * Enable the KO7 auto-loader for unserialization.
 *
 * @link https://www.php.net/manual/var.configuration#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

/**
 * Load composer autoload packages.
 */
require(DOCROOT . 'vendor/autoload.php');

/**
 * Reset the substitute character.
 *
 * @link https://www.php.net/manual/function.mb-substitute-character
 */
mb_substitute_character('none');

// -- Configuration and initialization -----------------------------------------

/**
 * Set the default language
 */
I18n::lang('en-us');

if (isset($_SERVER['SERVER_PROTOCOL'])) {
    // Replace the default protocol.
    HTTP::$protocol = $_SERVER['SERVER_PROTOCOL'];
}

/**
 * Set KO7::$environment if a 'KOSEVEN_ENV' environment variable has been supplied.
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant KO7::<INVALID_ENV_NAME>"
 */
if (isset($_SERVER['KOSEVEN_ENV'])) {
    KO7::$environment = constant('KO7::' . strtoupper($_SERVER['KOSEVEN_ENV']));
}

/**
 * Initialize KO7, setting the default options.
 * The following options are available:
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php", if set to FALSE uses clean URLS     index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - integer  cache_life  lifetime, in seconds, of items cached              60
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 * - boolean  expose      set the X-Powered-By header                        FALSE
 */
KO7::init(
    [
        'base_url' => '/',
    ]
);

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
KO7::$log->attach(new Log_File(APPPATH . 'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
KO7::$config->attach(new Config_File());

// Enable modules. Modules are referenced by a relative or absolute path.
$modules = [
    // 'auth'       => MODPATH.'auth',       // Authentication and authorization
    // 'cache'      => MODPATH.'cache',      // Caching data
    // 'database'   => MODPATH.'database',   // Database access
    // 'encrypt'    => MODPATH.'encrypt',    // Encryption support
    // 'image'      => MODPATH.'image',      // Image manipulation
    // 'orm'        => MODPATH.'orm',        // Object Relationship Mapping
    // 'pagination' => MODPATH.'pagination', // Pagination helper
];

if (PHP_SAPI === 'cli') {
    $modules += [
        // CLI tasks
        'minion' => MODPATH . 'minion',
    ];
}
if (KO7::$environment !== KO7::PRODUCTION) {
    $modules += [
        // Benchmarking tool
        'codebench' => MODPATH . 'codebench',
        // Unit testing
        'unittest' => MODPATH . 'unittest',
        // User guide and API documentation
        'userguide' => MODPATH . 'userguide',
    ];
}
/**
 * Initialize modules.
 */
KO7::modules($modules);

// @todo move in config 'cookie.php' and Cookie class
/**
 * Cookie Salt
 *
 * @see  http://koseven.dev/3.3/guide/ko7/cookies
 * If you have not defined a cookie salt in your Cookie class then
 * uncomment the line below and define a preferrably long salt.
 */
// Cookie::$salt = NULL;
/**
 * Cookie HttpOnly directive
 * If set to true, disallows cookies to be accessed from JavaScript
 *
 * @see https://en.wikipedia.org/wiki/Session_hijacking
 */
// Cookie::$httponly = TRUE;
/**
 * If website runs on secure protocol HTTPS, allows cookies only to be transmitted via HTTPS.
 * Warning: HSTS must also be enabled in `.htaccess`, otherwise first request to `http://www.example.com` will still
 * reveal this cookie.
 */
Cookie::$secure = filter_input(INPUT_SERVER, 'HTTPS') === 'on';

// Set the routes. Each route must have a minimum of a name, a URI and a set of defaults for the URI.
if (! Route::cache()) {
    Route::set(
        'default',
        '(<namespace>/(<controller>/(<action>/(<slug>/))))',
        ['namespace']
    )->defaults(['controller' => 'welcome', 'action' => 'index']);
    
    Route::cache(true);
}
