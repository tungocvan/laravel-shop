#!/bin/bash

# ==========================================
# Xóa tất cả file có đuôi .Identifier
# Bắt đầu tìm từ thư mục hiện tại
# ==========================================

echo "Đang tìm các file *.Identifier..."

find . -type f -name "*.Identifier" -print

echo
read -p "Bạn có chắc muốn xóa tất cả các file trên? (y/N): " confirm

if [[ "$confirm" =~ ^[Yy]$ ]]; then
    find . -type f -name "*.Identifier" -delete
    echo "Đã xóa xong tất cả file *.Identifier"
else
    echo "Đã hủy."
fi
