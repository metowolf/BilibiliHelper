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

class Task
{
    protected static $lock = 0;

    public static function run()
    {
        if (self::$lock > time()) {
            return;
        }

        $data = self::check();
        if (isset($data['double_watch_info']) && $data['double_watch_info']['status'] == 1) {
            self::double_watch_info();
        }

        self::$lock = time() + 3600;
    }

    protected static function check()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/i/api/taskInfo', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('每日任务检查失败! Error message: '.$data['message']);
        }

        return $data;
    }

    protected static function double_watch_info()
    {
        $payload = [
            'task_id' => 'double_watch_task',
        ];
        $data = Curl::post('https://api.live.bilibili.com/activity/v1/task/receive_award', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('「双端观看直播」任务奖励领取失败! Error message: '.$data['message']);
        }

        Log::info('「双端观看直播」任务奖励领取成功!');
    }
}
