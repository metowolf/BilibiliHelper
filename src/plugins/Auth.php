<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

namespace BilibiliHelper\Plugin;

use BilibiliHelper\Lib\Log;
use BilibiliHelper\Lib\Curl;

class Auth extends Base
{
    const PLUGIN_NAME = 'auth';

    protected static function init()
    {
        if (!static::data('lock')) {
            static::data('lock', time());
        }
    }

    protected static function work()
    {
        if (static::data('lock') > time()) {
            return;
        }

        if (static::config('ACCESS_TOKEN') == '') {
            static::loginPassword();
        } else {
            static::loginToken();
        }

        static::checkCookie();

        static::data('lock', time() + 3600);
    }

    protected static function loginPassword()
    {
        $data = static::getPublicKey();

        $user = static::config('APP_USER');
        $pass = static::config('APP_PASS');
        $key = $data['data']['key'];
        $hash = $data['data']['hash'];
        openssl_public_encrypt($hash.$pass, $crypt, $key);

        static::getToken($user, base64_encode($crypt));
        static::saveCookie();
    }

    protected static function loginToken()
    {
        if (! static::checkToken()) {
            Log::warning('检测到令牌即将过期');
            Log::info('申请更换令牌');
            if (! static::refresh()) {
                Log::warning('更换令牌失败');
                Log::info('使用帐号密码方式登录');
                static::loginPassword();
            }
            static::saveCookie();
        }
    }

    protected static function getCookie()
    {
        $payload = [];
        $data = Curl::get('https://passport.bilibili.com/api/login/sso', static::sign($payload));

        static::saveCookie();
    }

    protected static function saveCookie()
    {
        static::config('COOKIE_JAR', Curl::cookie());
    }

    protected static function checkCookie()
    {
        $payload = [
            'ts' => time(),
        ];
        $data = Curl::get('https://api.live.bilibili.com/User/getUserInfo', $payload);
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code'] != 'REPONSE_OK') {
            Log::error('检测到 Cookie 过期');
            static::getCookie();
        }
    }

    protected static function checkToken()
    {
        $payload = [
            'access_token' => static::config('ACCESS_TOKEN'),
        ];
        $data = Curl::get('https://passport.bilibili.com/api/oauth2/info', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('令牌验证失败');
            return false;
        }
        Log::info('令牌验证成功，有效期: '.date('Y-m-d H:i:s', $data['ts']+$data['data']['expires_in']));

        return $data['data']['expires_in'] > 86400;
    }

    protected static function refresh()
    {
        $payload = [
            'access_token' => static::config('ACCESS_TOKEN'),
            'refresh_token' => static::config('REFRESH_TOKEN'),
        ];
        $data = Curl::post('https://passport.bilibili.com/api/oauth2/refreshToken', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('续签令牌失败', ['msg' => $data['message']]);
            return false;
        } else {
            Log::notice('续签令牌成功');
        }

        return true;
    }

    protected static function getPublicKey()
    {
        $payload = [];
        $data = Curl::post('https://passport.bilibili.com/api/oauth2/getKey', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('公钥获取失败', ['msg' => $data['message']]);
        }
        Log::info('公钥获取成功');

        return $data;
    }

    protected static function getToken($username, $password)
    {
        $payload = [
            'subid' => 1,
            'permission' => 'ALL',
            'username' => $username,
            'password' => $password,
            'captcha' => '',
        ];
        $data = Curl::post('https://passport.bilibili.com/api/v2/oauth2/login', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('帐号登录失败');
            throw new \Exception($data['message']);
        }
        Log::notice('帐号登录成功');

        static::config('ACCESS_TOKEN', $data['data']['token_info']['access_token']);
        static::config('REFRESH_TOKEN', $data['data']['token_info']['refresh_token']);
    }
}
