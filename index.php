<?php
require "Bilibili.php";

$cookie = '';

$api = new Bilibili($cookie);
$api->debug = false;
$api->color = true;
$api->roomid = 3746256;
$api->callback = function() {
    echo "something wrong!";
};
$api->run();
