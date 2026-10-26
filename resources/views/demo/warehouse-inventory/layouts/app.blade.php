<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Quản Lý Kho Xuất Nhập Tồn</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('demo/warehouse-inventory/css/app.css') }}">
</head>
<body>
@yield('content')
<script src="{{ asset('demo/warehouse-inventory/js/app.js') }}"></script>
</body>
</html>
