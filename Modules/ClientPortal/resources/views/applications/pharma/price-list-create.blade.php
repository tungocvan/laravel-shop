@extends('ClientPortal::layouts.application')

@section('title', 'Tạo bảng giá')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Lập bảng giá cho khách hàng')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex items-center justify-between gap-3"><a href="{{ route('client.pharma.price-lists') }}" class="text-sm font-bold text-slate-600">← Bảng giá của tôi</a><span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800">Lưu ở trạng thái Nháp</span></div>
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Create Price List</p><h1 class="mt-2 text-2xl font-black sm:text-3xl">Tạo bảng giá cho khách hàng</h1><p class="mt-2 text-sm text-slate-300">Bảng giá khách hàng phải được khởi tạo từ bảng giá chung ACTIVE mà Admin đã cấp cho bạn.</p></section>
    @if($errors->any())<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('client.pharma.price-lists.store') }}" class="space-y-5">@csrf
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">01 · Khách hàng & mục đích</p><h2 class="mt-1 text-lg font-black text-slate-950">Thông tin bảng giá</h2></div>
            <div class="grid gap-4 lg:grid-cols-2">
                <label><span class="mb-1.5 block text-xs font-bold text-slate-500">Tên bảng giá *</span><input name="name" value="{{ old('name') }}" required maxlength="255" class="h-12 w-full rounded-2xl border border-slate-300 px-4" placeholder="VD: Bảng giá BV An Bình Q4/2026"></label>
                <div><span class="mb-1.5 block text-xs font-bold text-slate-500">Khách hàng *</span><x-search-select id="client-price-list-customer" name="partner_id" placeholder="Tra cứu khách hàng..." :value="old('partner_id')"><option value="">Chọn khách hàng</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string)old('partner_id') === (string)$customer->id)>{{ $customer->name }}{{ $customer->tax_code ? ' · MST '.$customer->tax_code : '' }}</option>@endforeach</x-search-select></div>
                <label><span class="mb-1.5 block text-xs font-bold text-slate-500">Mục đích *</span><select name="purpose_id" required class="h-12 w-full rounded-2xl border border-slate-300 px-4"><option value="">Chọn mục đích</option>@foreach($purposes as $purpose)<option value="{{ $purpose->id }}" @selected((string)old('purpose_id') === (string)$purpose->id)>{{ $purpose->name }}</option>@endforeach</select></label>
                <div class="grid grid-cols-2 gap-3"><label><span class="mb-1.5 block text-xs font-bold text-slate-500">Hiệu lực từ *</span><input type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" required class="h-12 w-full rounded-2xl border border-slate-300 px-3"></label><label><span class="mb-1.5 block text-xs font-bold text-slate-500">Đến *</span><input type="date" name="effective_to" value="{{ old('effective_to', now()->addMonth()->toDateString()) }}" required class="h-12 w-full rounded-2xl border border-slate-300 px-3"></label></div>
            </div>
            <label class="mt-4 block"><span class="mb-1.5 block text-xs font-bold text-slate-500">Ghi chú</span><textarea name="notes" rows="2" maxlength="1000" class="w-full rounded-2xl border border-slate-300 px-4 py-3">{{ old('notes') }}</textarea></label>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">02 · Bảng giá gốc</p><h2 class="mt-1 text-lg font-black text-slate-950">Chọn bảng giá để khởi tạo</h2><p class="mt-1 text-sm text-slate-500">Chỉ hiển thị bảng giá chung đang ACTIVE và nằm trong phạm vi Admin cấp cho User.</p></div>
            @if($sourcePriceLists->isEmpty())
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Hiện chưa có bảng giá chung ACTIVE được cấp cho bạn. Vui lòng liên hệ người quản trị Pharma.</div>
            @else
                <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <select id="source-price-list" name="source_price_list_id" required class="h-12 w-full rounded-2xl border border-slate-300 px-4"><option value="">Chọn bảng giá gốc</option>@foreach($sourcePriceLists as $source)<option value="{{ $source->id }}" @selected((string)old('source_price_list_id',$sourcePriceListId) === (string)$source->id)>{{ $source->code }} — {{ $source->name }} · {{ $source->items_count }} SP</option>@endforeach</select>
                    <button id="load-source-price-list" type="button" class="h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white">Khởi tạo từ bảng giá</button>
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">03 · Sản phẩm & giá</p><h2 class="mt-1 text-lg font-black text-slate-950">Chọn sản phẩm từ bảng giá gốc</h2><p class="mt-1 text-sm text-slate-500">Giá bán CT mặc định lấy từ bảng giá gốc. Bỏ checkbox nếu sản phẩm không áp dụng cho khách hàng này.</p></div>
            @if(!$sourcePriceListId)
                <div class="p-8 text-center text-sm text-slate-500">Chọn bảng giá tại Bước 02 để tải sản phẩm.</div>
            @else
                <div class="max-h-[620px] overflow-auto"><table class="w-full min-w-[980px] text-left text-sm"><thead class="sticky top-0 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="w-12 px-4 py-3">Chọn</th><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Hoạt chất</th><th class="px-4 py-3 text-right">Giá kê khai</th><th class="px-4 py-3 text-right">Giá gốc</th><th class="w-48 px-4 py-3">Giá bán CT *</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($sourceProducts as $sourceItem) @php $product=$sourceItem->variant; $medicine=$product?->medicine; @endphp
                    <tr><td class="px-4 py-3"><input type="checkbox" name="selected[{{ $product->id }}]" value="1" @checked(old('selected.'.$product->id, true)) class="h-5 w-5 rounded border-slate-300"></td><td class="px-4 py-3"><p class="font-black">{{ $medicine?->name }}</p><p class="mt-1 text-xs text-slate-400">{{ $product?->sku }} · {{ $medicine?->packaging_specification }}</p></td><td class="px-4 py-3 text-slate-600">{{ $medicine?->active_ingredients ?: '—' }}</td><td class="px-4 py-3 text-right font-bold tabular-nums">{{ $sourceItem->declared_price_snapshot !== null ? number_format((float)$sourceItem->declared_price_snapshot,0,',','.') : '—' }}</td><td class="px-4 py-3 text-right font-black tabular-nums">{{ $sourceItem->company_sale_price !== null ? number_format((float)$sourceItem->company_sale_price,0,',','.') : '—' }}</td><td class="px-4 py-3"><input type="number" min="0" step="1" name="company_price[{{ $product->id }}]" value="{{ old('company_price.'.$product->id, $sourceItem->company_sale_price !== null ? (int)$sourceItem->company_sale_price : '') }}" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-right font-bold tabular-nums"></td></tr>
                @empty <tr><td colspan="6" class="p-8 text-center text-slate-500">Bảng giá gốc chưa có sản phẩm ACTIVE.</td></tr> @endforelse
                </tbody></table></div>
            @endif
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">04 · Kiểm tra & lưu</p>
            <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-black text-slate-950">Lưu bảng giá Nháp</h2><p class="mt-1 text-sm text-slate-500">Sau khi lưu, bạn có thể kiểm tra lại chi tiết trước khi Gửi duyệt.</p></div><div class="flex gap-2"><a href="{{ route('client.pharma.price-lists') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-bold text-slate-600">Hủy</a><button type="submit" @disabled(!$sourcePriceListId || $sourceProducts->isEmpty()) class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-black text-white disabled:opacity-40">Lưu bảng giá Nháp</button></div></div>
        </section>
    </form>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{const select=document.getElementById('source-price-list'),button=document.getElementById('load-source-price-list');if(!select||!button)return;button.addEventListener('click',()=>{if(!select.value)return;const url=new URL(window.location.href);url.searchParams.set('source_price_list_id',select.value);window.location.href=url.toString();});});</script>
@endsection
