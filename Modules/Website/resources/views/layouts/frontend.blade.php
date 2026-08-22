<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <meta name="application-name" content="INAFO Client Portal">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="INAFO">
    <link rel="manifest" href="/manifest.webmanifest">
    @php
        $faviconType = strtolower(pathinfo((string) $siteFavicon, PATHINFO_EXTENSION)) === 'ico'
            ? 'image/x-icon'
            : 'image/png';
    @endphp
    @if ($siteFavicon)
        <link id="site-favicon" rel="icon" type="{{ $faviconType }}" href="{{ str_starts_with($siteFavicon, 'http') ? $siteFavicon : asset('storage/' . $siteFavicon).'?v='.md5($siteFavicon) }}">
    @endif
    <title>@yield('title', $websiteSeo['title'] ?? 'HOMEPAGE')</title>
    <meta name="description" content="@yield('meta_description', $websiteSeo['description'] ?? '')">
    <meta name="robots" content="{{ $websiteSeo['robots'] ?? 'index,follow' }}">
    <link rel="canonical" href="@yield('canonical', $websiteSeo['canonical'] ?? url()->current())">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', $websiteSeo['title'] ?? 'HOMEPAGE')">
    <meta property="og:description" content="@yield('meta_description', $websiteSeo['description'] ?? '')">
    <meta property="og:url" content="@yield('canonical', $websiteSeo['canonical'] ?? url()->current())">
    @if(!empty($websiteSeo['image']))<meta property="og:image" content="{{ str_starts_with($websiteSeo['image'], 'http') ? $websiteSeo['image'] : asset('storage/'.$websiteSeo['image']) }}">@endif
    {{-- Privileged trusted configuration; mutation requires website.settings.manage. --}}
    {!! $headerScript !!}
    {!! $analyticsCode ?? '' !!}
    <script>
       // window.CHAT_CONFIG_HOST = "{{ env('NODEJS_SERVER_URL') }}";
        // window.CHAT_CONFIG_PORT = "{{ env('NODEJS_SERVER_PORT') ?? 6001 }}";
        window.CHAT_CONFIG_HOST =  @json(config('realtime.host') ?: request()->getSchemeAndHttpHost());
    </script>
    <x-realtime-config />
    @include('Website::partials.design-tokens')
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @yield('css')
    @stack('styles')
    @livewireStyles
</head>

<body class="bg-website-background text-website-text font-website-body antialiased flex flex-col min-h-screen">
    <a href="#main-content" class="sr-only z-[10000] rounded bg-white px-4 py-2 font-semibold text-blue-700 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Bỏ qua đến nội dung chính</a>

    @include('Website::partials.header')

    <main id="main-content" tabindex="-1" class="py-8 w-full flex-grow">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>

    @include('Website::partials.footer')
    <div x-data="{ open: false, type: 'success', message: '' }"
        @notify.window="type = $event.detail?.[0]?.type ?? $event.detail?.type ?? 'success'; message = $event.detail?.[0]?.message ?? $event.detail?.message ?? ''; open = true; setTimeout(() => open = false, 4000)"
        @alert.window="type = $event.detail?.[0]?.type ?? 'success'; message = $event.detail?.[0]?.message ?? ''; open = true; setTimeout(() => open = false, 4000)"
        x-show="open" x-transition role="status" aria-live="polite" class="fixed bottom-4 left-4 right-4 z-[10000] rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-xl sm:left-auto sm:max-w-sm"
        :class="type === 'error' ? 'bg-red-600' : (type === 'warning' ? 'bg-amber-500' : 'bg-emerald-600')" style="display:none">
        <span x-text="message"></span><button type="button" @click="open=false" aria-label="Đóng thông báo" class="float-right ml-4">×</button>
    </div>
    @stack('scripts')
    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));
        }
    </script>
</body>

</html>
