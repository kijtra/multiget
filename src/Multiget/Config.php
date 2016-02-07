<?php
namespace Kijtra\Multiget;

class Config
{

    public static $curlOptions = array(
        // CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => false,
        CURLOPT_FILETIME => true,
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla',
    );

    public static $filePrefix = 'kmg_';
    public static $fileExt = '.txt';
    public static $cacheSeconds = 600;
    public static $storagePath;

    public static function setStorage($dir = null)
    {
        if (
            !empty($dir) &&
            ($dir = realpath($dir)) &&
            is_writable($dir)
        ) {
            self::$storagePath = rtrim($dir, '/\\').DIRECTORY_SEPARATOR;
        }
    }

    public static function setFilePrefix($prefix)
    {
        self::$filePrefix = trim($prefix, '_').'_';
    }

    public static function setFileExt($ext)
    {
        self::$fileExt = '.'.trim($ext, '.');
    }

    public static function setCacheSecond($seconds)
    {
        self::$cacheSeconds = $seconds;
    }

    public static function setTimeout($seconds)
    {
        self::$curlOptions[CURLOPT_TIMEOUT] = $seconds;
    }

    public static function setUserAgent($ua)
    {
        self::$curlOptions[CURLOPT_USERAGENT] = $ua;
    }
}
