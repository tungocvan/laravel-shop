<!DOCTYPE html>
<html lang="vi">
<head>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý công nợ - Phong Excel</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2032%2032%22%3E%3Crect%20width%3D%2232%22%20height%3D%2232%22%20rx%3D%228%22%20fill%3D%22%23ea580c%22%2F%3E%3Cpath%20d%3D%22M9%2010h14M9%2016h14M9%2022h9%22%20stroke%3D%22%23fff%22%20stroke-width%3D%223%22%20stroke-linecap%3D%22round%22%2F%3E%3C%2Fsvg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">


<link rel="stylesheet" href="{{ asset('demo-assets/quan-ly-cong-no/css/app.css') }}?v={{ filemtime(public_path('demo-assets/quan-ly-cong-no/css/app.css')) }}">
</head>
<body>
@yield('content')
<script src="{{ asset('demo-assets/quan-ly-cong-no/js/core.js') }}?v={{ filemtime(public_path('demo-assets/quan-ly-cong-no/js/core.js')) }}"></script>
<script src="{{ asset('demo-assets/quan-ly-cong-no/js/menu-builder.js') }}?v={{ filemtime(public_path('demo-assets/quan-ly-cong-no/js/menu-builder.js')) }}"></script>
<script src="{{ asset('demo-assets/quan-ly-cong-no/js/app.js') }}?v={{ filemtime(public_path('demo-assets/quan-ly-cong-no/js/app.js')) }}"></script>
<script src="{{ asset('demo-assets/quan-ly-cong-no/js/server-datasets.js') }}?v={{ filemtime(public_path('demo-assets/quan-ly-cong-no/js/server-datasets.js')) }}"></script>
</body>
</html>
