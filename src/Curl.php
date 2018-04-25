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

use metowolf\Bilibili\Log;

class Curl
{
    public static $header = array(
            'Accept'          => '*/*',
            'Accept-Encoding' => 'gzip',
            'Accept-Language' => 'zh-cn',
            'Connection'      => 'keep-alive',
            'Content-Type'    => 'application/x-www-form-urlencoded',
            'User-Agent'      => 'User-Agent: bili-universal/6620 CFNetwork/897.15 Darwin/17.5.0',
    );

    public static function post($url, $payload = null)
    {
        $header = array_map(function ($k, $v) {
            return $k.': '.$v;
        }, array_keys(self::$header), self::$header);
        $curl = curl_init();
        if (!is_null($payload)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, is_array($payload) ? http_build_query($payload) : $payload);
        }
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_IPRESOLVE, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $raw = curl_exec($curl);
        Log::debug($raw);
        curl_close($curl);

        return $raw;
    }

    public static function get($url, $payload = null)
    {
        if (!is_null($payload)) {
            $url .= '?'.http_build_query($payload);
        }
        return self::post($url, null);
    }
}
