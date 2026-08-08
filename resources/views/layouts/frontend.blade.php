<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">

    <title>@yield('title', 'Home') - {{ config('app.name') }}</title>

    <x-realtime-config />
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
</head>

<body class="bg-white text-gray-900 flex flex-col min-h-screen">

    <x-frontend.navbar />

    <main class="flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <x-frontend.footer />

</body>
</html>
