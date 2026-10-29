<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chấm công &amp; Tính lương</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('demo-assets/cham-cong-tinh-luong/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('demo-assets/cham-cong-tinh-luong/css/extensions.css') }}">
    <link rel="stylesheet" href="{{ asset('demo-assets/cham-cong-tinh-luong/shared/menu-editor.css') }}">
</head>
<body data-dataset-base="{{ url('/demo/cham-cong-tinh-luong/datasets') }}" data-menu-base="{{ url('/demo/cham-cong-tinh-luong/menu') }}">
    @yield('content')
    <script src="{{ asset('demo-assets/cham-cong-tinh-luong/js/core.js') }}"></script>
    <script src="{{ asset('demo-assets/cham-cong-tinh-luong/js/payroll.js') }}"></script>
    <script src="{{ asset('demo-assets/cham-cong-tinh-luong/js/extensions.js') }}"></script>
    <div data-demo-menu="cham-cong-tinh-luong" data-menu-storage-key="shared_demo_sidebar_menu_v1_cham-cong-tinh-luong"></div>
    <script src="{{ asset('demo-assets/cham-cong-tinh-luong/shared/menu-editor.js') }}"></script>
</body>
</html>
