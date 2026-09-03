@extends('Admin::layouts.master')
@section('title', 'Giao diện đăng nhập')
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
                <h1 class="text-2xl font-bold text-gray-900">Giao diện đăng nhập</h1>
                <p class="mt-1 text-sm text-gray-500">Quản lý theme và nhận diện cho cổng đăng nhập Admin và Client / PWA.</p>
            </div>
            <a href="{{ route('admin.system.settings.index') }}"
               class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Cấu hình hệ thống
            </a>
        </div>

        @php
            $logoUploadError = $errors->getBag('logoUpload')->first('asset');
            $backgroundUploadError = $errors->getBag('backgroundUpload')->first('asset');
        @endphp

        @if($logoUploadError || $backgroundUploadError)
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                {{ $logoUploadError ?: $backgroundUploadError }}
            </div>
        @endif

        <div id="login-theme-client-upload-error"
             class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
             role="alert"></div>

        @livewire('system.settings.partials.login-theme')
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
                    // Do not remove the temporary input immediately on focus. On some
                    // browsers Windows restores focus before dispatching the file input's
                    // change event, which previously removed the selected file silently.
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