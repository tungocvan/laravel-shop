echo "
    Hướng dãn cài đặt hàng đợi laravel queues \n
    - trên ubuntu cài đặt pm2: \n
    npm install -g pm2 \n
    pm2 --version \n
    Tạo file tên queue-worker.sh ở gốc project Laravel: \n
    touch queue-worker.sh \n
    chmod +x queue-worker.sh \n
    Nội dung queue-worker.sh: \n
    Câu lệnh quản lý pm2 \n
    pm2 start queue-worker.sh	Khởi động \n
    hoặc chạy nền: ./pm2queue.sh \n
    pm2 stop laravel-queue	Dừng \n
    pm2 restart laravel-queue	Khởi động lại \n
    pm2 delete laravel-queue	Xóa tiến trình \n
    pm2 logs laravel-queue	Xem log \n
    pm2 flush laravel-queue // xóa các logs \n
    Lưu lại cấu hình để tự chạy lại khi khởi động máy \n
    pm2 save \n
    pm2 startup \n
"