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

class Task extends Base
{
    const PLUGIN_NAME = 'task';

    protected static function init()
    {
        if (!static::data('lock')) {
            static::data('lock', time());
            static::data('done', []);
        }
    }

    protected static function work()
    {
        if (static::data('lock') > time()) {
            return;
        }

        Log::info('检查每日任务');
        $data = static::check();

        static::double_watch_info($data);
        static::sign_info($data);

        if (count(static::data('done')) >= 2) {
            static::data('done', []);
            static::data('lock', strtotime(date('Y-m-d 23:59:59')) + 600);
        } else {
            static::data('lock', time() + 3600);
        }
    }

    protected static function check()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/i/api/taskInfo', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('每日任务检查失败');
        }

        return $data;
    }

    protected static function sign_info($value)
    {
        if (!isset($value['data']['sign_info'])) return;
        if (in_array('sign_info', static::data('done'))) return;

        Log::info('检查任务「每日签到」');

        $info = $value['data']['sign_info'];

        if ($info['status'] == 1) {
            Log::info('「每日签到」奖励已经领取');
            static::data('done', array_merge(static::data('done'), ['sign_info']));
            return;
        }

        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/appUser/getSignInfo', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('「每日签到」失败');
        } else {
            Log::notice('「每日签到」成功');
            static::data('done', array_merge(static::data('done'), ['sign_info']));
        }
    }

    protected static function double_watch_info($value)
    {
        if (!isset($value['data']['double_watch_info'])) return;
        if (in_array('double_watch_info', static::data('done'))) return;

        Log::info('检查任务「双端观看直播」');

        $info = $value['data']['double_watch_info'];

        if ($info['status'] == 2) {
            Log::info('「双端观看直播」奖励已经领取');
            static::data('done', array_merge(static::data('done'), ['double_watch_info']));
            return;
        }

        if ($info['mobile_watch'] != 1 || $info['web_watch'] != 1) {
            Log::warning('「双端观看直播」未完成，请等待');
            return;
        }

        $payload = [
            'task_id' => 'double_watch_task',
        ];
        $data = Curl::post('https://api.live.bilibili.com/activity/v1/task/receive_award', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('「双端观看直播」奖励领取失败');
        } else {
            Log::notice('「双端观看直播」奖励领取成功');
            foreach ($info['awards'] as $vo) {
                Log::info(sprintf("获得 %s × %d", $vo['name'], $vo['num']));
            }
            static::data('done', array_merge(static::data('done'), ['double_watch_info']));
        }
    }

}
