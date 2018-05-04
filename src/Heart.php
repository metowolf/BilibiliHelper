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

class Heart
{
    protected static $lock = 0;

    public static function run()
    {
        if (self::$lock > time()) {
            return;
        }

        $roomId = getenv('ROOM_ID');
        self::pc($roomId);
        self::mobile($roomId);

        self::$lock = time() + 300;
    }

    public static function pc($roomId)
    {
        $payload = [
            'room_id' => $roomId,
        ];
        $data = Curl::post('https://api.live.bilibili.com/User/userOnlineHeart', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('直播间 ' . $roomId . ' 心跳异常', ['msg' => $data['message']]);
        } else {
            Log::info('向直播间 ' . $roomId . ' 发送心跳包');
        }
    }

    public static function mobile($roomId)
    {
        $payload = [
            'room_id' => $roomId,
        ];
        $data = Curl::post('https://api.live.bilibili.com/mobile/userOnlineHeart', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('直播间 ' . $roomId . ' 心跳异常（客户端）', ['msg' => $data['message']]);
        } else {
            Log::info('向直播间 ' . $roomId . ' 发送心跳包（客户端）');
        }
    }
}
