@php
    $theme = $presentation['theme'] ?? 'classic-card';
    $backgroundUrl = ($presentation['background_type'] ?? 'image') === 'image' ? ($presentation['background_url'] ?? null) : null;
    $backgroundColor = $presentation['background_color'] ?? '#f1f5f9';
    $formBackgroundColor = $presentation['form_background_color'] ?? '#ffffff';
    $logoSize = $presentation['logo_size'] ?? 'large';
    $logoWidth = ['small' => '100px', 'medium' => '160px', 'large' => '220px'][$logoSize] ?? '220px';
    $primaryColor = $presentation['primary_color'] ?? '#0f172a';
    $overlayOpacity = ((int) ($presentation['overlay_opacity'] ?? 55)) / 100;
    $showGoogle = (bool) ($presentation['show_google'] ?? true);
    $footer = $presentation['footer'] ?? '';
    $googleRoute = $guard === 'admin' ? 'google' : 'client.apps.google';
@endphp

<div
    class="relative flex min-h-screen w-screen overflow-hidden {{ $theme === 'minimal' ? 'bg-white' : 'bg-slate-100' }}"
    style="background-color: {{ $backgroundColor }}; @if($backgroundUrl) background-image: url('{{ $backgroundUrl }}'); background-size: cover; background-position: center; @endif"
>
    @if($backgroundUrl)
        <div class="absolute inset-0 bg-black" style="opacity: {{ $overlayOpacity }}"></div>
    @endif

    <div class="relative z-10 flex min-h-screen w-full {{ $theme === 'split-brand' ? 'items-stretch' : 'items-center justify-center' }} px-3 py-4 sm:p-6 lg:p-8">
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
            <div class="w-full max-w-md {{ $theme === 'minimal' ? 'bg-white shadow-none' : ($theme === 'hero-overlay' ? 'bg-white/90 shadow-2xl backdrop-blur-md' : 'bg-white shadow-xl') }} rounded-2xl border {{ $theme === 'minimal' ? 'border-transparent' : 'border-white/60' }} p-5 sm:p-8" style="background-color: {{ $formBackgroundColor }};">
                <div class="text-center">
                    @if($logo)
                        <img src="{{ $logo }}" class="mx-auto h-auto max-w-[min(100%,180px)] object-contain sm:max-w-full" style="width: {{ $logoWidth }}; max-height: 140px;" alt="Logo {{ $login_name_line_2 }}">
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

                <form wire:submit="login" class="mt-5 space-y-4 sm:mt-7">
                    <div>
                        <label for="auth-email-{{ $guard }}" class="mb-2 block text-sm font-medium text-gray-700">Email đăng nhập</label>
                        <input id="auth-email-{{ $guard }}" wire:model="email" type="email" autocomplete="username"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-gray-900 outline-none transition focus:ring-2 @error('email') border-red-500 @enderror"
                            style="--tw-ring-color: {{ $primaryColor }}" placeholder="example@gmail.com">
                        @error('email')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label for="auth-password-{{ $guard }}" class="mb-2 block text-sm font-medium text-gray-700">Mật khẩu</label>
                        <div x-data="{ visible: false }" class="relative"><input id="auth-password-{{ $guard }}" wire:model="password" x-bind:type="visible ? 'text' : 'password'" type="password" autocomplete="current-password"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 pr-12 text-gray-900 outline-none transition focus:ring-2"
                            style="--tw-ring-color: {{ $primaryColor }}" placeholder="••••••••"><button type="button" x-on:click="visible = !visible" x-bind:aria-label="visible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'" x-bind:aria-pressed="visible.toString()" class="absolute inset-y-0 right-0 flex min-h-11 w-11 items-center justify-center rounded-r-xl text-gray-500 transition hover:text-gray-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" aria-label="Hiện mật khẩu"><svg x-show="!visible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6Z"/><circle cx="12" cy="12" r="3"/></svg><svg x-show="visible" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.6 6.1A11 11 0 0 1 12 6c6.25 0 9.75 6 9.75 6a15 15 0 0 1-3.1 3.5M6.1 6.9C3.6 8.5 2.25 12 2.25 12s3.5 6 9.75 6a10.7 10.7 0 0 0 3.1-.45"/></svg></button></div>
                        @error('password')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </div>

                    <label class="flex items-center gap-2">
                        <input wire:model="remember" type="checkbox" class="rounded border-gray-300 focus:ring-2" style="color: {{ $primaryColor }}; --tw-ring-color: {{ $primaryColor }}">
                        <span class="text-sm text-gray-600">Ghi nhớ đăng nhập</span>
                    </label>

                    <button type="submit" wire:loading.attr="disabled" wire:target="login"
                        class="min-h-11 w-full rounded-xl py-2.5 font-semibold text-white shadow-sm transition hover:brightness-110 active:scale-[0.985] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-60 motion-reduce:transform-none"
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

                    <a href="{{ route($googleRoute) }}" class="mt-5 flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 active:scale-[0.985] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:transform-none">
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.25 5.48-4.76 7.18l7.73 6C44.42 38.03 46.98 31.88 46.98 24.55Z"/><path fill="#FBBC05" d="M10.53 28.59A14.41 14.41 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.2A23.9 23.9 0 0 0 0 24c0 3.87.93 7.51 2.56 10.78l7.97-6.19Z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.73-6c-2.15 1.45-4.92 2.3-8.18 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.97 6.19C6.51 42.62 14.62 48 24 48Z"/></svg>
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
