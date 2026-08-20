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
    <title>{{ $pwaGeneral['browser_title'] }}</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute -left-28 -top-28 h-72 w-72 rounded-full bg-cyan-400/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-24 h-80 w-80 rounded-full bg-indigo-400/15 blur-3xl"></div>

        <main @class([
            'relative mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8',
            'lg:grid lg:grid-cols-2 lg:gap-12' => $pwaLogin['show_intro_panel'],
        ])>
            @if($pwaLogin['show_intro_panel'])
                <section class="hidden text-white lg:block">
                    @if(filled($pwaLogin['badge']))
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-slate-300">{{ $pwaLogin['badge'] }}</div>
                    @endif
                    <h1 class="mt-6 max-w-xl text-5xl font-black leading-tight tracking-tight">{{ $pwaLogin['heading'] }}</h1>
                    <p class="mt-5 max-w-lg text-lg leading-8 text-slate-300">{{ $pwaLogin['description'] }}</p>

                    @php($visibleCards = collect($pwaLogin['feature_cards'])->where('enabled', true))
                    @if($visibleCards->isNotEmpty())
                        <div class="mt-8 grid max-w-lg gap-3 text-sm text-slate-300 sm:grid-cols-2">
                            @foreach($visibleCards as $card)
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <strong class="block text-white">{{ $card['title'] }}</strong>
                                    <span class="mt-1 block">{{ $card['description'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            <section @class([
                'w-full max-w-md rounded-[2rem] bg-white p-5 shadow-2xl shadow-black/30 ring-1 ring-white/20 sm:p-8',
                'lg:justify-self-end' => $pwaLogin['show_intro_panel'],
            ])>
                @livewire('auth.auth.login-form', ['guard' => 'web', 'variant' => 'pwa'])
                <div class="mt-6 flex items-center justify-center gap-3 text-xs font-semibold text-slate-400">
                    <a href="/" class="hover:text-slate-700">{{ $pwaLogin['back_to_website_text'] }}</a>
                    <span>·</span>
                    <span id="pwa-login-mode">{{ $pwaLogin['web_mode_label'] }}</span>
                </div>
            </section>
        </main>
    </div>

    @livewireScripts
    <script>
        (() => {
            const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const label = document.getElementById('pwa-login-mode');
            const standaloneLabel = @json($pwaLogin['standalone_mode_label']);
            if (label && standalone) label.textContent = standaloneLabel;
            if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));
        })();
    </script>
</body>
</html>
