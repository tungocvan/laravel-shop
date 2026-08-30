<div class="space-y-6">
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div>
                <h2 class="text-xl font-black text-gray-800 uppercase tracking-tight">Đối soát hoa hồng Hybrid</h2>
                <p class="mt-1 text-sm text-gray-500">Theo dõi, lọc và xử lý hoa hồng theo đơn hàng.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[auto_auto_minmax(14rem,1fr)_auto_auto] gap-3 w-full xl:w-auto">
                <select wire:model.live="levelFilter" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả cấp bậc</option>
                    @foreach($levels as $lv)
                        <option value="{{ $lv->id }}">{{ $lv->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="statusFilter" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="pending">Chờ duyệt</option>
                    <option value="approved">Đã duyệt</option>
                    <option value="rejected">Từ chối</option>
                </select>

                <div class="relative min-w-0">
                    <input type="search" wire:model.live.debounce.300ms="search"
                           placeholder="Tìm mã đơn hàng..."
                           class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <div class="pointer-events-none absolute left-3 top-3 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                </div>

                <select wire:model.live="perPage" aria-label="Số dòng mỗi trang" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    @foreach([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}">{{ $size }}/trang</option>
                    @endforeach
                </select>

                <button type="button" wire:click="resetFilters" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    Xóa bộ lọc
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-x-auto">
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
                    <tr wire:key="comm-{{ $item->id }}" class="hover:bg-indigo-50/30 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button type="button" wire:click="openDetail({{ $item->id }})" class="text-indigo-600 font-bold hover:underline flex items-center gap-1 group text-sm">
                                {{ $item->order_code }}
                            </button>
                            <div class="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full border border-gray-200 bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600" aria-hidden="true">
                                    {{ strtoupper(mb_substr($item->affiliate->name ?? 'N', 0, 1)) }}
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-bold text-gray-900">{{ $item->affiliate->name ?? 'Unknown' }}</div>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 font-black uppercase tracking-tighter">
                                        {{ $item->items->first()->affiliate_level_snapshot ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-black text-indigo-600">{{ number_format($item->commission_amount) }}đ</div>
                            <div class="text-[9px] text-gray-400 font-medium">
                                {{ number_format($item->items->sum('commission_amount')) }}đ + {{ number_format($item->items->sum('commission_fixed_amount')) }}đ cố định
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
                            <button type="button" wire:click="openDetail({{ $item->id }})" class="text-gray-400 hover:text-indigo-600 transition p-2 bg-gray-50 rounded-lg" aria-label="Xem chi tiết {{ $item->order_code }}">
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

    @if($commissions->hasPages())
        <div class="mt-4">{{ $commissions->links('Website::vendor.pagination.admin-affiliate') }}</div>
    @endif

    @if($isModalOpen && $selectedOrder)
        @teleport('body')
            <div class="fixed inset-0 z-[999] overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

                    <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden transform transition-all">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 uppercase">Đối soát đơn: #{{ $selectedOrder->order_code }}</h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Hạng tại thời điểm mua: {{ $selectedOrder->items->first()->affiliate_level_snapshot ?? 'N/A' }}</p>
                            </div>
                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600" aria-label="Đóng"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                        </div>

                        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                            <div class="flex items-center justify-between p-5 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl border border-indigo-100">
                                <div>
                                    <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-1">Đối tác thụ hưởng</p>
                                    <p class="text-base font-bold text-gray-900">{{ $selectedOrder->affiliate->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $selectedOrder->affiliate->email }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mb-1">Tổng hoa hồng nhận</p>
                                    <p class="text-2xl font-black text-indigo-700">{{ number_format($selectedOrder->commission_amount) }}đ</p>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Phân bổ hoa hồng từng sản phẩm</h4>
                                <div class="border border-gray-100 rounded-2xl overflow-x-auto shadow-sm">
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
                                                    <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-black">{{ $item->commission_rate }}%</span></td>
                                                    <td class="px-4 py-3 text-center font-bold text-gray-600 text-[11px]">+{{ number_format($item->commission_fixed_amount) }}đ</td>
                                                    <td class="px-4 py-3 text-right font-black text-gray-900">{{ number_format($item->commission_amount + ($item->commission_fixed_amount * $item->quantity)) }}đ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if($showRejectForm)
                                <div class="p-5 bg-red-50 border border-red-100 rounded-2xl">
                                    <label class="block text-[10px] font-black text-red-700 uppercase tracking-widest mb-2">Lý do từ chối chi trả:</label>
                                    <textarea wire:model="rejectionReason" class="w-full rounded-xl border border-red-400 bg-white p-3 text-sm text-gray-900 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" rows="3" placeholder="VD: Đơn hàng bị hoàn trả, khách hàng từ chối nhận..."></textarea>
                                    @error('rejectionReason') <span class="block text-[10px] text-red-600 font-bold mt-1">{{ $message }}</span> @enderror
                                    <div class="flex gap-2 mt-4">
                                        <button type="button" wire:click="reject" wire:loading.attr="disabled" class="flex-1 bg-red-600 text-white py-2.5 rounded-xl font-bold text-sm hover:bg-red-700 disabled:opacity-60">Xác nhận từ chối</button>
                                        <button type="button" wire:click="$set('showRejectForm', false)" class="px-6 py-2.5 bg-white border border-gray-300 rounded-xl font-bold text-sm text-gray-600">Hủy</button>
                                    </div>
                                </div>
                            @endif

                            @if($selectedOrder->commission_status === 'rejected')
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 flex gap-3">
                                    <div class="text-sm">
                                        <p class="font-bold text-gray-500 uppercase text-[10px] tracking-widest">Đã từ chối chi trả</p>
                                        <p class="text-gray-700 italic mt-1 font-medium">“{{ $selectedOrder->rejection_reason }}”</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap justify-end gap-3">
                            @if($selectedOrder->commission_status === 'pending' && !$showRejectForm)
                                <button type="button" wire:click="approve({{ $selectedOrder->id }})" wire:confirm="Xác nhận duyệt chi trả và cập nhật thăng hạng cho đối tác này?" wire:loading.attr="disabled" class="px-8 py-2.5 bg-green-600 text-white rounded-xl font-bold text-sm hover:bg-green-700 disabled:opacity-60">Duyệt Hoa Hồng</button>
                                <button type="button" wire:click="$set('showRejectForm', true)" class="px-6 py-2.5 bg-white border border-red-200 text-red-600 rounded-xl font-bold text-sm hover:bg-red-50">Từ chối</button>
                            @endif
                            <button type="button" wire:click="closeModal" class="px-6 py-2.5 bg-white border border-gray-300 rounded-xl font-bold text-sm text-gray-700">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
