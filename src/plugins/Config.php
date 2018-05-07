<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 18.05.04
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

namespace BilibiliHelper\Plugin;

use Dotenv\Dotenv;

class Config
{
    protected static $instance;

    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public static function parse($configName)
    {
        $conf = new Dotenv(__DIR__.'/../../', $configName);

        // 检查配置文件完整性
        try {
            // 加载配置文件
            $conf->overload();
            // 账户设置
            $conf->required(['APP_USER', 'APP_PASS'])->notEmpty();
            $conf->required(['ACCESS_TOKEN', 'REFRESH_TOKEN', 'COOKIE_JAR']);
            // 功能设置
            $conf->required(['ROOM_ID', 'SOCKET_ROOM_ID', 'SMALLTV_RATE'])->isInteger();
            $conf->required(['SMALLTV_HOURS']);
            // 网络设置
            $conf->required(['NETWORK_PROXY']);
            // 日志设置
            $conf->required(['APP_DEBUG', 'APP_MULTIPLE'])->allowedValues(['true', 'false']);
            $conf->required(['APP_USER_IDENTITY', 'CALLBACK_URL']);
            $conf->required(['CALLBACK_LEVEL'])->isInteger();

        } catch (\Exception $e) {
            echo $e->getMessage(), PHP_EOL;
            echo "当前配置文件 config 不完整，请重新覆盖并填写配置文件", PHP_EOL, PHP_EOL;
            echo " $ mv config config.old", PHP_EOL;
            echo " $ cp config.example config", PHP_EOL, PHP_EOL;
            exit(1);
        }

        return [
            'path' => __DIR__.'/../../'.$configName,
            'config' => [
                // 账户设置
                'APP_USER' => getenv('APP_USER'),
                'APP_PASS' => getenv('APP_PASS'),
                'ACCESS_TOKEN' => getenv('ACCESS_TOKEN'),
                'REFRESH_TOKEN' => getenv('REFRESH_TOKEN'),
                'COOKIE_JAR' => empty(getenv('COOKIE_JAR')) ? '[]' : getenv('COOKIE_JAR'),
                // 功能设置
                'ROOM_ID' => intval(getenv('ROOM_ID')),
                'SOCKET_ROOM_ID' => intval(getenv('SOCKET_ROOM_ID')),
                'SMALLTV_RATE' => intval(getenv('SMALLTV_RATE')),
                'SMALLTV_HOURS' => array_map('intval', explode(',', getenv('SMALLTV_HOURS'))),
                // 网络设置
                'NETWORK_PROXY' => getenv('NETWORK_PROXY'),
                // 日志设置
                'APP_DEBUG' => getenv('APP_DEBUG') === 'true',
                'APP_MULTIPLE' => getenv('APP_MULTIPLE') === 'true',
                'APP_USER_IDENTITY' => getenv('APP_USER_IDENTITY'),
                'CALLBACK_URL' => getenv('CALLBACK_URL'),
                'CALLBACK_LEVEL' => intval(getenv('CALLBACK_LEVEL')),
            ],
        ];
    }

}
