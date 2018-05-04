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

class Sign
{
    public static function api($payload)
    {
        # iOS 6670
        $appkey = '27eb53fc9058f8c3';
        $appsecret = 'c2ed53a74eeefe3cf99fbd01d8c9c375';

        # Android
        // $appkey = '1d8b6e7d45233436';
        // $appsecret = '560c52ccd288fed045859ed18bffd973';

        $default = [
            'access_key' => getenv('ACCESS_TOKEN'),
            'actionKey' => 'appkey',
            'appkey' => $appkey,
            'build' => '6670',
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
