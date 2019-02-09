#!/bin/sh

if [ -z "$USERNAME" ]; then
  echo -e "\033[31mError: Username empty!\033[0m"
else
  sed -i -e "s:APP_USER=:APP_USER=${USERNAME}:" /app/config
fi

if [ -z "$PASSWORD" ]; then
  echo -e "\033[31mError: Password empty!\033[0m"
else
  sed -i -e "s:APP_PASS=:APP_PASS=${PASSWORD}:" /app/config
fi

if [ ! -z "$ROOMID" ]; then
  echo -e "\033[32mUsing roomid:\033[0m ${ROOMID}"
  sed -i -e "s:ROOM_ID=3746256:ROOM_ID=${ROOMID}:" /app/config
fi

if [ ! -z "$CALLBACK" ]; then
  echo -e "\033[32mUsing callback:\033[0m \"${CALLBACK}\"""
  sed -i -e "s:CALLBACK_URL=\"\":CALLBACK_URL=\"${CALLBACK}\"":" /app/config
fi

php index.php
