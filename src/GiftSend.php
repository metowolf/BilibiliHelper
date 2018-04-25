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

class GiftSend
{
    protected static $lock = 0;
    protected static $uid = 0;
    protected static $ruid = 0;
    protected static $roomid = 0;

    protected static function getRoomInfo()
    {
        Log::info('正在生成直播间信息...');

        $payload = [];
        $data = Curl::get('https://account.bilibili.com/api/myinfo/v2', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('获取帐号信息失败!', ['msg' => $data['message']]);
            Log::warning('清空礼物功能禁用!');
            self::$lock = time() + 100000000;
            return;
        }

        self::$uid = $data['mid'];

        $payload = [
            'id' => getenv('ROOM_ID'),
        ];
        $data = Curl::get('https://api.live.bilibili.com/room/v1/Room/get_info', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('获取主播房间号失败!', ['msg' => $data['message']]);
            Log::warning('清空礼物功能禁用!');
            self::$lock = time() + 100000000;
            return;
        }

        Log::info('直播间信息生成完毕!');

        self::$ruid = $data['data']['uid'];
        self::$roomid = $data['data']['room_id'];
    }

    public static function run()
    {
        if (empty(self::$ruid)) {
            self::getRoomInfo();
        }

        if (self::$lock > time()) {
            return;
        }

        $payload = [];
        $data = Curl::get('https://api.live.bilibili.com/gift/v2/gift/bag_list', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('背包查看失败!', ['msg' => $data['message']]);
        }

        if (isset($data['data']['list'])) {
            foreach ($data['data']['list'] as $vo) {
                if ($vo['expire_at'] >= $data['data']['time'] && $vo['expire_at'] <= $data['data']['time'] + 3600) {
                    self::send($vo);
                    sleep(3);
                }
            }
        }

        self::$lock = time() + 600;
    }

    protected static function send($value)
    {
        $payload = [
            'coin_type'        => 'silver',
            'gift_id'          => $value['gift_id'],
            'ruid'             => self::$ruid,
            'uid'              => self::$uid,
            'biz_id'           => self::$roomid,
            'gift_num'         => $value['gift_num'],
            'data_source_id'   => '',
            'data_behavior_id' => '',
            'bag_id'           => $value['bag_id']
        ];

        $data = Curl::post('https://api.live.bilibili.com/gift/v2/live/bag_send', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('送礼失败!', ['msg' => $data['message']]);
        } else {
            Log::info("成功向 {$payload['biz_id']} 投喂了 {$value['gift_num']} 个{$value['gift_name']}");
        }
    }
}
