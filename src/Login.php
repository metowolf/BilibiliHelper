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

class Login
{
    public static function getAccessKey($value='')
    {
        $access_key = getenv('ACCESS_TOKEN');
        if (empty($access_key) || !self::checkInfo()) {
            Log::warning('access_token expired! Try to renew...');
            self::login();
        } else {
            Log::info('access_token OK!');
            return $access_key;
        }
    }

    protected static function login()
    {
        $user = getenv('APP_USER');
        $pass = getenv('APP_PASS');
        if (empty($user) || empty($pass)) {
            Log::error('empty APP_USER and APP_PASS!');
            die();
        }

        $payload = [];
        $data = Curl::post('https://passport.bilibili.com/api/oauth2/getKey', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('get public key failed! Error message: '.$data['message']);
            die();
        }

        $public_key = $data['data']['key'];
        $hash = $data['data']['hash'];
        openssl_public_encrypt($hash.$pass, $crypt, $public_key);

        $payload = [
            'subid' => 1,
            'permission' => 'ALL',
            'username' => $user,
            'password' => base64_encode($crypt),
            'captcha' => '',
        ];
        $data = Curl::post('https://passport.bilibili.com/api/v2/oauth2/login', Sign::api($payload));

        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('login failed! Error message: '.$data['message']);
            die();
        }

        Log::info('success!');

        $access_token = $data['data']['token_info']['access_token'];
        self::writeNewEnvironmentFileWith('ACCESS_TOKEN', $access_token);
        Log::info('access token: '.$access_token);

        $refresh_token = $data['data']['token_info']['refresh_token'];
        self::writeNewEnvironmentFileWith('REFRESH_TOKEN', $refresh_token);
        Log::info('refresh token: '.$refresh_token);

        return;
    }

    protected static function checkInfo()
    {
        $payload = [];
        $data = Curl::get('https://account.bilibili.com/api/myinfo/v2', Sign::api($payload));
        $data = json_decode($data, true);

        return !isset($data['code']) || $data['code'] == 0;
    }

    protected static function writeNewEnvironmentFileWith($key, $value)
    {
        file_put_contents(__DIR__.'/../config', preg_replace(
            '/^'.$key.'='.getenv($key).'/m',
            $key.'='.$value,
            file_get_contents(__DIR__.'/../config')
        ));
    }
}
