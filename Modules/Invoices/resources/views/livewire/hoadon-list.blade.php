<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Bán ra', $totalSoldAmount, $totalSoldCustomers],
            ['Mua vào', $totalPurchaseAmount, $totalPurchaseCustomers],
        ] as [$label, $amount, $customers])
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:col-span-1 lg:col-span-2">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($amount) }} ₫</p>
                <p class="mt-1 text-sm text-gray-500">{{ number_format($customers) }} đối tác</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
            <select wire:model.live="type" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
                <option value="">Tất cả loại</option><option value="sold">Bán ra</option><option value="purchase">Mua vào</option>
            </select>
            <select wire:model.live="name" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
                <option value="">Tất cả đối tác</option>
                @foreach ($nameList as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach
            </select>
            <select wire:model.live="tax_code" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
                <option value="">Tất cả MST</option>
                @foreach ($taxCodeList as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach
            </select>
            <input type="date" wire:model.live="from_date" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
            <input type="date" wire:model.live="to_date" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
            <select wire:model.live="taxRateFilter" class="rounded-xl border border-gray-300 px-4 py-3 text-sm">
                <option value="all">Mọi thuế suất</option><option value="5">5%</option><option value="8">8%</option><option value="10">10%</option><option value="other">Khác</option>
            </select>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <select wire:model.live="perPage" class="h-11 rounded-xl border border-gray-300 px-4 text-sm" aria-label="Số hóa đơn mỗi trang">
                    @foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} / trang</option>@endforeach
                </select>

                @if (count($selected) > 0)
                    <span class="inline-flex h-9 items-center rounded-full bg-indigo-50 px-3 text-sm font-semibold text-indigo-700">
                        Đã chọn {{ number_format(count($selected)) }} hóa đơn
                    </span>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                <button wire:click="resetFilters" class="h-11 rounded-xl border border-gray-300 px-4 text-sm font-semibold hover:bg-gray-50">
                    Đặt lại bộ lọc
                </button>

                @if (auth('admin')->user()?->can('invoices-export'))
                    <button wire:click="exportSelected" wire:loading.attr="disabled" wire:target="exportSelected"
                        class="h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="exportSelected">
                            {{ count($selected) > 0 ? 'Xuất '.count($selected).' hóa đơn' : 'Xuất theo bộ lọc' }}
                        </span>
                        <span wire:loading wire:target="exportSelected">Đang xuất…</span>
                    </button>
                @endif

                @if (auth('admin')->user()?->can('invoices-download'))
                    <button wire:click="downloadSelected" wire:loading.attr="disabled" wire:target="downloadSelected"
                        @disabled(count($selected) === 0)
                        class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="downloadSelected">
                            {{ count($selected) > 0 ? 'Tải PDF ('.count($selected).')' : 'Tải PDF' }}
                        </span>
                        <span wire:loading wire:target="downloadSelected">Đang tải…</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr><th class="px-4 py-3"></th><th class="px-4 py-3">Số HĐ</th><th class="px-4 py-3">Ngày</th><th class="px-4 py-3">Đối tác</th><th class="px-4 py-3">MST</th><th class="px-4 py-3">Loại</th><th class="px-4 py-3 text-right">Tổng tiền</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $invoice->id }}" class="rounded border-gray-300"></td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $invoice->invoice_number ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->issued_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->tax_code ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $invoice->invoice_type === 'sold' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $invoice->invoice_type === 'sold' ? 'Bán ra' : 'Mua vào' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format($invoice->total_amount) }} ₫</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">Không có hóa đơn phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="border-t border-gray-200 px-4 py-4">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
