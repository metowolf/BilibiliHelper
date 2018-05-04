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

use metowolf\Bilibili\Log;

class Socket
{
    const ACTION_HEART = 0x02;
    const ACTION_ENTRY = 0x07;

    protected static $socket = NULL;
    protected static $heartLock = 0;

    public static function connect()
    {
        $payload = [
            'room_id' => intval(getenv('SOCKET_ROOM_ID')),
        ];
        $data = Curl::get('https://api.live.bilibili.com/room/v1/Danmu/getConf', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('弹幕服务器连接信息获取失败', ['msg' => $data['message']]);
            return false;
        }

        $socketIp = $data['data']['host'];
        $socketPort = $data['data']['port'];

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 2, 'usec' => 0));

        if (socket_connect($socket, $socketIp, $socketPort) === false) {
            Log::warning('弹幕服务器无法连接');
            return false;
        }
        self::$socket = $socket;

        $data = sprintf("{\"uid\":%d%08d,\"roomid\":%d}",
            mt_rand(1000000, 2999999),
            mt_rand(0, 99999999),
            intval(getenv('SOCKET_ROOM_ID'))
        );
        self::send(self::ACTION_ENTRY, $data);

        return true;
    }

    public static function heart()
    {
        if (is_null(self::$socket) && !self::connect()) {
            return false;
        }

        if (self::$heartLock > time()) {
            return false;
        }
        self::$heartLock = time() + 30;

        self::send(self::ACTION_HEART);

        return true;
    }

    public static function receive()
    {
        if (is_null(self::$socket) && !self::connect()) {
            return false;
        }

        /*
        +-----------+-----------+-----------+-----------+
        |   Length  |  UNKNOWN  |   TYPE    |  UNKNOWN  |
        +-----------+-----------+-----------+-----------+
        |     4     |  2  |  2  |    4      |     4     |
        +-----------+-----------+-----------+-----------+
        |                      DATA                     |
        +-----------------------------------------------+
        |                  $Length - 16                 |
        +-----------------------------------------------+
        */

        $length = -1;
        $type = -1;
        while ($length < 0x10 || $length > 0x100000 || $type < 0 || $type > 0x100) {
            if (($head = socket_read(self::$socket, 0x10)) === false) {
                return false;
            }
            Log::debug('RECV: '.bin2hex($head));
            $head = unpack('N*', $head);
            if (count($head) != 4) {
                return false;
            }
            $length = $head[1];
            $type = $head[3];
        }

        if ($length == 0x10) {
            return false;
        }

        $raw = socket_read(self::$socket, $length - 0x10);
        if ($type != 0x05) {
            return false;
        }

        return $raw;
    }

    public static function send($action, $value = '')
    {
        $head = pack('NnnNN', 0x10 + strlen($value), 0x10, 0x01, $action, 0x01);
        $str = $head . $value;

        if (!socket_write(self::$socket, $str, strlen($str))) {
            Log::warning('弹幕服务器已经断开');
            self::$socket = NULL;
            return false;
        }

        Log::debug('SEND: '.bin2hex($head));
        if (strlen($value)) {
            Log::debug($value);
        }

        return true;
    }
}
