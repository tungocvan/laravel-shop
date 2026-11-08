@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="min-w-0 space-y-4 pb-6">
    <header class="flex flex-wrap items-start justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <div class="min-w-0">
            <a href="{{ route('client.pharma.dashboard') }}" class="text-xs font-medium text-indigo-700 hover:underline">← Pharma</a>
            <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $presentation['eyebrow'] }}</p>
            <h1 class="mt-1 text-xl font-extrabold text-slate-950 sm:text-2xl">{{ $presentation['page_title'] }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $presentation['page_description'] }}</p>
        </div>
        <div class="rounded-xl bg-indigo-50 px-4 py-3 text-right">
            <p class="text-xs font-medium text-indigo-700">Hồ sơ phù hợp</p>
            <p class="text-2xl font-extrabold tabular-nums text-indigo-950">{{ number_format($items->total(), 0, ',', '.') }}</p>
        </div>
    </header>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Tổng quan theo dõi thầu">
        @foreach([
            ['label' => 'Tổng hồ sơ trúng thầu', 'value' => number_format($dashboard['total'], 0, ',', '.'), 'unit' => 'hồ sơ'],
            ['label' => 'Hồ sơ đang hoạt động', 'value' => number_format($dashboard['active'], 0, ',', '.'), 'unit' => 'hồ sơ'],
            ['label' => 'Tổng giá trị trúng thầu', 'value' => number_format($dashboard['amount'], 0, ',', '.'), 'unit' => 'đ'],
            ['label' => 'Giá trị đang hoạt động', 'value' => number_format($dashboard['active_amount'], 0, ',', '.'), 'unit' => 'đ'],
        ] as $metric)
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-3 sm:p-4">
                <p class="text-xs font-medium text-slate-600">{{ $metric['label'] }}</p>
                <p class="mt-2 break-all text-lg font-extrabold tabular-nums text-slate-950 sm:text-xl">{{ $metric['value'] }} <span class="text-xs font-medium text-slate-500">{{ $metric['unit'] }}</span></p>
            </div>
        @endforeach
    </section>
    <p class="text-xs text-slate-500">Chỉ số “đang hoạt động” dựa trên trạng thái dữ liệu KQLCNT, chưa xác nhận thời hạn hiệu lực hợp đồng.</p>

    @if(session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
    @if($canImportAwards || $canExportAwards)
    <details class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <summary class="flex min-h-12 cursor-pointer items-center justify-between gap-2 px-4 py-3 text-sm font-bold text-slate-900 sm:px-5">Công cụ Import / Export Excel <span class="text-xs font-medium text-indigo-700">Mở / thu gọn ▾</span></summary>
        <div class="grid gap-4 border-t border-slate-100 p-4 sm:p-5 lg:grid-cols-2">
            @if($canImportAwards)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-sm font-bold text-slate-950">Import danh mục trúng thầu</h2>
                <p class="mt-1 text-xs leading-5 text-slate-600">Mỗi file gồm một TBMT, một nhà thầu. Xem trước và đối chiếu trước khi ghi; không tự thêm thuốc mới.</p>
                <a data-pwa-file-handoff href="{{ route('client.pharma.kqlcnt-awards.import.template') }}" class="mt-3 inline-flex min-h-11 items-center rounded-xl border border-emerald-300 bg-emerald-50 px-4 text-sm font-semibold text-emerald-800">↓ Tải Excel mẫu kèm hướng dẫn</a>
                <form method="POST" action="{{ route('client.pharma.kqlcnt-awards.import.preview') }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                    @csrf
                    <label class="block text-xs font-semibold text-slate-700" for="kqlcnt-import-file">Chọn file Excel (.xlsx, .xls; tối đa 5 MB)</label>
                    <input id="kqlcnt-import-file" type="file" name="file" accept=".xlsx,.xls" required class="block w-full rounded-xl border border-slate-300 bg-white p-2 text-sm">
                    <button type="submit" class="min-h-11 rounded-xl bg-indigo-700 px-4 text-sm font-bold text-white hover:bg-indigo-800">Xem trước Import</button>
                </form>
            </div>
            @endif
            @if($canExportAwards)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-sm font-bold text-slate-950">Export danh mục trúng thầu</h2>
                <p class="mt-1 text-xs leading-5 text-slate-600">Xuất đúng một TBMT và một nhà thầu. Chọn “Xuất Excel” trên hồ sơ tương ứng ở danh sách bên dưới.</p>
                <p class="mt-3 text-xs font-medium text-slate-500">File có hai sheet: thông tin trúng thầu và danh mục thuốc, tương thích với mẫu Admin.</p>
            </div>
            @endif
        </div>
    </details>
    @endif

    <form id="kqlcnt-live-filters" method="GET" action="{{ route('client.pharma.kqlcnt-awards.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <h2 class="text-base font-bold text-slate-950">Bộ lọc hồ sơ</h2>
        <p class="mt-1 text-xs text-slate-500">Lọc theo mã TBMT, chủ đầu tư, thuốc hoặc trạng thái.</p>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:flex xl:items-end">
            <div class="min-w-0 xl:w-44 xl:shrink-0">
                <label for="kqlcnt-notify" class="mb-1 block text-xs font-semibold text-slate-700">Mã TBMT</label>
                <x-pwa-select-search id="kqlcnt-notify" name="notify_no" :selected="$filters['notify_no'] ?? ''" placeholder="Tất cả mã TBMT" search-placeholder="Tìm mã TBMT...">
                    <button type="button" data-pwa-select-search-option data-value="" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-indigo-50">Tất cả mã TBMT</button>
                    @foreach($options['notices'] as $notice)
                        <button type="button" data-pwa-select-search-option data-value="{{ $notice }}" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-indigo-50">{{ $notice }}</button>
                    @endforeach
                </x-pwa-select-search>
            </div>
            <div class="min-w-0 xl:min-w-0 xl:flex-[1.5]">
                <label for="kqlcnt-investor" class="mb-1 block text-xs font-semibold text-slate-700">Chủ đầu tư</label>
                <x-pwa-select-search id="kqlcnt-investor" name="investor_code" :selected="$filters['investor_code'] ?? ''" placeholder="Tất cả chủ đầu tư" search-placeholder="Tìm chủ đầu tư...">
                    <button type="button" data-pwa-select-search-option data-value="" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-indigo-50">Tất cả chủ đầu tư</button>
                    @foreach($options['investors'] as $investor)
                        <button type="button" data-pwa-select-search-option data-value="{{ $investor->investor_code }}" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-indigo-50">{{ $investor->investor_name }} — {{ $investor->investor_code }}</button>
                    @endforeach
                </x-pwa-select-search>
            </div>
            <div class="min-w-0 xl:min-w-0 xl:flex-1">
                <label for="kqlcnt-medicine" class="mb-1 block text-xs font-semibold text-slate-700">Tên thuốc / Hoạt chất</label>
                <x-pwa-select-search id="kqlcnt-medicine" name="medicine" :selected="$filters['medicine'] ?? ''" placeholder="Tất cả thuốc" search-placeholder="Tìm tên thuốc...">
                    <button type="button" data-pwa-select-search-option data-value="" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-indigo-50">Tất cả thuốc</button>
                    @foreach($options['medicines'] as $medicine)
                        <button type="button" data-pwa-select-search-option data-value="{{ $medicine }}" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-indigo-50">{{ $medicine }}</button>
                    @endforeach
                </x-pwa-select-search>
            </div>
            <label class="block min-w-0 text-xs font-semibold text-slate-700 xl:w-44 xl:shrink-0">Trạng thái
                <select name="active" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1" @selected(($filters['active'] ?? '') === '1')>Đang hoạt động</option>
                    <option value="0" @selected(($filters['active'] ?? '') === '0')>Không hoạt động</option>
                </select>
            </label>
        @if(collect($filters)->only(['notify_no', 'investor_code', 'medicine', 'active'])->contains(fn ($value) => filled($value)))
            <div class="flex items-end sm:col-span-2 xl:col-span-1 xl:shrink-0">
                <a href="{{ route('client.pharma.kqlcnt-awards.index') }}" class="inline-flex min-h-11 items-center whitespace-nowrap rounded-xl border border-red-200 bg-red-50 px-4 text-sm font-semibold text-red-700 hover:bg-red-100">Xóa lọc</a>
            </div>
        @endif
        </div>
    </form>

    <section class="space-y-3" aria-label="Danh sách hồ sơ trúng thầu">
        <div class="flex items-center justify-between gap-2 px-1">
            <h2 class="text-base font-bold text-slate-900">Hồ sơ kết quả trúng thầu</h2>
            <span class="text-xs text-slate-500">{{ $items->total() }} hồ sơ</span>
        </div>
        @forelse($items as $item)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Mã TBMT</p>
                        <h3 class="mt-0.5 break-all text-base font-extrabold text-slate-950">{{ $item->notify_no ?: 'Chưa có mã TBMT' }}</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $item->all_active ? 'bg-emerald-50 text-emerald-800' : ($item->any_active ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                        {{ $item->all_active ? 'Đang hoạt động' : ($item->any_active ? 'Trạng thái hỗn hợp' : 'Không hoạt động') }}
                    </span>
                </div>
                <div class="grid gap-3 px-4 py-4 sm:grid-cols-2 sm:px-5 xl:grid-cols-3">
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500">Nhà thầu</p>
                        <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $item->contractor_name ?: 'Chưa cập nhật' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500">Chủ đầu tư</p>
                        <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $item->investor_name ?: 'Chưa cập nhật' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500">Thời gian thực hiện hợp đồng</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item->contract_period !== null ? $item->contract_period.' '.($item->contract_period_unit ?: 'tháng') : ($item->contract_period_text ?: 'Chưa cập nhật') }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Số tiền trúng thầu</p>
                        <p class="mt-1 text-lg font-extrabold tabular-nums text-slate-950">{{ number_format((float) $item->winning_amount, 0, ',', '.') }} <span class="text-xs font-semibold text-slate-500">đ</span></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Số lượng sản phẩm</p>
                        <p class="mt-1 text-lg font-extrabold tabular-nums text-slate-950">{{ number_format((int) $item->products_count, 0, ',', '.') }} <span class="text-xs font-semibold text-slate-500">sản phẩm</span></p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-5">
                    <span class="text-sm font-semibold text-slate-700">Danh mục thuốc trúng thầu</span>
                    @if($canExportAwards)
                        <form method="POST" action="{{ route('client.pharma.kqlcnt-awards.export') }}" data-pwa-file-handoff-form class="ml-auto">
                            @csrf
                            <input type="hidden" name="notify_no" value="{{ $item->notify_no }}">
                            <input type="hidden" name="contractor_code" value="{{ $item->contractor_code }}">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-bold text-emerald-800">↓ Xuất Excel</button>
                        </form>
                    @endif
                    @if($canAllocateAwards)
                        <a href="{{ route('client.pharma.kqlcnt-awards.allocation', $item->id) }}" class="inline-flex min-h-11 items-center rounded-xl border border-indigo-200 bg-white px-4 text-sm font-bold text-indigo-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600">Phân bổ số lượng <span class="ml-1 text-[10px] font-normal">(xem trước)</span></a>
                    @endif
                    @if($canPolicyAwards)
                        <a href="{{ route('client.pharma.kqlcnt-awards.commercial-policy', $item->id) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600">Chính sách kinh doanh <span class="ml-1 text-[10px] font-normal">(xem trước)</span></a>
                    @endif
                    <a href="{{ route('client.pharma.kqlcnt-awards.show', $item->id) }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-4 text-sm font-bold text-white hover:bg-indigo-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Xem chi tiết <span aria-hidden="true" class="ml-2">→</span></a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">Không tìm thấy hồ sơ TBMT phù hợp. Hãy thử điều chỉnh từ khóa hoặc trạng thái.</div>
        @endforelse
    </section>
    {{ $items->links() }}
</div>
@endsection

@push('application-scripts')
<script>
(() => {
    // Installed PWA: fetch authenticated binary without navigating away; share on a second tap.
    const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (standalone) {
        const prepare = async (url, body, control) => {
            const oldLabel = control.textContent;
            control.disabled = true;
            control.textContent = 'Đang chuẩn bị Excel...';
            try {
                const response = await fetch(url, { method: body ? 'POST' : 'GET', body, credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' } });
                if (!response.ok) throw new Error('Không thể tải Excel (' + response.status + '). Kiểm tra quyền hoặc dữ liệu hồ sơ.');
                const blob = await response.blob();
                const filename = (response.headers.get('Content-Disposition') || '').match(/filename="?([^";]+)"?/i)?.[1] || 'KQLCNT.xlsx';
                const file = new File([blob], filename, { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                if (!navigator.canShare?.({ files: [file] })) throw new Error('Thiết bị không hỗ trợ chia sẻ file trong PWA. Hãy mở bằng trình duyệt để tải Excel.');
                control.textContent = 'Chia sẻ / lưu Excel';
                control.disabled = false;
                control.addEventListener('click', async (event) => {
                    event.preventDefault();
                    if (control.tagName === 'BUTTON') event.stopPropagation();
                    try { await navigator.share({ files: [file], title: filename }); }
                    catch (error) { if (error.name !== 'AbortError') alert(error.message); }
                }, { capture: true });
            } catch (error) {
                control.textContent = oldLabel;
                control.disabled = false;
                alert(error.message);
            }
        };
        document.querySelectorAll('a[data-pwa-file-handoff]').forEach(link => link.addEventListener('click', event => {
            event.preventDefault();
            if (link.dataset.prepared) return;
            link.dataset.prepared = '1';
            prepare(link.href, null, link);
        }, { once: true }));
        document.querySelectorAll('form[data-pwa-file-handoff-form]').forEach(exportForm => exportForm.addEventListener('submit', event => {
            event.preventDefault();
            if (exportForm.dataset.prepared) return;
            exportForm.dataset.prepared = '1';
            prepare(exportForm.action, new FormData(exportForm), exportForm.querySelector('button'));
        }, { once: true }));
    }
    const form = document.getElementById('kqlcnt-live-filters');
    if (!form || form.dataset.liveBound === '1') return;
    form.dataset.liveBound = '1';
    let pending = false;
    const submit = () => {
        if (pending) return;
        pending = true;
        // The shared PWA selector updates its hidden input during option click.
        // Wait until its own event handler finishes before reading the form.
        window.setTimeout(() => {
            const params = new URLSearchParams(new FormData(form));
            for (const [key, value] of [...params]) {
                if (!value) params.delete(key);
            }
            const url = new URL(form.action, window.location.href);
            url.search = params.toString();
            window.location.assign(url.toString());
        }, 0);
    };
    form.addEventListener('click', (event) => {
        if (event.target.closest('[data-pwa-select-search-option]')) submit();
    }, true);
    form.querySelector('select[name="active"]')?.addEventListener('change', submit);
})();
</script>
@endpush
