<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden h-full flex flex-col">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h3 class="text-base font-semibold text-gray-900">Đơn hàng mới nhất</h3>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Xem tất cả &rarr;</a>
    </div>

    <div class="flex-1 overflow-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($orders as $order)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-bold text-gray-900">#{{ $order->order_code }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ number_format($order->total) }} ₫</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            {!! $order->status_badge !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-10 text-center text-gray-500 italic">Chưa có đơn hàng nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
