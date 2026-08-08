<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex flex-col gap-1">
        <div class="flex items-center gap-2 text-xs text-gray-500">
            <a href="{{ route('admin.pharma.hssp.index') }}" class="hover:text-blue-600 transition-colors">Danh mục thuốc</a>
            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-700 font-medium">{{ $isEditMode ? 'Chỉnh sửa hồ sơ' : 'Thêm hồ sơ mới' }}</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
            {{ $isEditMode ? 'Chỉnh sửa Hồ sơ Thuốc' : 'Thêm Thuốc Mới vào Hệ thống' }}
        </h1>
        <p class="text-sm text-gray-500">Vui lòng nhập chính xác thông tin pháp lý và dược lý của sản phẩm.</p>
    </div>

    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3">1. Thông tin sản phẩm cốt lõi</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-600 block">Tên thương mại của thuốc <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="name" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Số đăng ký lưu hành <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="registration_number" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('registration_number') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Tên hoạt chất chính <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="active_ingredients" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('active_ingredients') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Nồng độ / Hàm lượng <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="concentration" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('concentration') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Dạng bào chế <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="dosage_form" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('dosage_form') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Đường dùng thuốc <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="route_of_administration" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('route_of_administration') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Đơn vị tính <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="unit" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('unit') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Hạn sử dụng <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="shelf_life" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow" placeholder="Ví dụ: 36 tháng">
                    @error('shelf_life') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600 block">Quy cách đóng gói sản phẩm <span class="text-rose-500">*</span></label>
                <input type="text" wire:model="packaging_specification" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                @error('packaging_specification') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3">2. Cơ sở sản xuất & Pháp lý thông tư</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Cơ sở đăng ký <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="registered_company" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('registered_company') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Cơ sở sản xuất <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="manufacturing_company" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('manufacturing_company') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Nước sản xuất <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="manufacturing_country" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('manufacturing_country') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Số thứ tự theo thông tư</label>
                    <input type="text" wire:model="circular_order_number" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Phân nhóm theo thông tư</label>
                    <input type="text" wire:model="circular_group" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Hiệu lực Visa / Số đăng ký</label>
                    <input type="date" wire:model="visa_validity_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('visa_validity_date') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">GMP Cơ sở sản xuất</label>
                    <input type="date" wire:model="gmp_certification_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('gmp_certification_date') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Giá kê khai công bố (VNĐ)</label>
                    <input type="number" wire:model="declared_price" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                    @error('declared_price') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3">3. Phân loại kiểm soát & Hồ sơ tài liệu</h3>

            <div>
                <label class="text-sm font-medium text-gray-600 block">Đường dẫn liên kết hồ sơ sản phẩm (Link)</label>
                <input type="url" wire:model="profile_link" placeholder="https://example.com/tailieu.pdf" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow">
                @error('profile_link') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600 block">Ghi chú bổ sung</label>
                <textarea wire:model="notes" rows="3" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-shadow"></textarea>
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" wire:model="is_special_control" class="w-5 h-5 rounded-md border-gray-300 text-blue-600 focus:ring-blue-500">
                    <div>
                        <span class="text-sm font-semibold text-gray-900 block">Danh mục Hoạt chất Kiểm soát Đặc biệt</span>
                        <span class="text-xs text-gray-500">Đánh dấu tích nếu thuốc này thuộc nhóm độc, gây nghiện, hướng thần hoặc tiền chất.</span>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.pharma.hssp.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3 font-semibold text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                Hủy bỏ
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 font-semibold text-sm text-white hover:bg-blue-700 transition-colors shadow-sm">
                Xác nhận lưu hồ sơ
            </button>
        </div>
    </form>
</div>
