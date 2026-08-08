<li class="group">
    <div class="flex justify-between items-center p-4 hover:bg-gray-50 transition-all">
        <div class="flex items-center gap-3">
            {{-- Thụt lề dựa trên cấp độ --}}
            @if($level > 1)
                <span class="text-gray-300 ml-6">└─</span>
            @else
                <span class="cursor-move text-gray-400 group-hover:text-blue-500">⋮⋮</span>
            @endif

            <div class="flex flex-col">
                <span class="font-bold text-gray-800 {{ $level > 1 ? 'text-sm' : '' }}">{{ $item->title }}</span>
                <span class="text-xs text-gray-400 font-mono">{{ $item->url ?: '/' }}</span>
            </div>

            @if(!$item->is_active)
                <span class="text-[10px] uppercase tracking-widest text-red-500 bg-red-50 px-2 py-0.5 rounded-full font-bold">Ẩn</span>
            @endif
        </div>

        <div class="flex items-center gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
            <button wire:click="openModal({{ $item->id }})" class="text-blue-600 hover:text-blue-800 text-xs font-bold uppercase tracking-tighter">
                Sửa
            </button>
            <button wire:confirm="Xóa mục này sẽ xóa toàn bộ mục con. Bạn chắc chắn?"
                    wire:click="deleteMenuItem({{ $item->id }})"
                    class="text-red-500 hover:text-red-700 text-xs font-bold uppercase tracking-tighter">
                Xóa
            </button>
        </div>
    </div>

    {{-- Đệ quy hiển thị con --}}
    @if($item->children->count() > 0)
        <ul class="bg-gray-50/50 border-t border-gray-50">
            @foreach($item->children as $child)
                @include('Admin::livewire.header.partials.menu-item-row', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>
