<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
    <form wire:submit.prevent="save" class="space-y-6">

        {{-- Brand Info --}}
        <div>
            <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4">Thông tin thương hiệu (Cột 1)</h4>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mô tả ngắn</label>
                    <textarea wire:model="brand_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Địa chỉ</label>
                        <input type="text" wire:model="address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="text" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hotline</label>
                        <input type="text" wire:model="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500">
                    </div>
                </div>
            </div>
        </div>

        {{-- App Links --}}
        <div>
            <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4">Link Tải App</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">App Store URL</label>
                    <input type="text" wire:model="appstore_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Google Play URL</label>
                    <input type="text" wire:model="playstore_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500">
                </div>
            </div>
        </div>

        {{-- Copyright --}}
        <div>
            <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4">Bottom Bar</h4>
            <div>
                <label class="block text-sm font-medium text-gray-700">Copyright Text</label>
                <input type="text" wire:model="copyright" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500">
            </div>
        </div>

        <div class="pt-2 text-right">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition shadow-sm font-bold">
                Lưu thông tin
            </button>
        </div>
    </form>
</div>
