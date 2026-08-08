<div class="max-w-7xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.orders.index') }}" class="p-2 rounded-lg hover:bg-white hover:shadow-sm border border-transparent hover:border-gray-200 transition text-gray-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    Đơn hàng #{{ $order->order_code }}
                    {!! $order->status_badge !!}
                </h1>
                <p class="text-sm text-gray-500 mt-1">Đặt ngày {{ $order->created_at->format('d/m/Y \l\ú\c H:i') }}</p>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('admin.orders.print', $orderId) }}" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                In hóa đơn
            </a>

            <a href="{{ route('admin.orders.pdf', $orderId) }}" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white shadow-sm hover:bg-indigo-500 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Xuất PDF
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-900">
                    Chi tiết sản phẩm
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Giá</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">SL</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-md border border-gray-200 bg-gray-100">
                                                @if($item->product && $item->product->avatar)
                                                    <img class="h-full w-full object-cover" src="{{ asset('storage/'.$item->product->avatar) }}" alt="">
                                                @else
                                                    <svg class="h-full w-full text-gray-300 p-2" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                                                @if($item->options && is_array($item->options))
                                                    <div class="text-xs text-gray-500 mt-0.5">
                                                        @foreach($item->options as $key => $val)
                                                            <span class="inline-block bg-gray-100 rounded px-1.5 py-0.5 mr-1">{{ $key }}: {{ $val }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-500">
                                        {{ number_format($item->price) }} ₫
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-900 font-medium">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                        {{ number_format($item->total) }} ₫
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                <div class="flex justify-end">
                    <div class="w-full md:w-1/2 space-y-3">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Tạm tính (Subtotal)</span>
                            <span>{{ number_format($order->subtotal) }} ₫</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Phí vận chuyển</span>
                            <span>{{ number_format($order->shipping_fee) }} ₫</span>
                        </div>
                        @if($order->discount > 0)
                            <div class="flex justify-between text-sm text-green-600">
                                <span>Giảm giá</span>
                                <span>- {{ number_format($order->discount) }} ₫</span>
                            </div>
                        @endif
                        <div class="border-t border-gray-100 pt-3 flex justify-between text-base font-bold text-indigo-600">
                            <span>Tổng cộng</span>
                            <span>{{ number_format($order->total) }} ₫</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="space-y-6">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Trạng thái đơn hàng</h3>

                <div class="space-y-4">
                    <div>
                        <select wire:model="newStatus" class="block w-full rounded-md border-0 py-2 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="pending">🟡 Chờ xử lý</option>
                            <option value="processing">🔵 Đang xử lý (Gọi xác nhận)</option>
                            <option value="shipping">🚚 Đang giao hàng</option>
                            <option value="completed">🟢 Hoàn thành</option>
                            <option value="cancelled">🔴 Đã hủy</option>
                        </select>
                    </div>

                    <button wire:click="updateStatus" wire:loading.attr="disabled" class="w-full flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition">
                        <span wire:loading.remove>Cập nhật trạng thái</span>
                        <span wire:loading>Đang xử lý...</span>
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4 border-b pb-2">Lịch sử đơn hàng</h3>

                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach($order->histories as $history)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endif

                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-indigo-50 flex items-center justify-center ring-8 ring-white">
                                                <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            </span>
                                        </div>

                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-sm text-gray-500">
                                                    <span class="font-medium text-gray-900">{{ $history->action }}</span>
                                                    : {{ $history->description }}
                                                </p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-xs text-gray-500">
                                                <time>{{ $history->created_at->format('H:i d/m') }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach

                        <li>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-green-50 flex items-center justify-center ring-8 ring-white">
                                       <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-500">Đơn hàng được tạo thành công</p>
                                    </div>
                                    <div class="whitespace-nowrap text-right text-xs text-gray-500">
                                        <time>{{ $order->created_at->format('H:i d/m') }}</time>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4 border-b pb-2">Thông tin khách hàng</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 w-5 h-5 text-gray-400 flex-shrink-0">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</div>
                            <div class="text-xs text-gray-500">Khách vãng lai</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 w-5 h-5 text-gray-400 flex-shrink-0">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                        </div>
                        <div class="text-sm text-gray-600">{{ $order->customer_phone }}</div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 w-5 h-5 text-gray-400 flex-shrink-0">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <div class="text-sm text-gray-600 leading-relaxed">{{ $order->customer_address }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4 border-b pb-2">Thanh toán</h3>
                <div class="text-sm text-gray-600 space-y-2">
                    <p class="flex justify-between">
                        <span>Phương thức:</span>
                        <span class="font-medium text-gray-900">{{ $order->payment_method_label }}</span>
                    </p>
                    <p class="flex justify-between items-center">
                        <span>Trạng thái:</span>
                        @if($order->status == 'completed')
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Đã thanh toán</span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Chưa thanh toán</span>
                        @endif
                    </p>
                </div>
            </div>

            @if($order->note)
                <div class="bg-yellow-50 rounded-xl shadow-sm ring-1 ring-yellow-600/10 p-4">
                    <h3 class="text-sm font-semibold text-yellow-800 mb-2">Ghi chú từ khách:</h3>
                    <p class="text-sm text-yellow-700 italic">"{{ $order->note }}"</p>
                </div>
            @endif

        </div>
    </div>
</div>
