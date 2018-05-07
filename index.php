<?php

/*!
 * metowolf BilibiliHelper
 * https://i-meto.com/
 * Version 0.9.0
 *
 * Copyright 2018, metowolf
 * Released under the MIT license
 */

require 'vendor/autoload.php';

$app = new BilibiliHelper\Lib\Helper();

$config = $app->get('config')::parse('config');
while (true) {
    $app->get('websocket')::run($config);
    $app->get('auth')::run($config);
    $app->get('heart')::run($config);
    $app->get('dailyBag')::run($config);
    $app->get('task')::run($config);
    $app->get('giftSend')::run($config);
    $app->get('silver')::run($config);
    $app->get('smallTV')::run($config);
    sleep(10);
}
