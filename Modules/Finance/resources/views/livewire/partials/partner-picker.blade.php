<div class="space-y-2 text-sm">
    <label for="debt-partner-search" class="block font-medium">Đối tác ({{ $draftDirection === 'payable' ? 'Nhà cung cấp' : 'Khách hàng' }})</label>
    <input id="debt-partner-search" type="search" wire:model.live.debounce.350ms="partnerSearch" placeholder="Tìm theo tên hoặc mã số thuế" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3">
    <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2" aria-label="Danh sách đối tác">
        @forelse ($partners as $partner)
            <button type="button" wire:key="debt-partner-{{ $partner->id }}" wire:click="choosePartner({{ $partner->id }})" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-indigo-50 {{ $partnerId === (string) $partner->id ? 'bg-indigo-100 font-semibold' : '' }}">
                {{ $partner->name }} <span class="text-slate-500">({{ $partner->tax_code ?: 'Chưa có MST' }})</span>
            </button>
        @empty
            <p class="p-2 text-slate-500">Không có đối tác đang hoạt động phù hợp.</p>
        @endforelse
    </div>
    <p class="text-xs text-slate-600">Đối tác đã chọn: {{ $partnerId ?: 'Chưa chọn' }}. <a href="/admin/partners" class="underline">Quản lý khách hàng / nhà cung cấp</a></p>
    @error('partnerId') <p role="alert" class="text-red-700">{{ $message }}</p> @enderror
</div>
