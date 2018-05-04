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

class Silver
{
    protected static $lock = 0;
    protected static $task = 0;

    public static function run()
    {
        if (self::$lock > time()) {
            return;
        }

        if (!empty(self::$task)) {
            self::pushTask();
        } else {
            self::pullTask();
        }
    }

    protected static function pushTask()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/mobile/freeSilverAward', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('开启宝箱失败！', $data['message']);
            self::$lock = time() + 60;
            return;
        }

        Log::notice("开启宝箱成功，瓜子 {$data['data']['silver']}(+{$data['data']['awardSilver']})");

        self::$task = 0;
        self::$lock = time() + 10;
    }

    protected static function pullTask()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/mobile/freeSilverCurrentTask', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code'] == -10017) {
            Log::notice($data['message']);
            self::$lock = strtotime(date('Y-m-d 23:59:59')) + 600;
            return;
        }

        if (isset($data['code']) && $data['code']) {
            Log::error('领取宝箱任务失败！'.$data['message']);
            die();
        }

        Log::notice("领取宝箱成功，内含 {$data['data']['silver']} 个瓜子");
        Log::info("等待 {$data['data']['minute']} 分钟后打开宝箱");

        self::$task = $data['data']['time_start'];
        self::$lock = time() + $data['data']['minute'] * 60 + mt_rand(5, 30);
    }
}
