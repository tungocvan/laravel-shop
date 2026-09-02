@props(['menu', 'selected' => []])

@php
    $menuId = data_get($menu, 'id');
    $children = data_get($menu, 'children', []);
    $hasChildren = ! empty($children) && count($children) > 0;
    $isSelected = in_array((string) $menuId, array_map('strval', $selected), true);
    $isSection = empty(data_get($menu, 'url'));
    $isActive = (bool) data_get($menu, 'is_active');
@endphp

@if($menuId)
<li
    data-id="{{ $menuId }}"
    class="relative"
    x-data="{ expanded: false, actionsOpen: false }"
    x-on:menu-tree-expand.window="expanded = true"
    x-on:menu-tree-collapse.window="expanded = false"
    x-on:click.outside="actionsOpen = false"
>
    <div class="group grid grid-cols-[auto_auto_auto_minmax(0,1fr)_auto] items-center gap-2 rounded-xl border px-3 py-2.5 shadow-sm transition sm:gap-3 {{ $isSection ? 'border-indigo-100 bg-indigo-50/40' : ($isActive ? 'border-gray-200 bg-white' : 'border-gray-200 bg-gray-50 opacity-75') }} hover:border-indigo-300 hover:shadow">
        <input
            type="checkbox"
            @checked($isSelected)
            wire:click="toggleMenuSelection({{ $menuId }})"
            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            aria-label="Chọn menu {{ data_get($menu, 'name') }}{{ $hasChildren ? ' và toàn bộ menu con' : '' }}"
        >

        @if($hasChildren)
            <button type="button" @click.stop="expanded = !expanded" :aria-expanded="expanded.toString()" aria-label="Thu gọn hoặc mở rộng menu con" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-white hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                <svg class="h-4 w-4 transition-transform duration-150" :class="expanded ? 'rotate-90' : 'rotate-0'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        @else
            <span class="h-7 w-7 shrink-0"></span>
        @endif

        <button type="button" class="drag-handle flex h-7 w-7 cursor-grab items-center justify-center rounded-lg text-gray-300 transition hover:bg-gray-100 hover:text-indigo-600 active:cursor-grabbing" title="Kéo để sắp xếp" aria-label="Kéo để sắp xếp menu">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h.01M8 12h.01M8 17h.01M16 7h.01M16 12h.01M16 17h.01"/></svg>
        </button>

        <div class="grid min-w-0 gap-2 lg:grid-cols-[minmax(220px,1.15fr)_minmax(180px,1fr)_minmax(170px,0.8fr)_auto] lg:items-center lg:gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border {{ $isSection ? 'border-indigo-100 bg-white text-indigo-600' : 'border-gray-200 bg-gray-50 text-gray-500' }}">
                    @if(data_get($menu, 'icon'))
                        <x-icon name="{{ data_get($menu, 'icon') }}" class="h-5 w-5" />
                    @elseif($isSection)
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                    @else
                        <span class="text-xs font-bold text-gray-300">#</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                        <span class="truncate text-sm font-semibold text-gray-900">{{ data_get($menu, 'name') }}</span>
                        @if($isSection)<span class="rounded-full border border-indigo-100 bg-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-600">Section</span>@endif
                        @if($hasChildren)<span class="rounded-full bg-indigo-100/70 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">{{ count($children) }} mục</span>@endif
                    </div>
                    <div class="mt-0.5 text-[11px] text-gray-400 lg:hidden">{{ data_get($menu, 'url') ?: 'Nhóm menu không có liên kết' }}</div>
                </div>
            </div>

            <div class="hidden min-w-0 lg:block">
                <span class="block truncate font-mono text-xs text-gray-500">{{ data_get($menu, 'url') ?: '—' }}</span>
            </div>

            <div class="hidden min-w-0 lg:block">
                @if(data_get($menu, 'can'))
                    <span class="inline-flex max-w-full truncate rounded-full border border-amber-100 bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-700" title="{{ data_get($menu, 'can') }}">{{ data_get($menu, 'can') }}</span>
                @else
                    <span class="inline-flex rounded-full border border-emerald-100 bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700">Public</span>
                @endif
            </div>

            <div class="hidden lg:flex lg:justify-end">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[11px] font-semibold {{ $isActive ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                    {{ $isActive ? 'Hoạt động' : 'Đang ẩn' }}
                </span>
            </div>
        </div>

        <div class="relative flex items-center gap-1">
            <button type="button" wire:click="toggleStatus({{ $menuId }})" wire:loading.attr="disabled" wire:target="toggleStatus" class="flex h-8 w-8 items-center justify-center rounded-lg border border-transparent {{ $isActive ? 'text-emerald-600 hover:bg-emerald-50' : 'text-gray-400 hover:bg-gray-100' }}" title="{{ $isActive ? 'Tắt menu' : 'Bật menu' }}" aria-label="Đổi trạng thái menu">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            </button>
            <button type="button" @click.stop="actionsOpen = !actionsOpen" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Mở thao tác menu" :aria-expanded="actionsOpen.toString()">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 10a2 2 0 114 0 2 2 0 01-4 0zm5 0a2 2 0 114 0 2 2 0 01-4 0zm5 0a2 2 0 114 0 2 2 0 01-4 0z"/></svg>
            </button>
            <div x-show="actionsOpen" x-transition x-cloak class="absolute right-0 top-9 z-30 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-xl">
                <a href="{{ route('admin.menus.edit', ['id' => $menuId]) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700">Chỉnh sửa</a>
                <button type="button" wire:click="duplicate({{ $menuId }})" @click="actionsOpen = false" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">Nhân bản</button>
                <div class="my-1 border-t border-gray-100"></div>
                <button type="button" wire:click="delete({{ $menuId }})" wire:confirm="Xóa menu '{{ data_get($menu, 'name') }}'?" @click="actionsOpen = false" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50">Xóa menu</button>
            </div>
        </div>
    </div>

    @if($hasChildren)
        <ul x-show="expanded" x-collapse class="menu-list ml-3 mt-2 space-y-2 border-l border-indigo-100 pl-4 sm:ml-5 sm:pl-5">
            @foreach($children as $child)
                <x-menu-item :menu="$child" :selected="$selected" />
            @endforeach
        </ul>
    @endif
</li>
@endif
