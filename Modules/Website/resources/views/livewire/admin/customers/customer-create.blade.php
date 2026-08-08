<div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Thêm khách hàng mới</h1>
        <p class="mt-1 text-sm text-gray-500">Tạo tài khoản khách hàng thủ công.</p>
    </div>

    <form wire:submit="save" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Họ và tên <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" class="w-full p-2 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm" placeholder="VD: Nguyễn Văn A">
                    @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Email đăng nhập <span class="text-red-500">*</span></label>
                    <input type="email" wire:model="email" class="w-full p-2 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm" placeholder="email@example.com">
                    @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Mật khẩu <span class="text-red-500">*</span></label>
                    <input type="password" wire:model="password" class="w-full p-2 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm" placeholder="Nhập mật khẩu...">
                    @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                    <input type="text" wire:model="phone" class="w-full p-2 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm" placeholder="09xxxxxxx">
                    @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="h-5 w-5 p-2 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-gray-900">Kích hoạt tài khoản ngay</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Nếu bỏ chọn, khách hàng sẽ không thể đăng nhập cho đến khi được kích hoạt.</p>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-gray-100">
            <a href="{{ route('admin.customers.index') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Hủy bỏ
            </a>
            <button type="submit" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 rounded-lg text-sm font-bold text-white hover:bg-indigo-700 transition shadow-sm flex items-center">
                <span wire:loading.remove>Tạo khách hàng</span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Đang xử lý...
                </span>
            </button>
        </div>
    </form>
</div>