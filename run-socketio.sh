#!/bin/bash

set -e  # nếu có lỗi -> dừng luôn (tránh deploy nửa chừng)

CURRENT_DIR=$(basename "$PWD")
APP_NAME="Socketio-$CURRENT_DIR"

pm2 start socket/server.js --name $APP_NAME

