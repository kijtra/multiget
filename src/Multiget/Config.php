<?php
namespace Kijtra\Multiget;

trait Config
{
    public static $optionsGetHeader = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FILETIME => true,
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla',
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_FORBID_REUSE => false,
        CURLOPT_ENCODING => '',
    );

    public static $optionsGetContent = array(
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

    public static function setTimeout($seconds)
    {
        self::$optionsGetHeader[CURLOPT_TIMEOUT] = $seconds;
        self::$optionsGetContent[CURLOPT_TIMEOUT] = $seconds;
    }

    public static function setUserAgent($ua)
    {
        self::$optionsGetHeader[CURLOPT_USERAGENT] = $ua;
        self::$optionsGetContent[CURLOPT_USERAGENT] = $ua;
    }
}
