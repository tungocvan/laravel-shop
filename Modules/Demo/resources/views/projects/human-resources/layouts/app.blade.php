<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý nhân sự</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-nhan-su/css/app.css') }}">
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-nhan-su/shared/menu-editor.css') }}">
</head>
<body>
@yield('content')
<script src="{{ asset('demo-assets/quan-ly-nhan-su/js/app.js') }}"></script>
<div data-demo-menu="quan-ly-nhan-su" data-menu-storage-key="shared_demo_sidebar_menu_v1_quan-ly-nhan-su"></div>
<script src="{{ asset('demo-assets/quan-ly-nhan-su/shared/menu-editor.js') }}"></script>
</body>
</html>
