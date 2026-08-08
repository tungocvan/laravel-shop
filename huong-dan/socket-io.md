
# tạo route hay lắng nghe sự kiện trong file server.js
# cấu trúc cơ bản nodejs server.js
```package.json
    {
  "dependencies": {
    "express": "^5.1.0",
    "socket.io": "^4.8.1"
  }
}
```
# tạo project nodejs: npm i
```js
    const express = require("express");
    const app = express();
    const { createServer } = require("http");
    const { Server } = require("socket.io");

    const httpServer = createServer(app); // ✅ phải truyền app vào
    const io = new Server(httpServer, {
        cors: {
            origin: "*", // hoặc domain Laravel nếu muốn bảo mật
            methods: ["GET", "POST"],
        }
    });

    httpServer.listen(6002, "0.0.0.0", () => {
        console.log("🚀 Socket.IO server running at http://0.0.0.0:6002");
    });

    io.on("connection", (socket) => {
        console.log("🔌 Client connected:", socket.id);

        socket.on("disconnect", () => {
            console.log("❌ Client disconnected:", socket.id);
        });
    });

    // Route test 
    app.get("/", (req, res) => {
        res.send("NodeJS Socket.IO Server đang chạy trên cổng 6002 🚀");
    });

    app.use(express.json());

```
# khởi tạo project: pm2 start socket/server.js --name nodejs-server-socketio-flexbiz
# cấu hình nginx để chạy https
# Redirect HTTP -> HTTPS cho node.laravel.tk
http://127.0.0.1:6002 => https://node.laravel.tk
```nginx
server {
    listen 80;
    server_name node.laravel.tk;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name node.laravel.tk;
    ssl_certificate     /etc/.cert/laravel.tk-cert.pem;
    ssl_certificate_key /etc/.cert/laravel.tk-key.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    location / {
        proxy_pass http://127.0.0.1:6002;
        proxy_http_version 1.1;

        # WebSocket headers
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
    }
}

```
# cấu hình trên vps thật:
server {
    listen 80;
    server_name node.tungocvan.com;

    location / {
        proxy_pass         http://127.0.0.1:6001;
        proxy_http_version 1.1;

        # WebSocket support
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
    }
}

# cách cài đặt domain test trên wsl
# trên windows:  add-host.bat flexbiz.nodejs.tk
# trên wsl cài đặt chứng chỉ: nodejs.tk nếu chưa cài:
# ssl-domain.sh nodejs.tk
# thiết lập nginx , ví dụ: nano flexbiz.nodejs.tk
```
# Redirect HTTP -> HTTPS cho flexbiz.nodejs.tk
server {
    listen 80;
    server_name flexbiz.nodejs.tk;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name flexbiz.nodejs.tk;
    ssl_certificate     /etc/.cert/nodejs.tk-cert.pem;
    ssl_certificate_key /etc/.cert/nodejs.tk-key.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    location / {
        proxy_pass http://127.0.0.1:6002;
        proxy_http_version 1.1;

        # WebSocket headers
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
    }
}
```
# vps + cloudflare 
# trên cloudflare tạo subdomain, ví dụ: node.tungocvan.com
```
server {
    listen 80;
    server_name node.tungocvan.com;

    location / {
        proxy_pass         http://127.0.0.1:6001;
        proxy_http_version 1.1;

        # WebSocket support
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
    }
} 
```
# cài đặt laravel echo:
npm install laravel-echo socket.io-client
sau đó: import './echo'; vào resource/app.js hoặc resource/tailwind.js
npm run build

# thiết lập .env: 
BROADCAST_CONNECTION=socket.io
SOCKET_IO_HOST=https://flexbiz.nodejs.tk

# thiết lập config/broadcasting.php
# nano config/broadcasting.php
```php
<?php

return [
    'default' => env('BROADCAST_CONNECTION', 'null'),
    'connections' => [
        'null' => [
            'driver' => 'null',
        ],
        'socket.io' => [
            'driver' => 'socket.io',
            'host' => env('SOCKET_IO_HOST', 'https://flexbiz.nodejs.tk'),
        ],
    ],
];

```
# tới bước này xem như thiết lập xong laravel echo + socket io
# luồng thiết lập:
Order::create()
   ↓
Model created():
```php
 protected static function booted()
    {
        static::created(function (Order $order) {

            Http::post(config('services.socket.url') . '/broadcast', [
                'channel' => 'orders',
                'event'   => 'order.created',
                'data'    => $order->toArray(),
            ]);

        });
    }
```
   ↓
Gửi data sang NodeJS server.js
```server.js
app.post("/broadcast", (req, res) => {
    const { channel, event, data } = req.body;

    console.log("📦 Order from Laravel:", data.id);

    io.emit(`${channel}:${event}`, data);

    res.json({ ok: true });
});
```
   ↓
Socket.IO emit
   ↓
Frontend Echo nhận => hiển thị, lưu vào database 
```
window.Echo
    .channel("orders")
    .listen(".order.created", (order) => {
        console.log("🔥 New order:", order);
    });
```

# Debug PM2: xem các log chạy các serivice bằng pm2
pm2 monit 