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
                <div><div class="font-bold leading-tight">{{ $launcher['brand_title'] }}</div><div class="text-xs text-slate-500">{{ $launcher['brand_subtitle'] }}</div></div>
            </a>
            <div class="flex items-center gap-2">
                <button id="install-app" type="button" hidden class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50">{{ $launcher['install_button_text'] }}</button>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm">{{ $launcher['logout_button_text'] }}</button></form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
        <section class="mb-8 rounded-3xl bg-slate-900 px-6 py-7 text-white shadow-sm sm:px-8 sm:py-9">
            <p class="text-sm font-semibold text-slate-300">{{ $launcher['workspace_label'] }}</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">{{ $launcher['heading'] }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">{{ $launcher['description'] }}</p>
        </section>
        @if($applications->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm sm:p-12"><h2 class="text-lg font-semibold">{{ $launcher['empty_title'] }}</h2><p class="mt-2 text-sm text-slate-500">{{ $launcher['empty_description'] }}</p></div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($applications as $application)
                    <a href="{{ route($application['route']) }}" class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        @if($launcher['show_source_module'])
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $application['module'] }}</p>
                        @endif
                        <h2 class="mt-2 text-xl font-bold">{{ $application['name'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $application['description'] }}</p>
                        <div class="mt-6 flex items-center justify-between text-sm font-semibold"><span>{{ $launcher['open_application_text'] }}</span><span>→</span></div>
                    </a>
                @endforeach
            </div>
        @endif
    </main>
</div>
<script>
(() => {
    if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));
    let installPrompt = null; const button = document.getElementById('install-app');
    window.addEventListener('beforeinstallprompt', e => { e.preventDefault(); installPrompt = e; button.hidden = false; });
    button?.addEventListener('click', async () => { if (!installPrompt) return; await installPrompt.prompt(); installPrompt = null; button.hidden = true; });
})();
</script>
</body>
</html>
