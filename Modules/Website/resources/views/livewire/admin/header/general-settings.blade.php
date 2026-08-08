<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Thông tin chung & Topbar</h3>

    {{-- Thêm wire:loading để biết đang xử lý --}}
    <div wire:loading wire:target="save" class="fixed top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded shadow-lg z-50 text-sm font-bold">
        Đang lưu dữ liệu...
    </div>

    <form wire:submit.prevent="save" class="space-y-4">
        {{-- Branding --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Tên thương hiệu <span class="text-red-500">*</span></label>
                <input type="text" wire:model="brand_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('brand_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Logo URL (Tạm thời)</label>
                <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50 text-sm" disabled value="Tính năng Upload ở Phase sau">
            </div>
        </div>

        <hr class="border-gray-100">

        {{-- Topbar Contact --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Hotline Topbar</label>
                <input type="text" wire:model="hotline" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('hotline') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email Hỗ trợ</label>
                <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Links --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Link Trợ giúp (URL)</label>
                <input type="text" wire:model="help_url" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                {{-- QUAN TRỌNG: Hiển thị lỗi URL sai định dạng --}}
                @error('help_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Link Theo dõi đơn hàng (URL)</label>
                <input type="text" wire:model="order_tracking_url" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('order_tracking_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="pt-4 text-right">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition shadow-sm font-medium text-sm">
                Lưu thay đổi
            </button>
        </div>
    </form>
</div>
