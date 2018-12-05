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

class Silver extends Base
{
    const PLUGIN_NAME = 'silver';

    protected static function init()
    {
        if (!static::data('lock')) {
            static::data('lock', time());
        }

        if (!static::data('task')) {
            static::data('task', 0);
        }
    }

    protected static function work()
    {
        if (static::data('lock') > time()) {
            return;
        }

        if (!static::data('task')) {
            static::getTask();
        } else {
            static::openTask();
        }
    }

    protected static function openTask()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/mobile/freeSilverAward', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('开启宝箱失败');
            static::data('lock', time() + mt_rand(60, 120));
            return;
        }

        Log::notice("开启宝箱成功，瓜子 {$data['data']['silver']}(+{$data['data']['awardSilver']})");

        static::data('task', 0);
        static::data('lock', time() + mt_rand(5, 20));
    }

    protected static function getTask()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/lottery/v1/SilverBox/getCurrentTask', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code'] == -10017) {
            Log::notice($data['message']);
            static::data('lock', strtotime(date('Y-m-d 23:59:59')) + 600);
            return;
        }

        if (isset($data['code']) && $data['code']) {
            Log::error('领取宝箱任务失败');
            return;
        }

        Log::notice("领取宝箱成功，内含 {$data['data']['silver']} 个瓜子");
        Log::info("等待 {$data['data']['minute']} 分钟后打开宝箱");

        static::data('task', $data['data']['time_start']);
        static::data('lock', time() + $data['data']['minute'] * 60 + mt_rand(5, 30));
    }

}
