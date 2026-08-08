#!/bin/bash

# ===============================
# Script: update_env_db.sh
# Mục đích: Cập nhật các biến APP_NAME, APP_URL, DB_DATABASE trong file .env
# Cách dùng: ./update_env_db.sh <ten_project>
# Ví dụ: ./update_env_db.sh inafo
# => APP_NAME=inafo
# => APP_URL=https://inafo.laravel.tk
# => DB_DATABASE=db_inafo
# ===============================

# Kiểm tra xem có truyền tham số không
if [ -z "$1" ]; then
  echo "❌ Vui lòng nhập tên project. Ví dụ:"
  echo "   ./update_env_db.sh inafo"
  exit 1
fi

PROJECT_NAME="$1"
DB_NAME="db_${PROJECT_NAME}"
APP_URL="https://${PROJECT_NAME}.laravel.tk"
ENV_FILE=".env"

# Kiểm tra file .env tồn tại
if [ ! -f "$ENV_FILE" ]; then
  echo "❌ Không tìm thấy file .env trong thư mục hiện tại!"
  exit 1
fi

# Tạo bản sao dự phòng
cp "$ENV_FILE" "${ENV_FILE}.bak"

# ===== Cập nhật APP_NAME =====
if grep -q "^APP_NAME=" "$ENV_FILE"; then
  sed -i.bak "s/^APP_NAME=.*/APP_NAME=${PROJECT_NAME}/" "$ENV_FILE"
else
  echo "APP_NAME=${PROJECT_NAME}" >> "$ENV_FILE"
fi

# ===== Cập nhật APP_URL =====
if grep -q "^APP_URL=" "$ENV_FILE"; then
  sed -i.bak "s|^APP_URL=.*|APP_URL=${APP_URL}|" "$ENV_FILE"
else
  echo "APP_URL=${APP_URL}" >> "$ENV_FILE"
fi

# ===== Cập nhật DB_DATABASE =====
if grep -q "^DB_DATABASE=" "$ENV_FILE"; then
  sed -i.bak "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" "$ENV_FILE"
else
  echo "DB_DATABASE=${DB_NAME}" >> "$ENV_FILE"
fi

echo "✅ Đã cập nhật:"
echo "   APP_NAME=${PROJECT_NAME}"
echo "   APP_URL=${APP_URL}"
echo "   DB_DATABASE=${DB_NAME}"
echo "📦 File backup: ${ENV_FILE}.bak"
