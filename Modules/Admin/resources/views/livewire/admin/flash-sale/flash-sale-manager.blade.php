<div class="max-w-7xl mx-auto pb-20">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Flash Sale</h1>
            <p class="text-sm text-gray-500">Quản lý các chiến dịch giảm giá giờ vàng.</p>
        </div>
        <button wire:click="create"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Tạo chiến dịch
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($sales as $sale)
            <div class="bg-white border rounded-xl shadow-sm hover:shadow-md transition p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 mt-4 mr-4">
                    @if ($sale->is_running)
                        <span
                            class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-0.5 rounded animate-pulse">ĐANG
                            CHẠY</span>
                    @elseif(!$sale->is_active)
                        <span class="bg-gray-100 text-gray-800 text-xs font-bold px-2.5 py-0.5 rounded">ĐÃ TẮT</span>
                    @elseif($sale->end_time < now())
                        <span class="bg-red-100 text-red-800 text-xs font-bold px-2.5 py-0.5 rounded">KẾT THÚC</span>
                    @else
                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-0.5 rounded">SẮP DIỄN
                            RA</span>
                    @endif
                </div>

                <h3 class="text-lg font-bold text-gray-900 truncate pr-20">{{ $sale->title }}</h3>

                <div class="mt-4 space-y-2 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ $sale->start_time->format('H:i d/m/Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Đến: {{ $sale->end_time->format('H:i d/m/Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>{{ $sale->items_count }} sản phẩm</span>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t flex justify-end gap-3">
                    <button wire:click="edit({{ $sale->id }})"
                        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Chỉnh sửa</button>
                    <button wire:confirm="Xóa chiến dịch này?" wire:click="delete({{ $sale->id }})"
                        class="text-red-600 hover:text-red-800 text-sm font-medium">Xóa</button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-10 bg-gray-50 rounded-lg border border-dashed text-gray-500">
                Chưa có chương trình Flash Sale nào.
            </div>
        @endforelse
    </div>

    @if ($isModalOpen)
        <div class="fixed inset-0 z-40 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"
                wire:click="$set('isModalOpen', false)"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-4xl bg-white rounded-xl shadow-2xl flex flex-col max-h-[90vh]">

                    <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $isEditMode ? 'Cập nhật Flash Sale' : 'Tạo mới Flash Sale' }}</h3>
                        <button wire:click="$set('isModalOpen', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Tên chương trình</label>
                                <input type="text" wire:model="title"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2"
                                    placeholder="VD: Sale ngày đôi 9.9">
                                @error('title')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bắt đầu</label>
                                <input type="datetime-local" wire:model="start_time"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                @error('start_time')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kết thúc</label>
                                <input type="datetime-local" wire:model="end_time"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                @error('end_time')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-base font-bold text-gray-900">Danh sách sản phẩm Sale</h4>
                                <button type="button" wire:click="openPicker"
                                    class="text-sm bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded hover:bg-indigo-100 font-medium border border-indigo-200">
                                    + Thêm sản phẩm
                                </button>
                            </div>

                            <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                Sản phẩm</th>
                                            <th
                                                class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">
                                                Giá gốc</th>
                                            <th
                                                class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-32">
                                                Giá Sale</th>
                                            <th
                                                class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-24">
                                                Số lượng</th>
                                            <th class="px-4 py-2 text-center w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @forelse($items as $index => $item)
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center">
                                                        <img src="{{ \Illuminate\Support\Str::startsWith($item['image'], ['http', 'https']) ? $item['image'] : asset('storage/' . $item['image']) }}"
                                                            class="h-10 w-10 rounded object-cover mr-3 bg-gray-200">
                                                        <div class="text-sm font-medium text-gray-900 truncate max-w-[200px]"
                                                            title="{{ $item['name'] }}">{{ $item['name'] }}</div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right text-sm text-gray-500">
                                                    {{ number_format($item['original_price']) }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number"
                                                        wire:model="items.{{ $index }}.price"
                                                        class="block w-full rounded-md border-gray-300 py-1 text-sm focus:border-indigo-500 focus:ring-indigo-500 border px-2 text-right">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number"
                                                        wire:model="items.{{ $index }}.quantity"
                                                        class="block w-full rounded-md border-gray-300 py-1 text-sm focus:border-indigo-500 focus:ring-indigo-500 border px-2 text-center">
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button wire:click="removeItem({{ $index }})"
                                                        class="text-red-400 hover:text-red-600">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5"
                                                    class="px-6 py-8 text-center text-sm text-gray-500 italic">
                                                    Chưa có sản phẩm nào. Bấm "Thêm sản phẩm" để chọn.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @error('items')
                                <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center rounded-b-xl">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="is_active" id="activeSale"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                            <label for="activeSale" class="ml-2 text-sm text-gray-900">Kích hoạt ngay</label>
                        </div>
                        <div class="flex gap-3">
                            <button wire:click="$set('isModalOpen', false)"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Hủy</button>
                            <button wire:click="save"
                                class="px-4 py-2 bg-indigo-600 rounded-lg text-sm font-medium text-white hover:bg-indigo-700">Lưu
                                chương trình</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showProductPicker)
        <div class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="fixed inset-0 bg-black bg-opacity-25" wire:click="$set('showProductPicker', false)"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-lg shadow-xl">
                    <div class="p-4 border-b">
                        <input type="text" wire:model.live.debounce.300ms="productSearchQuery"
                            placeholder="Tìm sản phẩm..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                    </div>

                    <div class="max-h-80 overflow-y-auto p-2 space-y-1">
                        @forelse($searchProducts as $prod)
                            {{-- 1. KIỂM TRA TRẠNG THÁI: Sản phẩm này đã được chọn chưa? --}}
                            @php
                                // Kiểm tra xem ID sản phẩm này có nằm trong mảng $items không
                                $isSelected = collect($items)->contains('product_id', $prod->id);
                            @endphp

                            <div {{-- Nếu chưa chọn thì mới cho click --}}
                                @if (!$isSelected) wire:click="addProduct({{ $prod->id }})" @endif
                                class="flex items-center gap-3 p-2 rounded border transition select-none
            {{-- 2. ĐỔI GIAO DIỆN DỰA TRÊN TRẠNG THÁI --}}
            {{ $isSelected
                ? 'bg-green-50 border-green-200 cursor-default'
                : 'bg-white border-transparent hover:bg-indigo-50 cursor-pointer hover:border-indigo-100' }} group">
                                {{-- Ảnh sản phẩm --}}
                                <img src="{{ \Illuminate\Support\Str::startsWith($prod->image, ['http', 'https']) ? $prod->image : asset('storage/' . $prod->image) }}"
                                    class="h-10 w-10 rounded object-cover bg-gray-200">

                                <div class="flex-1">
                                    <div
                                        class="text-sm font-medium {{ $isSelected ? 'text-green-700' : 'text-gray-900' }}">
                                        {{ $prod->title }}
                                    </div>
                                    <div class="text-xs text-gray-500">Giá gốc:
                                        {{ number_format($prod->regular_price) }}đ</div>
                                </div>

                                {{-- 3. NÚT TRẠNG THÁI --}}
                                <div class="text-sm font-bold">
                                    @if ($isSelected)
                                        <span class="flex items-center text-green-600">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Đã thêm
                                        </span>
                                    @else
                                        <span
                                            class="text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                            + Thêm
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-sm text-gray-500">
                                <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                {{ empty($productSearchQuery) ? 'Gõ từ khóa để tìm kiếm...' : 'Không tìm thấy sản phẩm nào.' }}
                            </div>
                        @endforelse
                    </div>

                    <div class="p-2 border-t bg-gray-50 text-right">
                        <button wire:click="$set('showProductPicker', false)"
                            class="text-sm text-gray-600 font-medium px-3 py-1 hover:text-gray-900">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
