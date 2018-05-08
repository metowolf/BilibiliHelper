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
use Wrench\Client;

class Websocket extends Base
{
    const PLUGIN_NAME = 'websocket';

    protected static function init()
    {
        if (!static::data('lock')) {
            static::data('lock', time());
        }

        if (!static::data('heartlock')) {
            static::data('heartlock', time());
        }

        if (!static::data('websocket')) {
            $client = new Client(
                'wss://broadcastlv.chat.bilibili.com:2245/sub',
                'https://live.bilibili.com'
            );
            static::data('websocket', $client);
        }

        static::data('smalltv', []);
    }

    protected static function work()
    {
        if (static::data('lock') > time()) {
            return;
        }

        static::heart();
        static::receive();
    }

    protected static function type($id)
    {
        $option = [
            0x0002 => 'WS_OP_HEARTBEAT',
            0x0003 => 'WS_OP_HEARTBEAT_REPLY',
            0x0005 => 'WS_OP_MESSAGE',
            0x0007 => 'WS_OP_USER_AUTHENTICATION',
            0x0008 => 'WS_OP_CONNECT_SUCCESS',
        ];
        return isset($option[$id]) ? $option[$id] : "WS_OP_UNKNOW($id)";
    }

    protected static function parse($raw)
    {
        $data = json_decode($raw, true);
        if ($data['cmd'] == 'SYS_MSG' && isset($data['tv_id'])) {
            Log::debug($raw);
            Log::notice("直播间 {$data['real_roomid']} 开启了第 {$data['tv_id']} 轮小电视抽奖");
            static::$config['data'][static::PLUGIN_NAME]['smalltv'][$data['tv_id']] = $data['real_roomid'];
        }
    }

    protected static function split($bin)
    {
        if (strlen($bin)) {
            $head = unpack('N*', substr($bin, 0, 16));
            $bin = substr($bin, 16);

            $length = isset($head[1]) ? $head[1] : 16;
            $type = isset($head[3]) ? $head[3] : 0x0000;
            $body = substr($bin, 0, $length-16);

            Log::debug(static::type($type)." (len=$length)");
            if ($type == 0x0005) {
                static::parse($body);
            }

            $bin = substr($bin, $length-16);
            if (strlen($bin)) {
                static::split($bin);
            }
        }
    }

    protected static function receive()
    {
        $responses = static::data('websocket')->receive();
        if (is_array($responses)) {
            foreach ($responses as $response) {
                static::split($response->getPayload());
            }
        }
    }

    protected static function connect()
    {
        Log::notice('连接弹幕服务器');
        if (!static::data('websocket')->connect()) {
            Log::error('连接弹幕服务器失败');
            static::data('lock', time()+60);
            return;
        }
        static::data('websocket')->sendData(
            static::packMsg(json_encode([
                'uid' => 0,
                'roomid' => static::config('SOCKET_ROOM_ID'),
                'protover' => 1,
                'platform' => 'web',
                'clientver' => '1.3.3',
            ]), 0x0007)
        );
    }

    protected static function disconnect()
    {
        Log::info('断开弹幕服务器');
        static::data('websocket')->disconnect();
    }

    protected static function heart() {
        if (!static::data('websocket')->isConnected()) {
            static::connect();
            return;
        }
        if (static::data('heartlock') <= time()) {
            if (static::data('websocket')->sendData(static::packMsg('', 0x0002))) {
                static::data('heartlock', time()+30);
            }
        }
    }

    protected static function packMsg($value, $option) {
        $head = pack('NnnNN', 0x10 + strlen($value), 0x10, 0x01, $option, 0x0001);
        $str = $head.$value;
        static::split($str);
        return $str;
    }
}
