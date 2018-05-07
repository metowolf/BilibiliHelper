<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

namespace BilibiliHelper\Plugin;

use BilibiliHelper\Lib\Curl;
use BilibiliHelper\Lib\Log;

class Base
{
    protected static $instance = [];
    protected static $config;

    public static function getInstance()
    {
        $calledClass = static::PLUGIN_NAME;
        if (!isset(static::$instance[$calledClass])) {
            static::$instance[$calledClass] = new static;
        }
        return static::$instance[$calledClass];
    }

    public static function run(&$config)
    {
        Curl::config($config);
        Log::config($config);
        static::$config = $config;
        static::init();
        static::work();
        $config = static::$config;
    }

    protected static function config($key, $value = null)
    {
        if (!is_null($value)) {
            file_put_contents(static::$config['path'], preg_replace(
                '/^'.$key.'=\S*/m',
                $key.'='.$value,
                file_get_contents(static::$config['path'])
            ));
            static::$config['config'][$key] = $value;
        }
        return static::$config['config'][$key];
    }

    protected static function data($key, $value = null)
    {
        $calledClass = static::PLUGIN_NAME;
        if (!is_null($value) || !isset(static::$config['data'][$calledClass][$key])) {
            static::$config['data'][$calledClass][$key] = $value;
        }
        return static::$config['data'][$calledClass][$key];
    }

    protected static function sign($payload)
    {
        # iOS 6680
        $appkey = '27eb53fc9058f8c3';
        $appsecret = 'c2ed53a74eeefe3cf99fbd01d8c9c375';

        # Android
        // $appkey = '1d8b6e7d45233436';
        // $appsecret = '560c52ccd288fed045859ed18bffd973';

        # 云视听 TV
        // $appkey = '4409e2ce8ffd12b8';
        // $appsecret = '59b43e04ad6965f34319062b478f83dd';

        $default = [
            'access_key' => static::$config['config']['ACCESS_TOKEN'],
            'actionKey' => 'appkey',
            'appkey' => $appkey,
            'build' => '6680',
            'device' => 'phone',
            'mobi_app' => 'iphone',
            'platform' => 'ios',
            'ts' => time(),
            'type' => 'json',
        ];
        $payload = array_merge($payload, $default);
        if (isset($payload['sign'])) {
            unset($payload['sign']);
        }
        ksort($payload);
        $data = http_build_query($payload);
        $payload['sign'] = md5($data . $appsecret);

        return $payload;
    }

}
