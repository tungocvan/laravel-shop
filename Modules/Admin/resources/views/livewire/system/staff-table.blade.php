<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Danh sách Nhân sự</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý tài khoản, phân quyền và bảo mật hệ thống.</p>
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

            <a href="{{ route('admin.staff.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Thêm nhân viên
            </a>
        </div>
    </div>

    <div class="mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 relative">
            
            <div wire:loading.flex wire:target="search, filterRole, perPage, deleteSelected" 
                 class="absolute inset-0 bg-white/60 z-20 items-center justify-center backdrop-blur-[1px]">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>

            @if(count($selected) > 0)
                <div class="p-3 bg-indigo-50 flex items-center justify-between animate-fade-in">
                    <div class="flex items-center gap-3">
                        <button wire:click="resetSelection" class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-100 transition" title="Hủy chọn">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <span class="text-sm font-semibold text-indigo-900">
                            Đã chọn <span class="font-bold text-indigo-700 text-base mx-1">{{ count($selected) }}</span> nhân viên
                        </span>
                    </div>
                    <button wire:click="deleteSelected" wire:confirm="CẢNH BÁO: Xóa vĩnh viễn các tài khoản này?" 
                            class="flex items-center px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold shadow-sm hover:bg-red-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Xóa tất cả
                    </button>
                </div>
            @else
                <div class="p-2 flex flex-col md:flex-row gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Tìm kiếm tên, email, sđt..." 
                               class="block w-full pl-10 pr-3 py-2 border-transparent bg-transparent focus:border-transparent focus:ring-0 text-gray-900 placeholder-gray-400 sm:text-sm h-10 transition">
                    </div>
                    
                    <div class="hidden md:block w-px bg-gray-200 my-1"></div>

                    <div class="relative min-w-[150px]">
                        <select wire:model.live="filterRole" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                            <option value="">Tất cả vai trò</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>

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
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-4 py-4 w-10 text-center">
                            <input type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Thông tin nhân viên</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vai trò (Role)</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày tạo</th>
                        <th class="relative px-6 py-3 text-right"><span class="sr-only">Hành động</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition duration-150 {{ in_array($user->id, $selected) ? 'bg-indigo-50/40' : '' }}">
                            
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" value="{{ $user->id }}" wire:model.live="selected" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer">
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold border border-indigo-200 text-sm shadow-sm">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-bold text-gray-900">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($user->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold uppercase tracking-wide 
                                            {{ $role->name === 'Super Admin' 
                                                ? 'bg-red-100 text-red-800 border border-red-200' 
                                                : 'bg-blue-50 text-blue-700 border border-blue-200' 
                                            }}">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center">
                                @if($user->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20">
                                        Hoạt động
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 ring-1 ring-inset ring-gray-500/10">
                                        Đã khóa
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-center text-sm text-gray-500">
                                {{ $user->created_at->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.staff.edit', $user->id) }}" class="text-gray-400 hover:text-indigo-600 transition" title="Chỉnh sửa">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    
                                    <button wire:confirm="Xóa nhân viên {{ $user->name }}? Hành động này không thể hoàn tác." wire:click="delete({{ $user->id }})" class="text-gray-400 hover:text-red-600 transition" title="Xóa">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="h-10 w-10 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <p>Chưa có nhân viên nào.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-200 px-4 py-3 sm:px-6">
            {{ $users->links() }}
        </div>
    </div>

    <div x-data="{ show: @entangle('showImportModal') }" x-show="show" style="display: none;" class="relative z-50">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="show = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all w-full max-w-lg">
                    <div class="px-6 py-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="h-6 w-6 text-indigo-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            Import Nhân viên (JSON)
                        </h3>
                        <div class="space-y-4">
                            <div class="bg-amber-50 border border-amber-100 rounded-lg p-3 text-xs text-amber-800">
                                <span class="font-bold">Quy tắc:</span> Email trùng sẽ bị bỏ qua. Mật khẩu mặc định là <code>12345678</code>.
                            </div>
                            <label class="block w-full rounded-xl border-2 border-dashed border-gray-300 p-8 text-center hover:bg-gray-50 hover:border-indigo-400 cursor-pointer transition">
                                <span class="text-sm text-gray-600 font-medium" x-text="$wire.importFile ? 'Đã chọn: ' + $wire.importFile.name : 'Click để chọn file .json'"></span>
                                <input type="file" wire:model="importFile" class="hidden" accept=".json">
                            </label>
                            @error('importFile') <p class="text-red-500 text-xs font-semibold">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                        <button type="button" @click="show = false" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Hủy</button>
                        <button type="button" wire:click="import" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 rounded-lg text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-70">
                            <span wire:loading.remove wire:target="import">Tiến hành Import</span>
                            <span wire:loading wire:target="import">Đang tải...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>