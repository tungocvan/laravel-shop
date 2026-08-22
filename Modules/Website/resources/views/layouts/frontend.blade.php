<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('Website::partials.layout.head-meta')
    @include('Website::partials.layout.runtime-head')
</head>

<body class="bg-website-background text-website-text font-website-body antialiased flex flex-col min-h-screen">
    <a href="#main-content" class="sr-only z-[10000] rounded bg-white px-4 py-2 font-semibold text-blue-700 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Bỏ qua đến nội dung chính</a>

    @if(data_get($websiteShell ?? [], 'header_enabled', true))
        @include('Website::partials.header')
    @endif

    <main id="main-content" tabindex="-1" class="py-8 w-full flex-grow">
        @if(data_get($websiteShell ?? [], 'maintenance.enabled', false))
            @include('Website::partials.layout.maintenance')
        @elseif(!request()->routeIs('home') || data_get($websiteShell ?? [], 'homepage_enabled', true))
            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        @endif
    </main>

    @if(data_get($websiteShell ?? [], 'footer_enabled', true))
        @include('Website::partials.footer')
    @endif

    @include('Website::partials.layout.global-toast')
    @include('Website::partials.layout.runtime-scripts')
</body>
</html>
