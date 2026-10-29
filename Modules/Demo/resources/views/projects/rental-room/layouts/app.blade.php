<!DOCTYPE html>
<html lang="vi"><head>
<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản Lý Phòng Trọ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-phong-tro/css/app.css') }}">
<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-phong-tro/shared/menu-editor.css') }}">
</head>
<body>
@yield('content')
<script id="du-lieu" type="application/json">null</script>

<script src="{{ asset('demo-assets/quan-ly-phong-tro/js/app.js') }}"></script>
<script src="{{ asset('demo-assets/quan-ly-phong-tro/js/integrations.js') }}"></script>
<div data-demo-menu="quan-ly-phong-tro" data-menu-storage-key="rental_room_sidebar_v1"></div>
<script src="{{ asset('demo-assets/quan-ly-phong-tro/shared/menu-editor.js') }}"></script></body></html>