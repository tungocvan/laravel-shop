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
            <select wire:model.live="perPage" class="h-11 rounded-xl border border-gray-300 px-4 text-sm">
                @foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
            </select>
            <div class="flex gap-2">
                <button wire:click="resetFilters" class="h-11 rounded-xl border border-gray-300 px-4 text-sm font-semibold">Đặt lại</button>
                <button wire:click="exportSelected" wire:loading.attr="disabled" class="h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white disabled:opacity-50">Xuất đã chọn</button>
                <button wire:click="downloadSelected" wire:loading.attr="disabled" class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white disabled:opacity-50">Tải PDF</button>
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
                        <tr>
                            <td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $invoice->id }}" class="rounded border-gray-300"></td>
                            <td class="px-4 py-3">{{ $invoice->invoice_number ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->issued_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->tax_code ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $invoice->invoice_type === 'sold' ? 'Bán ra' : 'Mua vào' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($invoice->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">Không có hóa đơn phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($perPage !== 'All' && $invoices->hasPages())
            <div class="border-t border-gray-200 px-4 py-4">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
