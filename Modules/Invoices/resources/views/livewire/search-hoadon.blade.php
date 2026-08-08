<div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="grid gap-4 p-6 md:grid-cols-3">
        <div>
            <label class="text-sm font-medium text-gray-700">Từ ngày</label>
            <input type="date" wire:model.live="start_date" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
            @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Đến ngày</label>
            <input type="date" wire:model.live="end_date" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
            @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Loại hóa đơn</label>
            <select wire:model.live="vatIn" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
                <option value="0">Bán ra</option><option value="1">Mua vào</option>
            </select>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 px-6 py-4">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model.live="useQueue" class="rounded border-gray-300"> Xử lý qua queue
        </label>
        <button wire:click="run" wire:loading.attr="disabled" class="h-11 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white disabled:opacity-50">
            <span wire:loading.remove wire:target="run">Chạy đồng bộ</span><span wire:loading wire:target="run">Đang xử lý…</span>
        </button>
        <button wire:click="importExcel" wire:loading.attr="disabled" class="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:opacity-50">
            <span wire:loading.remove wire:target="importExcel">Import database</span><span wire:loading wire:target="importExcel">Đang import…</span>
        </button>
    </div>

    <div class="border-t border-gray-200 bg-gray-950 p-5 font-mono text-xs text-gray-200">
        <p class="mb-3 font-sans text-sm font-semibold text-white">Nhật ký xử lý</p>
        <div class="max-h-80 space-y-1 overflow-y-auto">
            @forelse ($logs as $line)<div>{{ $line }}</div>@empty<div class="text-gray-400">Chưa có tác vụ.</div>@endforelse
        </div>
    </div>
</div>
