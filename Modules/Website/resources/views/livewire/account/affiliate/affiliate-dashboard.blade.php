<div class="space-y-8 font-sans">

    {{-- 1. HERO SECTION: CÔNG CỤ TẠO LINK --}}
    <div class="bg-gradient-to-r from-blue-900 to-blue-700 rounded-3xl p-8 md:p-12 text-white shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 opacity-10 pointer-events-none">
            <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
        </div>

        <div class="relative z-10 max-w-3xl">
            <h1 class="text-3xl font-black tracking-tight mb-4">Chương Trình Đối Tác FlexBiz</h1>
            <p class="text-blue-100 text-lg mb-8">Chia sẻ link sản phẩm và nhận ngay hoa hồng <span class="font-bold text-yellow-300 italic">lên đến 20%</span> cho mỗi đơn hàng thành công.</p>

            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-2 rounded-2xl flex flex-col md:flex-row gap-2"
                 x-data="{
                     link: '{{ $referralLink }}',
                     copy() {
                        navigator.clipboard.writeText(this.link);
                        window.dispatchEvent(new CustomEvent('notify', {detail: {type: 'success', message: 'Đã sao chép link!'}}));
                     }
                 }">
                <div class="flex-1 flex items-center bg-white rounded-xl px-4 py-3">
                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                    <input type="text" x-model="link" readonly class="w-full bg-transparent border-none focus:ring-0 text-gray-800 font-bold text-sm truncate">
                </div>
                <button @click="copy()" class="bg-yellow-400 hover:bg-yellow-300 text-blue-900 font-bold px-8 py-3 rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                    Sao chép
                </button>
            </div>
            <p class="mt-3 text-sm text-blue-200">💡 Mẹo: Bạn có thể thêm <code class="bg-white/20 px-1 rounded">?ref={{ $referralCode }}</code> vào bất kỳ link sản phẩm nào.</p>
        </div>
    </div>

    {{-- 2. LỊCH SỬ HOA HỒNG --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
            <h3 class="text-lg font-bold text-gray-900">Lịch sử giới thiệu</h3>
            <div class="flex p-1 bg-gray-100 rounded-xl">
                @foreach(['all' => 'Tất cả', 'approved' => 'Đã nhận', 'pending' => 'Đang chờ', 'rejected' => 'Đã hủy'] as $key => $label)
                    <button wire:click="$set('statusFilter', '{{ $key }}')"
                            class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $statusFilter === $key ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Mã đơn</th>
                        <th class="px-6 py-4 text-right text-gray-400">Giá trị đơn</th>
                        <th class="px-6 py-4 text-right">Hoa hồng</th>
                        <th class="px-6 py-4">Trạng thái</th>
                        <th class="px-6 py-4 text-center">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($commissions as $order)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <span class="font-bold text-gray-900">#{{ $order->order_code }}</span>
                                <div class="text-[10px] text-gray-400 font-normal mt-0.5">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-500 font-medium">
                                {{ number_format($order->total) }}đ
                            </td>
                            <td class="px-6 py-4 text-right font-black {{ $order->commission_status === 'rejected' ? 'text-gray-400 line-through' : 'text-green-600' }}">
                                +{{ number_format($order->commission_amount) }}đ
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $badgeClass = match($order->commission_status) {
                                        'approved' => 'bg-green-100 text-green-800 border-green-200',
                                        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        default => 'bg-red-100 text-red-800 border-red-200',
                                    };
                                    $statusLabel = match($order->commission_status) {
                                        'approved' => 'Đã duyệt',
                                        'pending' => 'Chờ duyệt',
                                        default => 'Đã hủy',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="openOrderModal({{ $order->id }})"
                                        class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">Chưa có dữ liệu đơn hàng giới thiệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $commissions->links() }}</div>
    </div>

    {{-- 3. MODAL CHI TIẾT HOA HỒNG ĐA TẦNG --}}
    @if($isModalOpen && $selectedOrder)
        @teleport('body')
            <div class="fixed inset-0 z-[99999]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all w-full max-w-lg">
                            
                            {{-- Modal Header --}}
                            <div class="bg-white px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                                <div>
                                    <h3 class="text-xl font-black text-gray-900">Chi tiết thu nhập</h3>
                                    <p class="text-xs text-gray-400 mt-1 font-mono uppercase tracking-widest">Đơn hàng: #{{ $selectedOrder->order_code }}</p>
                                </div>
                                <button wire:click="closeModal" class="p-2 rounded-full bg-gray-50 text-gray-400 hover:text-gray-600 transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                            </div>

                            {{-- Modal Body --}}
                            <div class="px-6 py-6 space-y-6">
                                {{-- Alert Status --}}
                                @if($selectedOrder->commission_status === 'rejected')
                                    <div class="bg-red-50 border border-red-100 p-4 rounded-2xl flex gap-3">
                                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                        <div class="text-sm">
                                            <p class="font-bold text-red-800 uppercase text-[10px]">Đã từ chối chi trả</p>
                                            <p class="text-red-700 mt-1 italic leading-relaxed">"{{ $selectedOrder->rejection_reason ?: 'Không đáp ứng điều kiện ghi nhận đơn hàng.' }}"</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Bảng kê Snapshot Hoa hồng từng món --}}
                                <div>
                                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-3">Phân bổ hoa hồng sản phẩm</h4>
                                    <div class="space-y-3">
                                        @foreach($selectedOrder->items as $item)
                                            <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 border border-gray-100">
                                                <div class="flex-1 pr-4">
                                                    <p class="text-sm font-bold text-gray-800 line-clamp-1">{{ $item->product_name }}</p>
                                                    <p class="text-[10px] text-gray-400 mt-1 uppercase font-medium">SL: {{ $item->quantity }} • Tỷ lệ: <span class="text-blue-600 font-bold">{{ $item->commission_rate }}%</span></p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-black text-gray-900">+{{ number_format($item->commission_amount) }}đ</p>
                                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ number_format($item->price) }}đ/sp</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Tổng kết tài chính --}}
                                <div class="bg-gradient-to-br from-blue-900 to-indigo-800 p-6 rounded-3xl text-white shadow-xl shadow-blue-200">
                                    <div class="flex justify-between items-center text-blue-200 text-xs mb-1">
                                        <span class="font-medium uppercase tracking-widest">Doanh thu đơn</span>
                                        <span class="font-bold">{{ number_format($selectedOrder->total) }}đ</span>
                                    </div>
                                    <div class="h-px bg-white/10 my-4"></div>
                                    <div class="flex justify-between items-end">
                                        <div>
                                            <p class="text-[10px] font-black text-blue-300 uppercase tracking-[0.2em]">Thu nhập bạn nhận</p>
                                            <p class="text-3xl font-black text-yellow-400 mt-1 tracking-tight">+{{ number_format($selectedOrder->commission_amount) }}đ</p>
                                        </div>
                                        <div class="text-[10px] text-blue-200/60 font-medium italic">Bao gồm VAT (nếu có)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50/50 px-6 py-4 text-center">
                                <button wire:click="closeModal" class="w-full py-3 bg-white border border-gray-200 rounded-2xl font-bold text-gray-600 hover:bg-gray-50 transition shadow-sm">Đóng cửa sổ</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>