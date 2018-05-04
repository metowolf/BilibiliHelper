<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 18.05.04 (0.8.0)
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

require 'vendor/autoload.php';

use metowolf\Bilibili\Loader;
use metowolf\Bilibili\Curl;
use metowolf\Bilibili\Daily;
use metowolf\Bilibili\GiftSend;
use metowolf\Bilibili\Heart;
use metowolf\Bilibili\Login;
use metowolf\Bilibili\Silver;
use metowolf\Bilibili\SmallTV;
use metowolf\Bilibili\Task;

Loader::config();

if (Login::run() === false) {
    Loader::overload();
}

while (true) {
    Login::check();
    Daily::run();
    GiftSend::run();
    Heart::run();
    Silver::run();
    SmallTV::run();
    Task::run();
    sleep(10);
}
