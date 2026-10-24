<div wire:key="debt-partner-picker-{{ $draftDirection }}" class="space-y-2 text-sm">
    <label class="block font-medium">Đối tác ({{ $draftDirection === 'payable' ? 'Nhà cung cấp' : 'Khách hàng' }})</label>
    <x-search variant="select" :options="$partnerOptions" :selected="$partnerId" placeholder="Chọn khách hàng / nhà cung cấp" search-placeholder="Tìm theo tên hoặc mã số thuế" search-model="partnerSearch" select-action="choosePartner" />
    <p class="text-xs text-slate-600">Tối đa 20 kết quả mỗi lượt. <a href="/admin/partners" class="underline">Quản lý khách hàng / nhà cung cấp</a></p>
    @error('partnerId') <p role="alert" class="text-red-700">{{ $message }}</p> @enderror
</div>
