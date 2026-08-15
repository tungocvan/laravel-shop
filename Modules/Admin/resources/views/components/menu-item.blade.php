@props(['menu', 'selected' => []])

@php
    $menuId = data_get($menu, 'id');
    $children = data_get($menu, 'children', []);
    $hasChildren = ! empty($children) && count($children) > 0;
    $isSelected = in_array((string) $menuId, array_map('strval', $selected), true);
@endphp

@if($menuId)
<li data-id="{{ $menuId }}" class="relative" x-data="{ expanded: false }">
    <div class="{{ data_get($menu, 'is_active') ? 'bg-white' : 'bg-gray-50 opacity-75' }} group flex items-center justify-between rounded-lg border border-gray-200 p-3 shadow-sm transition-colors hover:border-indigo-300">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <input
                type="checkbox"
                @checked($isSelected)
                wire:click="toggleMenuSelection({{ $menuId }})"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                aria-label="Chọn menu {{ data_get($menu, 'name') }}{{ $hasChildren ? ' và toàn bộ menu con' : '' }}"
            >

            @if($hasChildren)
                <button type="button" @click.stop="expanded = !expanded" :aria-expanded="expanded.toString()" aria-label="Thu gọn hoặc mở rộng menu con" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                    <svg class="h-4 w-4 transition-transform duration-150" :class="expanded ? 'rotate-90' : 'rotate-0'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            @else
                <span class="h-7 w-7 shrink-0"></span>
            @endif

            <div class="drag-handle cursor-move p-1 text-gray-400 hover:text-indigo-600"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg></div>

            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded border border-gray-100 bg-gray-50 text-gray-500">
                    @if(data_get($menu, 'icon'))<x-icon name="{{ data_get($menu, 'icon') }}" class="h-5 w-5" />@else<span class="text-xs font-bold text-gray-300">#</span>@endif
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-sm font-bold text-gray-800">
                        <span>{{ data_get($menu, 'name') }}</span>
                        @if(empty(data_get($menu, 'url')))<span class="rounded border border-gray-200 bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase text-gray-600">Section</span>@endif
                        @if(!data_get($menu, 'is_active'))<span class="rounded border border-red-200 bg-red-100 px-1.5 py-0.5 text-[10px] uppercase text-red-600">Ẩn</span>@endif
                        @if($hasChildren)<span class="rounded border border-indigo-100 bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-600">{{ count($children) }} menu con</span>@endif
                    </div>
                    <div class="truncate font-mono text-xs text-gray-500">{{ data_get($menu, 'url') ?? '---' }}</div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 opacity-100 transition-opacity md:opacity-0 md:group-hover:opacity-100">
            <div class="flex items-center gap-1 rounded border border-gray-100 bg-gray-50 px-2 py-1 text-xs text-gray-400">@if(data_get($menu, 'can'))<span>{{ data_get($menu, 'can') }}</span>@else<span class="text-green-600">Public</span>@endif</div>
            <button type="button" wire:click="toggleStatus({{ $menuId }})" class="{{ data_get($menu, 'is_active') ? 'text-green-500' : 'text-gray-300' }} transition hover:scale-110" aria-label="Đổi trạng thái menu"><svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg></button>
            <button type="button" wire:click="duplicate({{ $menuId }})" class="rounded p-1 text-teal-500 hover:bg-teal-50 hover:text-teal-700" aria-label="Nhân bản menu"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 01-2-2V5a2 2 0 012-2h4.586"/></svg></button>
            <a href="{{ route('admin.menus.edit', ['id' => $menuId]) }}" class="rounded p-1 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800" aria-label="Sửa menu"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></a>
            <button type="button" wire:click="delete({{ $menuId }})" class="rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600" aria-label="Xóa menu"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
        </div>
    </div>

    @if($hasChildren)
        <ul x-show="expanded" x-collapse class="menu-list ml-4 mt-2 space-y-2 border-l-2 border-gray-100 pl-8">
            @foreach($children as $child)<x-menu-item :menu="$child" :selected="$selected" />@endforeach
        </ul>
    @endif
</li>
@endif
