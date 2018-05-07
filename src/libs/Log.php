<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

namespace BilibiliHelper\Lib;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Bramus\Monolog\Formatter\ColoredLineFormatter;

class Log
{
    protected static $config;
    protected static $instance;

    public static function getLogger()
    {
        if (!self::$instance) {
            self::configureInstance();
        }
        return self::$instance;
    }

    public static function config(&$config)
    {
        static::$config = $config;
        return new self;
    }

    protected static function configureInstance()
    {
        $handler = new StreamHandler('php://stdout', static::$config['config']['APP_DEBUG'] ? Logger::DEBUG : Logger::INFO);
        $handler->setFormatter(new ColoredLineFormatter(null, "[%datetime%] %channel%.%level_name%: %message%\n"));

        $logger = new Logger('Bilibili');
        $logger->pushHandler($handler);

        self::$instance = $logger;
    }

    private static function prefix()
    {
        if (static::$config['config']['APP_MULTIPLE']) {
            return '[' . (empty($t = static::$config['config']['APP_USER_IDENTITY']) ? static::$config['config']['APP_USER'] : $t) . ']';
        }
        return '';
    }

    public static function debug($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::getLogger()->addDebug($message, $context);
    }

    public static function info($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::getLogger()->addInfo($message, $context);
        self::callback(Logger::INFO, 'INFO', $message);
    }

    public static function notice($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::getLogger()->addNotice($message, $context);
        self::callback(Logger::NOTICE, 'NOTICE', $message);
    }

    public static function warning($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::getLogger()->addWarning($message, $context);
        self::callback(Logger::WARNING, 'WARNING', $message);
    }

    public static function error($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::getLogger()->addError($message, $context);
        self::callback(Logger::ERROR, 'ERROR', $message);
    }

    public static function callback($levelId, $level, $message)
    {
        $callback_level = intval(static::$config['config']['CALLBACK_LEVEL']);
        if ($levelId >= $callback_level) {
            $url = str_replace('{account}', self::prefix(), static::$config['config']['CALLBACK_URL']);
            $url = str_replace('{level}', $level, $url);
            $url = str_replace('{message}', urlencode($message), $url);
            Curl::get($url);
        }
    }

}
