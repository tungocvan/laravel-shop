<!DOCTYPE html>
<html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Tính giá thành sản phẩm</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('demo-assets/tinh-gia-thanh-san-pham/css/app.css') }}?v={{ filemtime(public_path('demo-assets/tinh-gia-thanh-san-pham/css/app.css')) }}">
</head><body>
@yield('content')
<script src="{{ asset('demo-assets/tinh-gia-thanh-san-pham/js/app.js') }}?v={{ filemtime(public_path('demo-assets/tinh-gia-thanh-san-pham/js/app.js')) }}"></script>
<script src="{{ asset('demo-assets/tinh-gia-thanh-san-pham/js/integrations.js') }}?v={{ filemtime(public_path('demo-assets/tinh-gia-thanh-san-pham/js/integrations.js')) }}"></script>
</body></html>
