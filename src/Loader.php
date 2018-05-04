<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 18.05.04
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

namespace metowolf\Bilibili;

use Dotenv\Dotenv;

class Loader
{
    public static $conf;

    public static function config()
    {
        // 时区设置
        date_default_timezone_set('Asia/Shanghai');

        // 检查配置文件完整性
        try {
            // 加载配置文件
            $conf = new Dotenv(__DIR__, '/../config');
            $conf->load();
            // 账户设置
            $conf->required(['APP_USER', 'APP_PASS'])->notEmpty();
            $conf->required(['ACCESS_TOKEN', 'REFRESH_TOKEN']);
            // 功能设置
            $conf->required(['ROOM_ID', 'SOCKET_ROOM_ID', 'SMALLTV_RATE'])->isInteger();
            $conf->required(['SMALLTV_HOURS']);
            // 日志设置
            $conf->required(['APP_DEBUG'])->allowedValues(['true', 'false']);
            $conf->required(['CALLBACK_URL']);
            $conf->required(['CALLBACK_LEVEL'])->isInteger();
            
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
            die('当前配置文件 config 不完整，请使用 config.example 覆盖并重新填写');
        }

        self::$conf = $conf;
    }

    public static function overload()
    {
        self::$conf->overload();
    }
}
