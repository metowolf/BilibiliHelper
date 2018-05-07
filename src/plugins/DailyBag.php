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

class DailyBag extends Base
{
    const PLUGIN_NAME = 'dailybag';

    protected static function init()
    {
        if (!static::data('lock_web')) {
            static::data('lock_web', time());
        }

        if (!static::data('lock_mobile')) {
            static::data('lock_mobile', time());
        }
    }

    protected static function work()
    {
        if (static::data('lock_web') <= time()) {
            static::web();
        }

        if (static::data('lock_mobile') <= time()) {
            static::mobile();
        }
    }

    public static function web()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/gift/v2/live/receive_daily_bag', $payload);
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('每日礼包领取失败');
            static::data('lock_web', time() + 600);
        } else {
            Log::notice('每日礼包领取成功');
            static::data('lock_web', strtotime(date('Y-m-d 23:59:59')) + 600);
        }
    }

    public static function mobile()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/AppBag/sendDaily', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('每日礼包领取失败 (APP)');
            static::data('lock_mobile', time() + 600);
        } else {
            Log::notice('每日礼包领取成功 (APP)');
            static::data('lock_mobile', strtotime(date('Y-m-d 23:59:59')) + 600);
        }

        static::data('lock_mobile', strtotime(date('Y-m-d 23:59:59')) + 600);
    }
}
