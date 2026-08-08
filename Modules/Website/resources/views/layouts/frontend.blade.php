<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        use Modules\Website\Models\Setting;
        $favicon = Setting::getValue('site_favicon');
        $faviconType = strtolower(pathinfo((string) $favicon, PATHINFO_EXTENSION)) === 'ico'
            ? 'image/x-icon'
            : 'image/png';
    @endphp
    @if ($favicon)
        <link id="site-favicon" rel="icon" type="{{ $faviconType }}" href="{{ asset('storage/' . $favicon) }}?v={{ md5($favicon) }}">
    @endif
    <title>@yield('title', 'HOMEPAGE')</title>
    {!! Setting::getValue('header_script') !!}
    <script>
        // window.CHAT_CONFIG_HOST = "{{ env('NODEJS_SERVER_URL') }}";
        // window.CHAT_CONFIG_PORT = "{{ env('NODEJS_SERVER_PORT') ?? 6001 }}";
        window.CHAT_CONFIG_HOST = window.location.origin;
    </script>
    {{-- <script src="https://unpkg.com/@tailwindcss/browser@4"></script> --}}
    <x-realtime-config />
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @yield('css')
    @stack('styles')
    @livewireStyles
</head>

<body class="bg-gray-50 text-gray-900 font-sans antialiased flex flex-col min-h-screen">

    @include('Website::partials.header')

    <main class="py-8 w-full flex-grow">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>

    @include('Website::partials.footer')
    @stack('scripts')
    @livewireScripts
</body>

</html>
