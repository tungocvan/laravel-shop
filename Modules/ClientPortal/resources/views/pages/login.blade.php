<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="application-name" content="INAFO Client Portal">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="INAFO">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/pwa/icon.svg" type="image/svg+xml">
    <title>Đăng nhập · INAFO Client Portal</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute -left-28 -top-28 h-72 w-72 rounded-full bg-cyan-400/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -right-24 h-80 w-80 rounded-full bg-indigo-400/15 blur-3xl"></div>

        <main class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-8 sm:px-6 lg:grid lg:grid-cols-2 lg:gap-12 lg:px-8">
            <section class="hidden text-white lg:block">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-slate-300">INAFO · Progressive Web App</div>
                <h1 class="mt-6 max-w-xl text-5xl font-black leading-tight tracking-tight">Một nơi để mở tất cả ứng dụng công việc của bạn.</h1>
                <p class="mt-5 max-w-lg text-lg leading-8 text-slate-300">Tra cứu, đồng bộ dữ liệu, quản lý danh sách quan tâm, lập bảng giá và sử dụng các ứng dụng được cấp quyền ngay trên điện thoại.</p>
                <div class="mt-8 grid max-w-lg gap-3 text-sm text-slate-300 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong class="block text-white">Cài như ứng dụng</strong><span class="mt-1 block">Mở nhanh từ màn hình chính.</span></div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong class="block text-white">Phân quyền riêng</strong><span class="mt-1 block">Chỉ thấy chức năng được cấp.</span></div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong class="block text-white">Tối ưu Mobile</strong><span class="mt-1 block">Giao diện dành cho thao tác nhanh.</span></div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong class="block text-white">Queue nền</strong><span class="mt-1 block">Tiếp tục làm việc khi tác vụ đang xử lý.</span></div>
                </div>
            </section>

            <section class="w-full max-w-md rounded-[2rem] bg-white p-5 shadow-2xl shadow-black/30 ring-1 ring-white/20 sm:p-8 lg:justify-self-end">
                @livewire('auth.auth.login-form', ['guard' => 'web', 'variant' => 'pwa'])
                <div class="mt-6 flex items-center justify-center gap-3 text-xs font-semibold text-slate-400">
                    <a href="/" class="hover:text-slate-700">← Về website</a>
                    <span>·</span>
                    <span id="pwa-login-mode">Web App</span>
                </div>
            </section>
        </main>
    </div>

    @livewireScripts
    <script>
        (() => {
            const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const label = document.getElementById('pwa-login-mode');
            if (label && standalone) label.textContent = 'PWA đã cài đặt';
            if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));
        })();
    </script>
</body>
</html>
