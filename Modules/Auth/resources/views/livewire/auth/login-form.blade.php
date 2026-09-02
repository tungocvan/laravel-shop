@php
    $theme = $presentation['theme'] ?? 'classic-card';
    $backgroundUrl = $presentation['background_url'] ?? null;
    $primaryColor = $presentation['primary_color'] ?? '#0f172a';
    $overlayOpacity = ((int) ($presentation['overlay_opacity'] ?? 55)) / 100;
    $showGoogle = (bool) ($presentation['show_google'] ?? true);
    $footer = $presentation['footer'] ?? '';
    $googleRoute = $guard === 'admin' ? 'google' : 'client.apps.google';
@endphp

<div
    class="relative flex min-h-screen w-screen overflow-hidden {{ $theme === 'minimal' ? 'bg-white' : 'bg-slate-100' }}"
    @if($backgroundUrl)
        style="background-image: url('{{ $backgroundUrl }}'); background-size: cover; background-position: center;"
    @endif
>
    @if($backgroundUrl)
        <div class="absolute inset-0 bg-black" style="opacity: {{ $overlayOpacity }}"></div>
    @endif

    <div class="relative z-10 flex min-h-screen w-full {{ $theme === 'split-brand' ? 'items-stretch' : 'items-center justify-center' }} p-4 sm:p-6 lg:p-8">
        @if($theme === 'split-brand')
            <section class="hidden w-1/2 flex-col justify-end p-10 text-white lg:flex" aria-label="Thông tin hệ thống">
                <div class="max-w-xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-white/80">{{ $login_name_line_1 }}</p>
                    <h1 class="mt-3 text-4xl font-bold leading-tight">{{ $login_name_line_2 }}</h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-white/80">{{ $login_description }}</p>
                </div>
            </section>
        @endif

        <main class="flex w-full items-center justify-center {{ $theme === 'split-brand' ? 'lg:w-1/2' : '' }}">
            <div class="w-full max-w-md {{ $theme === 'minimal' ? 'bg-white shadow-none' : ($theme === 'hero-overlay' ? 'bg-white/90 shadow-2xl backdrop-blur-md' : 'bg-white shadow-xl') }} rounded-2xl border {{ $theme === 'minimal' ? 'border-transparent' : 'border-white/60' }} p-6 sm:p-8">
                <div class="text-center">
                    @if($logo)
                        <img src="{{ $logo }}" class="mx-auto h-24 w-24 object-contain sm:h-28 sm:w-28" alt="Logo {{ $login_name_line_2 }}">
                    @endif

                    @if($theme !== 'split-brand')
                        @if($login_name_line_1)
                            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">{{ $login_name_line_1 }}</p>
                        @endif
                        <h1 class="mt-2 text-lg font-bold leading-snug text-gray-900">{{ $login_name_line_2 }}</h1>
                        @if($login_description)
                            <p class="mt-3 text-sm leading-6 text-gray-500">{{ $login_description }}</p>
                        @endif
                    @else
                        <h1 class="mt-4 text-lg font-bold text-gray-900">Đăng nhập hệ thống</h1>
                        <p class="mt-2 text-sm text-gray-500">Sử dụng tài khoản được cấp để tiếp tục.</p>
                    @endif
                </div>

                <form wire:submit="login" class="mt-7 space-y-4">
                    <div>
                        <label for="auth-email-{{ $guard }}" class="mb-2 block text-sm font-medium text-gray-700">Email đăng nhập</label>
                        <input id="auth-email-{{ $guard }}" wire:model="email" type="email" autocomplete="username"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-gray-900 outline-none transition focus:ring-2 @error('email') border-red-500 @enderror"
                            style="--tw-ring-color: {{ $primaryColor }}" placeholder="example@gmail.com">
                        @error('email')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label for="auth-password-{{ $guard }}" class="mb-2 block text-sm font-medium text-gray-700">Mật khẩu</label>
                        <input id="auth-password-{{ $guard }}" wire:model="password" type="password" autocomplete="current-password"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-gray-900 outline-none transition focus:ring-2"
                            style="--tw-ring-color: {{ $primaryColor }}" placeholder="••••••••">
                        @error('password')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </div>

                    <label class="flex items-center gap-2">
                        <input wire:model="remember" type="checkbox" class="rounded border-gray-300 focus:ring-2" style="color: {{ $primaryColor }}; --tw-ring-color: {{ $primaryColor }}">
                        <span class="text-sm text-gray-600">Ghi nhớ đăng nhập</span>
                    </label>

                    <button type="submit" wire:loading.attr="disabled" wire:target="login"
                        class="w-full rounded-xl py-2.5 font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-60"
                        style="background-color: {{ $primaryColor }}">
                        <span wire:loading.remove wire:target="login">Đăng nhập hệ thống</span>
                        <span wire:loading wire:target="login">Đang xử lý...</span>
                    </button>
                </form>

                @if($showGoogle && Route::has($googleRoute))
                    <div class="mt-6 flex items-center gap-3">
                        <div class="h-px flex-1 bg-gray-200"></div>
                        <span class="text-xs text-gray-400">hoặc</span>
                        <div class="h-px flex-1 bg-gray-200"></div>
                    </div>

                    <a href="{{ route($googleRoute) }}" class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Đăng nhập bằng Google Workspace
                    </a>
                @endif

                @if($footer)
                    <p class="mt-6 text-center text-xs text-gray-400">{{ $footer }}</p>
                @endif
            </div>
        </main>
    </div>
</div>
