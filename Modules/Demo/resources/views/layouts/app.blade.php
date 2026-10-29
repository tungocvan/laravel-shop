<!DOCTYPE html>
<html lang="vi"><head>
<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('demo-title', 'Demo')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
@stack('demo-styles')

</head>
<body>
@yield('content')
@stack('demo-data')

@stack('demo-scripts')
</body></html>