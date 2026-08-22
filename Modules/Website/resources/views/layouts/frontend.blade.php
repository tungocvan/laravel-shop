<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('Website::partials.layout.head-meta')
    @include('Website::partials.layout.runtime-head')
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
    @include('Website::partials.layout.global-toast')
    @include('Website::partials.layout.runtime-scripts')
</body>
</html>
