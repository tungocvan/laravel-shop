@extends('Admin::layouts.master')

@section('title', 'Tra cứu cơ sở KCB BHXH')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-600">Pharma / BHXH Facility Lookup</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Tra cứu cơ sở KCB ký hợp đồng BHYT</h1>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">CAPTCHA được tải trực tiếp từ cổng BHXH và phải nhập thủ công. Kết quả tra cứu chỉ hiển thị để kiểm tra; chưa ghi Partner và chưa tự động staging.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.pharma.official-facilities.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Import XLSX/CSV</a>
                <a href="{{ route('admin.pharma.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-indigo-300">Quay về Dashboard</a>
            </div>
        </header>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900">Tra cứu trực tuyến BHXH</h2>
                <p class="mt-1 text-sm text-slate-500">Ví dụ Cần Thơ: <strong>MaTinh = 92TTT</strong>. Mã quận/huyện có thể để trống để tra toàn tỉnh.</p>
            </div>

            <form data-bhxh-lookup-form class="grid gap-4 lg:grid-cols-4">
                @csrf
                <div>
                    <label for="ma_tinh" class="mb-1 block text-sm font-medium text-slate-700">Mã tỉnh BHXH</label>
                    <input id="ma_tinh" name="ma_tinh" value="92TTT" required maxlength="50" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div>
                    <label for="ma_quan_huyen" class="mb-1 block text-sm font-medium text-slate-700">Mã quận/huyện</label>
                    <input id="ma_quan_huyen" name="ma_quan_huyen" maxlength="50" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-sky-500 focus:ring-sky-500" placeholder="Để trống = toàn tỉnh">
                </div>
                <div>
                    <label for="captcha" class="mb-1 block text-sm font-medium text-slate-700">Mã xác nhận</label>
                    <input id="captcha" name="captcha" required maxlength="20" autocomplete="off" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm uppercase focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div>
                    <span class="mb-1 block text-sm font-medium text-slate-700">CAPTCHA BHXH</span>
                    <div class="flex min-h-11 items-center gap-3">
                        <img data-bhxh-captcha src="{{ route('admin.pharma.official-facilities.bhxh.captcha') }}?v={{ now()->timestamp }}" alt="CAPTCHA BHXH" class="h-12 min-w-36 rounded-lg border border-slate-300 bg-white object-contain px-2">
                        <button type="button" data-refresh-captcha class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 hover:border-sky-300 hover:text-sky-700">Mã khác</button>
                    </div>
                </div>

                <div class="lg:col-span-4 flex items-center justify-between border-t border-slate-200 pt-4">
                    <p class="text-xs text-slate-500">Không OCR / không bypass CAPTCHA. Mỗi lần tra cứu xong hệ thống yêu cầu CAPTCHA mới.</p>
                    <button type="submit" data-lookup-button class="min-h-11 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-300">Tra cứu BHXH</button>
                </div>
            </form>

            <div data-bhxh-message class="mt-4 hidden rounded-xl border px-4 py-3 text-sm"></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Kết quả</h2>
                    <p class="mt-1 text-sm text-slate-500">Mã cơ sở và tên cơ sở do cổng BHXH trả về.</p>
                </div>
                <div data-result-count class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">0 cơ sở</div>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr><th class="px-4 py-3">STT</th><th class="px-4 py-3">Mã CSKCB</th><th class="px-4 py-3">Tên CSKCB</th></tr>
                    </thead>
                    <tbody data-result-body class="divide-y divide-slate-100 bg-white">
                        <tr data-empty-row><td colspan="3" class="px-4 py-8 text-center text-slate-500">Chưa có kết quả tra cứu.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-bhxh-lookup-form]');
            const captchaImage = document.querySelector('[data-bhxh-captcha]');
            const refreshCaptcha = document.querySelector('[data-refresh-captcha]');
            const button = document.querySelector('[data-lookup-button]');
            const message = document.querySelector('[data-bhxh-message]');
            const resultBody = document.querySelector('[data-result-body]');
            const resultCount = document.querySelector('[data-result-count]');
            const captchaInput = document.querySelector('#captcha');

            const reloadCaptcha = () => {
                captchaImage.src = `{{ route('admin.pharma.official-facilities.bhxh.captcha') }}?v=${Date.now()}`;
                captchaInput.value = '';
                captchaInput.focus();
            };

            const showMessage = (text, ok) => {
                message.textContent = text;
                message.className = `mt-4 rounded-xl border px-4 py-3 text-sm ${ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'}`;
            };

            const renderRows = (facilities) => {
                resultCount.textContent = `${facilities.length} cơ sở`;
                resultBody.innerHTML = '';

                if (facilities.length === 0) {
                    resultBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">Không có cơ sở phù hợp hoặc CAPTCHA/mã địa bàn chưa hợp lệ.</td></tr>';
                    return;
                }

                facilities.forEach((facility, index) => {
                    const row = document.createElement('tr');
                    [index + 1, facility.external_id, facility.facility_name].forEach((value) => {
                        const cell = document.createElement('td');
                        cell.className = 'px-4 py-3 text-slate-700';
                        cell.textContent = value ?? '';
                        row.appendChild(cell);
                    });
                    resultBody.appendChild(row);
                });
            };

            refreshCaptcha.addEventListener('click', reloadCaptcha);

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                button.disabled = true;
                button.textContent = 'Đang tra cứu...';

                try {
                    const response = await fetch(`{{ route('admin.pharma.official-facilities.bhxh.lookup') }}`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        },
                        body: new FormData(form),
                    });
                    const payload = await response.json();

                    renderRows(payload.facilities ?? []);
                    showMessage(payload.message ?? (response.ok ? 'Tra cứu hoàn tất.' : 'Tra cứu thất bại.'), response.ok && (payload.facilities ?? []).length > 0);
                } catch (error) {
                    renderRows([]);
                    showMessage('Không thể gọi route tra cứu BHXH. Kiểm tra kết nối máy chủ và log Laravel.', false);
                } finally {
                    button.disabled = false;
                    button.textContent = 'Tra cứu BHXH';
                    reloadCaptcha();
                }
            });
        });
    </script>
@endsection
