<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 pb-12">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                {{ $isEdit ? 'Cập nhật Vai trò' : 'Tạo Vai trò mới' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">Định nghĩa quyền hạn truy cập cho từng nhóm nhân viên.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Hủy bỏ
            </a>
            <button wire:click="save" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 rounded-lg text-sm font-bold text-white hover:bg-indigo-700 shadow-sm transition flex items-center disabled:opacity-70">
                <span wire:loading.remove>Lưu Vai trò</span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Đang lưu...
                </span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center">
                    <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg mr-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    Thông tin chung
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Tên Vai trò <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="VD: Sale Manager">
                        @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-800 leading-relaxed">
                        <span class="font-bold">Mẹo:</span> Đặt tên vai trò rõ ràng theo chức vụ (VD: Trưởng kho, Kế toán trưởng) để dễ quản lý nhân sự sau này.
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Phân quyền chi tiết</h3>
                        <p class="text-xs text-gray-500">Tích chọn các hành động mà vai trò này được phép thực hiện.</p>
                    </div>
                    
                    <div class="text-xs font-medium bg-white border px-3 py-1 rounded-full shadow-sm">
                        Đã chọn: <span class="text-indigo-600 font-bold text-sm">{{ count($selectedPermissions) }}</span>
                    </div>
                </div>

                <div class="p-6 bg-gray-50/30">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        
                        @foreach($permissionGroups as $module => $permissions)
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col h-full">
                                
                                <div class="px-4 py-3 border-b border-gray-100 bg-indigo-50/50 flex items-center justify-between rounded-t-xl">
                                    <span class="font-bold text-indigo-900 uppercase tracking-wider text-xs flex items-center gap-2">
                                        {{-- Icon đại diện cho module (Logic đơn giản) --}}
                                        @if(in_array($module, ['product', 'category'])) 🛍️
                                        @elseif(in_array($module, ['order', 'bill'])) 📦
                                        @elseif(in_array($module, ['user', 'staff', 'role'])) 👥
                                        @elseif(in_array($module, ['setting', 'system'])) ⚙️
                                        @else 🔹 @endif
                                        {{ $module }}
                                    </span>
                                </div>
                                
                                <div class="p-4 space-y-3 flex-1">
                                    @foreach($permissions as $perm)
                                        <label class="flex items-start space-x-3 cursor-pointer group select-none">
                                            <div class="flex items-center h-5">
                                                <input type="checkbox" wire:model="selectedPermissions" value="{{ $perm->name }}" 
                                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer transition">
                                            </div>
                                            <div class="flex-1">
                                                <span class="text-sm text-gray-600 group-hover:text-indigo-700 transition font-medium">
                                                    {{-- Làm đẹp tên quyền: view_product -> View --}}
                                                    {{ ucwords(str_replace('_', ' ', str_replace('_'.$module, '', $perm->name))) }}
                                                </span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>