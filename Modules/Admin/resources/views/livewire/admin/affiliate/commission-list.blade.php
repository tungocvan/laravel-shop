<div class="space-y-6">
    {{-- 1. Header & Filters --}}
    <div class="flex flex-col lg:flex-row justify-between gap-4 items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-200">
        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tight">Đối soát hoa hồng Hybrid</h2>
        
        <div class="flex flex-wrap gap-3 w-full lg:w-auto">
            <select wire:model.live="levelFilter" class="border-gray-200 rounded-xl text-xs font-bold focus:ring-blue-500">
                <option value="all">Tất cả cấp bậc</option>
                @foreach($levels as $lv)
                    <option value="{{ $lv->id }}">{{ $lv->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="border-gray-300 rounded-xl text-xs font-bold focus:ring-blue-500">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">Chờ duyệt (Pending)</option>
                <option value="approved">Đã duyệt (Approved)</option>
                <option value="rejected">Đã hủy (Rejected)</option>
            </select>
            
            <div class="relative flex-1 sm:w-64">
                <input type="text" wire:model.live.debounce.300ms="search" 
                       placeholder="Tìm mã đơn hàng..." 
                       class="w-full border-gray-200 rounded-xl text-xs pl-10 focus:ring-blue-500">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Data Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50/50 text-gray-500 font-bold text-[10px] uppercase tracking-widest">
                <tr>
                    <th class="px-6 py-4">Mã Đơn</th>
                    <th class="px-6 py-4">Đối tác & Hạng</th>
                    <th class="px-6 py-4 text-right">Hoa hồng Hybrid</th>
                    <th class="px-6 py-4">Trạng thái</th>
                    <th class="px-6 py-4 text-right"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($commissions as $item)
                    <tr wire:key="comm-{{ $item->id }}" class="hover:bg-blue-50/30 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click="openDetail({{ $item->id }})" 
                                    class="text-blue-600 font-bold hover:underline flex items-center gap-1 group text-sm">
                                {{ $item->order_code }}
                            </button>
                            <div class="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img class="h-8 w-8 rounded-full border border-gray-200" src="https://ui-avatars.com/api/?name={{ urlencode($item->affiliate->name ?? 'N/A') }}&background=random" alt="">
                                <div class="ml-3">
                                    <div class="text-sm font-bold text-gray-900">{{ $item->affiliate->name ?? 'Unknown' }}</div>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 font-black uppercase tracking-tighter">
                                        {{ $item->items->first()->affiliate_level_snapshot ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-black text-blue-600">{{ number_format($item->commission_amount) }}đ</div>
                            <div class="text-[9px] text-gray-400 font-medium">
                                ({{ number_format($item->items->sum('commission_amount')) }}% + {{ number_format($item->items->sum('commission_fixed_amount')) }} VNĐ)
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($item->commission_status === 'approved')
                                <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-full bg-green-100 text-green-700 border border-green-200">Đã duyệt</span>
                            @elseif($item->commission_status === 'rejected')
                                <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-full bg-red-100 text-red-700 border border-red-200">Từ chối</span>
                            @else
                                <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-full bg-yellow-100 text-yellow-700 border border-yellow-200">Chờ duyệt</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <button wire:click="openDetail({{ $item->id }})" class="text-gray-400 hover:text-blue-600 transition p-2 bg-gray-50 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">Không tìm thấy dữ liệu đối soát.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $commissions->links() }}</div>

    {{-- 3. MODAL CHI TIẾT & ĐỐI SOÁT HYBRID --}}
    @if($isModalOpen && $selectedOrder)
        @teleport('body')
            <div class="fixed inset-0 z-[999] overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

                    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden transform transition-all">
                        {{-- Header --}}
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 uppercase">Đối soát đơn: #{{ $selectedOrder->order_code }}</h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Hạng tại thời điểm mua: {{ $selectedOrder->items->first()->affiliate_level_snapshot ?? 'N/A' }}</p>
                            </div>
                            <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                        </div>

                        {{-- Body --}}
                        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                            
                            {{-- Thông tin đối tác --}}
                            <div class="flex items-center justify-between p-5 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-100">
                                <div>
                                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">Đối tác thụ hưởng</p>
                                    <p class="text-base font-bold text-gray-900">{{ $selectedOrder->affiliate->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $selectedOrder->affiliate->email }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">Tổng hoa hồng nhận</p>
                                    <p class="text-2xl font-black text-blue-700">{{ number_format($selectedOrder->commission_amount) }}đ</p>
                                </div>
                            </div>

                            {{-- Bảng kê Hybrid Snapshot --}}
                            <div>
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Phân bổ hoa hồng từng sản phẩm</h4>
                                <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50 text-gray-500 font-bold text-[10px] uppercase">
                                            <tr>
                                                <th class="px-4 py-2 text-left">Sản phẩm</th>
                                                <th class="px-4 py-2 text-center">Tỷ lệ</th>
                                                <th class="px-4 py-2 text-center">Tiền mặt</th>
                                                <th class="px-4 py-2 text-right">Tổng nhận</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($selectedOrder->items as $item)
                                                <tr>
                                                    <td class="px-4 py-3">
                                                        <div class="font-bold text-gray-800 text-xs">{{ $item->product_name }}</div>
                                                        <div class="text-[9px] text-gray-400 uppercase">Giá: {{ number_format($item->price) }}đ x {{ $item->quantity }}</div>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-black">
                                                            {{ $item->commission_rate }}%
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center font-bold text-gray-600 text-[11px]">
                                                        +{{ number_format($item->commission_fixed_amount) }}đ
                                                    </td>
                                                    <td class="px-4 py-3 text-right font-black text-gray-900">
                                                        {{ number_format($item->commission_amount + ($item->commission_fixed_amount * $item->quantity)) }}đ
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Xử lý Từ chối --}}
                            @if($showRejectForm)
                                <div class="p-5 bg-red-50 border border-red-100 rounded-2xl">
                                    <label class="block text-[10px] font-black text-red-700 uppercase tracking-widest mb-2">Lý do từ chối chi trả:</label>
                                    <textarea wire:model="rejectionReason" class="w-full border-red-200 rounded-xl text-sm p-3 focus:ring-red-500" rows="3" placeholder="VD: Đơn hàng bị hoàn trả, khách hàng từ chối nhận..."></textarea>
                                    @error('rejectionReason') <span class="text-[10px] text-red-600 font-bold mt-1">{{ $message }}</span> @enderror
                                    <div class="flex gap-2 mt-4">
                                        <button wire:click="reject" class="flex-1 bg-red-600 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-red-700 transition shadow-lg shadow-red-200">Xác nhận từ chối</button>
                                        <button wire:click="$set('showRejectForm', false)" class="px-6 py-2.5 bg-white border border-gray-200 rounded-xl font-bold text-sm text-gray-600">Hủy</button>
                                    </div>
                                </div>
                            @endif

                            @if($selectedOrder->commission_status === 'rejected')
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 flex gap-3">
                                    <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                    <div class="text-sm">
                                        <p class="font-bold text-gray-500 uppercase text-[10px] tracking-widest">Đã từ chối chi trả</p>
                                        <p class="text-gray-700 italic mt-1 font-medium">"{{ $selectedOrder->rejection_reason }}"</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Footer Actions --}}
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                            @if($selectedOrder->commission_status === 'pending' && !$showRejectForm)
                                <button wire:click="approve({{ $selectedOrder->id }})" 
                                        wire:confirm="Xác nhận duyệt chi trả và cập nhật thăng hạng cho đối tác này?"
                                        class="px-8 py-2.5 bg-green-600 text-white rounded-xl font-bold text-sm hover:bg-green-700 shadow-xl shadow-green-100 transition">
                                    Duyệt Hoa Hồng
                                </button>
                                <button wire:click="$set('showRejectForm', true)" class="px-6 py-2.5 bg-white border border-red-200 text-red-600 rounded-xl font-bold text-sm hover:bg-red-50 transition">
                                    Từ chối
                                </button>
                            @endif
                            <button wire:click="closeModal" class="px-6 py-2.5 bg-white border border-gray-300 rounded-xl font-bold text-sm text-gray-700">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>