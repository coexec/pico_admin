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
 * Helper for server (URL, paths, etc.)
 */
class Pico_Admin_Helper_Server {

    /**
     * @return  String  http or https
     */
    public static function getProtocol() {
        return 'http' . ( array_key_exists('HTTPS', $_SERVER) && $_SERVER[ 'HTTPS' ] == 'on' ? 's' : '' );
    }

    /**
     * @param   bool    $removeAdmin    remove '/admin#' and '/admin' ?
     * @return  string
     */
    public static function getCurrentUrl($removeAdmin = false)
    {
        $url = self::getProtocol() . '://'
            . $_SERVER[ 'SERVER_NAME' ]
            . ( $_SERVER[ 'SERVER_PORT' ] != '80' ? ':' . $_SERVER[ 'SERVER_PORT' ] : '' )
            . $_SERVER[ 'REQUEST_URI' ];

        return $removeAdmin ? str_replace(array('/admin#', '/admin'), '', $url) : $url;
    }

    /**
     * @return  String  additional directories in path of base-URL (eg. 'p123')
     */
    public static function getUrlBaseSuffix()
    {
        return substr($_SERVER[ 'REQUEST_URI' ], 0, strpos($_SERVER[ 'REQUEST_URI' ], '/admin/'));
    }

    /**
     * @return  string
     */
    public static function getUrlRoot($basePath) {
        return
            self::getProtocol()
            . '://' . $_SERVER[ 'SERVER_NAME' ]
            . ( $_SERVER[ 'SERVER_PORT' ] != '80' ? ":" . $_SERVER[ 'SERVER_PORT' ] : '' )
            . '/'
            .  (Pico_Admin_Helper_Strings::endsWith($basePath, '/')
                ? substr($basePath, 0, strlen($basePath) - 1)
                : $basePath
            );
    }

}