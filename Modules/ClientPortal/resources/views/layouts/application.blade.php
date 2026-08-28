<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="{{ route('website.manifest') }}">
    <link rel="icon" href="/pwa/icon.svg" type="image/svg+xml">
    <title>@yield('title', 'Ứng dụng') · INAFO</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @php
        $applicationContext = app(\Modules\ClientPortal\Support\ApplicationContext::class)->current();
        $shellExtensions = $applicationContext['shell_extensions'] ?? [];
    @endphp
    @foreach($shellExtensions['head'] ?? [] as $extension)
        @if(request()->routeIs(...$extension['routes']))
            @include($extension['view'])
        @endif
    @endforeach
    @stack('application-head')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-[1536px] items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8 xl:px-10 2xl:px-12">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('client.apps.index') }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg shadow-sm" aria-label="Ứng dụng của tôi">←</a>
            <div class="min-w-0"><div class="truncate font-bold">@yield('app-name', 'INAFO')</div><div class="truncate text-xs text-slate-500">@yield('app-subtitle', 'INAFO Client Application')</div></div>
        </div>
        <div class="flex items-center gap-2">
            @hasSection('app-dashboard-route')<a href="@yield('app-dashboard-route')" class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 sm:block">Tổng quan</a>@endif
            @include('ClientPortal::partials.account-menu')
        </div>
    </div>
</header>
@php
    $portalNavigation = $applicationContext
        ? app(\Modules\ClientPortal\Services\PortalNavigationResolver::class)->forApplication($applicationContext, auth('web')->user())
        : collect();
    $primaryNavigation = $portalNavigation->where('placement', 'primary')->values();
    $moreNavigation = $portalNavigation->where('placement', 'more')->values();
@endphp
<div class="mx-auto flex min-h-[calc(100dvh-65px)] w-full max-w-[1536px]">
    @include('ClientPortal::partials.adaptive-navigation', [
        'primaryNavigation' => $primaryNavigation,
        'moreNavigation' => $moreNavigation,
    ])
    <main class="min-w-0 flex-1 px-4 py-6 pb-24 sm:px-6 sm:py-8 sm:pb-10 lg:px-8 xl:px-10 2xl:px-12">@yield('content')</main>
</div>
@foreach($shellExtensions['overlays'] ?? [] as $extension)
    @if(request()->routeIs(...$extension['routes']))
        @include($extension['view'])
    @endif
@endforeach
@stack('application-overlays')
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js', { updateViaCache: 'none' }).catch(() => {});
    });
}
</script>
@foreach($shellExtensions['scripts'] ?? [] as $extension)
    @if(request()->routeIs(...$extension['routes']))
        @include($extension['view'])
    @endif
@endforeach
@stack('application-scripts')
</body>
</html>