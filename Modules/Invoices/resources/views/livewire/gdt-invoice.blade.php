<div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <form wire:submit="searchInvoices" class="grid gap-4 p-6 md:grid-cols-4">
        <div>
            <label class="text-sm font-medium text-gray-700">Loại hóa đơn</label>
            <select wire:model.live="invoiceType" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
                <option value="sold">Bán ra</option>
                <option value="purchase">Mua vào</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Từ ngày</label>
            <input type="date" wire:model.live="fromDate" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
            @error('fromDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Đến ngày</label>
            <input type="date" wire:model.live="toDate" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
            @error('toDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end">
            <button class="h-11 w-full rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:opacity-50"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="searchInvoices">Tìm hóa đơn</span>
                <span wire:loading wire:target="searchInvoices">Đang tìm…</span>
            </button>
        </div>
    </form>

    @if (session('error'))
        <div class="mx-6 mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="overflow-x-auto border-t border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr><th class="px-4 py-3">Số HĐ</th><th class="px-4 py-3">Ngày lập</th><th class="px-4 py-3">Người bán</th><th class="px-4 py-3">Người mua</th><th class="px-4 py-3 text-right">Tổng tiền</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="px-4 py-3">{{ $invoice['shdon'] ?? '-' }}</td>
                        <td class="px-4 py-3">{{ isset($invoice['tdlap']) ? \Carbon\Carbon::parse($invoice['tdlap'])->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-3">{{ $invoice['nbten'] ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $invoice['nmten'] ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($invoice['tgtttbso'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">Chưa có hóa đơn để hiển thị.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
