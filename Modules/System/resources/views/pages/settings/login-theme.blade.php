@extends('Admin::layouts.master')
@section('title', 'Giao diện & Đăng nhập')
@section('content')
    <style>
        .login-theme-logo-contrast {
            background-color: #e5e7eb !important;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 0.25rem;
        }
    </style>

    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Access Experience</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">Giao diện & Đăng nhập</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">Một workspace duy nhất cho branding đăng nhập, trải nghiệm xác thực và điều hướng mặc định sau đăng nhập.</p>
            </div>
            <a href="{{ route('admin.system.dashboard') }}"
               class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Dashboard hệ thống
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('info'))
            <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800">
                {{ session('info') }}
            </div>
        @endif

        @php
            $logoUploadError = $errors->getBag('logoUpload')->first('asset');
            $backgroundUploadError = $errors->getBag('backgroundUpload')->first('asset');
        @endphp

        @if($logoUploadError || $backgroundUploadError)
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                {{ $logoUploadError ?: $backgroundUploadError }}
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">1. Giao diện</span>
                <h2 class="mt-3 text-lg font-bold text-slate-900">Branding đăng nhập</h2>
                <p class="mt-1 text-sm text-slate-500">Logo, ảnh nền, màu sắc và nhận diện cho cổng Admin và Client / PWA.</p>
            </div>

            <div id="login-theme-client-upload-error"
                 class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
                 role="alert"></div>

            @livewire('system.settings.partials.login-theme')
        </section>

        <section id="login-navigation" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 pb-5">
                <div>
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">2. Hành vi & Điều hướng</span>
                </div>
                <h2 class="mt-2 text-lg font-bold text-slate-900">Đăng nhập & Điều hướng</h2>
                <p class="text-sm leading-6 text-slate-500">Quản lý landing page của Admin và route thay thế khi Website không phục vụ <code>/</code>.</p>
            </div>

            <form method="POST" action="{{ route('admin.system.settings.login-redirect.update') }}" class="mt-6 space-y-6">
                @csrf

                <div>
                    <label for="admin-login-redirect-route" class="block text-sm font-medium text-slate-900">Trang mặc định sau đăng nhập Admin</label>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Route <code>/admin</code> là entrypoint động. Nếu destination không còn khả dụng, hệ thống vẫn giữ fallback an toàn.</p>
                    <select id="admin-login-redirect-route"
                            name="route_name"
                            class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($routeOptions as $name => $label)
                            <option value="{{ $name }}" @selected(old('route_name', $routeName) === $name)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('route_name')
                        <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <label for="application-root-fallback-route" class="block text-sm font-medium text-slate-900">Route thay thế cho <code>/</code></label>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Chỉ áp dụng khi Website không đăng ký route gốc. Nếu Website đang phục vụ <code>/</code>, trang chủ Website vẫn được ưu tiên.</p>
                    <select id="application-root-fallback-route"
                            name="root_route_name"
                            class="mt-3 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($rootRouteOptions as $name => $label)
                            <option value="{{ $name }}" @selected(old('root_route_name', $rootRouteName) === $name)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('root_route_name')
                        <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-800">
                    Danh sách chỉ gồm GET route có tên, không có tham số bắt buộc và không gây vòng lặp về chính <code>/</code>.
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-5">
                    <button type="submit" class="min-h-11 rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Lưu đăng nhập & điều hướng
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        const loginThemeUploadError = document.getElementById('login-theme-client-upload-error');

        const showLoginThemeUploadError = (message) => {
            if (! loginThemeUploadError) return;
            loginThemeUploadError.textContent = message;
            loginThemeUploadError.classList.remove('hidden');
            loginThemeUploadError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };

        const clearLoginThemeUploadError = () => {
            if (! loginThemeUploadError) return;
            loginThemeUploadError.textContent = '';
            loginThemeUploadError.classList.add('hidden');
        };

        const applyLoginThemeLogoContrast = () => {
            document.querySelectorAll('img[alt="Logo đăng nhập"], img[alt="Logo preview"]').forEach((image) => {
                image.classList.add('login-theme-logo-contrast');
            });
        };

        document.addEventListener('DOMContentLoaded', applyLoginThemeLogoContrast);
        document.addEventListener('livewire:navigated', applyLoginThemeLogoContrast);

        if (window.Livewire) {
            window.Livewire.hook('morph.updated', () => applyLoginThemeLogoContrast());
        } else {
            document.addEventListener('livewire:init', () => {
                window.Livewire.hook('morph.updated', () => applyLoginThemeLogoContrast());
            }, { once: true });
        }

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('label[for="login-logo-file"], label[for="login-background-file"]');
            if (! trigger) return;

            clearLoginThemeUploadError();

            const originalInput = document.getElementById(trigger.getAttribute('for'));
            if (! originalInput || originalInput.disabled) {
                event.preventDefault();
                return;
            }

            const sourceForm = originalInput.form;
            if (! sourceForm) return;

            event.preventDefault();

            const isLogo = originalInput.id === 'login-logo-file';
            const maxBytes = (isLogo ? 3 : 6) * 1024 * 1024;
            const maxLabel = isLogo ? '3 MB' : '6 MB';
            const assetLabel = isLogo ? 'Logo' : 'Ảnh nền';

            const pickerForm = document.createElement('form');
            pickerForm.method = 'POST';
            pickerForm.action = sourceForm.action;
            pickerForm.enctype = 'multipart/form-data';
            pickerForm.style.display = 'none';

            const token = sourceForm.querySelector('input[name="_token"]');
            const target = sourceForm.querySelector('input[name="target"]');

            [token, target].forEach((source) => {
                if (! source) return;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = source.name;
                input.value = source.value;
                pickerForm.appendChild(input);
            });

            const picker = document.createElement('input');
            picker.type = 'file';
            picker.name = 'asset';
            picker.accept = originalInput.accept;
            pickerForm.appendChild(picker);
            document.body.appendChild(pickerForm);

            let completed = false;
            const cleanup = () => {
                if (pickerForm.isConnected) pickerForm.remove();
            };

            picker.addEventListener('change', () => {
                completed = true;

                if (! picker.files || picker.files.length === 0) {
                    cleanup();
                    return;
                }

                const file = picker.files[0];
                if (file.size > maxBytes) {
                    showLoginThemeUploadError(`${assetLabel} không được vượt quá ${maxLabel}. Vui lòng chọn tệp nhỏ hơn trước khi tải lên.`);
                    cleanup();
                    return;
                }

                pickerForm.submit();
            }, { once: true });

            window.addEventListener('focus', () => {
                window.setTimeout(() => {
                    if (! completed && pickerForm.isConnected) {
                        window.setTimeout(() => {
                            if (! completed) cleanup();
                        }, 1000);
                    }
                }, 250);
            }, { once: true });

            picker.click();
        });
    </script>
@endsection
