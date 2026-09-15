<div class="space-y-4">
    @if(in_array($step, [2, 3], true) && $selectedProducts->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
            <header class="flex flex-col gap-3 border-b border-emerald-100 bg-emerald-50/60 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div><p class="text-xs font-black uppercase tracking-[.18em] text-emerald-700">Bid Intelligence</p><h2 class="mt-1 text-lg font-bold text-slate-950">Kết quả trúng thầu của sản phẩm đã chọn</h2><p class="mt-1 text-sm text-slate-600">Tự động đối chiếu Medicine → SKU → Package canonical. Giá trúng thầu chỉ là evidence tham khảo và không tự thay đổi giá bán công ty.</p></div>
                <a href="{{ route('admin.pharma.drug-bid-awards.review') }}" target="_blank" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 text-sm font-bold text-emerald-800">Rà soát liên kết ↗</a>
            </header>
            <div class="divide-y divide-slate-100">
                @foreach($selectedProducts as $row)
                    <div class="p-4 sm:p-5">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                            <div><div class="font-bold text-slate-950">{{ $row->name }}</div><div class="mt-0.5 font-mono text-xs text-slate-500">{{ $row->medicine_code }} · {{ $row->sku }}</div><div class="mt-1 text-xs text-slate-600">{{ $row->packaging_text ?: $row->packaging_specification ?: 'Chưa có quy cách' }}</div></div>
                            <div class="text-xs font-semibold text-slate-500">Giá kê khai: <strong class="text-slate-900">{{ $row->declared_price !== null ? number_format((float)$row->declared_price,0,',','.').' ₫' : '—' }}</strong></div>
                        </div>
                        @include('Pharma::livewire.price-list.partials.bid-reference', ['row' => $row])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @include('Pharma::livewire.price-list.create')
    @include('Pharma::livewire.price-list.partials.bid-history-modal')
</div>
