<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $pwaGeneral['theme_color'] }}">
    <meta name="application-name" content="{{ $pwaGeneral['application_name'] }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $pwaGeneral['apple_title'] }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/pwa/icon.svg" type="image/svg+xml">
    <title>{{ $launcher['browser_title'] }}</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div class="min-h-screen pb-20 sm:pb-0">
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('client.apps.index') }}" class="flex items-center gap-3">
                <img src="/pwa/icon.svg" alt="{{ $pwaGeneral['short_name'] }}" class="h-10 w-10 rounded-xl">
                <div>
                    <div class="font-bold leading-tight">{{ $launcher['brand_title'] }}</div>
                    <div class="text-xs text-slate-500">{{ $launcher['brand_subtitle'] }}</div>
                </div>
            </a>
            <div class="flex items-center gap-2">
                @include('ClientPortal::partials.pwa-install')
                @if(Route::has('client.apps.google.link') && ! auth('web')->user()?->google_id)
                    <a href="{{ route('client.apps.google.link') }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        Liên kết Google
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm">{{ $launcher['logout_button_text'] }}</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="mb-8 rounded-3xl bg-slate-900 px-6 py-7 text-white shadow-sm sm:px-8 sm:py-9">
            <p class="text-sm font-semibold text-slate-300">{{ $launcher['workspace_label'] }}</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">{{ $launcher['heading'] }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">{{ $launcher['description'] }}</p>
        </section>

        @if($portalContext['application_count'] === 0)
            <section class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm sm:p-12" aria-labelledby="portal-empty-title">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75A2.25 2.25 0 0 1 6 4.5h12a2.25 2.25 0 0 1 2.25 2.25v10.5A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75h7.5M8.25 13.5h4.5" />
                    </svg>
                </div>
                <h2 id="portal-empty-title" class="mt-4 text-lg font-semibold">{{ $launcher['empty_title'] }}</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">{{ $launcher['empty_description'] }}</p>
            </section>
        @else
            <section aria-labelledby="portal-applications-title">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $launcher['workspace_label'] }}</p>
                        <h2 id="portal-applications-title" class="mt-1 text-xl font-bold sm:text-2xl">{{ $launcher['heading'] }}</h2>
                    </div>
                    <p class="shrink-0 text-sm text-slate-500">{{ $portalContext['application_count'] }} ứng dụng</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($applications as $application)
                        <a href="{{ route($application['route']) }}" class="group flex min-h-48 flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                            @if($launcher['show_source_module'])
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $application['module'] }}</p>
                            @endif
                            <h3 class="mt-2 text-xl font-bold">{{ $application['name'] }}</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-slate-600">{{ $application['description'] }}</p>
                            <div class="mt-6 flex items-center justify-between text-sm font-semibold">
                                <span>{{ $launcher['open_application_text'] }}</span>
                                <span aria-hidden="true">→</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</div>
<script>
(() => {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js', { updateViaCache: 'none' }).catch(() => {}));
    }
})();
</script>
</body>
</html>
