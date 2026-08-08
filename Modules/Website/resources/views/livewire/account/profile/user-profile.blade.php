<div class="space-y-8">

    {{-- 1. KHỐI THÔNG TIN CÁ NHÂN --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900">Thông tin cá nhân</h3>
            <p class="text-sm text-gray-500">Cập nhật hình ảnh và thông tin liên hệ của bạn.</p>
        </div>

        <form wire:submit="updateProfile" class="p-6 md:p-8">
            <div class="flex flex-col md:flex-row gap-8">

                {{-- CỘT TRÁI: AVATAR UPLOAD --}}
                <div class="flex flex-col items-center gap-4 md:w-1/4">
                    <div class="relative group cursor-pointer" onclick="document.getElementById('avatarInput').click()">
                        {{-- Hiển thị Avatar: Ưu tiên ảnh mới upload (Preview) -> Ảnh DB -> Ảnh Placeholder --}}
                        @if($newAvatar)
                            <img src="{{ $newAvatar->temporaryUrl() }}" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg group-hover:opacity-75 transition">
                        @elseif($avatar)
                            <img src="{{ Storage::url($avatar) }}" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg group-hover:opacity-75 transition">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($name) }}&background=random" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg group-hover:opacity-75 transition">
                        @endif

                        {{-- Icon Camera Overlay --}}
                        <div class="absolute inset-0 flex items-center justify-center rounded-full opacity-0 group-hover:opacity-100 transition duration-300">
                            <div class="bg-black/50 p-2 rounded-full text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                        </div>
                    </div>

                    <input type="file" wire:model="newAvatar" id="avatarInput" class="hidden" accept="image/*">

                    <div class="text-center">
                        <span class="text-xs text-gray-400">Cho phép: JPG, PNG, GIF</span>
                        <div wire:loading wire:target="newAvatar" class="text-xs text-blue-600 font-bold mt-1">Đang tải ảnh lên...</div>
                        @error('newAvatar') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- CỘT PHẢI: FORM INFO --}}
                <div class="flex-1 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        {{-- Tên --}}
                        <div class="space-y-1">
                            <label class="text-sm font-bold text-gray-700">Họ và tên</label>
                            <input type="text" wire:model="name"
                                   class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Số điện thoại --}}
                        <div class="space-y-1">
                            <label class="text-sm font-bold text-gray-700">Số điện thoại</label>
                            <input type="text" wire:model="phone"
                                   class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition">
                            @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Email (Readonly) --}}
                        <div class="space-y-1 md:col-span-2">
                            <label class="text-sm font-bold text-gray-700">Email đăng nhập</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                                </span>
                                <input type="email" value="{{ $email }}" disabled
                                       class="w-full bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-lg block pl-10 p-2.5 cursor-not-allowed">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Bạn không thể tự thay đổi email. Vui lòng liên hệ CSKH nếu cần.</p>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition transform hover:-translate-y-0.5 flex items-center gap-2">
                            <span wire:loading.remove wire:target="updateProfile">Lưu thay đổi</span>
                            <span wire:loading wire:target="updateProfile">Đang lưu...</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- 2. KHỐI ĐỔI MẬT KHẨU --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900">Bảo mật</h3>
            <p class="text-sm text-gray-500">Đổi mật khẩu định kỳ để bảo vệ tài khoản.</p>
        </div>

        <form wire:submit="changePassword" class="p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Mật khẩu cũ --}}
                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">Mật khẩu hiện tại</label>
                    <input type="password" wire:model="current_password"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition">
                    @error('current_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Mật khẩu mới --}}
                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">Mật khẩu mới</label>
                    <input type="password" wire:model="password"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition">
                    @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Nhập lại mật khẩu mới --}}
                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700">Xác nhận mật khẩu mới</label>
                    <input type="password" wire:model="password_confirmation"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 transition">
                </div>
            </div>

            <div class="flex justify-end pt-6 border-t border-gray-50 mt-6">
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-bold rounded-lg shadow-md transition transform hover:-translate-y-0.5">
                    <span wire:loading.remove wire:target="changePassword">Đổi mật khẩu</span>
                    <span wire:loading wire:target="changePassword">Đang xử lý...</span>
                </button>
            </div>
        </form>
    </div>

</div>
