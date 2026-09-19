@extends('Admin::layouts.master')

@section('title', 'Kho dữ liệu cơ sở KCB nguồn')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-600">Pharma / Official Source Mirror</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Kho dữ liệu cơ sở KCB nguồn</h1>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Kho mirror giữ dữ liệu nguồn BHXH. Tỉnh/Thành là nhãn ERP-facing; mã vùng nguồn BHXH được giữ riêng để không làm mất lịch sử địa bàn nguồn.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.pharma.official-facilities.bhxh.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Tra cứu BHXH</a>
                <a href="{{ route('admin.pharma.official-facilities.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Official Import</a>
            </div>
        </header>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.pharma.official-facilities.source.index') }}" autocomplete="off" data-live-filter-form class="grid gap-3 lg:grid-cols-12">
                <x-search
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Tìm mã, tên cơ sở..."
                    class="lg:col-span-2"
                    data-live-search
                />

                <select name="business_region" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm lg:col-span-2">
                    <option value="">Tất cả vùng miền</option>
                    @foreach ($businessRegions as $regionKey => $regionName)
                        <option value="{{ $regionKey }}" @selected(request('business_region') === $regionKey)>{{ $regionName }}</option>
                    @endforeach
                </select>

                <select name="source" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm lg:col-span-1">
                    <option value="">Tất cả nguồn</option>
                    <option value="bhxh" @selected(request('source') === 'bhxh')>BHXH</option>
                </select>

                <select name="province" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm lg:col-span-2">
                    <option value="">Tất cả tỉnh/thành</option>
                    @foreach ($provinceOptions as $provinceName)
                        <option value="{{ $provinceName }}" @selected(request('province') === $provinceName)>{{ $provinceName }}</option>
                    @endforeach
                </select>

                <select name="partition" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm lg:col-span-2">
                    <option value="">Tất cả vùng nguồn BHXH</option>
                    @foreach ($partitionOptions as $partition)
                        <option value="{{ $partition->source_province_code }}" @selected(request('partition') === $partition->source_province_code)>
                            {{ $partitionLabels[$partition->source_province_code] ?? ($partition->province_name.' · '.$partition->source_province_code) }}
                        </option>
                    @endforeach
                </select>

                <select name="status" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm lg:col-span-1">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="stale" @selected(request('status') === 'stale')>Stale</option>
                </select>

                <select name="per_page" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm lg:col-span-1">
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / trang</option>
                    @endforeach
                </select>

                @if (request()->hasAny(['search', 'business_region', 'source', 'province', 'partition', 'status', 'per_page']))
                    <div class="flex min-h-11 items-center justify-end lg:col-span-1">
                        <a href="{{ route('admin.pharma.official-facilities.source.index') }}" class="whitespace-nowrap text-sm font-semibold text-slate-500 hover:text-sky-700">Xóa bộ lọc</a>
                    </div>
                @endif
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Dữ liệu đã đồng bộ</h2>
                    <p class="mt-1 text-sm text-slate-500">Identity nguồn: <code>(source, external_id)</code>. Tỉnh/Thành và vùng nguồn BHXH được hiển thị tách biệt.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="text-sm font-semibold text-slate-700">{{ number_format($facilities->total()) }} cơ sở</div>
                    <button type="submit" form="official-source-export-form" data-export-selected class="inline-flex min-h-10 items-center justify-center rounded-xl border border-sky-200 bg-white px-4 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50">Xuất Excel</button>
                    @can('import_pharma_official_facilities')
                        <button type="button" data-import-open class="inline-flex min-h-10 items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Import Excel</button>
                    @endcan
                </div>
            </div>

            <form id="official-source-export-form" method="POST" action="{{ route('admin.pharma.official-facilities.source.export') }}">
                @csrf
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="business_region" value="{{ request('business_region') }}">
                <input type="hidden" name="source" value="{{ request('source') }}">
                <input type="hidden" name="province" value="{{ request('province') }}">
                <input type="hidden" name="partition" value="{{ request('partition') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="w-12 px-4 py-3"><input type="checkbox" data-select-page aria-label="Chọn tất cả cơ sở trên trang hiện tại" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"></th>
                            <th class="px-4 py-3">Nguồn</th>
                            <th class="px-4 py-3">Mã CSKCB</th>
                            <th class="px-4 py-3">Tên cơ sở</th>
                            <th class="px-4 py-3">Vùng miền</th>
                            <th class="px-4 py-3">Tỉnh/Thành</th>
                            <th class="px-4 py-3">Vùng nguồn BHXH</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Lần đồng bộ cuối</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($facilities as $facility)
                            <tr>
                                <td class="px-4 py-3"><input type="checkbox" name="selected_ids[]" value="{{ $facility->id }}" data-row-select aria-label="Chọn {{ $facility->facility_name }}" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"></td>
                                <td class="px-4 py-3 font-semibold text-slate-700">{{ strtoupper($facility->source) }}</td>
                                <td class="px-4 py-3 font-mono text-slate-800">{{ $facility->external_id }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $facility->facility_name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $provinceRegions[$facility->province_name] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $facility->province_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div>{{ $partitionLabels[$facility->source_province_code] ?? $facility->source_province_code }}</div>
                                    <div class="mt-0.5 text-xs text-slate-400">{{ $facility->source_province_code }}</div>
                                </td>
                                <td class="px-4 py-3"><span class="rounded-full border px-2 py-1 text-xs font-semibold {{ $facility->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">{{ $facility->is_active ? 'ACTIVE' : 'STALE' }}</span></td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($facility->last_synced_at)->format('d/m/Y H:i') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">Không có dữ liệu phù hợp bộ lọc hiện tại.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </form>
            @if ($facilities->hasPages())
                <nav class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between" aria-label="Phân trang cơ sở KCB nguồn">
                    <span>Trang {{ $facilities->currentPage() }} / {{ $facilities->lastPage() }} · {{ number_format($facilities->total()) }} cơ sở</span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @if ($facilities->onFirstPage())
                            <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-300">Trước</span>
                        @else
                            <a href="{{ $facilities->previousPageUrl() }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50">Trước</a>
                        @endif

                        @php
                            $firstPage = max(1, $facilities->currentPage() - 2);
                            $lastPage = min($facilities->lastPage(), $facilities->currentPage() + 2);
                            if ($lastPage - $firstPage < 4) {
                                $firstPage = max(1, $lastPage - 4);
                                $lastPage = min($facilities->lastPage(), $firstPage + 4);
                            }
                        @endphp
                        @if ($firstPage > 1)
                            <a href="{{ $facilities->url(1) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50">1</a>
                            @if ($firstPage > 2)<span class="px-1 text-slate-400">…</span>@endif
                        @endif
                        @for ($page = $firstPage; $page <= $lastPage; $page++)
                            @if ($page === $facilities->currentPage())
                                <span aria-current="page" class="rounded-lg border border-sky-600 bg-sky-600 px-3 py-2 font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $facilities->url($page) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50">{{ $page }}</a>
                            @endif
                        @endfor
                        @if ($lastPage < $facilities->lastPage())
                            @if ($lastPage < $facilities->lastPage() - 1)<span class="px-1 text-slate-400">…</span>@endif
                            <a href="{{ $facilities->url($facilities->lastPage()) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50">{{ $facilities->lastPage() }}</a>
                        @endif

                        @if ($facilities->hasMorePages())
                            <a href="{{ $facilities->nextPageUrl() }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50">Sau</a>
                        @else
                            <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-300">Sau</span>
                        @endif
                    </div>
                </nav>
            @endif
        </section>

        @can('import_pharma_official_facilities')
            <div data-import-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Import Kho dữ liệu nguồn</h2>
                            <p class="mt-1 text-sm text-slate-500">Chọn file .xlsx/.xls được xuất từ màn hình này. Identity import là (Source, External ID).</p>
                        </div>
                        <button type="button" data-import-close class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100">✕</button>
                    </div>
                    <form data-import-form class="mt-5 space-y-4" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                        <div data-import-message class="hidden rounded-xl border px-4 py-3 text-sm"></div>
                        <div class="flex justify-end gap-2">
                            <button type="button" data-import-close class="min-h-10 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Hủy</button>
                            <button type="submit" data-import-submit class="min-h-10 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Import Excel</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4"><h2 class="text-lg font-semibold text-slate-900">20 batch đồng bộ gần nhất</h2><p class="mt-1 text-sm text-slate-500">Theo dõi queue và kết quả upsert/stale.</p></div>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-3 py-3">Batch</th><th class="px-3 py-3">Tỉnh/Thành</th><th class="px-3 py-3">Vùng nguồn</th><th class="px-3 py-3">Scope</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Fetched</th><th class="px-3 py-3">Created</th><th class="px-3 py-3">Updated</th><th class="px-3 py-3">Unchanged</th><th class="px-3 py-3">Stale</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($batches as $batch)
                            <tr><td class="px-3 py-3 font-semibold">#{{ $batch->id }}</td><td class="px-3 py-3">{{ $batch->province_name }}</td><td class="px-3 py-3">{{ $partitionLabels[$batch->source_province_code] ?? $batch->source_province_code }}</td><td class="px-3 py-3">{{ strtoupper($batch->sync_scope) }}</td><td class="px-3 py-3 font-semibold">{{ $batch->status }}</td><td class="px-3 py-3">{{ $batch->fetched_count }}</td><td class="px-3 py-3">{{ $batch->created_count }}</td><td class="px-3 py-3">{{ $batch->updated_count }}</td><td class="px-3 py-3">{{ $batch->unchanged_count }}</td><td class="px-3 py-3">{{ $batch->stale_count }}</td></tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-8 text-center text-slate-500">Chưa có batch đồng bộ.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-live-filter-form]');
            if (!form) return;

            const submit = () => form.requestSubmit();
            form.querySelectorAll('[data-live-filter]').forEach((element) => element.addEventListener('change', submit));

            const search = form.querySelector('[data-live-search]');
            let timer = null;
            search?.addEventListener('input', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(submit, 450);
            });
            search?.addEventListener('search', submit);

            const selectPage = document.querySelector('[data-select-page]');
            const rowSelections = Array.from(document.querySelectorAll('[data-row-select]'));
            const exportButton = document.querySelector('[data-export-selected]');
            const refreshSelection = () => {
                const selected = rowSelections.filter((checkbox) => checkbox.checked).length;
                if (exportButton) exportButton.textContent = selected > 0 ? `Xuất Excel (${selected} đã chọn)` : 'Xuất Excel';
                if (selectPage) {
                    selectPage.checked = rowSelections.length > 0 && selected === rowSelections.length;
                    selectPage.indeterminate = selected > 0 && selected < rowSelections.length;
                }
            };
            selectPage?.addEventListener('change', () => {
                rowSelections.forEach((checkbox) => checkbox.checked = selectPage.checked);
                refreshSelection();
            });
            rowSelections.forEach((checkbox) => checkbox.addEventListener('change', refreshSelection));
            refreshSelection();

            const importModal = document.querySelector('[data-import-modal]');
            const importForm = document.querySelector('[data-import-form]');
            const importMessage = document.querySelector('[data-import-message]');
            const importSubmit = document.querySelector('[data-import-submit]');
            document.querySelector('[data-import-open]')?.addEventListener('click', () => {
                importModal?.classList.remove('hidden');
                importModal?.classList.add('flex');
            });
            document.querySelectorAll('[data-import-close]').forEach((button) => button.addEventListener('click', () => {
                importModal?.classList.add('hidden');
                importModal?.classList.remove('flex');
            }));
            importForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                importSubmit.disabled = true;
                importSubmit.textContent = 'Đang import...';
                try {
                    const response = await fetch(`{{ route('admin.pharma.official-facilities.source.import') }}`, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': importForm.querySelector('input[name="_token"]').value },
                        body: new FormData(importForm),
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message ?? 'Import Excel thất bại.');
                    importMessage.textContent = `${payload.message} Tạo mới ${payload.created} · cập nhật ${payload.updated} · không đổi ${payload.unchanged} · lỗi ${payload.errors}.`;
                    importMessage.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800';
                    window.setTimeout(() => window.location.reload(), 1200);
                } catch (error) {
                    importMessage.textContent = error.message || 'Import Excel thất bại.';
                    importMessage.className = 'rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800';
                } finally {
                    importSubmit.disabled = false;
                    importSubmit.textContent = 'Import Excel';
                }
            });
        });
    </script>
@endsection
