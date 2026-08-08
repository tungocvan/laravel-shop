<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-8">

    {{-- Header --}}
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Sổ địa chỉ</h3>
            <p class="text-sm text-gray-500">Quản lý địa chỉ giao hàng của bạn.</p>
        </div>
        <button wire:click="openCreateModal"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Thêm địa chỉ
        </button>
    </div>

    {{-- List Addresses --}}
    <div class="p-6">
        @if($addresses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($addresses as $addr)
                    <div class="group relative border {{ $addr->is_default ? 'border-blue-500 bg-blue-50/10 ring-1 ring-blue-500' : 'border-gray-200 hover:border-blue-300' }} rounded-xl p-5 transition-all">

                        {{-- Badge Mặc định --}}
                        @if($addr->is_default)
                            <span class="absolute top-4 right-4 bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">
                                Mặc định
                            </span>
                        @else
                            {{-- Nút Set Default (Hiện khi hover) --}}
                            <button wire:click="setAsDefault({{ $addr->id }})"
                                    class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 text-xs text-blue-600 hover:underline transition">
                                Đặt làm mặc định
                            </button>
                        @endif

                        {{-- Thông tin --}}
                        <div class="space-y-1 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900">{{ $addr->name }}</span>
                                <span class="text-gray-300">|</span>
                                <span class="text-gray-600 text-sm">{{ $addr->phone }}</span>
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                {{ $addr->address }}<br>
                                {{ $addr->ward }}, {{ $addr->district }}, {{ $addr->city }}
                            </p>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-3 pt-3 border-t {{ $addr->is_default ? 'border-blue-100' : 'border-gray-100' }}">
                            <button wire:click="openEditModal({{ $addr->id }})" class="text-xs font-bold text-gray-600 hover:text-blue-600 flex items-center gap-1 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Sửa
                            </button>
                            <button wire:click="delete({{ $addr->id }})"
                                    wire:confirm="Bạn có chắc chắn muốn xóa địa chỉ này?"
                                    class="text-xs font-bold text-red-500 hover:text-red-700 flex items-center gap-1 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Xóa
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10">
                <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <p class="text-gray-500 text-sm">Bạn chưa lưu địa chỉ nào.</p>
            </div>
        @endif
    </div>





    {{-- MODAL FORM (NUCLEAR VERSION - INPUT BORDER TINH TẾ) --}}
    @if($isModalOpen)
        @teleport('body')
            {{-- Wrapper tổng: Z-index cao nhất --}}
            <div class="relative" style="z-index: 999999;" aria-labelledby="modal-title" role="dialog" aria-modal="true">

                {{-- A. BACKDROP --}}
                <div class="fixed inset-0 bg-gray-900/75 transition-opacity backdrop-blur-sm"
                     wire:click="closeModal"></div>

                {{-- B. CONTAINER --}}
                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">

                        {{-- C. MODAL CONTENT --}}
                        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-xl border border-gray-100">

                            {{-- Header --}}
                            <div class="bg-white px-6 py-5 border-b border-gray-100 flex justify-between items-center sticky top-0 z-20 shadow-sm">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">
                                        {{ $isEditMode ? 'Cập nhật địa chỉ' : 'Thêm địa chỉ mới' }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-1">Vui lòng điền chính xác thông tin nhận hàng.</p>
                                </div>
                                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 p-2 rounded-full transition focus:outline-none">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            {{-- Body --}}
                            <div class="max-h-[80vh] overflow-y-auto custom-scrollbar bg-white">
                                <form wire:submit="save">
                                    <div class="px-6 py-6 space-y-6">

                                        {{-- GROUP 1: THÔNG TIN LIÊN HỆ --}}
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                Người nhận
                                            </h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                                {{-- Tên --}}
                                                <div class="relative group">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                    </div>
                                                    <input type="text" wire:model="name" placeholder="Họ và tên"
                                                           class="pl-10 block w-full border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm py-2.5 placeholder-gray-400 text-gray-900">
                                                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                                </div>

                                                {{-- SĐT --}}
                                                <div class="relative group">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                    </div>
                                                    <input type="text" wire:model="phone" placeholder="Số điện thoại"
                                                           class="pl-10 block w-full border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm py-2.5 placeholder-gray-400 text-gray-900">
                                                    @error('phone') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                                </div>
                                            </div>
                                        </div>

                                        {{-- GROUP 2: ĐỊA CHỈ --}}
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                Địa chỉ giao hàng
                                            </h4>

                                            {{-- Tỉnh/Huyện/Xã --}}
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                                <div>
                                                    <input type="text" wire:model="city" placeholder="Tỉnh/Thành phố"
                                                           class="block p-2 w-full border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm py-2.5 placeholder-gray-400 text-gray-900">
                                                    @error('city') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                                </div>
                                                <div>
                                                    <input type="text" wire:model="district" placeholder="Quận/Huyện"
                                                           class="block p-2 w-full border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm py-2.5 placeholder-gray-400 text-gray-900">
                                                    @error('district') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                                </div>
                                                <div>
                                                    <input type="text" wire:model="ward" placeholder="Phường/Xã"
                                                           class="block w-full p-2 border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm py-2.5 placeholder-gray-400 text-gray-900">
                                                    @error('ward') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            {{-- Địa chỉ chi tiết --}}
                                            <div class="relative group">
                                                <div class="absolute top-3 left-3 pointer-events-none">
                                                    <svg class="w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                                </div>
                                                <textarea wire:model="address" rows="2" placeholder="Số nhà, tên đường, tòa nhà, khu dân cư..."
                                                          class="pl-10 block w-full border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm py-2.5 placeholder-gray-400 text-gray-900"></textarea>
                                                @error('address') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        {{-- GROUP 3: SETTINGS --}}
                                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 flex items-center gap-3 cursor-pointer hover:bg-gray-100 transition shadow-sm" onclick="document.getElementById('defaultCheck').click()">
                                            <input type="checkbox" id="defaultCheck" wire:model="is_default"
                                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                            <div>
                                                <label for="defaultCheck" class="block text-sm font-bold text-gray-900 cursor-pointer">Đặt làm địa chỉ mặc định</label>
                                                <p class="text-xs text-gray-500">Ưu tiên giao hàng đến địa chỉ này.</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Footer Actions --}}
                                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-100">
                                        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            {{ $isEditMode ? 'Cập nhật' : 'Hoàn thành' }}
                                        </button>
                                        <button type="button" wire:click="closeModal" class="w-full sm:w-auto px-6 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-bold rounded-xl transition shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                                            Hủy bỏ
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
