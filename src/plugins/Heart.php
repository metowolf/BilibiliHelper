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

class Heart extends Base
{
    const PLUGIN_NAME = 'heart';

    protected static function init()
    {
        if (!static::data('lock')) {
            static::data('lock', time());
        }
    }

    protected static function work()
    {
        if (static::data('lock') > time()) {
            return;
        }

        $roomId = static::config('ROOM_ID');
        static::web($roomId);
        static::mobile($roomId);

        static::data('lock', time() + 300);
    }

    public static function web($roomId)
    {
        $data = Curl::post('https://api.live.bilibili.com/User/userOnlineHeart');
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning("直播间 $roomId 心跳异常 (web)");
        } else {
            Log::info("向直播间 $roomId 发送心跳包 (web)");
        }
    }

    public static function mobile($roomId)
    {
        $payload = [
            'room_id' => $roomId,
        ];
        $data = Curl::post('https://api.live.bilibili.com/mobile/userOnlineHeart', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning("直播间 $roomId 心跳异常 (APP)");
        } else {
            Log::info("向直播间 $roomId 发送心跳包 (APP)");
        }
    }
}
