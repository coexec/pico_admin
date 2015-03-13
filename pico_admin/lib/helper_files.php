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
class Pico_Admin_Helper_Files {

    /**
     * @param   string  $file_url
     * @return  mixed|string
     */
    public static function getFilePath($basePath, $file_url)
    {
        $pathFile = str_replace(
            array(
                Pico_Admin_Helper_Server::getProtocol() . '://',

                  $_SERVER[ 'SERVER_NAME' ]
                . ( $_SERVER[ 'SERVER_PORT' ] != '80' ? ":" . $_SERVER[ 'SERVER_PORT' ] : '' )
                . Pico_Admin_Helper_Server::getUrlBaseSuffix()
            ),
            '',
            dirname(strip_tags($file_url))
        );

        if( !empty( $pathFile ) ) {
            $pathFile .= '/';
        }

        return $pathFile;
    }

    /**
     * @param   String $pathFile
     * @return  String
     */
    public static function getFileExtension($pathFile) {
        return substr($pathFile, strrpos($pathFile, '.') + 1);
    }

    /**
     * @param   String  $pathFile
     * @param   String  $filename
     */
    public static function download($pathFile, $filename){
        header('Content-Type: text/xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        die( file_get_contents($pathFile) );
    }

}