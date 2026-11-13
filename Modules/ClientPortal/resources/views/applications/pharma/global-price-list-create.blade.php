@extends('ClientPortal::layouts.application')

@section('title', 'Tạo bảng giá chung')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mx-auto max-w-5xl space-y-5 pb-24">
    <header class="flex items-center justify-between gap-3">
        <div><a class="text-sm text-indigo-600" href="{{ route('client.pharma.price-lists') }}">← Bảng giá của tôi</a><h1 class="mt-1 text-xl font-black text-slate-950">Tạo bảng giá chung</h1><p class="text-sm text-slate-500">Chọn thuốc trực tiếp từ Medicine Master, thiết lập giá và phạm vi User áp dụng.</p></div>
        <span class="rounded-xl bg-amber-100 px-3 py-2 text-xs font-bold text-amber-800">Kích hoạt trực tiếp</span>
    </header>
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><strong>Không thể lưu bảng giá:</strong><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form id="global-price-list-form" method="POST" action="{{ route('client.pharma.price-lists.global.store') }}" class="space-y-5">
        @csrf
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="mb-4 text-base font-black">01 · Thông tin bảng giá</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-semibold">Tên bảng giá *<input name="name" maxlength="255" required value="{{ old('name') }}" class="mt-1 h-12 w-full rounded-xl border border-slate-300 px-3" placeholder="Tên bảng giá chung"></label>
                <label class="block text-sm font-semibold">Mã bảng giá <input disabled value="Tự động tạo khi lưu" class="mt-1 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3"></label>
                <x-pwa-date name="effective_from" label="Ngày hiệu lực *" :value="old('effective_from', now()->toDateString())" required />
                <x-pwa-date name="effective_to" label="Ngày hết hiệu lực *" :value="old('effective_to', now()->addMonth()->toDateString())" required />
                <label class="block text-sm font-semibold sm:col-span-2">Ghi chú<textarea name="notes" maxlength="1000" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 p-3">{{ old('notes') }}</textarea></label>
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="mb-3 text-base font-black">02 · Phạm vi User áp dụng</h2>
            <label class="flex min-h-12 items-center gap-3 rounded-xl border border-slate-200 p-3"><input id="global-apply-all" type="checkbox" name="apply_all_users" value="1" @checked(old('apply_all_users', '1')) class="h-5 w-5"><span class="text-sm font-bold">Áp dụng cho tất cả User</span></label>
            <div id="global-user-list" class="mt-3 max-h-56 space-y-1 overflow-auto rounded-xl border border-slate-200 p-3">
                <p class="mb-2 text-xs text-slate-500">Bỏ chọn “Áp dụng cho tất cả User” để chọn người dùng cụ thể.</p>
                @foreach($activeUsers as $assignedUser)
                <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="global_user_ids[]" value="{{ $assignedUser->id }}" @checked(in_array((string) $assignedUser->id, array_map('strval', old('global_user_ids', [])), true)) class="h-5 w-5"><span>{{ $assignedUser->name }} <small class="text-slate-500">{{ $assignedUser->email }}</small></span></label>
                @endforeach
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-base font-black">03 · Chọn thuốc và thiết lập giá</h2>
            <p class="mt-1 text-sm text-slate-500">Chọn SKU đang hoạt động. Ba mức giá mặc định theo giá kê khai (nếu có) và có thể chỉnh sửa.</p>
    <div id="global-medicine-search" data-action="{{ route('client.pharma.price-lists.create') }}" class="mt-4 flex items-center gap-2">
        <input type="hidden" name="type" value="global">
        <input id="global-search-input" type="search" name="q" value="{{ $search }}" maxlength="120" autocomplete="off" placeholder="Tìm tên thuốc, SĐK hoặc SKU..." aria-label="Tìm thuốc" class="min-h-11 min-w-0 flex-1 rounded-xl border border-slate-300 px-3 text-sm">
        <button id="global-search-clear" type="button" aria-label="Xóa tìm kiếm" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-bold {{ $search === '' ? 'hidden' : '' }}">×</button>
    </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm font-bold"><input id="global-select-page" type="checkbox" class="h-5 w-5"> Chọn tất cả trên trang</label>
                <span id="global-selected-count" class="text-sm text-slate-600">0 SKU được chọn</span>
            </div>
            <div id="global-medicine-results" class="mt-3 space-y-3">
                @forelse($variants as $variant)
                    @php $medicine = $variant->medicine; $id = $variant->id; $declaredPrice = $medicine?->declared_price; @endphp
                    <article class="rounded-xl border border-slate-200 p-3" data-global-product>
                        <label class="flex items-start gap-3"><input type="checkbox" name="selected[{{ $id }}]" value="1" @checked(old('selected.'.$id)) class="mt-1 h-5 w-5" data-global-select><span><strong class="text-sm">{{ $medicine?->name }}</strong><small class="mt-1 block text-xs text-slate-500">{{ $variant->sku }} · {{ $medicine?->registration_number }} · Giá kê khai: {{ $medicine?->declared_price === null ? '—' : number_format((float) $medicine->declared_price, 0, ',', '.') }} ₫</small></span></label>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            @foreach(['company_price' => 'Giá bán (VAT) *', 'actual_receivable_price' => 'Giá thu về', 'invoice_price' => 'Giá Xuất HĐ'] as $key => $label)
                                <label class="text-xs font-bold text-slate-600">{{ $label }}<input type="number" min="0" step="1" name="{{ $key }}[{{ $id }}]" value="{{ old($key.'.'.$id, $declaredPrice === null ? '' : (float) $declaredPrice) }}" inputmode="numeric" class="mt-1 h-11 w-full rounded-xl border border-slate-300 px-3" data-global-price></label>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl bg-slate-50 p-5 text-sm text-slate-500">Không có SKU phù hợp.</p>
                @endforelse
            </div>
            <div id="global-medicine-pagination" class="mt-4">{{ $variants->links() }}</div>
            <p class="mt-2 text-xs text-slate-500">SKU đã chọn và giá nhập được giữ khi tìm kiếm hoặc chuyển trang.</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-base font-black">04 · Kiểm tra và kích hoạt</h2>
            <p class="mt-2 text-sm text-slate-600">Bảng giá sẽ được kiểm tra theo quy tắc Pharma và kích hoạt ngay sau khi lưu thành công.</p>
            <button id="global-submit" type="submit" class="mt-4 min-h-12 w-full rounded-xl bg-indigo-600 px-5 font-bold text-white disabled:opacity-50 sm:w-auto">Kích hoạt bảng giá chung</button>
        </section>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('global-price-list-form');
    const searchForm = document.getElementById('global-medicine-search');
    const searchInput = document.getElementById('global-search-input');
    const clearSearch = document.getElementById('global-search-clear');
    const results = document.getElementById('global-medicine-results');
    const pagination = document.getElementById('global-medicine-pagination');
    const all = document.getElementById('global-apply-all');
    const users = document.getElementById('global-user-list');
    const selectPage = document.getElementById('global-select-page');
    const count = document.getElementById('global-selected-count');
    const submit = document.getElementById('global-submit');
    const selected = new Map();
    let searchTimer;
    let requestController;
    let requestVersion = 0;

    const visibleRows = () => [...results.querySelectorAll('[data-global-product]')];
    const capture = () => {
        visibleRows().forEach(row => {
            const checkbox = row.querySelector('[data-global-select]');
            const id = checkbox.name.match(/\[(\d+)\]/)?.[1];
            if (!id) return;
            if (!checkbox.checked) {
                selected.delete(id);
                return;
            }
            const prices = {};
            row.querySelectorAll('[data-global-price]').forEach(input => {
                prices[input.name.split('[')[0]] = input.value;
            });
            selected.set(id, prices);
        });
    };
    const restore = () => {
        visibleRows().forEach(row => {
            const checkbox = row.querySelector('[data-global-select]');
            const id = checkbox.name.match(/\[(\d+)\]/)?.[1];
            if (!selected.has(id)) return;
            checkbox.checked = true;
            const prices = selected.get(id);
            row.querySelectorAll('[data-global-price]').forEach(input => {
                const key = input.name.split('[')[0];
                if (Object.prototype.hasOwnProperty.call(prices, key)) input.value = prices[key];
            });
        });
        sync();
    };
    const sync = () => {
        users.classList.toggle('hidden', all.checked);
        const rows = visibleRows();
        const checked = rows.filter(row => row.querySelector('[data-global-select]').checked).length;
        count.textContent = selected.size + ' SKU được chọn';
        selectPage.checked = rows.length > 0 && checked === rows.length;
        selectPage.indeterminate = checked > 0 && checked < rows.length;
        submit.disabled = selected.size === 0;
    };
    const load = async (url) => {
        capture();
        requestController?.abort();
        requestController = new AbortController();
        const version = ++requestVersion;
        results.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                signal: requestController.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Không thể tải danh sách thuốc');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextResults = doc.getElementById('global-medicine-results');
            const nextPagination = doc.getElementById('global-medicine-pagination');
            if (!nextResults || !nextPagination) throw new Error('Phản hồi tìm kiếm không hợp lệ');
            if (version !== requestVersion) return;
            results.innerHTML = nextResults.innerHTML;
            pagination.innerHTML = nextPagination.innerHTML;
            restore();
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
                alert('Không tải được danh sách thuốc. Vui lòng thử lại.');
            }
        } finally {
            if (version === requestVersion) results.removeAttribute('aria-busy');
        }
    };
    const search = () => {
        const url = new URL(searchForm.dataset.action, window.location.href);
        url.searchParams.set('type', 'global');
        if (searchInput.value.trim()) url.searchParams.set('q', searchInput.value.trim());
        load(url.toString());
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        clearSearch.classList.toggle('hidden', searchInput.value.length === 0);
        searchTimer = setTimeout(search, 400);
    });
    clearSearch.addEventListener('click', () => {
        clearTimeout(searchTimer);
        searchInput.value = '';
        clearSearch.classList.add('hidden');
        searchInput.focus();
        search();
    });
    pagination.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        event.preventDefault();
        load(link.href);
    });
    results.addEventListener('change', event => {
        if (event.target.matches('[data-global-select], [data-global-price]')) { capture(); sync(); }
    });
    results.addEventListener('input', event => {
        if (event.target.matches('[data-global-price]')) { capture(); sync(); }
    });
    all.addEventListener('change', sync);
    selectPage.addEventListener('change', () => {
        visibleRows().forEach(row => row.querySelector('[data-global-select]').checked = selectPage.checked);
        capture();
        sync();
    });
    form.addEventListener('submit', event => {
        capture();
        if (!all.checked && !users.querySelector('input:checked')) {
            event.preventDefault(); alert('Vui lòng chọn ít nhất một User.'); return;
        }
        if (selected.size === 0) { event.preventDefault(); return; }
        form.querySelectorAll('[data-global-persisted]').forEach(el => el.remove());
        // Only persisted values are submitted; visible page inputs must not overwrite them.
        visibleRows().forEach(row => {
            row.querySelectorAll('[data-global-select], [data-global-price]').forEach(input => input.disabled = true);
        });
        const fragment = document.createDocumentFragment();
        selected.forEach((prices, id) => {
            const values = { ['selected[' + id + ']']: '1' };
            Object.entries(prices).forEach(([key, value]) => { values[key + '[' + id + ']'] = value; });
            Object.entries(values).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                input.dataset.globalPersisted = '1';
                fragment.appendChild(input);
            });
        });
        form.appendChild(fragment);
        submit.disabled = true;
    });
    capture();
    sync();
});
</script>
@endsection
