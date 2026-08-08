<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <title>@yield('title', 'Thủ tục hành chính')</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    @livewire('administrative.public.public-header')
    <main>@yield('content')</main>
    <footer class="mt-12 border-t border-slate-200 bg-white"><div class="mx-auto max-w-6xl px-4 py-6 text-sm text-slate-500 sm:px-6">Cổng tiếp nhận hồ sơ hành chính trực tuyến</div></footer>
    @livewireScripts
</body>
</html>
