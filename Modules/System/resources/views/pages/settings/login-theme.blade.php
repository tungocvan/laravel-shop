@extends('Admin::layouts.master')
@section('title', 'Giao diện đăng nhập')
@section('content')
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

        @livewire('system.settings.partials.login-theme')
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('label[for="login-logo-file"], label[for="login-background-file"]');

            if (! trigger) {
                return;
            }

            const originalInput = document.getElementById(trigger.getAttribute('for'));

            if (! originalInput || originalInput.disabled) {
                event.preventDefault();
                return;
            }

            const sourceForm = originalInput.form;

            if (! sourceForm) {
                return;
            }

            event.preventDefault();

            const pickerForm = document.createElement('form');
            pickerForm.method = 'POST';
            pickerForm.action = sourceForm.action;
            pickerForm.enctype = 'multipart/form-data';
            pickerForm.style.display = 'none';

            const token = sourceForm.querySelector('input[name="_token"]');
            const target = sourceForm.querySelector('input[name="target"]');

            if (token) {
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = token.value;
                pickerForm.appendChild(tokenInput);
            }

            if (target) {
                const targetInput = document.createElement('input');
                targetInput.type = 'hidden';
                targetInput.name = 'target';
                targetInput.value = target.value;
                pickerForm.appendChild(targetInput);
            }

            const picker = document.createElement('input');
            picker.type = 'file';
            picker.name = 'asset';
            picker.accept = originalInput.accept;
            pickerForm.appendChild(picker);
            document.body.appendChild(pickerForm);

            let submitted = false;
            const cleanup = () => {
                if (! submitted && pickerForm.isConnected) {
                    pickerForm.remove();
                }
            };

            picker.addEventListener('change', () => {
                if (! picker.files || picker.files.length === 0) {
                    cleanup();
                    return;
                }

                submitted = true;
                pickerForm.submit();
            }, { once: true });

            window.addEventListener('focus', () => {
                window.setTimeout(() => {
                    if (! submitted && (! picker.files || picker.files.length === 0)) {
                        cleanup();
                    }
                }, 250);
            }, { once: true });

            picker.click();
        });
    </script>
@endsection
