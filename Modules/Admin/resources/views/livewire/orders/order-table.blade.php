<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
    
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Danh sách Đơn hàng</h1>
            <p class="mt-1 text-sm text-gray-500">Theo dõi và xử lý các đơn hàng từ khách hàng.</p>
        </div>
        </div>

    <div class="mb-6 overflow-x-auto pb-1 custom-scrollbar">
        <nav class="flex space-x-1 bg-gray-100/50 p-1 rounded-xl w-max min-w-full md:min-w-0" aria-label="Tabs">
            @foreach([
                'all' => 'Tất cả',
                'pending' => 'Chờ xử lý',
                'processing' => 'Đang xử lý',
                'shipping' => 'Đang giao',
                'completed' => 'Hoàn thành',
                'cancelled' => 'Đã hủy'
            ] as $key => $label)
                <button
                    wire:click="setStatus('{{ $key }}')"
                    class="
                        relative min-w-[100px] px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 focus:outline-none select-none
                        {{ $status === $key 
                            ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-gray-200' 
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' 
                        }}
                    ">
                    {{ $label }}
                    {{-- <span class="ml-1 text-[10px] bg-gray-100 px-1.5 py-0.5 rounded-full text-gray-600">12</span> --}}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 relative">
            
            <div wire:loading.flex wire:target="setStatus, search, perPage, deleteSelected" 
                 class="absolute inset-0 bg-white/60 z-20 items-center justify-center backdrop-blur-[1px]">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>

            @if(count($selected) > 0)
                <div class="p-3 bg-red-50 flex items-center justify-between animate-fade-in">
                    <div class="flex items-center gap-3">
                        <button wire:click="$set('selected', [])" class="p-2 rounded-lg text-red-600 hover:bg-red-100 transition" title="Hủy chọn">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <span class="text-sm font-semibold text-red-900">
                            Đã chọn <span class="font-bold text-red-700 text-base mx-1">{{ count($selected) }}</span> đơn hàng
                        </span>
                    </div>

                    <button 
                        wire:click="deleteSelected" 
                        wire:confirm="CẢNH BÁO: Xóa vĩnh viễn {{ count($selected) }} đơn hàng này? Hành động không thể hoàn tác!" 
                        class="flex items-center px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold shadow-sm hover:bg-red-600 hover:text-white hover:border-red-600 transition"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Xóa vĩnh viễn
                    </button>
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
                            placeholder="Tìm kiếm mã đơn, tên khách, SĐT..." 
                            class="block w-full pl-10 pr-3 py-2 border-transparent bg-transparent focus:border-transparent focus:ring-0 text-gray-900 placeholder-gray-400 sm:text-sm h-10 transition"
                        >
                        @if($search)
                            <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 cursor-pointer">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <div class="hidden md:block w-px bg-gray-200 my-1"></div>

                    <div class="relative min-w-[140px]">
                        <select wire:model.live="perPage" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                            <option value="10">Hiển thị: 10</option>
                            <option value="25">Hiển thị: 25</option>
                            <option value="50">Hiển thị: 50</option>
                            <option value="100">Hiển thị: 100</option>
                        </select>
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mã đơn hàng</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Khách hàng</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Tổng tiền</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày đặt</th>
                        <th scope="col" class="relative px-6 py-3 text-right"><span class="sr-only">Hành động</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition duration-150 {{ in_array($order->id, $selected) ? 'bg-indigo-50/40' : '' }}">
                            
                            <td class="px-4 py-4 text-center">
                                @if(in_array($order->status, ['pending', 'cancelled', 'completed'])) 
                                    {{-- Cho phép chọn các đơn đã xong hoặc hủy hoặc mới tạo --}}
                                    <input type="checkbox" value="{{ $order->id }}" wire:model.live="selected" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer">
                                @else
                                    {{-- Đơn đang xử lý/ship thì hạn chế xóa nhầm --}}
                                    <input type="checkbox" disabled class="h-4 w-4 rounded border-gray-200 text-gray-300 bg-gray-50 cursor-not-allowed" title="Đơn đang xử lý">
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="group flex items-center">
                                    <span class="font-mono font-bold text-indigo-600 group-hover:text-indigo-800 transition">#{{ $order->order_code }}</span>
                                    <span class="ml-2 inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                        {{ $order->items_count }} món
                                    </span>
                                </a>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                                        {{ substr($order->customer_name, 0, 1) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ $order->customer_phone }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-bold text-gray-900">{{ number_format($order->total) }} ₫</div>
                                <div class="text-xs text-gray-500">
                                    @if($order->payment_method === 'cod')
                                        <span class="inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg> COD</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-blue-600"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg> Chuyển khoản</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                {!! $order->status_badge !!}
                                {{-- 
                                    Lưu ý: Model Order cần có Accessor getStatusBadgeAttribute trả về HTML chuẩn Tailwind.
                                    Ví dụ: 
                                    <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Chờ xử lý</span> 
                                --}}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                <div class="font-medium text-gray-900">{{ $order->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $order->created_at->format('H:i') }}</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold hover:underline inline-flex items-center">
                                    Chi tiết
                                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-14 w-14 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900">Không tìm thấy đơn hàng</h3>
                                    <p class="mt-1 text-sm text-gray-500 max-w-xs mx-auto">Thử thay đổi bộ lọc trạng thái hoặc tìm kiếm với từ khóa khác.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="bg-gray-50 border-t border-gray-200 px-4 py-3 sm:px-6">
            {{ $orders->links() }}
        </div>
    </div>
</div>