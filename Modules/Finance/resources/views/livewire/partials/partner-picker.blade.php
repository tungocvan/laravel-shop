<div class="space-y-2 text-sm" x-data="{
    open: false,
    query: '',
    selectedLabel: '',
    init() {
        this.$nextTick(() => this.sync());
    },
    sync() {
        const current = this.$wire.partnerId;
        const selected = this.$el.querySelector('[data-partner-id="' + current + '"]');
        if (selected) this.selectedLabel = selected.dataset.partnerLabel;
    },
    pick(id, label) {
        this.$wire.set('partnerId', String(id));
        this.selectedLabel = label;
        this.open = false;
        this.query = '';
    },
    search() {
        this.$wire.searchPartners(this.query);
    }
}" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
    <label class="block font-medium">Đối tác ({{ $draftDirection === 'payable' ? 'Nhà cung cấp' : 'Khách hàng' }})</label>
    <x-pwa-select-search id="debt-partner-select" name="debt_partner_id" placeholder="Chọn khách hàng / nhà cung cấp" search-placeholder="Tìm theo tên hoặc mã số thuế" :selected="$partnerId">
        @foreach ($partnerOptions as $partner)
            <button type="button" role="option" data-partner-id="{{ $partner['id'] }}" data-partner-label="{{ $partner['label'] }}" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-slate-100" x-on:click="pick(@js($partner['id']), @js($partner['label']))">{{ $partner['label'] }}</button>
        @endforeach
    </x-pwa-select-search>
    <p class="text-xs text-slate-600">Tối đa 20 kết quả mỗi lượt. <a href="/admin/partners" class="underline">Quản lý khách hàng / nhà cung cấp</a></p>
    @error('partnerId') <p role="alert" class="text-red-700">{{ $message }}</p> @enderror
</div>
