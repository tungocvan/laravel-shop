<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Báo cáo công việc hằng ngày</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('demo-assets/bao-cao-cong-viec-hang-ngay/css/app.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
</head><body>
@yield('content')
<script src="{{ asset('demo-assets/bao-cao-cong-viec-hang-ngay/js/app.js') }}" defer></script>
<x-demo.sidebar-menu-builder slug="bao-cao-cong-viec-hang-ngay" />
</body></html>
