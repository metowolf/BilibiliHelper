<?php
/*
* 生成config文件
*/
function init(){
    if(getenv('APP_USER')==false or getenv('APP_PASS')==false){
        echo '[ERROR] no account! run docker with -e APP_USER=xxx -e APP_PASS=xxx'.PHP_EOL;
        exit(-1);
    }
    $configArr=array(
        'APP_USER'=>getenv('APP_USER'),
        'APP_PASS'=>getenv('APP_PASS'),
        'ACCESS_TOKEN'=>'',
        'REFRESH_TOKEN'=>'',
        'COOKIE_JAR'=>'',
        'ROOM_ID'=>getenv('ROOM_ID')?getenv('ROOM_ID'):'3746256',
        'NETWORK_PROXY'=>getenv('NETWORK_PROXY')?getenv('NETWORK_PROXY'):'',
        'APP_DEBUG'=>getenv('APP_DEBUG')?getenv('APP_DEBUG'):'true',
        'APP_MULTIPLE'=>getenv('APP_MULTIPLE')?getenv('APP_MULTIPLE'):'false',
        'APP_USER_IDENTITY'=>getenv('APP_USER_IDENTITY')?getenv('APP_USER_IDENTITY'):'',
        'CALLBACK_URL'=>getenv('CALLBACK_URL')?'"'.getenv('CALLBACK_URL').'"':'""',
        'CALLBACK_LEVEL'=>getenv('CALLBACK_LEVEL')?getenv('CALLBACK_LEVEL'):'400'
    );
    print_r($configArr);
    $configTxt='';
    foreach ($configArr as $key => $value) {
        $configTxt.=$key;
        $configTxt.='=';
        $configTxt.=$value;
        $configTxt.=PHP_EOL;
    }
    file_put_contents(getenv('BLIVE_PATH').'/config', $configTxt);
}
init();