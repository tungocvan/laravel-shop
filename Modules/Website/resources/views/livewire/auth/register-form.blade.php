<div class="bg-white p-8 rounded-lg shadow-md border border-gray-200">
    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">Đăng ký tài khoản</h2>

    <form wire:submit="register" class="space-y-4">

        <div>
            <label class="block text-sm font-medium text-gray-700">Họ và tên</label>
            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Mật khẩu</label>
            <input type="password" wire:model="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Nhập lại mật khẩu</label>
            <input type="password" wire:model="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border p-2">
        </div>

        <button type="submit"
                wire:loading.attr="disabled"
                class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
            <span wire:loading.remove>Đăng ký</span>
            <span wire:loading>Đang xử lý...</span>
        </button>
    </form>

    <div class="mt-4 text-center text-sm">
        Đã có tài khoản? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Đăng nhập ngay</a>
    </div>
</div>
