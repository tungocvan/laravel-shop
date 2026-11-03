@extends('ClientPortal::layouts.application')

@section('title', 'Chọn lô thực xuất')
@section('app-name', $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mx-auto max-w-4xl space-y-4 px-3 py-4 sm:px-6">
    <a href="{{ route('client.pharma.orders.show', $issue) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-indigo-700">← Trở về đơn {{ $issue->number }}</a>
    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <h1 class="text-xl font-black text-slate-950">Chọn lô và hạn dùng</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Phân bổ đúng số lượng đã duyệt cho từng sản phẩm. Lô hết hạn không được sử dụng. Chỉ ghi sổ sau khi kiểm tra toàn bộ phân bổ.</p>
    </div>
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('client.pharma.orders.lots.post', $issue) }}" class="space-y-4" onsubmit="return confirm('Xác nhận ghi sổ theo các lô và số lượng đã chọn?')">
        @csrf
        @forelse($postableItems as $item)
            @php($lots = $balances->get($item->medicine_id, collect()))
            <section data-lot-product data-required="{{ (int) $item->quantity }}" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-4">
                    <h2 class="font-black text-slate-950">{{ $item->medicine?->name ?: 'Sản phẩm #'.$item->medicine_id }}</h2>
                    <p class="mt-1 text-sm text-slate-600">Đã duyệt: <strong>{{ number_format((float) $item->quantity, 0, ',', '.') }}</strong> · {{ $lots->count() }} lô khả dụng</p>
                    <p data-lot-summary aria-live="polite" class="mt-2 text-xs font-bold">Đang kiểm tra phân bổ...</p>
                </div>
                <div class="space-y-3 p-4">
                    @php($remainingForFefo = (int) $item->quantity)
                    @forelse($lots as $lot)
                        @php($suggested = min($remainingForFefo, (int) $lot->quantity_on_hand))
                        @php($remainingForFefo -= $suggested)
                        <div class="grid grid-cols-1 gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-[1fr_140px] sm:items-center">
                            <div class="text-sm">
                                <p class="font-bold text-slate-900">{{ $lot->batch_number }} @if($loop->first)<span class="ml-2 text-xs font-black text-emerald-700">FEFO</span>@endif</p>
                                <p class="text-slate-600">HSD {{ $lot->expiry_date?->format('d/m/Y') }} · Tồn {{ number_format((float) $lot->quantity_on_hand, 0, ',', '.') }}</p>
                            </div>
                            <label class="text-xs font-bold text-slate-700">Số lượng thực xuất
                                <input type="hidden" name="batches[{{ $item->id }}][{{ $lot->id }}][balance_id]" value="{{ $lot->id }}">
                                <input type="number" data-lot-quantity inputmode="numeric" min="0" max="{{ (int) $lot->quantity_on_hand }}" step="1" name="batches[{{ $item->id }}][{{ $lot->id }}][quantity]" value="{{ old('batches.'.$item->id.'.'.$lot->id.'.quantity', $suggested) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-base focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            </label>
                        </div>
                    @empty
                        <p class="text-sm font-semibold text-amber-800">Không có lô còn hạn và còn tồn.</p>
                    @endforelse
                </div>
            </section>
        @empty
            <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Không có sản phẩm thực xuất. Các sản phẩm đang chờ cung ứng không được trừ kho.</p>
        @endforelse
        <p class="rounded-xl bg-indigo-50 p-3 text-xs font-semibold text-indigo-900">Gợi ý FEFO được điền sẵn. Có thể chia lại giữa các lô; tổng từng sản phẩm phải bằng số lượng đã duyệt. Tồn kho và hạn dùng được kiểm tra lại khi ghi sổ.</p>
        <button type="submit" id="lot-post-submit" @disabled($postableItems->isEmpty()) class="min-h-12 w-full rounded-2xl bg-emerald-700 px-4 font-black text-white disabled:cursor-not-allowed disabled:bg-slate-300">Xác nhận lô và ghi sổ</button>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
 const products = [...document.querySelectorAll('[data-lot-product]')];
 const submit = document.getElementById('lot-post-submit');
 const fmt = n => new Intl.NumberFormat('vi-VN', {maximumFractionDigits: 0}).format(n);
 function refresh() {
  let valid = products.length > 0;
  products.forEach(product => {
   const required = Number(product.dataset.required);
   const inputs = [...product.querySelectorAll('[data-lot-quantity]')];
   const quantities = inputs.map(input => Number(input.value));
   const total = quantities.reduce((sum, n) => sum + n, 0);
   const rowsValid = inputs.every((input, i) => input.value.trim() !== '' && Number.isInteger(quantities[i]) && quantities[i] >= 0 && quantities[i] <= Number(input.max));
   const matched = rowsValid && total === required;
   const summary = product.querySelector('[data-lot-summary]');
   summary.textContent = 'Đã chọn ' + fmt(total) + ' / ' + fmt(required) + (matched ? ' · Đủ số lượng' : ' · Chưa khớp số lượng hoặc tồn lô');
   summary.classList.toggle('text-rose-700', !matched);
   summary.classList.toggle('text-emerald-700', matched);
   valid = valid && matched;
  });
  if (submit) submit.disabled = !valid;
 }
 document.querySelectorAll('[data-lot-quantity]').forEach(input => input.addEventListener('input', refresh));
 refresh();
});
</script>
@endsection
