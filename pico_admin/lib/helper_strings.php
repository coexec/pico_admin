<?php

/**
 * Pico Admin plugin 2.0 for Pico CMS
 *
 * @author Kay Stenschke
 * @link http://www.coexec.com
 * @version 2.0.1
 */

/**
 * Class Pico_Admin_Helper_Strings
 *
 * Helper for strings (format, convert, validate, etc.)
 */
class Pico_Admin_Helper_Strings {

    /**
     * @param   string          $text
     * @return  mixed|string
     */
    public static function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        return empty($text) ? 'n-a' : $text;
    }

    /**
     * Detect client locale and translate (if resp. language is available, otherwise fallback to english)
     *
     * @param   String  $html
     * @return  String
     */
    public static function translate($html)
    {
        $locale = Locale::acceptFromHttp($_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ]);
        $localeKey = substr($locale, 0, 2);

        $translationFile = dirname(__FILE__) .'/../templates/translations/' . $localeKey . '.php';
        if( ! file_exists($translationFile)) {
            $translationFile = dirname(__FILE__) .'/../templates/translations/en.php';
        }
        include($translationFile); // defines $translations as associative array of translation keys and labels
        /** @var $translations array */
        foreach($translations as $key => $label) {
            $html = str_replace('trans.' . $key, $label, $html);
        }

        return $html;
    }

    /**
     * @param   string      $haystack
     * @param   string      $needle
     * @return  boolean     Given string ends w/ given needle?
     */
    public static function endsWith($haystack, $needle)
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * @param   int     $bytes
     * @param   int     $precision
     * @return  string
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(( $bytes ? log($bytes) : 0 ) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[ $pow ];
    }


}