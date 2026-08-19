<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/pwa/icon.svg" type="image/svg+xml">
    <title>@yield('title', 'Ứng dụng') · INAFO</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('client.apps.index') }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg shadow-sm" aria-label="Ứng dụng của tôi">←</a>
            <div class="min-w-0"><div class="truncate font-bold">@yield('app-name', 'INAFO')</div><div class="truncate text-xs text-slate-500">INAFO Client Application</div></div>
        </div>
        <div class="flex items-center gap-2">
            @hasSection('app-dashboard-route')<a href="@yield('app-dashboard-route')" class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 sm:block">Tổng quan</a>@endif
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Đăng xuất</button></form>
        </div>
    </div>
</header>
<main class="mx-auto max-w-7xl px-4 py-6 pb-24 sm:px-6 sm:py-8 sm:pb-10 lg:px-8">@yield('content')</main>
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 pb-[max(.7rem,env(safe-area-inset-bottom))] pt-2 backdrop-blur sm:hidden">
    @hasSection('mobile-nav')
        <div class="mx-auto grid max-w-md grid-cols-3 text-center text-xs font-semibold text-slate-500">@yield('mobile-nav')</div>
    @else
        <div class="mx-auto max-w-md text-center text-xs font-semibold text-slate-500"><a href="{{ route('client.apps.index') }}" class="inline-flex rounded-xl px-4 py-2"><span class="mr-2">▦</span>Ứng dụng</a></div>
    @endif
</nav>
<script>if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));</script>
</body>
</html>
