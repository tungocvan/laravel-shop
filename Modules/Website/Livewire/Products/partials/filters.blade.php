<div class="space-y-8">
    <div class="flex items-center justify-between">
        <h3 class="font-bold text-gray-900 text-lg">Bộ lọc tìm kiếm</h3>
        @if(!empty($selected_categories) || !empty($price_range))
            <button wire:click="resetFilters" class="text-xs text-red-500 font-medium hover:underline">
                Xóa tất cả
            </button>
        @endif
    </div>

    <div x-data="{ open: true }" class="border-b border-gray-100 pb-6">
        <button @click="open = !open" class="flex items-center justify-between w-full py-2 text-left font-medium text-gray-900 hover:text-blue-600 transition">
            <span>Danh mục</span>
            <span :class="{'rotate-180': open}" class="transform transition-transform duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </span>
        </button>

        <div x-show="open" x-collapse class="mt-4 space-y-3">
            @foreach($categories as $cat)
            <label class="flex items-center group cursor-pointer">
                <input type="checkbox" wire:model.live="selected_categories" value="{{ $cat->id }}"
                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition cursor-pointer">
                <span class="ml-3 text-sm text-gray-600 group-hover:text-blue-600 transition-colors">{{ $cat->name }}</span>
            </label>
            @endforeach
        </div>
    </div>

    <div x-data="{ open: true }" class="border-b border-gray-100 pb-6">
        <button @click="open = !open" class="flex items-center justify-between w-full py-2 text-left font-medium text-gray-900 hover:text-blue-600 transition">
            <span>Khoảng giá</span>
            <span :class="{'rotate-180': open}" class="transform transition-transform duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </span>
        </button>

        <div x-show="open" x-collapse class="mt-4 space-y-3">
            <label class="flex items-center group cursor-pointer">
                <input type="radio" wire:model.live="price_range" value="0-100000" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <span class="ml-3 text-sm text-gray-600">Dưới 100k</span>
            </label>
            <label class="flex items-center group cursor-pointer">
                <input type="radio" wire:model.live="price_range" value="100000-300000" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <span class="ml-3 text-sm text-gray-600">100k - 300k</span>
            </label>
            <label class="flex items-center group cursor-pointer">
                <input type="radio" wire:model.live="price_range" value="300000-500000" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <span class="ml-3 text-sm text-gray-600">300k - 500k</span>
            </label>
            <label class="flex items-center group cursor-pointer">
                <input type="radio" wire:model.live="price_range" value="500000-99999999" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <span class="ml-3 text-sm text-gray-600">Trên 500k</span>
            </label>
        </div>
    </div>
</div>
