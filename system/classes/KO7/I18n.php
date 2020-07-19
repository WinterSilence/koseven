<?php

/**
 * Internationalization(i18n) class. Provides language loading and translation methods without dependencies on
 * [gettext](http://php.net/gettext). Typically this class would never be used directly, but used via the `__()`
 * function, which loads the message and replaces parameters.
 *
 * @package KO7
 * @category Base
 * @copyright (c) 2008 - 2016 Kohana Team
 * @copyright (c) 2018 - 2020 KO7 team
 * @license BSD-3-ClauseBSD-3-Clause
 */
class KO7_I18n
{
    /**
     * @var string Target language: en-us, es-es, zh-cn, etc
     */
    public static $lang = 'en-us';
    
    /**
     * @var string  Source language: en-us, es-es, zh-cb, etc
     */
    public static $source = 'en-us';
    
    /**
     * @var array Cache of loaded languages
     */
    protected static $cache = [];
    
    /**
     * Get and set the target language.
     *
     * @param string|null $lang New target language
     * @return string
     */
    public static function lang(string $lang = null): string
    {
        if ($lang !== static::$lang) {
            static::$lang = strtolower(str_replace([' ', '_'], '-', $lang));
        }
        return static::$lang;
    }
    
    /**
     * Returns translation of a string. If no translation exists, the original string will be returned. No parameters
     * are replaced.
     *
     * @param string|string[] $string Text to translate or array [text, values]
     * @param string|null $lang Target Language
     * @param string|null $source Source Language
     * @return string
     */
    public static function get($string, ?string $lang = null, ?string $source = null): string
    {
        $values = [];
        // Check if `$string` is `[text, values]`
        if (is_array($string)) {
            if (isset($string[1]) && is_array($string[1])) {
                $values = $string[1];
            }
            $string = $string[0];
        }
        $string = (string) $string;
        // Set target Language if not set
        if (! $lang) {
            // Use the global target language
            $lang = static::$lang;
        }
        // Set source Language if not set
        if (! $source) {
            // Use the global source language
            $source = static::$source;
        }
        // Load Table only if Source language does not match target language
        if ($source !== $lang) {
            // Load the translation table for this language
            $table = static::load($lang);
            // Return the translated string if it exists
            $string = $table[$string] ?? $string;
        }
        
        return empty($values) ? $string : strtr($string, $values);
    }
    
    /**
     * Returns the translation table for a given language.
     *
     * @param string $lang language to load
     * @return string[]
     */
    public static function load(string $lang): array
    {
        if (isset(static::$cache[$lang])) {
            return static::$cache[$lang];
        }
        
        // New translation table
        $table = [[]];
        
        // Split the language: language, region, locale, etc
        $parts = (array) explode('-', $lang);
        
        // Loop through Paths
        foreach ([$parts[0], implode(DIRECTORY_SEPARATOR, $parts)] as $path) {
            // Load files
            $files = KO7::find_file('i18n', $path);
            
            // Loop through files
            if (! empty($files)) {
                $t = [[]];
                foreach ($files as $file) {
                    // Merge the language strings into the sub table
                    $t[] = KO7::load($file);
                }
                $table[] = $t;
            }
        }
        
        $table = array_merge(...array_merge(...$table));
        
        // Cache the translation table locally
        return static::$cache[$lang] = $table;
    }
}

if (! function_exists('__')) {
    /**
     * Short-hands translation/internationalization function.
     * Note: The target language is defined by `I18n::lang()`.
     *
     * @param string $string Text to translate
     * @param array $values Values to replace in the translated text
     * @param string|null $lang Source language
     * @return string
     */
    function __(string $string, array $values = [], ?string $lang = null): string
    {
        return I18n::get($values ? [$string, $values] : $string, $lang);
    }
}
