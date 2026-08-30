@php
    $children = $category->relationLoaded('childrenRecursive') ? $category->childrenRecursive : $category->children;
    $hasChildren = $children->isNotEmpty();
    $indent = 16 + ((int) $depth * 22);
@endphp

<div
    class="border-b border-gray-100 last:border-0"
    @if($hasChildren)
        x-data="{ open: false }"
        x-init="$nextTick(() => { open = $el.querySelector('[data-category-children] input[type=checkbox]:checked') !== null })"
    @endif
>
    <div class="group flex min-h-11 items-center gap-2 pr-3 transition hover:bg-gray-50" style="padding-left: {{ $indent }}px">
        @if($hasChildren)
            <button
                type="button"
                @click="open = ! open"
                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-sm font-semibold text-gray-500 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700"
                :aria-expanded="open.toString()"
                aria-label="Mở hoặc thu gọn danh mục {{ $category->name }}"
            >
                <span x-text="open ? '−' : '+'">+</span>
            </button>
        @else
            <span class="inline-block h-6 w-6 shrink-0"></span>
        @endif

        <label class="flex min-w-0 flex-1 cursor-pointer select-none items-center gap-3 py-2.5">
            <input type="checkbox" value="{{ $category->id }}" {{ $wireModel }} class="h-4 w-4 shrink-0 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span class="truncate text-sm {{ $depth === 0 ? 'font-semibold text-gray-900' : 'font-medium text-gray-700' }} group-hover:text-indigo-700">
                {{ $category->name }}
            </span>
        </label>
    </div>

    @if($hasChildren)
        <div data-category-children x-show="open" x-cloak class="bg-gray-50/40">
            @foreach($children as $child)
                @include('Admin::components.category-select-row', [
                    'category' => $child,
                    'depth' => $depth + 1,
                    'wireModel' => $wireModel,
                ])
            @endforeach
        </div>
    @endif
</div>
