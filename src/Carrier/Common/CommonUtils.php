<?php

namespace Carrier\Common;

/**
 * Common utils
 * 
 * @package carrier-core
 *
 * @author juancrrn
 *
 * @version 0.0.1
 */

class CommonUtils
{
    
    /**
     * @var string Standard MySQL DATETIME format
     */
    public const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    
    /**
     * @var string Standard MySQL DATE format
     */
    public const MYSQL_DATE_FORMAT = 'Y-m-d';
    
    /**
     * @var string Standard human-readable Spanish DATETIME format
     */
    public const HUMAN_DATETIME_FORMAT = 'j \d\e F \d\e\l Y \a \l\a\s H:i';
    
    /**
     * @var string Standard human-readable Spanish DATETIME format for strftime
     */
    public const HUMAN_DATETIME_FORMAT_STRF = '%e de %B de %Y a las %H:%M';
    
    /**
     * @var string Standard human-readable Spanish DATE format
     */
    public const HUMAN_DATE_FORMAT = 'j \d\e F \d\e\l Y';
    
    /**
     * @var string Standard human-readable Spanish DATE format for strftime
     */
    public const HUMAN_DATE_FORMAT_STRF = '%e de %B de %Y';

    /**
     * Quick debug function
     */
    public static function dd($var)
    {
        $bt = debug_backtrace();

        echo 'dd(): ' . $bt[1]['class'] . '::' . $bt[1]['function'] . '() @ ' . $bt[1]['file'] . ':' . $bt[1]['line'] . "\n";

        var_dump($var);

        die();
    }

    /**
     * Quick debug function with file name and line number
     */
    public static function ddl(?string $message, mixed $var)
    {
        $bt = debug_backtrace();
        $caller = array_shift($bt);

        echo 'ddl(): ' . $caller['file'] . ':' . $caller['line'] . "\n";

        if (! is_null($message)) {
            echo $message . "\n";
        }

        if (! is_null($var)) {
            var_dump($var);
        }

        die();
    }
}