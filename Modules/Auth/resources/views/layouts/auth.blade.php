<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'HOMEPAGE')</title>
    @yield('css')
    <script>
        // window.CHAT_CONFIG_HOST = "{{ env('NODEJS_SERVER_URL') }}";
        // window.CHAT_CONFIG_PORT = "{{ env('NODEJS_SERVER_PORT') ?? 6001 }}";
        window.CHAT_CONFIG_HOST = window.location.origin;
    </script>
    <x-realtime-config />
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @stack('styles')
    @livewireStyles
</head>


<body class="bg-gray-200 h-screen flex items-center justify-center">
    <div class="flex h-screen overflow-hidden">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </div>
    @yield('js')
    @stack('scripts')
    @livewireScripts
</body>

</html>
