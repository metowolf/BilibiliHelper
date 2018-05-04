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
use metowolf\Bilibili\Socket;
use metowolf\Bilibili\Heart;

class SmallTV
{
    protected static $lock = 0;
    protected static $heartLock = 0;
    protected static $smallTV = [];

    public static function run()
    {
        Socket::heart();
        while(self::receive());
        self::solve();
    }

    public static function solve()
    {
        foreach (self::$smallTV as $vo) {
            // Join
            if ($vo['status'] == 0 && $vo['lock'] <= time()) {
                if (self::check($vo)) {
                    self::join($vo);
                }
            }
            // Waiting
            if ($vo['status'] == 1 && $vo['lock'] <= time()) {
                self::notice($vo);
            }
        }
    }

    public static function notice($value)
    {
        $payload = [
            'roomid' => $value['roomid'],
            'raffleId' => $value['tvid'],
        ];
        $data = Curl::get('https://api.live.bilibili.com/gift/v2/smalltv/notice', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('小电视 #' . $value['tvid'] . '  抽奖失败', ['msg' => $data['message']]);
            unset(self::$smallTV[$value['tvid']]);
        }

        if ($data['data']['status'] == 3) {
            self::$smallTV[$value['tvid']]['lock'] += 30;
            return;
        }

        Log::notice('在直播间 ' . $value['roomid'] . ' 获得 ' . $data['data']['gift_num'] . ' 个' . $data['data']['gift_name']);
        unset(self::$smallTV[$value['tvid']]);
    }

    public static function join($value)
    {
        $payload = [
            'roomid' => $value['roomid'],
            'raffleId' => $value['tvid'],
        ];
        $data = Curl::post('https://api.live.bilibili.com/gift/v2/smalltv/join', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning('小电视 #' . $value['tvid'] . '  抽奖加入失败', ['msg' => $data['message']]);
            unset(self::$smallTV[$value['tvid']]);
        }

        self::$smallTV[$value['tvid']]['status'] = 1;
        self::$smallTV[$value['tvid']]['lock'] = time() + $data['data']['time'] + rand(5, 60);
    }

    public static function check($value)
    {
        Log::info('检查直播间 ' . $value['roomid']);

        $payload = [
            'id' => $value['roomid'],
        ];
        $data = Curl::get('https://api.live.bilibili.com/room/v1/Room/room_init', Sign::api($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::error('检查直播间 ' . $value['roomid'] . ' 失败', ['msg' => $data['message']]);
            die();
        }

        if ($data['data']['is_hidden'] || $data['data']['is_locked'] || $data['data']['encrypted']) {
            Log::warning('检查直播间 ' . $value['roomid'] . ' 可能非法，放弃小电视抽奖');
            return false;
        }

        SmallTV::entryAction($value['roomid']);

        return true;
    }

    public static function entryAction($value)
    {
        Log::info('进入直播间 ' . $value);

        $payload = [
            'room_id' => $value,
        ];
        Curl::post('https://api.live.bilibili.com/room/v1/Room/room_entry_action', Sign::api($payload));

        Heart::mobile($value);

        return true;
    }

    public static function checkHours()
    {
        $hours = explode(',', getenv('SMALLTV_HOURS'));
        $hours = array_map('intval', $hours);
        return in_array(intval(date('H')), $hours);
    }

    public static function receive()
    {
        $raw = Socket::receive();
        if ($raw) {
            $data = json_decode($raw, true);
            if ($data['cmd'] == 'SYS_MSG' && isset($data['tv_id'])) {
                Log::debug($raw);
                Log::notice('直播间 ' . $data['roomid'] . ' 开启了第 ' . $data['tv_id'] . ' 轮小电视抽奖');
                if (self::checkHours()) {
                    if (mt_rand(0, 100) < intval(getenv('SMALLTV_RATE'))) {
                        self::$smallTV[$data['tv_id']]= [
                            'roomid' => $data['real_roomid'],
                            'tvid' => $data['tv_id'],
                            'status' => 0,
                            'lock' => time() + mt_rand(5, 60),
                        ];
                    } else {
                        Log::info('根据抽奖比率 (' . getenv('SMALLTV_RATE') . '%) 设置，放弃小电视抽奖');
                    }
                } else {
                    Log::info('当前为休息时段，放弃小电视抽奖');
                }
            }
            return true;
        }

        return false;
    }
}
