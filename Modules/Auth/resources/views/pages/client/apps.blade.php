<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="application-name" content="INAFO">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="INAFO">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/pwa/icon.svg" type="image/svg+xml">
    <title>Ứng dụng của tôi · INAFO</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen pb-20 sm:pb-0">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('client.apps.index') }}" class="flex items-center gap-3">
                    <img src="/pwa/icon.svg" alt="INAFO" class="h-10 w-10 rounded-xl">
                    <div>
                        <div class="font-bold leading-tight">INAFO</div>
                        <div class="text-xs text-slate-500">Client Portal</div>
                    </div>
                </a>
                <div class="flex items-center gap-2">
                    <button id="install-app" type="button" hidden class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50">Cài ứng dụng</button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Đăng xuất</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
            <section class="mb-8 rounded-3xl bg-slate-900 px-6 py-7 text-white shadow-sm sm:px-8 sm:py-9">
                <p class="text-sm font-semibold text-slate-300">Không gian làm việc</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">Ứng dụng của tôi</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">Chọn ứng dụng được quản trị viên cấp quyền. INAFO có thể được cài lên thiết bị như một web app.</p>
            </section>

            @if($applications->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm sm:p-12">
                    <img src="/pwa/icon.svg" alt="" class="mx-auto h-14 w-14 rounded-2xl opacity-80">
                    <h2 class="mt-4 text-lg font-semibold">Chưa có ứng dụng được cấp</h2>
                    <p class="mt-2 text-sm text-slate-500">Quản trị viên cần cấp quyền ứng dụng cho tài khoản này.</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($applications as $application)
                        <a href="{{ route($application['route']) }}" class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $application['module'] }}</p>
                                    <h2 class="mt-2 text-xl font-bold">{{ $application['name'] }}</h2>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $application['description'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600">APP</span>
                            </div>
                            <div class="mt-6 flex items-center justify-between text-sm font-semibold">
                                <span class="text-slate-700 group-hover:text-slate-950">Mở ứng dụng</span>
                                <span aria-hidden="true">→</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 px-4 pb-[max(.75rem,env(safe-area-inset-bottom))] pt-2 backdrop-blur sm:hidden" aria-label="Điều hướng Client">
            <div class="mx-auto grid max-w-md grid-cols-3 text-center text-xs font-semibold text-slate-500">
                <a href="{{ route('client.apps.index') }}" class="rounded-xl px-3 py-2 text-slate-950"><span class="block text-lg">⌂</span>Ứng dụng</a>
                <button type="button" disabled class="rounded-xl px-3 py-2 opacity-50"><span class="block text-lg">⌕</span>Tìm kiếm</button>
                <button type="button" disabled class="rounded-xl px-3 py-2 opacity-50"><span class="block text-lg">●</span>Thông báo</button>
            </div>
        </nav>
    </div>

    <script>
        (() => {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));
            }

            let installPrompt = null;
            const installButton = document.getElementById('install-app');

            window.addEventListener('beforeinstallprompt', (event) => {
                event.preventDefault();
                installPrompt = event;
                installButton.hidden = false;
            });

            installButton?.addEventListener('click', async () => {
                if (!installPrompt) return;
                await installPrompt.prompt();
                installPrompt = null;
                installButton.hidden = true;
            });

            window.addEventListener('appinstalled', () => {
                installPrompt = null;
                if (installButton) installButton.hidden = true;
            });
        })();
    </script>
</body>
</html>
