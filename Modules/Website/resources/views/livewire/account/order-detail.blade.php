<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Chi tiết đơn hàng #{{ $order->order_code }}</h1>
        <a href="{{ route('account.orders') }}" class="text-blue-600 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Quay lại
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Thông tin giao hàng</h3>
            <div class="space-y-2 text-sm">
                <p><span class="font-medium text-gray-600">Người nhận:</span> {{ $order->customer_name }}</p>
                <p><span class="font-medium text-gray-600">Điện thoại:</span> {{ $order->customer_phone }}</p>
                <p><span class="font-medium text-gray-600">Email:</span> {{ $order->customer_email ?? 'Không có' }}</p>
                <p><span class="font-medium text-gray-600">Địa chỉ:</span> {{ $order->customer_address }}</p>
                <p><span class="font-medium text-gray-600">Ngày đặt:</span> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                @if($order->note)
                    <p class="text-gray-500 italic mt-2">"{{ $order->note }}"</p>
                @endif
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Thanh toán</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Trạng thái:</span>
                    <span class="font-bold uppercase">{{ $order->status }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Phương thức:</span>
                    <span>COD (Thanh toán khi nhận hàng)</span>
                </div>
                <div class="border-t my-2"></div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Tạm tính:</span>
                    <span>{{ number_format($order->subtotal) }}đ</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Giảm giá:</span>
                    <span>-{{ number_format($order->discount) }}đ</span>
                </div>
                <div class="flex justify-between text-lg font-bold text-blue-600 mt-2">
                    <span>Tổng cộng:</span>
                    <span>{{ number_format($order->total) }}đ</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-gray-900">Sản phẩm đã mua</h3>
        </div>
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Sản phẩm</th>
                    <th class="px-6 py-3 text-center">Giá</th>
                    <th class="px-6 py-3 text-center">Số lượng</th>
                    <th class="px-6 py-3 text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">
                            {{ $item->product_name }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            {{ number_format($item->price) }}đ
                        </td>
                        <td class="px-6 py-4 text-center">
                            x{{ $item->quantity }}
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-gray-900">
                            {{ number_format($item->total) }}đ
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
