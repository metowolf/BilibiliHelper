<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 18.04.21
 *
 * Copyright 2018, laverboy & metowolf
 * https://gist.github.com/laverboy/fd0a32e9e4e9fbbf9584
 * Released under the MIT license
 */

namespace metowolf\Bilibili;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Bramus\Monolog\Formatter\ColoredLineFormatter;
use metowolf\Bilibili\Curl;

class Log
{

    protected static $instance;

    static public function getLogger()
    {
        if (!self::$instance) {
            self::configureInstance();
        }
        return self::$instance;
    }

    protected static function configureInstance()
    {
        $logger = new Logger('Bilibili');
        $handler = new StreamHandler('php://stdout', getenv('APP_DEBUG') == 'true' ? Logger::DEBUG : Logger::INFO);
        $handler->setFormatter(new ColoredLineFormatter());
        $logger->pushHandler($handler);

        self::$instance = $logger;
    }

    private static function prefix()
    {
        if (getenv('APP_MULTIPLE') == 'true') {
            return '[' . (empty($t = getenv('APP_USER_IDENTITY')) ? getenv('APP_USER') : $t) . ']';
        }
        return '';
    }

    public static function debug($message, array $context = [])
    {
        $message = self::prefix() . $message;
        self::getLogger()->addDebug($message, $context);
        self::callback(Logger::DEBUG, 'DEBUG', $message);
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
        $callback_level = empty(getenv('APP_CALLBACK_LEVEL')) ? (Logger::ERROR) : intval(getenv('APP_CALLBACK_LEVEL'));
        if ($levelId >= $callback_level) {
            $url = str_replace('{account}', self::prefix(), getenv('APP_CALLBACK'));
            $url = str_replace('{level}', $level, $url);
            $url = str_replace('{message}', urlencode($message), $url);
            Curl::get($url);
        }
    }
}
