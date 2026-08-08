<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 pb-10">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
            <div class="relative">
                <img class="h-24 w-24 rounded-full object-cover border-4 border-gray-50 shadow-sm"
                     src="{{ $user->avatar_url }}" alt="{{ $user->name }}">
                <span class="absolute bottom-1 right-1 h-5 w-5 rounded-full border-2 border-white {{ $user->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
            </div>

            <div class="flex-1 text-center md:text-left">
                <div class="flex flex-col md:flex-row md:items-center gap-2 mb-1">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                    @if($user->orders_sum_total > 10000000)
                        <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                            👑 Khách hàng VIP
                        </span>
                    @else
                         <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                            Thành viên
                        </span>
                    @endif
                </div>

                <div class="text-sm text-gray-500 space-y-1">
                    <p class="flex items-center justify-center md:justify-start gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $user->email }}
                    </p>
                    <p class="flex items-center justify-center md:justify-start gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $user->phone ?? 'Chưa cập nhật SĐT' }}
                    </p>
                </div>
            </div>

            <div class="flex gap-4 border-t md:border-t-0 md:border-l border-gray-100 pt-4 md:pt-0 md:pl-6">
                <div class="text-center px-4">
                    <div class="text-2xl font-bold text-indigo-600">{{ $user->orders_count }}</div>
                    <div class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Đơn hàng</div>
                </div>
                <div class="text-center px-4 border-l border-gray-100">
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($user->orders_sum_total / 1000000, 1) }}M</div>
                    <div class="text-xs text-gray-500 uppercase font-semibold tracking-wide">Chi tiêu (VNĐ)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 flex space-x-1 bg-gray-100/80 p-1 rounded-xl w-max max-w-full overflow-x-auto">
        <button wire:click="$set('activeTab', 'info')"
            class="px-5 py-2.5 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'info' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
            Thông tin cá nhân
        </button>
        <button wire:click="$set('activeTab', 'addresses')"
            class="px-5 py-2.5 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'addresses' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
            Sổ địa chỉ
        </button>
        <button wire:click="$set('activeTab', 'orders')"
            class="px-5 py-2.5 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'orders' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
            Lịch sử đơn hàng
        </button>
    </div>

    <div class="min-h-[400px]">

        @if($activeTab === 'info')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 animate-fade-in">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Cập nhật thông tin</h3>
                    <form wire:submit="updateProfile" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                                <input type="text" wire:model="name" class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all">
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" wire:model="email" class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all">
                                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                                <input type="text" wire:model="phone" class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                                <select wire:model="is_active" class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all">
                                    <option value="1">Đang hoạt động</option>
                                    <option value="0">Khóa tài khoản</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">
                                Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-fit">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Bảo mật</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Đặt lại mật khẩu mới</label>
                            <input type="password" wire:model="new_password" placeholder="Nhập mật khẩu mới..." class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all">
                            <p class="text-xs text-gray-500 mt-1">Chỉ nhập nếu bạn muốn đổi mật khẩu cho khách.</p>
                        </div>
                        <button wire:click="updateProfile" class="w-full bg-gray-50 text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-100 transition">
                            Cập nhật mật khẩu
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'addresses')
            <div class="animate-fade-in">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Danh sách địa chỉ</h3>
                    <button wire:click="openAddressModal" class="text-sm bg-indigo-50 text-indigo-700 px-3 py-2 rounded-lg font-bold hover:bg-indigo-100 transition">
                        + Thêm địa chỉ mới
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($addresses as $addr)
                        <div class="bg-white rounded-xl border border-gray-200 p-5 relative group hover:shadow-md transition">
                            @if($addr->is_default)
                                <span class="absolute top-3 right-3 bg-green-100 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-green-200">Mặc định</span>
                            @endif

                            <div class="font-bold text-gray-900 mb-1">{{ $addr->name }}</div>
                            <div class="text-sm text-gray-600 mb-3">{{ $addr->phone }}</div>
                            <div class="text-sm text-gray-500 h-10 line-clamp-2">{{ $addr->address }}, {{ $addr->city }}</div>

                            <div class="mt-4 flex gap-3 border-t border-gray-50 pt-3">
                                <button wire:click="openAddressModal({{ $addr->id }})" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Sửa</button>
                                <button wire:confirm="Xóa địa chỉ này?" wire:click="deleteAddress({{ $addr->id }})" class="text-xs font-bold text-red-600 hover:text-red-800">Xóa</button>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-10 text-center bg-white rounded-xl border border-dashed border-gray-300 text-gray-500">
                            Chưa có địa chỉ nào được lưu.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        @if($activeTab === 'orders')
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden animate-fade-in">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ngày mua</th>
                            <th class="px-6 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-indigo-600">
                                    <a href="{{ route('admin.orders.show', $order->id) }}">#{{ $order->order_code }}</a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                    {{ number_format($order->total) }} ₫
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    {!! $order->status_badge !!}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    {{ $order->created_at->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 hover:underline">Xem</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">Khách hàng này chưa có đơn hàng nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-gray-200">{{ $orders->links() }}</div>
            </div>
        @endif

    </div>

    @if($showAddressModal)
    <div class="relative z-[100]" aria-labelledby="modal-title" role="dialog" aria-modal="true">

        <div
            x-data
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"
            wire:click="$set('showAddressModal', false)"
        ></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">

                <div
                    x-data
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100"
                >

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">
                                {{ $isEditAddress ? 'Cập nhật địa chỉ' : 'Thêm địa chỉ mới' }}
                            </h3>
                            <button wire:click="$set('showAddressModal', false)" class="text-gray-400 hover:text-gray-500 transition">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-4 py-5 sm:p-6 space-y-5">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tên người nhận <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="addr_name"
                                class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all"
                                placeholder="Nhập họ tên đầy đủ">
                            @error('addr_name') <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Số điện thoại <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="addr_phone"
                                class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all"
                                placeholder="Ví dụ: 0987...">
                            @error('addr_phone') <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Địa chỉ chi tiết <span class="text-red-500">*</span></label>
                            <textarea wire:model="addr_address" rows="3"
                                class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all resize-none"
                                placeholder="Số nhà, tên đường, phường/xã..."></textarea>
                            @error('addr_address') <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tỉnh / Thành phố</label>
                            <input type="text" wire:model="addr_city"
                                class="block w-full rounded-lg border-0 p-2.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 transition-all"
                                placeholder="Nhập tên tỉnh/thành">
                        </div>

                        <div class="relative flex items-start">
                            <div class="flex h-6 items-center">
                                <input id="def" wire:model="addr_is_default" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 cursor-pointer">
                            </div>
                            <div class="ml-3 text-sm leading-6">
                                <label for="def" class="font-medium text-gray-900 cursor-pointer">Đặt làm địa chỉ mặc định</label>
                                <p class="text-gray-500 text-xs">Địa chỉ này sẽ được chọn tự động khi thanh toán.</p>
                            </div>
                        </div>

                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100">
                        <button type="button" wire:click="saveAddress" wire:loading.attr="disabled"
                            class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="saveAddress">Lưu địa chỉ</span>
                            <span wire:loading wire:target="saveAddress">Đang lưu...</span>
                        </button>
                        <button type="button" wire:click="$set('showAddressModal', false)"
                            class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto transition-all">
                            Hủy bỏ
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endif

    <x-toast />
</div>
