<div>
    {{-- Chỉ hiển thị khi isOpen = true --}}
    @if($isOpen)
    
        {{-- CONTAINER CHÍNH (Fixed toàn màn hình, Z-Index cực cao) --}}
        <div class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                {{-- 1. LỚP NỀN XÁM (BACKDROP) --}}
                {{-- Thêm transition để mượt mà --}}
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     wire:click="close"
                     aria-hidden="true"></div>

                {{-- Căn giữa cho Desktop --}}
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- 2. HỘP THOẠI (CONTENT) --}}
                {{-- Quan trọng: relative, z-index cao, và text-left --}}
                <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    
                    @if($order)
                        {{-- Header --}}
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-between items-center border-b border-gray-100">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                Đơn hàng #{{ $order->order_code }}
                            </h3>
                            <button wire:click="close" class="text-gray-400 hover:text-gray-500 focus:outline-none p-2">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        {{-- Body --}}
                        <div class="px-4 py-5 sm:p-6 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Thông tin khách hàng --}}
                                <div>
                                    <h4 class="font-bold text-gray-700 mb-3 text-xs uppercase tracking-wide border-b pb-1">Khách hàng</h4>
                                    <div class="text-sm space-y-2">
                                        <p><span class="font-medium text-gray-500">Họ tên:</span> {{ $order->customer_name }}</p>
                                        <p><span class="font-medium text-gray-500">SĐT:</span> {{ $order->customer_phone }}</p>
                                        <p><span class="font-medium text-gray-500">Email:</span> {{ $order->customer_email ?? '---' }}</p>
                                        <p><span class="font-medium text-gray-500">Địa chỉ:</span> {{ $order->customer_address }}</p>
                                    </div>
                                </div>

                                {{-- Thông tin Affiliate --}}
                                <div>
                                    <h4 class="font-bold text-gray-700 mb-3 text-xs uppercase tracking-wide border-b pb-1">Đối tác Affiliate</h4>
                                    <div class="text-sm space-y-2">
                                        <p><span class="font-medium text-gray-500">Đối tác:</span> {{ $order->affiliate->name ?? 'Không có' }}</p>
                                        <p><span class="font-medium text-gray-500">Hoa hồng:</span> <span class="font-bold text-blue-600">{{ number_format($order->commission_amount) }}đ</span></p>
                                        <p><span class="font-medium text-gray-500">Trạng thái:</span> 
                                            <span class="font-bold uppercase text-xs px-2 py-0.5 rounded
                                                {{ $order->commission_status === 'approved' ? 'bg-green-100 text-green-700' : 
                                                  ($order->commission_status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                                {{ $order->commission_status }}
                                            </span>
                                        </p>
                                        {{-- @if($order->commission_status === 'rejected' && $order->rejection_reason)
                                        <div>
                                            <h3 class="text-sm font-bold text-red-800">
                                                Lý do từ chối
                                            </h3>
                                            <div class="mt-1 text-sm text-red-700 italic">
                                                "{{ $order->rejection_reason }}"
                                            </div>
                                        </div>
                                        @endif --}}
                                    </div>
                                </div>
                                
                            </div>

                            {{-- Bảng sản phẩm --}}
                            <div class="mt-8">
                                <h4 class="font-bold text-gray-700 mb-3 text-xs uppercase tracking-wide">Chi tiết sản phẩm</h4>
                                <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tên SP</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($order->items as $item)
                                            <tr>
                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $item->product_name }}</td>
                                                <td class="px-3 py-2 text-sm text-center text-gray-500">{{ $item->quantity }}</td>
                                                <td class="px-3 py-2 text-sm text-right font-medium">{{ number_format($item->total) }}đ</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="2" class="px-3 py-2 text-right font-bold text-gray-700">Tổng đơn:</td>
                                            <td class="px-3 py-2 text-right font-black text-blue-600">{{ number_format($order->total) }}đ</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- Footer --}}
                        {{-- ... Phần trên giữ nguyên ... --}}

                    {{-- FOOTER ACTIONS --}}
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 border-t border-gray-100">
                        
                        @if($order->commission_status === 'pending')
                            
                            {{-- TRẠNG THÁI 1: Đang nhập lý do từ chối --}}
                            @if($isRejecting)
                                <div class="w-full bg-red-50 border border-red-100 rounded-lg p-4 animate-fade-in-up">
                                    <label class="block text-sm font-bold text-red-700 mb-2">Tại sao bạn từ chối hoa hồng này?</label>
                                    <textarea wire:model="rejectReason" 
                                              rows="2" 
                                              placeholder="Nhập lý do (VD: Đơn hàng hoàn trả, Gian lận...)"
                                              class="w-full border-red-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"></textarea>
                                    @error('rejectReason') <span class="text-red-600 text-xs font-bold">{{ $message }}</span> @enderror
                                    
                                    <div class="flex justify-end gap-3 mt-3">
                                        <button wire:click="cancelReject" class="px-3 py-1.5 text-xs font-bold text-gray-600 hover:bg-white rounded border border-transparent hover:border-gray-300 transition">
                                            Quay lại
                                        </button>
                                        <button wire:click="confirmReject" class="px-3 py-1.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded shadow-sm transition">
                                            Xác nhận Từ chối
                                        </button>
                                    </div>
                                </div>
                            
                            {{-- TRẠNG THÁI 2: Hiển thị nút mặc định --}}
                            @else
                                <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                                    <button type="button" wire:click="close" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:text-sm">
                                        Đóng
                                    </button>
                                    
                                    <button type="button" wire:click="startReject" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-100 text-base font-bold text-red-700 hover:bg-red-200 focus:outline-none sm:text-sm">
                                        Từ chối
                                    </button>

                                    <button type="button" wire:click="approve" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-bold text-white hover:bg-green-700 focus:outline-none sm:text-sm">
                                        Duyệt Hoa Hồng
                                    </button>
                                </div>
                            @endif

                        @else
                            {{-- Nếu đã duyệt/hủy rồi thì chỉ hiện nút Đóng --}}
                            <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                                @if($order->commission_status === 'rejected' && $order->rejection_reason)
                                    <div class="mr-auto text-left">
                                        <span class="text-xs font-bold text-red-500 uppercase">Lý do từ chối:</span>
                                        <p class="text-sm text-gray-600 italic">"{{ $order->rejection_reason }}"</p>
                                    </div>
                                @endif
                                <button type="button" wire:click="close" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:text-sm">
                                    Đóng
                                </button>
                            </div>
                        @endif
                    </div>
                    @else
                        {{-- Loading State (Nếu dữ liệu đang tải) --}}
                        <div class="p-10 text-center">
                            <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 text-sm">Đang tải dữ liệu...</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>