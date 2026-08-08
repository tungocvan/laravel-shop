<div class="flex items-center gap-2">
    <span class="text-sm text-gray-500 hidden sm:block">Sắp xếp:</span>
    <div class="relative">
        <select wire:model.live="sort"
                class="block w-full pl-3 pr-8 py-2 text-sm border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm bg-white text-gray-700 cursor-pointer">
            <option value="latest">Mới nhất</option>
            <option value="price_asc">Giá: Thấp đến Cao</option>
            <option value="price_desc">Giá: Cao đến Thấp</option>
            <option value="name_asc">Tên: A-Z</option>
            <option value="name_desc">Tên: Z-A</option>
        </select>
    </div>
</div>
