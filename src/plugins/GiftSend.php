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

class GiftSend extends Base
{
    const PLUGIN_NAME = 'giftsend';

    protected static function init()
    {
        if (!static::data('lock')) {
            static::data('lock', time());
        }
        if (!static::data('uid')) {
            if (!static::getRoomInfo()) {
                Log::error('直播间信息补全失败，礼物功能禁用');
                static::data('lock', strtotime('2099-12-31 00:00:00'));
            }
        }
    }

    protected static function work()
    {
        if (static::data('lock') > time()) {
            return;
        }

        $data = static::getBagList();

        if (isset($data['data']['list'])) {
            foreach ($data['data']['list'] as $vo) {
                if ($vo['expire_at'] >= $data['data']['time'] && $vo['expire_at'] <= $data['data']['time'] + 3600) {
                    static::giftSend($vo);
                    sleep(mt_rand(0, 5));
                }
            }
        }

        static::data('lock', time() + 600);
    }


    protected static function giftSend($value)
    {
        $payload = [
            'coin_type'        => 'silver',
            'gift_id'          => $value['gift_id'],
            'ruid'             => static::data('ruid'),
            'uid'              => static::data('uid'),
            'biz_id'           => static::data('roomid'),
            'gift_num'         => $value['gift_num'],
            'data_source_id'   => '',
            'data_behavior_id' => '',
            'bag_id'           => $value['bag_id']
        ];

        $data = Curl::post('https://api.live.bilibili.com/gift/v2/live/bag_send', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error("尝试向直播间投喂{$value['gift_name']}失败");
        } else {
            Log::notice("成功向直播间 {$payload['biz_id']} 投喂了 {$value['gift_num']} 个{$value['gift_name']}");
        }
    }

    protected static function getBagList()
    {
        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/gift/v2/gift/bag_list', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('背包查看失败');
            return null;
        }

        return $data;
    }

    protected static function getRoomInfo()
    {
        Log::info('正在补全用户信息');

        $payload = [];
        $data = Curl::get('https://account.bilibili.com/api/myinfo/v2', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('获取用户信息失败');
            return false;
        }

        static::data('uid', $data['mid']);


        Log::info('正在补全直播间信息');

        $payload = [
            'id' => static::config('ROOM_ID'),
        ];
        $data = Curl::get('https://api.live.bilibili.com/room/v1/Room/get_info', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('获取直播间信息失败');
            return false;
        }

        static::data('ruid', $data['data']['uid']);
        static::data('roomid', $data['data']['room_id']);

        return true;
    }

}
