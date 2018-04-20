<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 18.04.19
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

class Log {

	protected static $instance;

	static public function getLogger()
	{
		if (! self::$instance) {
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

	public static function debug($message, array $context = []){
		self::getLogger()->addDebug($message, $context);
	}

	public static function info($message, array $context = []){
		self::getLogger()->addInfo($message, $context);
	}

	public static function notice($message, array $context = []){
		self::getLogger()->addNotice($message, $context);
	}

	public static function warning($message, array $context = []){
		self::getLogger()->addWarning($message, $context);
	}

	public static function error($message, array $context = []){
		self::getLogger()->addError($message, $context);
		self::callback($message);
	}

	public static function callback($message){
		$url = str_replace('{message}', urlencode($message), getenv('APP_CALLBACK'));
		Curl::get($url);
	}

}
