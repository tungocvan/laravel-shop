<div class="max-w-6xl mx-auto px-4 sm:px-6 md:px-8 pb-12">
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center">
                @if($isEdit)
                    <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>
                    Cập nhật nhân viên
                @else
                    <span class="bg-green-100 text-green-600 p-2 rounded-lg mr-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </span>
                    Thêm nhân viên mới
                @endif
            </h1>
            <p class="mt-1 text-sm text-gray-500 ml-14">Thiết lập thông tin đăng nhập và phân quyền truy cập hệ thống.</p>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Quay lại
            </a>
        </div>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="text-base font-bold text-gray-900">Thông tin đăng nhập</h3>
                    <span class="text-xs text-gray-400 font-medium uppercase tracking-wider">Account Info</span>
                </div>
                
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Họ và tên <span class="text-red-500">*</span></label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <input type="text" wire:model="name" class="block w-full pl-10 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 transition" placeholder="VD: Nguyễn Văn Quản Trị">
                        </div>
                        @error('name') <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <input type="email" wire:model="email" class="block w-full pl-10 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 transition" placeholder="staff@company.com">
                            </div>
                            @error('email') <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                Mật khẩu {{ $isEdit ? "(Không bắt buộc)" : "*" }}
                            </label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <input type="password" wire:model="password" class="block w-full pl-10 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2.5 transition" placeholder="{{ $isEdit ? 'Để trống nếu giữ nguyên' : 'Nhập mật khẩu...' }}">
                            </div>
                            @error('password') <p class="mt-1 text-sm text-red-600 font-medium">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Trạng thái hoạt động</h3>
                    <p class="text-sm text-gray-500 mt-1">Gạt tắt để tạm thời chặn quyền truy cập của nhân viên này.</p>
                </div>
                
                <button type="button" wire:click="$toggle('is_active')" 
                        class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 {{ $is_active ? 'bg-green-500' : 'bg-gray-200' }}">
                    <span class="sr-only">Toggle status</span>
                    <span aria-hidden="true" 
                          class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}">
                    </span>
                </button>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                
                <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100">
                    <h3 class="text-base font-bold text-indigo-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Phân Vai Trò
                    </h3>
                    <p class="text-xs text-indigo-700 mt-1">Chọn ít nhất một vai trò.</p>
                </div>

                <div class="p-4 space-y-3 max-h-[500px] overflow-y-auto custom-scrollbar">
                    @foreach($roles as $role)
                        <label class="group relative flex items-start p-3 border rounded-xl cursor-pointer transition-all duration-200 select-none
                            {{ in_array($role->name, $selectedRoles) 
                                ? 'bg-indigo-50 border-indigo-200 ring-1 ring-indigo-500 z-10' 
                                : 'border-gray-200 hover:bg-gray-50 hover:border-gray-300' 
                            }}">
                            
                            <div class="flex items-center h-5">
                                <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}" 
                                       class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            </div>
                            
                            <div class="ml-3 w-full">
                                <div class="flex justify-between items-center">
                                    <span class="block text-sm font-bold text-gray-900 {{ in_array($role->name, $selectedRoles) ? 'text-indigo-700' : '' }}">
                                        {{ $role->name }}
                                    </span>
                                    @if($role->name === 'Super Admin')
                                        <span class="bg-red-100 text-red-700 text-[10px] px-1.5 py-0.5 rounded font-bold">CORE</span>
                                    @endif
                                </div>
                                <span class="block text-xs text-gray-500 mt-0.5 group-hover:text-gray-700">Guard: {{ $role->guard_name }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>

                @error('selectedRoles') 
                    <div class="px-6 pb-4">
                        <p class="text-sm text-red-600 bg-red-50 p-2 rounded border border-red-100 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $message }}
                        </p>
                    </div>
                @enderror

                <div class="p-6 pt-4 border-t border-gray-100 bg-gray-50">
                    <button type="submit" wire:loading.attr="disabled" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                        <span wire:loading.remove>
                            {{ $isEdit ? 'Lưu thay đổi' : 'Tạo nhân viên ngay' }}
                        </span>
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Đang xử lý...
                        </span>
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>