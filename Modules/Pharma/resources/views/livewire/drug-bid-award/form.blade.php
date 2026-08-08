<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $isEditMode ? 'Cập nhật thông tin trúng thầu' : 'Thêm mới hồ sơ trúng thầu' }}</h1>
        <p class="text-sm text-gray-500 mt-1">Thiết lập chi tiết cấu trúc thông tin thương mại kết quả đấu thầu cung ứng thuốc công vụ y tế.</p>
    </div>

    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">1. Thông tin hàng hóa & giá thầu</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div x-data="{
                    init() {
                        this.$watch('$wire.medicine_id', id => {
                            if (!id) return;

                            // Tìm thuốc được chọn từ danh mục để map tự động sang các ô input
                            const selectEl = document.getElementById('select-medicine-id');
                            if (!selectEl) return;

                            const option = selectEl.querySelector(`option[value='${id}']`);
                            if (option) {
                                $wire.set('medicine_name', option.getAttribute('data-name') || '');
                                $wire.set('packaging_specification', option.getAttribute('data-pack') || '');
                            }
                        });
                    }
                }">
                    <label class="text-sm font-medium text-gray-600 block">Liên kết hồ sơ gốc hệ thống</label>
                    <div class="mt-1">
                        <x-select-search id="select-medicine-id" wire:model="medicine_id" placeholder="-- Chọn thuốc danh mục gốc để tự động điền --">
                            <option value="">-- Chọn thuốc danh mục gốc nếu có --</option>
                            @foreach($medicines as $med)
                                <option value="{{ $med->id }}" data-name="{{ $med->name }}" data-pack="{{ $med->packaging_specification }}">
                                    {{ $med->name }} ({{ $med->registration_number }})
                                </option>
                            @endforeach
                        </x-select-search>
                    </div>
                    @error('medicine_id') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Tên thuốc trúng thầu <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="medicine_name" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('medicine_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Quy cách đóng gói <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="packaging_specification" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('packaging_specification') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Số lượng trúng thầu <span class="text-rose-500">*</span></label>
                    <input type="number" wire:model="quantity" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('quantity') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Đơn giá trúng thầu (VNĐ) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" wire:model="unit_price" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('unit_price') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">2. Thông tin pháp lý & Đơn vị tổ chức thầu</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Mã thông báo mời thầu <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="bidding_notice_code" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('bidding_notice_code') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Tên chủ đầu tư (Cơ sở y tế tổ chức) <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="investor_name" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('investor_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Số quyết định pháp lý <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="decision_number" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('decision_number') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Ngày ban hành quyết định <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model="decision_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('decision_date') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Thời hạn hiệu lực hợp đồng (Số tháng) <span class="text-rose-500">*</span></label>
                    <input type="number" wire:model="contract_duration_months" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('contract_duration_months') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Tên doanh nghiệp nhà thầu trúng thầu <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="winning_company_name" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('winning_company_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Đường dẫn tài liệu đính kèm (URL Văn bản pháp lý)</label>
                    <input type="url" wire:model="decision_document_url" placeholder="https://example.com/document.pdf" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('decision_document_url') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3 font-semibold text-sm text-gray-700 hover:bg-gray-50 transition-colors">Hủy lệnh</a>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 font-semibold text-sm text-white hover:bg-blue-700 transition-colors shadow-sm">Lưu hồ sơ thông tin</button>
        </div>
    </form>
</div>
