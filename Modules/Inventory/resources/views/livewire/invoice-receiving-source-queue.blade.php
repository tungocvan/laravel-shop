<div class="space-y-5">
    @if($errorMessage)
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-900">{{ $errorMessage }}</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Danh sách hóa đơn mua hàng</h2>
                    <p class="mt-1 text-sm text-slate-500">Chọn kỳ hóa đơn. Hóa đơn đủ dữ liệu sẽ sẵn sàng để bắt đầu quy trình nhập kho.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:w-[360px]">
                    <label class="text-xs font-semibold text-slate-600">Năm
                        <select wire:model.live="year" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            @foreach($years as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-slate-600">Tháng
                        <select wire:model.live="month" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            @foreach(range(1,12) as $value)<option value="{{ $value }}">Tháng {{ str_pad((string)$value, 2, '0', STR_PAD_LEFT) }}</option>@endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm số hóa đơn, ký hiệu, nhà cung cấp, MST..." class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <div class="flex flex-wrap gap-2" aria-label="Lọc trạng thái hóa đơn">
                    @foreach(['all'=>'Tất cả','ready'=>'Sẵn sàng','needs_raw'=>'Chờ dữ liệu','in_progress'=>'Đang xử lý','confirmed'=>'Đã nhập'] as $value=>$label)
                        <button type="button" wire:click="$set('queueStatus','{{ $value }}')" class="min-h-11 rounded-xl border px-3 text-sm font-semibold transition {{ $queueStatus === $value ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-indigo-300' }}">{{ $label }} <span class="ml-1 opacity-75">{{ $counts[$value] ?? 0 }}</span></button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($rows as $row)
                @php
                    $invoice = $row['invoice'];
                    $inbox = $row['inbox'];
                    $state = $row['state'];
                    $stateMeta = match($state) {
                        'ready' => ['Sẵn sàng nhập kho','bg-emerald-100 text-emerald-800'],
                        'needs_raw' => ['Chờ RAW detail','bg-amber-100 text-amber-800'],
                        'in_progress' => ['Đang xử lý','bg-indigo-100 text-indigo-800'],
                        'confirmed' => ['Đã nhập kho','bg-slate-200 text-slate-700'],
                        default => ['Cần phân loại','bg-orange-100 text-orange-800'],
                    };
                @endphp
                <article class="p-5 transition hover:bg-slate-50/70">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-base font-bold text-slate-950">#{{ $invoice->invoice_number ?: '—' }}</span>
                                @if($invoice->symbol)<span class="text-xs text-slate-500">{{ $invoice->symbol }}</span>@endif
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $stateMeta[1] }}">{{ $stateMeta[0] }}</span>
                                @if($row['classification'] !== 'UNCLASSIFIED')<span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{{ $row['classification'] }}</span>@endif
                            </div>
                            <div class="mt-2 font-semibold text-slate-800">{{ $invoice->name ?: 'Chưa có tên nhà cung cấp' }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
                                <span>MST {{ $invoice->tax_code ?: '—' }}</span>
                                <span>Ngày HĐ {{ $invoice->issued_date?->format('d/m/Y') ?: '—' }}</span>
                                <span>{{ number_format((float)$invoice->total_amount, 0, ',', '.') }} đ</span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-lg px-2 py-1 {{ $row['hasRaw'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $row['hasRaw'] ? '✓ Đã có RAW detail' : '! Chưa có RAW detail' }}</span>
                                @if($inbox)<span class="rounded-lg bg-indigo-50 px-2 py-1 text-indigo-700">Phiên nhập kho: {{ $inbox->processing_status }}</span>@endif
                            </div>
                        </div>

                        <div class="flex lg:justify-end">
                            @if($state === 'ready' && $canManageReceipt)
                                <button type="button" wire:click="startReceiving({{ $invoice->id }})" wire:loading.attr="disabled" wire:target="startReceiving({{ $invoice->id }})" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-50 lg:w-auto">Nhập kho →</button>
                            @elseif(in_array($state, ['in_progress','confirmed'], true) && $inbox)
                                <button type="button" wire:click="continueReceiving({{ $inbox->id }})" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-indigo-300 bg-white px-5 text-sm font-bold text-indigo-700 hover:bg-indigo-50 lg:w-auto">{{ $state === 'confirmed' ? 'Xem phiếu' : 'Tiếp tục →' }}</button>
                            @elseif($state === 'needs_raw')
                                <div class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center text-xs font-medium text-amber-800 lg:w-auto">Đồng bộ chi tiết tại Invoices trước</div>
                            @else
                                <div class="w-full rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-center text-xs font-medium text-orange-800 lg:w-auto">Phân loại GOODS/MIXED trước</div>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="text-base font-semibold text-slate-800">Không có hóa đơn trong kỳ đã chọn</div>
                    <p class="mt-2 text-sm text-slate-500">Thử đổi tháng/năm hoặc từ khóa tìm kiếm.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
