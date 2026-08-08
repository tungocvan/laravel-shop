<div class="space-y-6">
    {{-- 1. Bộ chọn vị trí Menu --}}
    <div class="flex justify-between items-center bg-gray-50 p-4 rounded-xl border border-gray-100">
        <div class="flex items-center gap-4">
            <label class="text-sm font-bold text-gray-700">Đang chỉnh sửa:</label>
            <select wire:model.live="currentLocation" class="rounded-lg border-gray-300 text-sm focus:ring-blue-500">
                @foreach($menuLocations as $key => $name)
                    <option value="{{ $key }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="openModal" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 shadow-sm transition">
            + Thêm mục mới
        </button>
    </div>

    {{-- 2. Danh sách cây Menu (Recursive) --}}
    <div class="border border-gray-100 rounded-xl overflow-hidden bg-white">
        @if($menuTree->isEmpty())
            <div class="p-12 text-center">
                <div class="text-gray-400 mb-2">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </div>
                <p class="text-gray-500">Vị trí này chưa có menu nào. Hãy bắt đầu tạo ngay!</p>
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($menuTree as $item)
                    @include('Admin::livewire.header.partials.menu-item-row', ['item' => $item, 'level' => 1])
                @endforeach
            </ul>
        @endif
    </div>
</div>
