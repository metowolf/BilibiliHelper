<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 18.04.21
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

        Log::info('正在检查每日任务...');

        $data = self::check();

        if (isset($data['data']['double_watch_info'])) {
            self::double_watch_info($data['data']['double_watch_info']);
        }
        if (isset($data['data']['sign_info'])) {
            self::sign_info($data['data']['sign_info']);
        }

        self::$lock = time() + 3600;
    }

    protected static function check()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/i/api/taskInfo', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('每日任务检查失败!', ['msg' => $data['message']]);
        }

        return $data;
    }

    protected static function sign_info($info)
    {
        Log::info('检查任务「每日签到」...');

        if ($info['status'] == 1) {
            Log::notice('该任务已完成');
            return;
        }

        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/appUser/getSignInfo', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('签到失败', ['msg' => $data['message']]);
        } else {
            Log::info('签到成功');
        }
    }

    protected static function double_watch_info($info)
    {
        Log::info('检查任务「双端观看直播」...');

        if ($info['status'] == 2) {
            Log::notice('已经领取奖励');
            return;
        }

        if ($info['mobile_watch'] != 1 || $info['web_watch'] != 1) {
            Log::notice('任务未完成，请等待');
            return;
        }

        $payload = [
            'task_id' => 'double_watch_task',
        ];
        $data = Curl::post('https://api.live.bilibili.com/activity/v1/task/receive_award', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('「双端观看直播」任务奖励领取失败!', ['msg' => $data['message']]);
        } else {
            Log::info('奖励领取成功!');
            foreach ($info['awards'] as $vo) {
                Log::info(sprintf("获得 %s × %d", $vo['name'], $vo['num']));
            }
        }
    }
}
