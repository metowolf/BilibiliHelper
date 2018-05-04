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

use metowolf\Bilibili\Curl;
use metowolf\Bilibili\Sign;
use metowolf\Bilibili\Log;

class Daily
{
    protected static $lock = 0;

    public static function run()
    {
        if (self::$lock > time()) {
            return;
        }

        self::dailyBag();
    }

    protected static function dailyBag()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/gift/v2/live/receive_daily_bag', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('每日礼包领取失败!', ['msg' => $data['message']]);
            self::$lock = time() + 600;
        } else {
            Log::notice('每日礼包领取成功');
            self::$lock = strtotime(date('Y-m-d 23:59:59')) + 600;
        }
    }

}
