<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

namespace BilibiliHelper\Lib;

use BilibiliHelper\Plugin\Auth;
use BilibiliHelper\Plugin\Capsule;
use BilibiliHelper\Plugin\DailyBag;
use BilibiliHelper\Plugin\Danmaku;
use BilibiliHelper\Plugin\GiftSend;
use BilibiliHelper\Plugin\Group;
use BilibiliHelper\Plugin\Heart;
use BilibiliHelper\Plugin\Silver;
use BilibiliHelper\Plugin\SmallTV;
use BilibiliHelper\Plugin\Task;

class Helper
{
    protected $helper;

    public function __construct()
    {
        date_default_timezone_set('Asia/Shanghai');
        $this->registerAll();
    }

    private function registerAll()
    {
        $this->set('auth', Auth::getInstance());
        $this->set('capsule', Capsule::getInstance());
        $this->set('config', Config::getInstance());
        $this->set('dailyBag', DailyBag::getInstance());
        $this->set('giftSend', GiftSend::getInstance());
        $this->set('group', Group::getInstance());
        $this->set('heart', Heart::getInstance());
        $this->set('silver', Silver::getInstance());
        $this->set('smallTV', SmallTV::getInstance());
        $this->set('task', Task::getInstance());
        $this->set('websocket', Danmaku::getInstance());
    }

    public function get($name)
    {
        if ($this->isRegister($name)) {
            return $this->helper[$name];
        }
        else {
            return false;
        }
    }

    public function set($name, $value)
    {
        if (!isset($name) || !isset($value)) {
            return false;
        }
        $this->helper[$name] = $value;
        return $this->helper[$name];
    }

    private function isRegister($name)
    {
        return isset($this->helper[$name]);
    }
}
