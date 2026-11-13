@extends('ClientPortal::layouts.application')

@section('title', 'Bổ sung sản phẩm vào bảng giá')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Phiên bản bảng giá chung')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mx-auto max-w-5xl space-y-5 pb-20">
    <a href="{{ route('client.pharma.price-lists.show', $revision->id) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-600">← Chi tiết bản Nháp</a>
    <section class="rounded-3xl bg-slate-950 p-5 text-white sm:p-7">
        <p class="text-xs font-bold uppercase tracking-widest text-slate-300">Bảng giá chung · Bản Nháp</p>
        <h1 class="mt-2 text-xl font-black sm:text-2xl">{{ $revision->name }}</h1>
        <p class="mt-2 text-sm text-slate-300">{{ $revision->items_count }} sản phẩm hiện có. Bảng giá gốc vẫn ACTIVE cho đến khi bạn kích hoạt phiên bản mới.</p>
    </section>
    @if(session('success'))<p role="status" class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('success') }}</p>@endif
    @if($errors->any())<p role="alert" class="rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first() }}</p>@endif
    <section class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-6">
        <h2 class="text-lg font-black text-slate-900">03 · Chọn thuốc và thiết lập giá</h2>
        <p class="mt-1 text-sm text-slate-500">Chọn SKU chưa có trong bản Nháp. Ba mức giá mặc định theo giá kê khai (nếu có). Sản phẩm đã lưu không bị mất khi tìm kiếm.</p>
        <form id="revision-search" method="GET" action="{{ route('client.pharma.price-lists.revision.edit', $revision->id) }}" class="mt-4">
            <label for="revision-query" class="mb-1 block text-xs font-bold text-slate-600">Tìm thuốc</label>
            <div class="flex gap-2">
                <div class="relative min-w-0 flex-1">
                    <input id="revision-query" name="q" value="{{ $search }}" autocomplete="off" placeholder="Tên thuốc, SKU, SĐK..." class="h-12 w-full rounded-xl border border-slate-300 px-4 pr-11 text-sm" />
                    <button type="button" id="revision-clear" aria-label="Xóa chữ tìm kiếm" class="absolute right-1 top-1 h-10 w-10 rounded-lg text-slate-500">×</button>
                </div>
                <button type="submit" class="h-12 rounded-xl bg-slate-950 px-4 text-sm font-bold text-white">Tìm</button>
            </div>
        </form>
        <form method="POST" action="{{ route('client.pharma.price-lists.revision.items.store', $revision->id) }}" class="mt-4 space-y-3">
            @csrf
            @forelse($variants as $variant)
                @php $declared = $variant->medicine?->declared_price ?? $variant->declared_price; @endphp
                <div class="rounded-2xl border border-slate-200 p-4">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="selected[]" value="{{ $variant->id }}" class="mt-1 h-5 w-5 rounded border-slate-300" />
                        <span class="min-w-0"><strong class="block text-sm text-slate-900">{{ $variant->medicine?->name ?? $variant->sku }}</strong><small class="block break-all text-xs text-slate-500">{{ $variant->sku }} · Giá kê khai: {{ $declared !== null ? number_format((float) $declared, 0, ',', '.') . ' ₫' : 'Chưa có' }}</small></span>
                    </label>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <label class="text-xs font-bold text-slate-600">Giá bán (VAT) *
                            <input type="number" min="0" step="0.01" name="company_price[{{ $variant->id }}]" value="{{ $declared }}" class="mt-1 h-11 w-full rounded-xl border border-slate-300 px-3 text-sm" />
                        </label>
                        <label class="text-xs font-bold text-slate-600">Giá thu về
                            <input type="number" min="0" step="0.01" name="receivable_price[{{ $variant->id }}]" value="{{ $declared }}" class="mt-1 h-11 w-full rounded-xl border border-slate-300 px-3 text-sm" />
                        </label>
                        <label class="text-xs font-bold text-slate-600">Giá Xuất HĐ
                            <input type="number" min="0" step="0.01" name="invoice_price[{{ $variant->id }}]" value="{{ $declared }}" class="mt-1 h-11 w-full rounded-xl border border-slate-300 px-3 text-sm" />
                        </label>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-slate-500">Không có SKU phù hợp hoặc tất cả SKU đã được thêm.</p>
            @endforelse
            @if($variants->count())
                <button type="submit" class="min-h-12 w-full rounded-2xl bg-indigo-600 px-5 text-sm font-black text-white">+ Lưu sản phẩm đã chọn vào bản Nháp</button>
            @endif
        </form>
        <div class="mt-4">{{ $variants->links() }}</div>
    </section>
    <section class="rounded-3xl border border-amber-200 bg-white p-5">
        <h2 class="font-black text-slate-900">Kích hoạt phiên bản mới</h2>
        <p class="mt-2 text-sm text-slate-600">Chỉ thực hiện sau khi đã kiểm tra sản phẩm và giá. Thao tác này sẽ ngưng hiệu lực bảng giá gốc và kích hoạt bản mới trong cùng giao dịch.</p>
        <form method="POST" action="{{ route('client.pharma.price-lists.revision.activate', $revision->id) }}" class="mt-4" onsubmit="return confirm('Xác nhận ngưng bảng giá cũ và kích hoạt phiên bản mới? Hãy kiểm tra kỹ trước khi tiếp tục.')">
            @csrf
            <button type="submit" class="min-h-12 rounded-2xl bg-amber-600 px-5 text-sm font-black text-white">Xác nhận kích hoạt phiên bản mới</button>
        </form>
    </section>
</div>
<script>
(() => {
    const input = document.getElementById('revision-query');
    const form = document.getElementById('revision-search');
    let debounce;
    input?.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => form?.requestSubmit(), 400);
    });
    document.getElementById('revision-clear')?.addEventListener('click', () => {
        input.value = '';
        input.focus();
        // Only clear the search text; saved draft SKU selections are server-side.
        form?.requestSubmit();
    });
})();
</script>
@endsection
