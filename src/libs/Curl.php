<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

namespace BilibiliHelper\Lib;

class Curl
{
    protected static $config;
    protected static $instance;
    protected static $jar;

    public static function getClient()
    {
        if (!self::$instance) {
            self::configureInstance();
        }
        return self::$instance;
    }

    public static function getJar()
    {
        if (!self::$jar) {
            self::$jar = new \GuzzleHttp\Cookie\CookieJar;
        }
        return self::$jar;
    }

    public static function config(&$config)
    {
        static::$config = $config;

        $cookie = json_decode($config['config']['COOKIE_JAR'], true);
        static::getJar()->__construct(true, $cookie);
    }

    protected static function configureInstance()
    {
        $options = [
            'headers' => [
                'Accept'          => '*/*',
                'Accept-Encoding' => 'gzip',
                'Accept-Language' => 'zh-cn',
                'Connection'      => 'keep-alive',
                'Content-Type'    => 'application/x-www-form-urlencoded',
                'User-Agent'      => 'bili-universal/8230 CFNetwork/975.0.3 Darwin/18.2.0',
                'Referer'         => 'https://live.bilibili.com/'.static::$config['config']['ROOM_ID'],
            ],
            'timeout'     => 20.0,
            'http_errors' => false,
        ];
        if (!empty(static::$config['config']['NETWORK_PROXY'])) {
            $options['proxy'] = static::$config['config']['NETWORK_PROXY'];
        }

        self::$instance = new \GuzzleHttp\Client($options);
    }

    public static function get($url, $params = [])
    {
        Log::debug('GET: '.$url);
        $payload = [
            'cookies' => self::getJar(),
        ];
        if (count($params)) {
            $payload['query'] = $params;
        }
        $request = self::getClient()->get($url, $payload);
        $body = $request->getBody();
        Log::debug($body);
        return $body;
    }

    public static function post($url, $params = [])
    {
        Log::debug('POST: '.$url);
        $payload = [
            'cookies' => self::getJar(),
        ];
        if (count($params)) {
            $payload['form_params'] = $params;
        }
        $request = self::getClient()->post($url, $payload);
        $body = $request->getBody();
        Log::debug($body);
        return $body;
    }

    public static function cookie()
    {
        $cookies = self::getJar()->toArray();
        $cookies = json_encode($cookies);
        Log::debug($cookies);
        return $cookies;
    }

}
