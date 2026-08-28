<div class="w-full">
    <div class="mb-7 text-center">
        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-200">
            <img src="{{ $logo ?: asset('storage/logo.png') }}" class="h-full w-full object-contain p-2" alt="INAFO">
        </div>
        <p class="text-xs font-bold uppercase tracking-[0.28em] text-slate-400">INAFO Client Portal</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Chào mừng trở lại</h1>
        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Đăng nhập để mở các ứng dụng, dữ liệu và công cụ đã được cấp cho tài khoản của bạn.</p>
    </div>

    <form wire:submit="login" class="space-y-4">
        <label class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Email</span>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="m4 6 8 6 8-6"/></svg>
                </span>
                <input wire:model="email" type="email" autocomplete="email" inputmode="email" placeholder="you@example.com"
                    class="h-14 w-full rounded-2xl border border-slate-200 bg-white pl-12 pr-4 text-base text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100 @error('email') border-red-400 @enderror">
            </div>
            @error('email')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Mật khẩu</span>
            <div class="relative" x-data="{ show: false }">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                </span>
                <input wire:model="password" :type="show ? 'text' : 'password'" autocomplete="current-password" placeholder="••••••••"
                    class="h-14 w-full rounded-2xl border border-slate-200 bg-white pl-12 pr-12 text-base text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100 @error('password') border-red-400 @enderror">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400" aria-label="Hiện hoặc ẩn mật khẩu">
                    <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    <svg x-show="show" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18"/><path d="M10.6 6.2A11.7 11.7 0 0 1 12 6c6.5 0 10 6 10 6a16 16 0 0 1-3 3.8M6.1 6.1C3.5 8 2 12 2 12s3.5 6 10 6a10.7 10.7 0 0 0 4.1-.8"/></svg>
                </button>
            </div>
            @error('password')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
            <input wire:model="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
            <span class="text-sm font-medium text-slate-600">Giữ tôi đăng nhập trên thiết bị này</span>
        </label>

        <button type="submit" wire:loading.attr="disabled"
            class="flex h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-5 text-base font-black text-white shadow-lg shadow-slate-300 transition hover:bg-slate-800 disabled:cursor-wait disabled:opacity-70">
            <span wire:loading.remove>Đăng nhập vào ứng dụng</span>
            <span wire:loading class="inline-flex items-center gap-2"><span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>Đang đăng nhập...</span>
        </button>
    </form>

    @if(Route::has('client.apps.google'))
        <div class="my-4 flex items-center gap-3 text-xs font-bold uppercase tracking-[0.16em] text-slate-300">
            <span class="h-px flex-1 bg-slate-200"></span><span>hoặc</span><span class="h-px flex-1 bg-slate-200"></span>
        </div>
        <a href="{{ route('client.apps.google') }}" class="flex h-12 w-full items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white text-sm font-black text-slate-800 transition hover:bg-slate-50">
            <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21.35 12.18c0-.7-.06-1.22-.2-1.77H12v3.22h5.37a4.6 4.6 0 0 1-2 3.02l-.03.11 2.84 2.2.2.02c1.83-1.69 2.97-4.18 2.97-6.8Z"/><path fill="currentColor" d="M12 21.7c2.62 0 4.82-.86 6.43-2.72l-3.06-2.33c-.82.56-1.92.95-3.37.95a5.85 5.85 0 0 1-5.53-4.04l-.1.01-2.95 2.29-.04.09A9.7 9.7 0 0 0 12 21.7Z"/><path fill="currentColor" d="M6.47 13.56A5.98 5.98 0 0 1 6.15 12c0-.54.1-1.06.3-1.56v-.11L3.45 8l-.1.05A9.7 9.7 0 0 0 2.3 12c0 1.43.38 2.78 1.07 3.95l3.1-2.39Z"/><path fill="currentColor" d="M12 6.4c1.84 0 3.08.8 3.78 1.45l2.72-2.66C16.83 3.63 14.62 2.3 12 2.3A9.7 9.7 0 0 0 3.35 8.05l3.1 2.39A5.87 5.87 0 0 1 12 6.4Z"/></svg>
            Tiếp tục với Google
        </a>
    @endif

    @if(Route::has('client.apps.register'))
        <a href="{{ route('client.apps.register') }}" class="mt-4 flex h-12 w-full items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm font-black text-slate-800 transition hover:bg-slate-50">
            Đăng ký tài khoản
        </a>
    @endif

    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-xs leading-5 text-slate-500">
        Phiên đăng nhập được bảo vệ bằng HTTPS. Chỉ các ứng dụng được quản trị viên cấp quyền mới xuất hiện sau khi đăng nhập.
    </div>
</div>
