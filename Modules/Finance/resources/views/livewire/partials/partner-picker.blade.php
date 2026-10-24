<div class="space-y-2 text-sm">
    <label for="debt-partner-select" class="block font-medium">Đối tác ({{ $draftDirection === 'payable' ? 'Nhà cung cấp' : 'Khách hàng' }})</label>
    <x-select-search id="debt-partner-select" wire:model="partnerId" placeholder="Tìm khách hàng / nhà cung cấp...">
        <option value="">Chọn đối tác</option>
        @foreach ($partners as $partner)
            <option value="{{ $partner->id }}">{{ $partner->name }}{{ $partner->tax_code ? ' — '.$partner->tax_code : '' }}</option>
        @endforeach
    </x-select-search>
    <p class="text-xs text-slate-600">Danh sách tối đa 20 đối tác đang hoạt động. <a href="/admin/partners" class="underline">Quản lý khách hàng / nhà cung cấp</a></p>
    @error('partnerId') <p role="alert" class="text-red-700">{{ $message }}</p> @enderror
</div>
