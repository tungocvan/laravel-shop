<div wire:key="debt-partner-picker-{{ $draftDirection }}" class="space-y-2 text-sm">
    <label for="debt-partner-search" class="block font-medium">Đối tác ({{ $draftDirection === 'payable' ? 'Nhà cung cấp' : 'Khách hàng' }})</label>
    <div class="relative" x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
        <button type="button" class="flex w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 py-3 text-left" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-haspopup="listbox">
            <span>{{ $partnerId ? collect($partnerOptions)->firstWhere('id', $partnerId)['label'] ?? 'Đối tác đã chọn #'.$partnerId : 'Chọn khách hàng / nhà cung cấp' }}</span>
            <span aria-hidden="true">⌄</span>
        </button>
        <div x-show="open" x-cloak class="absolute left-0 right-0 z-50 mt-2 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
            <input id="debt-partner-search" type="search" wire:model.live.debounce.350ms="partnerSearch" placeholder="Tìm theo tên hoặc mã số thuế" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <div role="listbox" class="mt-2 max-h-64 overflow-y-auto">
                @forelse ($partnerOptions as $partner)
                    <button type="button" role="option" wire:key="partner-choice-{{ $partner['id'] }}" wire:click="choosePartner({{ (int) $partner['id'] }})" x-on:click="open = false" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-indigo-50">{{ $partner['label'] }}</button>
                @empty
                    <p class="p-3 text-slate-500">Không có đối tác phù hợp.</p>
                @endforelse
            </div>
        </div>
    </div>
    <p class="text-xs text-slate-600">Tối đa 20 kết quả mỗi lượt. <a href="/admin/partners" class="underline">Quản lý khách hàng / nhà cung cấp</a></p>
    @error('partnerId') <p role="alert" class="text-red-700">{{ $message }}</p> @enderror
</div>
