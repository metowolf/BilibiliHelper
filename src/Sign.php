<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 18.04.19
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

namespace metowolf\Bilibili;

class Sign
{
    public static function api($payload)
    {
        $default = [
            'access_key' => getenv('ACCESS_TOKEN'),
            'actionKey' => 'appkey',
            'appkey' => getenv('APP_KEY'),
            'appver' => '6620',
            'build' => '6620',
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
        $payload['sign'] = md5($data.getenv('APP_SECRET'));

        return $payload;
    }
}
