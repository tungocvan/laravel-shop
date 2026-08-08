<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <x-realtime-config />
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @livewireStyles
    @stack('css')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased @yield('classes_body')" @yield('body_data')>
    @yield('body')
    @livewireScripts
    @stack('js')
</body>
</html>
