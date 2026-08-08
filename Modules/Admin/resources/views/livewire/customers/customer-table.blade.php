<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Khách hàng</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý hồ sơ, lịch sử mua hàng và chăm sóc khách hàng.</p>
        </div>
        
        <div class="flex gap-2">
            <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export Excel
            </button>
            <a href="{{ route('admin.customers.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Thêm khách hàng
            </a>
        </div>
    </div>

    <div class="mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 relative">
            
            <div wire:loading.flex wire:target="search, filterStatus, perPage, deleteSelected, toggleStatus"
                 class="absolute inset-0 bg-white/60 z-20 items-center justify-center backdrop-blur-[1px]">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>

            @if(count($selected) > 0)
                <div class="p-3 bg-indigo-50 flex items-center justify-between animate-fade-in">
                    <div class="flex items-center gap-3">
                        <button wire:click="$set('selected', [])" class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-100 transition" title="Hủy chọn">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <span class="text-sm font-semibold text-indigo-900">
                            Đã chọn <span class="font-bold text-indigo-700 text-base mx-1">{{ count($selected) }}</span> khách hàng
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button wire:click="deleteSelected" wire:confirm="Xóa {{ count($selected) }} khách hàng này? Dữ liệu lịch sử đơn hàng vẫn được giữ (Soft Delete)." class="flex items-center px-3 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-medium shadow-sm hover:bg-red-50 transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Xóa tài khoản
                        </button>
                    </div>
                </div>

            @else
                <div class="p-2 flex flex-col md:flex-row gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input 
                            wire:model.live.debounce.300ms="search" 
                            type="text" 
                            placeholder="Tìm tên, email, số điện thoại..." 
                            class="block w-full pl-10 pr-3 py-2 border-transparent bg-transparent focus:border-transparent focus:ring-0 text-gray-900 placeholder-gray-400 sm:text-sm h-10 transition"
                        >
                        @if($search)
                            <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 cursor-pointer">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <div class="hidden md:block w-px bg-gray-200 my-1"></div>

                    <div class="flex gap-2">
                        <div class="relative min-w-[140px]">
                            <select wire:model.live="filterStatus" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                                <option value="">⚡ Trạng thái</option>
                                <option value="active">Hoạt động</option>
                                <option value="inactive">Đã khóa</option>
                            </select>
                        </div>

                        <div class="relative min-w-[100px]">
                            <select wire:model.live="perPage" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                                <option value="10">Hiển thị: 10</option>
                                <option value="25">Hiển thị: 25</option>
                                <option value="50">Hiển thị: 50</option>
                                <option value="100">Hiển thị: 100</option>
                            </select>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto min-h-[400px]">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th scope="col" class="px-4 py-4 w-10 text-center">
                            <input type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Khách hàng</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Liên hệ</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Thống kê mua sắm</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày tham gia</th>
                        <th scope="col" class="relative px-6 py-3 text-right"><span class="sr-only">Hành động</span></th>
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
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <img class="h-10 w-10 rounded-full object-cover border border-gray-200" src="{{ $user->avatar_url }}" alt="">
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-bold text-gray-900">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->phone)
                                    <div class="text-sm text-gray-900 font-mono">{{ $user->phone }}</div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Chưa cập nhật</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-bold text-gray-900">{{ number_format($user->orders_sum_total ?? 0) }} ₫</div>
                                <div class="text-xs text-gray-500">{{ $user->orders_count ?? 0 }} đơn hàng</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button wire:click="toggleStatus({{ $user->id }})" 
                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $user->is_active ? 'bg-green-500' : 'bg-gray-200' }}"
                                    title="{{ $user->is_active ? 'Đang hoạt động' : 'Đã bị khóa' }}">
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $user->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                {{ $user->created_at->format('d/m/Y') }}
                            </td> 

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.customers.show', $user->id) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold hover:underline">Chi tiết</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-14 w-14 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900">Không tìm thấy khách hàng</h3>
                                    <p class="mt-1 text-sm text-gray-500">Thử tìm kiếm với từ khóa khác.</p>
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
</div>