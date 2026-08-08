<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Quản lý Vai trò (Roles)</h1>
            <p class="mt-1 text-sm text-gray-500">Phân quyền truy cập cho nhân viên hệ thống.</p>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <button wire:click="export" wire:loading.attr="disabled" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </button>

            <button wire:click="$set('showImportModal', true)" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Import
            </button>

            <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tạo vai trò mới
            </a>
            <button wire:click="openPermissionModal" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Thêm Module Quyền
            </button>
        </div>
    </div>

    <div class="mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 relative">
            
            <div wire:loading.flex wire:target="search, deleteSelected, perPage" class="absolute inset-0 bg-white/60 z-20 items-center justify-center backdrop-blur-[1px]">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>

            @if(count($selected) > 0)
                <div class="p-3 bg-indigo-50 flex items-center justify-between animate-fade-in">
                    <div class="flex items-center gap-3">
                        <button wire:click="$set('selected', [])" class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-100 transition" title="Hủy chọn">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <span class="text-sm font-semibold text-indigo-900">
                            Đã chọn <span class="font-bold text-indigo-700 text-base mx-1">{{ count($selected) }}</span> vai trò
                        </span>
                    </div>
                    <button wire:click="deleteSelected" wire:confirm="Bạn có chắc muốn xóa các vai trò đã chọn? (Super Admin sẽ không bị xóa)" class="flex items-center px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold shadow-sm hover:bg-red-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Xóa
                    </button>
                </div>
            @else
                <div class="p-2 flex gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Tìm kiếm vai trò..." class="block w-full pl-10 pr-3 py-2 border-transparent bg-transparent focus:border-transparent focus:ring-0 text-gray-900 placeholder-gray-400 sm:text-sm h-10 transition">
                    </div>
                    
                    <div class="hidden md:block w-px bg-gray-200 my-1"></div>

                    <div class="relative min-w-[100px]">
                        <select wire:model.live="perPage" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                            <option value="10">Hiển thị: 10</option>
                            <option value="25">Hiển thị: 25</option>
                            <option value="50">Hiển thị: 50</option>
                        </select>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-4 py-4 w-10 text-center">
                        <input type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tên Vai trò</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nhân sự</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Guard</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                    <th class="relative px-6 py-3 text-right"><span class="sr-only">Hành động</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($roles as $role)
                    <tr class="hover:bg-gray-50 transition {{ in_array($role->id, $selected) ? 'bg-indigo-50/40' : '' }}">
                        <td class="px-4 py-4 text-center">
                            @if($role->name !== 'Super Admin')
                                <input type="checkbox" value="{{ $role->id }}" wire:model.live="selected" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            @else
                                <svg class="w-4 h-4 text-gray-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 rounded-lg flex items-center justify-center font-bold text-white shadow-sm
                                    {{ $role->name === 'Super Admin' ? 'bg-red-500' : 'bg-indigo-500' }}">
                                    {{ substr($role->name, 0, 1) }}
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-bold text-gray-900">{{ $role->name }}</div>
                                    @if($role->name === 'Super Admin')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-800">System Core</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                {{ $role->users_count }} tài khoản
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded border">{{ $role->guard_name }}</span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                            {{ $role->created_at->format('d/m/Y') }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.roles.edit', $role->id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold transition">Sửa</a>
                                
                                @if($role->name !== 'Super Admin')
                                    <button wire:confirm="Bạn có chắc muốn xóa vai trò này? Các user thuộc vai trò này sẽ mất quyền." wire:click="delete({{ $role->id }})" class="text-red-600 hover:text-red-900 transition font-bold">
                                        Xóa
                                    </button>
                                @else
                                    <span class="text-gray-300 cursor-not-allowed font-bold" title="Không thể xóa vai trò hệ thống">Xóa</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">
                            Chưa có vai trò nào. Hãy tạo mới hoặc import!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="bg-gray-50 border-t border-gray-200 px-4 py-3 sm:px-6">
        {{ $roles->links() }}
    </div>

    <div x-data="{ show: @entangle('showImportModal') }" x-show="show" style="display: none;" class="relative z-50">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="show = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all w-full max-w-lg">
                    <div class="px-6 py-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="h-6 w-6 text-indigo-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            Import Cấu hình Vai trò (JSON)
                        </h3> 
                        <div class="space-y-4">
                            <p class="text-sm text-gray-500">File JSON cần chứa danh sách Roles và Permissions tương ứng.</p>
                            <label class="block w-full rounded-xl border-2 border-dashed border-gray-300 p-8 text-center hover:bg-gray-50 hover:border-indigo-400 cursor-pointer transition">
                                <span class="text-sm text-gray-600 font-medium" x-text="$wire.importFile ? 'Đã chọn: ' + $wire.importFile.name : 'Click để chọn file .json'"></span>
                                <input type="file" wire:model="importFile" class="hidden" accept=".json">
                            </label>
                            @error('importFile') <p class="text-red-500 text-xs font-semibold">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                        <button type="button" @click="show = false" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Hủy bỏ</button>
                        <button type="button" wire:click="import" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 rounded-lg text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-70">
                            <span wire:loading.remove wire:target="import">Tiến hành Import</span>
                            <span wire:loading wire:target="import">Đang tải...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{ show: @entangle('showPermissionModal') }" x-show="show" style="display: none;" class="relative z-50">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="show = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all w-full max-w-md">
                    
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg mr-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/></svg>
                            </span>
                            Thêm Module Mới
                        </h3>
                        <button @click="show = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>

                    <div class="p-6 space-y-6">
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Tên Module (Tiếng Anh, không dấu) <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live="newModuleName" 
                                    class="w-full p-2 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                                   placeholder="VD: blog, marketing, report...">
                            @error('newModuleName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            
                            @if($newModuleName)
                                <div class="mt-2 text-xs text-gray-500 bg-gray-50 p-2 rounded border border-gray-200">
                                    Sẽ tạo: 
                                    <span class="font-mono text-indigo-600">view_{{ $newModuleName }}</span>, 
                                    <span class="font-mono text-indigo-600">create_{{ $newModuleName }}</span>...
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-3">Chọn các hành động cần tạo:</label>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach(['view' => 'Xem (View)', 'create' => 'Thêm (Create)', 'edit' => 'Sửa (Edit)', 'delete' => 'Xóa (Delete)', 'export' => 'Xuất (Export)'] as $key => $label)
                                    <label class="flex items-center space-x-3 p-2 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition {{ $newModuleActions[$key] ? 'border-indigo-200 bg-indigo-50' : '' }}">
                                        <input type="checkbox" wire:model="newModuleActions.{{ $key }}" class="h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                        <span class="text-sm text-gray-700 select-none">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="show = false" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Hủy bỏ</button>
                        <button type="button" wire:click="createModulePermissions" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 rounded-lg text-sm font-bold text-white hover:bg-indigo-700 shadow-sm transition flex items-center">
                            <span wire:loading.remove wire:target="createModulePermissions">Tạo ngay</span>
                            <span wire:loading wire:target="createModulePermissions" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Xử lý...
                            </span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>