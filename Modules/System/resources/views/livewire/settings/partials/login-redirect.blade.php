<form wire:submit="save" class="space-y-6 animate-fadeIn">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">Đăng nhập & Điều hướng</h2>
        <p class="mt-1 text-sm text-gray-500">Chọn trang mặc định được mở sau khi đăng nhập thành công. Khi chưa cấu hình, hệ thống mặc định chuyển về <code>/admin</code>. Route gốc <code>/</code> chỉ xuất hiện để lựa chọn khi route đó thực sự đang được đăng ký.</p>
    </div>

    @unless($canUpdate)
        <p class="text-xs font-bold text-amber-700">Tài khoản hiện tại chỉ có quyền xem.</p>
    @endunless

    <div>
        <label for="admin-login-redirect-route" class="block text-sm font-medium text-gray-900">Trang mặc định sau đăng nhập</label>
        <select
            id="admin-login-redirect-route"
            wire:model="routeName"
            @disabled(!$canUpdate)
            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
        >
            @foreach($routeOptions as $name => $label)
                <option value="{{ $name }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('routeName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        <p class="mt-2 text-xs text-gray-500">Nếu route đã cấu hình bị xóa, đổi tên hoặc không còn khả dụng, hệ thống tự động quay về <code>/admin</code>. Vì vậy việc tắt Website hoặc bỏ route <code>/</code> sẽ không làm điều hướng đăng nhập phụ thuộc vào Website.</p>
    </div>

    <div class="flex justify-end border-t pt-6">
        <button type="submit" wire:loading.attr="disabled" wire:target="save" @disabled(!$canUpdate) class="h-12 rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white disabled:opacity-50">
            <span wire:loading.remove wire:target="save">Lưu điều hướng đăng nhập</span>
            <span wire:loading wire:target="save">Đang lưu...</span>
        </button>
    </div>
</form>
