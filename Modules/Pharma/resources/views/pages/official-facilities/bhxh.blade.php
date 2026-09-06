@extends('Admin::layouts.master')

@section('title', 'Tra cứu cơ sở KCB BHXH')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-600">Pharma / BHXH Facility Lookup</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Tra cứu cơ sở KCB ký hợp đồng BHYT</h1>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Tỉnh/Thành là nhãn ERP-facing. Vùng dữ liệu và địa bàn BHXH giữ nguyên mã nguồn để tra cứu đúng dữ liệu lịch sử sau sắp xếp hành chính.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.pharma.official-facilities.source.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-sky-300 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100">Kho dữ liệu nguồn</a>
                <a href="{{ route('admin.pharma.official-facilities.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Import XLSX/CSV</a>
                <a href="{{ route('admin.pharma.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-indigo-300">Quay về Dashboard</a>
            </div>
        </header>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900">Tra cứu trực tuyến BHXH</h2>
                <p class="mt-1 text-sm text-slate-500">Nếu một tỉnh mới gồm nhiều vùng nguồn BHXH cũ, hãy chọn đúng vùng trước khi chọn địa bàn và nhập CAPTCHA.</p>
            </div>

            <form data-bhxh-lookup-form class="grid gap-4 xl:grid-cols-5">
                @csrf
                <div>
                    <label for="ma_tinh" class="mb-1 block text-sm font-medium text-slate-700">Tỉnh/Thành <span class="text-rose-500">*</span></label>
                    <select id="ma_tinh" name="ma_tinh" required class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">- Chọn Tỉnh/Thành -</option>
                        @foreach ($bhxhProvinces as $code => $name)
                            <option value="{{ $code }}" @selected($code === '92TTT')>{{ $name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Một tỉnh ERP có thể ánh xạ tới nhiều vùng nguồn BHXH.</p>
                </div>

                <div>
                    <label for="source_partition" class="mb-1 block text-sm font-medium text-slate-700">Vùng dữ liệu BHXH <span class="text-rose-500">*</span></label>
                    <select id="source_partition" name="source_partition" required class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:ring-sky-500"></select>
                    <p data-partition-status class="mt-1 text-xs text-slate-500">Mã vùng nguồn được giữ nguyên để gọi BHXH.</p>
                </div>

                <div>
                    <label for="ma_quan_huyen" class="mb-1 block text-sm font-medium text-slate-700">Địa bàn BHXH</label>
                    <select id="ma_quan_huyen" name="ma_quan_huyen" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">-- Toàn vùng --</option>
                    </select>
                    <p data-district-status class="mt-1 text-xs text-slate-500">Đang tải danh sách địa bàn từ BHXH...</p>
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

                <div class="xl:col-span-5 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">Không OCR / không bypass CAPTCHA. Mỗi lần tra cứu sử dụng đúng một vùng nguồn BHXH và yêu cầu CAPTCHA mới.</p>
                    <button type="submit" data-lookup-button class="min-h-11 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-slate-300">Tra cứu BHXH</button>
                </div>
            </form>

            <div data-bhxh-message class="mt-4 hidden rounded-xl border px-4 py-3 text-sm"></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Kết quả</h2>
                    <p class="mt-1 text-sm text-slate-500">Mã CSKCB và tên cơ sở do BHXH trả về; mã CSKCB tiếp tục là source identity để enrich dữ liệu về sau.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div data-result-count class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">0 cơ sở</div>
                    @can('sync_pharma_official_facilities')
                        <button type="button" data-sync-source disabled class="min-h-11 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300">Đồng bộ vùng này</button>
                    @endcan
                </div>
            </div>

            <div data-sync-message class="mt-4 hidden rounded-xl border px-4 py-3 text-sm"></div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr><th class="px-4 py-3">STT</th><th class="px-4 py-3">Mã CSKCB</th><th class="px-4 py-3">Tên CSKCB</th></tr>
                    </thead>
                    <tbody data-result-body class="divide-y divide-slate-100 bg-white">
                        <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">Chưa có kết quả tra cứu.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const partitions = @json($bhxhPartitions);
            const form = document.querySelector('[data-bhxh-lookup-form]');
            const provinceSelect = document.querySelector('#ma_tinh');
            const partitionSelect = document.querySelector('#source_partition');
            const partitionStatus = document.querySelector('[data-partition-status]');
            const districtSelect = document.querySelector('#ma_quan_huyen');
            const districtStatus = document.querySelector('[data-district-status]');
            const captchaImage = document.querySelector('[data-bhxh-captcha]');
            const refreshCaptcha = document.querySelector('[data-refresh-captcha]');
            const captchaInput = document.querySelector('#captcha');
            const button = document.querySelector('[data-lookup-button]');
            const message = document.querySelector('[data-bhxh-message]');
            const resultBody = document.querySelector('[data-result-body]');
            const resultCount = document.querySelector('[data-result-count]');
            const syncButton = document.querySelector('[data-sync-source]');
            const syncMessage = document.querySelector('[data-sync-message]');
            let syncPollTimer = null;

            const showMessage = (element, text, ok) => {
                if (!element) return;
                element.textContent = text;
                element.className = `mt-4 rounded-xl border px-4 py-3 text-sm ${ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'}`;
            };

            const showPendingMessage = (text) => {
                if (!syncMessage) return;
                syncMessage.textContent = text;
                syncMessage.className = 'mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800';
            };

            const reloadCaptcha = () => {
                captchaImage.src = `{{ route('admin.pharma.official-facilities.bhxh.captcha') }}?v=${Date.now()}`;
                captchaInput.value = '';
                captchaInput.focus();
            };

            const renderRows = (facilities) => {
                resultCount.textContent = `${facilities.length} cơ sở`;
                resultBody.innerHTML = '';

                if (facilities.length === 0) {
                    resultBody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">Không có cơ sở phù hợp hoặc CAPTCHA/vùng nguồn chưa hợp lệ.</td></tr>';
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

            const populatePartitions = () => {
                partitionSelect.innerHTML = '';
                const items = partitions[provinceSelect.value] ?? [];

                items.forEach((partition) => {
                    const option = document.createElement('option');
                    option.value = partition.source_code;
                    option.textContent = `${partition.partition_name} · ${partition.source_code}`;
                    partitionSelect.appendChild(option);
                });

                partitionSelect.disabled = items.length === 0;
                partitionStatus.textContent = items.length > 1
                    ? `Tỉnh này có ${items.length} vùng dữ liệu BHXH. Chọn đúng vùng nguồn cần tra cứu.`
                    : (items.length === 1 ? 'Tỉnh này có một vùng dữ liệu BHXH.' : 'Chưa có vùng dữ liệu BHXH.');
            };

            const loadDistricts = async () => {
                districtSelect.innerHTML = '<option value="">-- Toàn vùng --</option>';

                if (!provinceSelect.value || !partitionSelect.value) {
                    districtSelect.disabled = true;
                    districtStatus.textContent = 'Chọn Tỉnh/Thành và vùng dữ liệu BHXH.';
                    return;
                }

                districtSelect.disabled = true;
                districtStatus.textContent = 'Đang tải danh sách địa bàn từ BHXH...';

                try {
                    const url = new URL(`{{ route('admin.pharma.official-facilities.bhxh.districts') }}`, window.location.origin);
                    url.searchParams.set('ma_tinh', provinceSelect.value);
                    url.searchParams.set('source_partition', partitionSelect.value);
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message ?? 'Không tải được danh sách địa bàn BHXH.');

                    (payload.districts ?? []).forEach((district) => {
                        const option = document.createElement('option');
                        option.value = district.code;
                        option.textContent = district.name;
                        districtSelect.appendChild(option);
                    });

                    districtStatus.textContent = (payload.districts ?? []).length > 0
                        ? `Đã tải ${(payload.districts ?? []).length} địa bàn từ vùng nguồn BHXH đã chọn.`
                        : 'BHXH không trả danh sách địa bàn; vẫn có thể tra toàn vùng.';
                } catch (error) {
                    districtStatus.textContent = error.message || 'Không tải được danh sách địa bàn; vẫn có thể tra toàn vùng.';
                } finally {
                    districtSelect.disabled = false;
                }
            };

            const pollSyncStatus = async (batchId) => {
                const statusBase = `{{ route('admin.pharma.official-facilities.source.sync-status', ['batch' => '__BATCH__']) }}`;
                const statusUrl = statusBase.replace('__BATCH__', batchId);

                try {
                    const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message ?? 'Không đọc được trạng thái batch.');

                    if (payload.status === 'COMPLETED') {
                        window.clearTimeout(syncPollTimer);
                        syncPollTimer = null;
                        syncButton.textContent = 'Đồng bộ hoàn tất';
                        showMessage(syncMessage, `Đồng bộ hoàn tất. Batch #${payload.batch_id} · ${payload.fetched_count ?? 0} cơ sở · tạo mới ${payload.created_count ?? 0} · cập nhật ${payload.updated_count ?? 0} · không đổi ${payload.unchanged_count ?? 0}.`, true);
                        return;
                    }

                    if (payload.status === 'FAILED') {
                        window.clearTimeout(syncPollTimer);
                        syncPollTimer = null;
                        syncButton.textContent = 'Đồng bộ thất bại';
                        showMessage(syncMessage, payload.error_message || `Batch #${payload.batch_id} đồng bộ thất bại.`, false);
                        return;
                    }

                    syncButton.textContent = payload.status === 'RUNNING' ? 'Đang đồng bộ...' : 'Đang chờ queue...';
                    showPendingMessage(`Batch #${payload.batch_id} · ${payload.status === 'RUNNING' ? 'đang đồng bộ dữ liệu...' : 'đang chờ worker xử lý...'}`);
                    syncPollTimer = window.setTimeout(() => pollSyncStatus(batchId), 1500);
                } catch (error) {
                    window.clearTimeout(syncPollTimer);
                    syncPollTimer = null;
                    syncButton.textContent = 'Kiểm tra kho dữ liệu';
                    showMessage(syncMessage, error.message || 'Không thể đọc trạng thái đồng bộ.', false);
                }
            };

            const resetLookupState = () => {
                if (syncButton) {
                    syncButton.disabled = true;
                    syncButton.textContent = 'Đồng bộ vùng này';
                }
                renderRows([]);
            };

            provinceSelect.addEventListener('change', () => {
                resetLookupState();
                populatePartitions();
                loadDistricts();
            });
            partitionSelect.addEventListener('change', () => {
                resetLookupState();
                loadDistricts();
            });
            districtSelect.addEventListener('change', () => {
                if (syncButton) syncButton.disabled = true;
            });
            refreshCaptcha.addEventListener('click', reloadCaptcha);

            populatePartitions();
            loadDistricts();

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                button.disabled = true;
                button.textContent = 'Đang tra cứu...';
                if (syncButton) {
                    syncButton.disabled = true;
                    syncButton.textContent = 'Đồng bộ vùng này';
                }

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
                    showMessage(message, payload.message ?? (response.ok ? 'Tra cứu hoàn tất.' : 'Tra cứu thất bại.'), response.ok && (payload.facilities ?? []).length > 0);
                    if (syncButton) syncButton.disabled = !payload.can_sync;
                } catch (error) {
                    renderRows([]);
                    showMessage(message, 'Không thể gọi route tra cứu BHXH. Kiểm tra kết nối máy chủ và log Laravel.', false);
                } finally {
                    button.disabled = false;
                    button.textContent = 'Tra cứu BHXH';
                    reloadCaptcha();
                }
            });

            syncButton?.addEventListener('click', async () => {
                syncButton.disabled = true;
                syncButton.textContent = 'Đang đưa vào queue...';

                try {
                    const response = await fetch(`{{ route('admin.pharma.official-facilities.source.sync') }}`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                        },
                        body: JSON.stringify({}),
                    });
                    const payload = await response.json();
                    if (!response.ok) {
                        showMessage(syncMessage, payload.message ?? 'Không thể tạo batch đồng bộ.', false);
                        syncButton.disabled = false;
                        syncButton.textContent = 'Đồng bộ vùng này';
                        return;
                    }

                    showPendingMessage(`${payload.message} Batch #${payload.batch_id} · ${payload.count} cơ sở.`);
                    syncButton.textContent = 'Đang chờ queue...';
                    pollSyncStatus(payload.batch_id);
                } catch (error) {
                    showMessage(syncMessage, 'Không thể tạo yêu cầu đồng bộ. Kiểm tra queue/log Laravel.', false);
                    syncButton.disabled = false;
                    syncButton.textContent = 'Đồng bộ vùng này';
                }
            });
        });
    </script>
@endsection
