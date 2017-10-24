<?php
require "Bilibili.php";

$cookie=''; # 在这里填写 Cookie 信息
$api=new Bilibili($cookie);
$api->debug=false; # 开启后显示更多信息
$api->color=true; # 输出彩色日志
$api->break=true; # 每天 23:59 中断
$api->callback=function(){ # 回调函数
    echo "签到中断了呢";
};
$api->run();
