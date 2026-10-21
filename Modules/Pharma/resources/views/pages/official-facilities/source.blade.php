@extends('Admin::layouts.master')

@section('title', 'Kho dữ liệu cơ sở KCB nguồn')
@section('admin_container', 'full')

@section('content')
    <div class="w-full min-w-0 space-y-6">
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
            <form method="GET" action="{{ route('admin.pharma.official-facilities.source.index') }}" autocomplete="off" data-live-filter-form class="grid gap-2 md:grid-cols-2 xl:grid-cols-[minmax(160px,2fr)_minmax(145px,1.5fr)_minmax(125px,1.2fr)_minmax(105px,.85fr)_minmax(155px,1.6fr)_minmax(165px,1.7fr)_minmax(120px,1fr)_minmax(95px,.75fr)_auto] xl:items-center">
                <x-search
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Tìm mã, tên cơ sở..."
                    class="min-w-0 w-full"
                    data-live-search
                />

                <select name="facility_type" autocomplete="off" data-live-filter aria-label="Loại cơ sở theo tên" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm min-w-0 w-full">
                    <option value="">Tất cả loại cơ sở</option>
                    @foreach ($facilityNameTypes as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}" @selected(request('facility_type') === $typeKey)>{{ $typeLabel }}</option>
                    @endforeach
                </select>

                <select name="business_region" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm min-w-0 w-full">
                    <option value="">Tất cả vùng miền</option>
                    @foreach ($businessRegions as $regionKey => $regionName)
                        <option value="{{ $regionKey }}" @selected(request('business_region') === $regionKey)>{{ $regionName }}</option>
                    @endforeach
                </select>

                <select name="source" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm min-w-0 w-full">
                    <option value="">Tất cả nguồn</option>
                    <option value="bhxh" @selected(request('source') === 'bhxh')>BHXH</option>
                </select>

                <select name="province" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm min-w-0 w-full">
                    <option value="">Tất cả tỉnh/thành</option>
                    @foreach ($provinceOptions as $provinceName)
                        <option value="{{ $provinceName }}" @selected(request('province') === $provinceName)>{{ $provinceName }}</option>
                    @endforeach
                </select>

                <select name="partition" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm min-w-0 w-full">
                    <option value="">Tất cả vùng nguồn BHXH</option>
                    @foreach ($partitionOptions as $partition)
                        <option value="{{ $partition->source_province_code }}" @selected(request('partition') === $partition->source_province_code)>
                            {{ $partitionLabels[$partition->source_province_code] ?? ($partition->province_name.' · '.$partition->source_province_code) }}
                        </option>
                    @endforeach
                </select>

                <select name="status" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm min-w-0 w-full">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="stale" @selected(request('status') === 'stale')>Stale</option>
                </select>

                <select name="per_page" autocomplete="off" data-live-filter class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm min-w-0 w-full">
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / trang</option>
                    @endforeach
                </select>

                @if (request()->hasAny(['search', 'facility_type', 'business_region', 'source', 'province', 'partition', 'status', 'per_page']))
                    <div class="flex min-h-11 items-center justify-end whitespace-nowrap">
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
                        <button type="button" data-legal-import-open class="rounded-xl border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700">Import thông tin pháp lý</button>
                        <button type="button" data-import-open class="inline-flex min-h-10 items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Import Excel</button>
                    @endcan
                </div>
            </div>

            <form id="official-source-export-form" method="POST" action="{{ route('admin.pharma.official-facilities.source.export') }}">
                @csrf
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="facility_type" value="{{ request('facility_type') }}">
                <input type="hidden" name="business_region" value="{{ request('business_region') }}">
                <input type="hidden" name="source" value="{{ request('source') }}">
                <input type="hidden" name="province" value="{{ request('province') }}">
                <input type="hidden" name="partition" value="{{ request('partition') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="w-full overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-[1450px] w-full divide-y divide-slate-200 text-sm">
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
                            <th class="px-4 py-3">Mã số thuế đã xác nhận</th>
                            <th class="px-4 py-3">Địa chỉ pháp lý đã xác nhận</th>
                            <th class="px-4 py-3">Tra cứu doanh nghiệp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($facilities as $facility)
                            <tr>
                                @php
                                    $confirmedLegal = $facility->legalLookup?->confirmedCandidate();
                                @endphp
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
                                <td class="px-4 py-3 font-mono text-slate-700" data-legal-tax="{{ $facility->id }}">{{ $confirmedLegal['tax_code'] ?? '—' }}</td>
                                <td class="max-w-xs px-4 py-3 text-slate-600" data-legal-address="{{ $facility->id }}">{{ ($confirmedLegal['address'] ?? null) ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex min-w-36 flex-col items-start gap-2" data-legal-row="{{ $facility->id }}">
                                        <span data-legal-status class="text-xs text-slate-500">{{ ['queued' => 'Chờ queue', 'running' => 'Đang tra cứu', 'ready' => 'Có ứng viên', 'empty' => 'Không tìm thấy', 'failed' => 'Lỗi tra cứu', 'reviewed' => 'Đã xác nhận', 'imported' => 'Đã nhập Excel (chưa đối chiếu)'][$facility->legalLookup?->status ?? ''] ?? 'Chưa tra cứu' }}</span>
                                        <button type="button" data-legal-lookup data-url="{{ route('admin.pharma.official-facilities.source.legal-lookup.store', $facility) }}" data-show-url="{{ route('admin.pharma.official-facilities.source.legal-lookup.show', $facility) }}" data-review-url="{{ route('admin.pharma.official-facilities.source.legal-lookup.review', $facility) }}" class="inline-flex min-h-9 items-center rounded-lg border border-indigo-200 bg-white px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50" @disabled(in_array($facility->legalLookup?->status, ['queued', 'running']))>Tra cứu & đối chiếu</button>
                                        <button type="button" data-legal-view class="text-xs font-semibold text-sky-700 hover:underline" @if (!$facility->legalLookup) hidden @endif>Xem kết quả</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="px-4 py-10 text-center text-slate-500">Không có dữ liệu phù hợp bộ lọc hiện tại.</td></tr>
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
            <div data-legal-import-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-label="Import thông tin pháp lý">
                <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-semibold">Import MST và địa chỉ pháp lý</h2><button type="button" data-legal-import-close class="rounded-lg border px-3 py-2">Đóng</button></div>
                    <p class="mt-2 text-sm text-slate-600">File cần các cột: Mã CSKCB, Tên cơ sở khám chữa bệnh, Mã số thuế đã xác nhận, Địa chỉ pháp lý đã xác nhận. Chỉ nhập dữ liệu Excel chưa đối chiếu tại Pharma, không sửa BHXH/Partner.</p>
                    <a href="{{ route('admin.pharma.official-facilities.source.legal-import.template') }}" class="mt-3 inline-flex items-center rounded-lg border border-sky-200 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50">Tải file Excel mẫu (.xlsx)</a>
                    <form data-legal-import-form class="mt-4 flex flex-wrap gap-3" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" required accept=".xlsx,.xls" class="min-w-0 flex-1 rounded-lg border p-2 text-sm">
                        <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white">Xem trước</button>
                    </form>
                    <p data-legal-import-message class="mt-3 text-sm text-slate-700" role="status"></p>
                    <div class="mt-3 max-h-72 overflow-auto"><table class="w-full text-left text-xs"><thead><tr class="border-b"><th class="p-2">Dòng</th><th class="p-2">Mã CSKCB</th><th class="p-2">Tên cơ sở</th><th class="p-2">MST</th><th class="p-2">Kết quả</th></tr></thead><tbody data-legal-import-rows></tbody></table></div>
                    <button type="button" data-legal-import-confirm disabled class="mt-4 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Nhập các dòng hợp lệ (chưa đối chiếu)</button>
                </div>
            </div>
        @endcan

        <div data-legal-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-label="Kết quả tra cứu pháp lý">
            <div class="max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">Kết quả tra cứu pháp lý</h2>
                    <button type="button" data-legal-close class="rounded-lg border px-3 py-2">Đóng</button>
                </div>
                <p class="mt-2 text-sm text-slate-600" data-legal-message>Đang tải...</p>
                <div class="mt-4 space-y-3" data-legal-results></div>
                <p class="mt-3 text-xs text-amber-700">Xác nhận chỉ lưu ứng viên đã chọn tại Pharma, chưa tạo hoặc cập nhật hồ sơ Partner.</p>
            </div>
        </div>

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

            const legalModal = document.querySelector('[data-legal-modal]');
            const legalMessage = document.querySelector('[data-legal-message]');
            const legalResults = document.querySelector('[data-legal-results]');
            const legalLabels = { queued: 'Chờ queue', running: 'Đang tra cứu', ready: 'Có ứng viên', empty: 'Không tìm thấy', failed: 'Lỗi tra cứu', reviewed: 'Đã xác nhận' };
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('#official-source-export-form input[name="_token"]')?.value;
            let activeLegalButton = null;
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
            const updateLegalRow = (button, lookup) => {
                const row = button.closest('[data-legal-row]');
                row.querySelector('[data-legal-status]').textContent = lookup ? (legalLabels[lookup.status] || lookup.status) : 'Chưa tra cứu';
                row.querySelector('[data-legal-view]').hidden = !lookup;
                button.disabled = ['queued', 'running'].includes(lookup?.status);
                const confirmed = lookup?.status === 'reviewed'
                    ? (lookup.candidates || []).find((candidate) => candidate.tax_code === lookup.selected_tax_code)
                    : null;
                const facilityId = row.dataset.legalRow;
                const taxCell = document.querySelector('[data-legal-tax="' + facilityId + '"]');
                const addressCell = document.querySelector('[data-legal-address="' + facilityId + '"]');
                if (taxCell) taxCell.textContent = confirmed?.tax_code || '—';
                if (addressCell) addressCell.textContent = confirmed?.address || '—';
            };
            const renderLegal = (lookup) => {
                legalMessage.textContent = lookup ? (legalLabels[lookup.status] || lookup.status) + (lookup.error_message ? ': ' + lookup.error_message : '') : 'Chưa có kết quả.';
                legalResults.replaceChildren();
                if (!lookup || !['ready', 'reviewed', 'imported'].includes(lookup.status)) return;
                (lookup.candidates || []).forEach((candidate) => {
                    const item = document.createElement('div');
                    item.className = 'rounded-xl border border-slate-200 p-3 text-sm';
                    item.innerHTML = '<div class="font-semibold text-slate-900">' + escapeHtml(candidate.name) + '</div>' +
                        '<div class="mt-1 text-slate-600">MST: ' + escapeHtml(candidate.tax_code) + ' · ' + escapeHtml(candidate.address || 'Chưa có địa chỉ') + '</div>' +
                        '<div class="mt-1 text-xs text-slate-500">Nguồn: ' + escapeHtml(candidate.source_label || candidate.source) + '</div>';
                    if (lookup.status === 'ready') {
                        const confirm = document.createElement('button');
                        confirm.type = 'button';
                        confirm.className = 'mt-3 rounded-lg border border-sky-300 px-3 py-2 font-semibold text-sky-700 disabled:opacity-50';
                        confirm.textContent = 'Xác nhận ứng viên này';
                        confirm.addEventListener('click', async () => {
                            if (!window.confirm('Xác nhận ứng viên MST ' + candidate.tax_code + '? Chưa ghi vào Partner.')) return;
                            confirm.disabled = true;
                            try {
                                const response = await fetch(activeLegalButton.dataset.reviewUrl, {
                                    method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                                    body: JSON.stringify({ tax_code: candidate.tax_code })
                                });
                                const data = await response.json();
                                if (!response.ok) throw new Error(data.message || 'Không thể xác nhận.');
                                renderLegal(data.lookup);
                                updateLegalRow(activeLegalButton, data.lookup);
                            } catch (error) { legalMessage.textContent = error.message; confirm.disabled = false; }
                        });
                        item.appendChild(confirm);
                    } else if (lookup.selected_tax_code === candidate.tax_code) {
                        const selected = document.createElement('div');
                        selected.className = 'mt-2 font-semibold text-emerald-700';
                        selected.textContent = 'Đã xác nhận';
                        item.appendChild(selected);
                    }
                    legalResults.appendChild(item);
                });
            };
            const pollLegal = async (button, showModal = false) => {
                try {
                    const response = await fetch(button.dataset.showUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) throw new Error('Không thể tải trạng thái tra cứu.');
                    const data = await response.json();
                    updateLegalRow(button, data.lookup);
                    if (showModal && activeLegalButton === button) renderLegal(data.lookup);
                    if (['queued', 'running'].includes(data.lookup?.status)) window.setTimeout(() => pollLegal(button, legalModal.classList.contains('flex') && activeLegalButton === button), 3000);
                } catch (error) { if (showModal) legalMessage.textContent = error.message; button.disabled = false; }
            };
            document.querySelectorAll('[data-legal-lookup]').forEach((button) => {
                button.addEventListener('click', async () => {
                    button.disabled = true;
                    try {
                        const response = await fetch(button.dataset.url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Không thể đưa vào queue.');
                        updateLegalRow(button, data.lookup);
                        activeLegalButton = button;
                        legalModal.classList.remove('hidden');
                        legalModal.classList.add('flex');
                        renderLegal(data.lookup);
                        pollLegal(button, true);
                    } catch (error) { button.disabled = false; window.alert(error.message); }
                });
            });
            document.querySelectorAll('[data-legal-view]').forEach((button) => button.addEventListener('click', () => {
                activeLegalButton = button.closest('[data-legal-row]').querySelector('[data-legal-lookup]');
                legalModal.classList.remove('hidden');
                legalModal.classList.add('flex');
                pollLegal(activeLegalButton, true);
            }));
            document.querySelector('[data-legal-close]')?.addEventListener('click', () => {
                legalModal.classList.add('hidden');
                legalModal.classList.remove('flex');
            });
            document.querySelectorAll('[data-legal-lookup]:disabled').forEach((button) => pollLegal(button));

            const legalImportModal = document.querySelector('[data-legal-import-modal]');
            const legalImportForm = document.querySelector('[data-legal-import-form]');
            const legalImportMessage = document.querySelector('[data-legal-import-message]');
            const legalImportRows = document.querySelector('[data-legal-import-rows]');
            const legalImportConfirm = document.querySelector('[data-legal-import-confirm]');
            let legalImportToken = null;
            document.querySelector('[data-legal-import-open]')?.addEventListener('click', () => {
                legalImportModal.classList.remove('hidden');
                legalImportModal.classList.add('flex');
            });
            document.querySelector('[data-legal-import-close]')?.addEventListener('click', () => {
                legalImportModal.classList.add('hidden');
                legalImportModal.classList.remove('flex');
            });
            legalImportForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                legalImportToken = null;
                legalImportConfirm.disabled = true;
                legalImportRows.replaceChildren();
                legalImportMessage.textContent = 'Đang đối chiếu dữ liệu...';
                try {
                    const response = await fetch("{{ route('admin.pharma.official-facilities.source.legal-import.preview') }}", {
                        method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: new FormData(legalImportForm),
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Không thể xem trước file.');
                    legalImportToken = data.token;
                    legalImportMessage.textContent = 'Tổng ' + data.total + ' · Hợp lệ ' + data.ready + ' · Bỏ qua ' + data.skipped + '.';
                    (data.rows || []).forEach((row) => {
                        const tr = document.createElement('tr');
                        [row.row, row.code, row.name, row.tax_code, row.reason || 'Sẵn sàng'].forEach((value) => {
                            const td = document.createElement('td');
                            td.className = 'border-b p-2';
                            td.textContent = value ?? '';
                            tr.appendChild(td);
                        });
                        legalImportRows.appendChild(tr);
                    });
                    legalImportConfirm.disabled = data.ready === 0;
                } catch (error) { legalImportMessage.textContent = error.message; }
            });
            legalImportConfirm?.addEventListener('click', async () => {
                if (!legalImportToken || !window.confirm('Lưu các dòng hợp lệ tại Pharma? Không ghi vào Partner.')) return;
                legalImportConfirm.disabled = true;
                try {
                    const response = await fetch("{{ route('admin.pharma.official-facilities.source.legal-import.confirm') }}", {
                        method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ token: legalImportToken }),
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Không thể xác nhận import.');
                    legalImportMessage.textContent = data.message + ' Đã lưu ' + data.updated + ' · Bỏ qua ' + data.skipped + '.';
                    legalImportToken = null;
                    window.setTimeout(() => window.location.reload(), 1600);
                } catch (error) { legalImportMessage.textContent = error.message; legalImportConfirm.disabled = false; }
            });

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
