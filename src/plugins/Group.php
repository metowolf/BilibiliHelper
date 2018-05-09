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

class Group extends Base
{
    const PLUGIN_NAME = 'group';

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

        $groups = static::list();
        $count = count($groups);
        foreach ($groups as $group) {
            $count -= static::signIn($group);
        }

        if ($count == 0) {
            static::data('lock', strtotime(date('Y-m-d 23:59:59')) + 600);
        } else {
            static::data('lock', time() + 3600);
        }
    }

    public static function list()
    {
        $payload = [];
        $data = Curl::post('https://api.vc.bilibili.com/link_group/v1/member/my_groups', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning("查询应援团名单异常");
            return [];
        }

        if (empty($data['data']['list'])) {
            Log::notice('没有需要签到的应援团');
            return [];
        }

        return $data['data']['list'];
    }

    public static function signIn($value)
    {
        $payload = [
            'group_id' => $value['group_id'],
            'owner_id' => $value['owner_uid'],
        ];
        $data = Curl::post('https://api.vc.bilibili.com/link_setting/v1/link_setting/sign_in', static::sign($payload));
        $data = json_decode($data, true);

        if (isset($data['code']) && $data['code']) {
            Log::warning("应援团 {$value['group_name']} 签到异常");
            return false;
        }

        if ($data['data']['status']) {
            Log::notice("应援团 {$value['group_name']} 已经签到过了");
        } else {
            Log::notice("应援团 {$value['group_name']} 签到成功，增加 {$de_raw['data']['add_num']} 点亲密度");
        }

        return true;
    }
}
