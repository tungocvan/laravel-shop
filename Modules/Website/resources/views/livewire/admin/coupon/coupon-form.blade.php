<div class="max-w-6xl mx-auto px-4 sm:px-6 md:px-8 pb-10">
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                {{ $isEdit ? 'Cập nhật mã giảm giá' : 'Tạo chiến dịch mới' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">Cấu hình chi tiết mã khuyến mãi và thời gian áp dụng.</p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                Hủy bỏ
            </a>
            <button type="button" wire:click="save" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition disabled:opacity-70">
                <span wire:loading.remove>{{ $isEdit ? 'Lưu thay đổi' : 'Tạo mã ngay' }}</span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Đang lưu...
                </span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-bold text-gray-900 mb-5 flex items-center">
                    <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg mr-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    </span>
                    Thông tin cơ bản
                </h3>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Mã Coupon <span class="text-red-500">*</span></label>
                        <div class="relative flex rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                            <input type="text" wire:model="code" class="block w-full rounded-l-lg border-gray-300 pl-10 font-mono text-lg uppercase tracking-wider focus:ring-indigo-500 focus:border-indigo-500 transition py-2.5" placeholder="VD: SALE2024">
                            <button type="button" wire:click="$set('code', 'PROMO-' . strtoupper(Str::random(6)))" class="inline-flex items-center px-4 rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Tạo ngẫu nhiên
                            </button>
                        </div>
                        @error('code') <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả chương trình</label>
                        <textarea wire:model="description" rows="2" class="block w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition" placeholder="VD: Giảm giá mùa hè cho khách hàng mới..."></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-bold text-gray-900 mb-5 flex items-center">
                    <span class="bg-green-100 text-green-600 p-1.5 rounded-lg mr-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    Giá trị ưu đãi
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Loại giảm giá</label>
                        <div class="relative">
                            <select wire:model.live="type" class="block w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 pr-8 cursor-pointer">
                                <option value="fixed">Tiền mặt (Trừ trực tiếp)</option>
                                <option value="percent">Phần trăm (%)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mức giảm <span class="text-red-500">*</span></label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="number" wire:model="value" class="block w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 pr-12" placeholder="0">
                            
                            <div class="absolute inset-y-0 right-0 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm font-bold bg-gray-50 border-l border-gray-300 px-3 py-2.5 rounded-r-lg">
                                    {{ $type === 'percent' ? '%' : 'VNĐ' }}
                                </span>
                            </div>
                        </div>
                        @error('value') <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Trạng thái hoạt động</h3>
                        <p class="text-xs text-gray-500">Bật/Tắt mã giảm giá này</p>
                    </div>
                    <button type="button" wire:click="$toggle('is_active')" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-indigo-600' : 'bg-gray-200' }}">
                        <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-bold text-gray-900 mb-4">Thời gian hiệu lực</h3>
                
                <div class="space-y-5">
                    <div x-data="{ value: @entangle('starts_at') }"
                         x-init="flatpickr($refs.picker, {
                             enableTime: true,
                             dateFormat: 'Y-m-d H:i',
                             time_24hr: true,
                             defaultDate: value,
                             onChange: (selectedDates, dateStr) => { value = dateStr }
                         })"
                         wire:ignore>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <input x-ref="picker" type="text" class="block w-full pl-10 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 bg-white cursor-pointer" placeholder="Chọn ngày bắt đầu...">
                        </div>
                    </div>

                    <div x-data="{ value: @entangle('expires_at') }"
                         x-init="flatpickr($refs.picker, {
                             enableTime: true,
                             dateFormat: 'Y-m-d H:i',
                             time_24hr: true,
                             defaultDate: value,
                             onChange: (selectedDates, dateStr) => { value = dateStr }
                         })"
                         wire:ignore>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <input x-ref="picker" type="text" class="block w-full pl-10 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 bg-white cursor-pointer" placeholder="Chọn ngày kết thúc (tùy chọn)...">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Để trống nếu mã có hiệu lực vĩnh viễn.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-bold text-gray-900 mb-4">Điều kiện giới hạn</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Đơn tối thiểu</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="number" wire:model="min_order_value" class="block w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 pr-12">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">VNĐ</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Giới hạn lượt dùng</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="number" wire:model="usage_limit" class="block w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 pr-12" placeholder="∞">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Lần</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Tổng số lần mã này có thể được sử dụng.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>