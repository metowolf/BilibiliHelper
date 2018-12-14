#!/bin/bash
if [ -f "${BLIVE_PATH}/config" ];then
    echo '[INFO] start BilibiliHelper'
    php ${BLIVE_PATH}/index.php
else
    echo '[INFO] initing...'
    php ${BLIVE_PATH}/init.php
    echo '[INFO] start BilibiliHelper'
    php ${BLIVE_PATH}/index.php
fi