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

use metowolf\Bilibili\Curl;
use metowolf\Bilibili\Sign;
use metowolf\Bilibili\Log;

class SignIn
{
    protected static $lock = 0;

    public static function run()
    {
        if (self::$lock > time()) {
            return;
        }

        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/mobile/getUser', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('check userinfo failed!', $data['message']);
            die();
        }

        if ($data['data']['isSign'] != 1) {
            self::check();
        } else {
            Log::warning('Already signed!');
        }

        self::$lock = time() + 3600;
    }

    protected static function check()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/appUser/getSignInfo', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('sign in failed!', $data['message']);
        }

        Log::info($data['data']['sign_msg']);
    }
}
