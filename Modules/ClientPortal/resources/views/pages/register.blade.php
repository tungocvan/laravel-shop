<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $pwaGeneral['theme_color'] }}">
    <meta name="application-name" content="{{ $pwaGeneral['application_name'] }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/pwa/icon.svg" type="image/svg+xml">
    <title>Tạo tài khoản · {{ $pwaGeneral['browser_title'] }}</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @livewireStyles
</head>
<body class="min-h-screen text-slate-900 antialiased" style="background-color: {{ $pwaGeneral['background_color'] }}">
    <main class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
        <section class="w-full max-w-md rounded-[2rem] bg-white p-5 shadow-2xl shadow-black/30 ring-1 ring-white/20 sm:p-8">
            @livewire('auth.auth.registration-form')
            <div class="mt-6 flex items-center justify-center gap-3 text-xs font-semibold text-slate-400">
                <a href="/" class="hover:text-slate-700">{{ $pwaLogin['back_to_website_text'] }}</a>
                <span>·</span>
                <span>Đăng ký PWA</span>
            </div>
        </section>
    </main>
    @livewireScripts
</body>
</html>
