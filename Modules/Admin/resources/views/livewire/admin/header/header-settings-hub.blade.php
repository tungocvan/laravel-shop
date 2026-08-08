<div class="max-w-6xl mx-auto py-6" x-data="{ tab: @entangle('activeTab') }">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Cấu hình Header System</h2>
        <p class="text-sm text-gray-500">Quản lý toàn bộ giao diện đầu trang và điều hướng menu.</p>
    </div>

    <div class="flex border-b border-gray-200 mb-6">
        <button @click="tab = 'general'"
            :class="tab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="py-2 px-6 border-b-2 font-medium text-sm transition-colors">
            Thông tin chung
        </button>
        <button @click="tab = 'menu'"
            :class="tab === 'menu' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="py-2 px-6 border-b-2 font-medium text-sm transition-colors">
            Cấu trúc Menu
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

        <div x-show="tab === 'general'" x-cloak>
            <form wire:submit.prevent="saveGeneral" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên thương hiệu</label>
                        <input type="text" wire:model="generalData.brand_name" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email liên hệ</label>
                        <input type="email" wire:model="generalData.topbar_email" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition">
                        Lưu cấu hình
                    </button>
                </div>
            </form>
        </div>

        <div x-show="tab === 'menu'" x-cloak>
            @include('Admin::livewire.header.partials.menu-tree-manager')
        </div>

    </div>
</div>
