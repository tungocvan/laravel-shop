@php
    $visibleLookup = $visibleCategoryIds === null
        ? null
        : array_fill_keys(array_map('intval', $visibleCategoryIds), true);

    $children = $category->childrenRecursive
        ->filter(fn ($child) => $visibleLookup === null || isset($visibleLookup[(int) $child->id]));

    $hasChildren = $children->isNotEmpty();
    $isExpanded = in_array((int) $category->id, $expandedCategoryIds, true);
    $hasCategoryImage = filled($category->image)
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($category->image);
@endphp

<tr wire:key="category-{{ $category->id }}" class="hover:bg-gray-50">
    <td class="px-6 py-4">
        <div class="flex items-center gap-3" style="padding-left: {{ $depth * 1.5 }}rem">
            @if ($hasChildren)
                <button type="button"
                    wire:click="toggleNode({{ $category->id }})"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-sm font-semibold text-gray-600 shadow-sm transition hover:border-indigo-300 hover:text-indigo-600"
                    aria-label="{{ $isExpanded ? 'Thu gọn' : 'Mở rộng' }} {{ $category->name }}"
                    aria-expanded="{{ $isExpanded ? 'true' : 'false' }}">
                    {{ $isExpanded ? '−' : '+' }}
                </button>
            @else
                <span class="h-7 w-7 shrink-0"></span>
            @endif

            @if ($hasCategoryImage)
                <img class="h-10 w-10 shrink-0 rounded-lg border border-gray-200 object-cover"
                    src="{{ asset('storage/'.$category->image) }}"
                    alt="{{ $category->name }}">
            @else
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-indigo-100 bg-indigo-50 text-indigo-600"
                    aria-label="Ảnh mặc định danh mục">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75A2.25 2.25 0 0 1 6 4.5h3.19c.597 0 1.169.237 1.591.659l1.06 1.06c.422.422.994.659 1.591.659H18A2.25 2.25 0 0 1 20.25 9v8.25A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75Z" />
                    </svg>
                </div>
            @endif

            <div class="min-w-0">
                <div class="font-semibold text-gray-900">{{ $category->name }}</div>
                <div class="truncate text-xs text-gray-500">/{{ $category->slug }}</div>
            </div>
        </div>
    </td>
    <td class="px-6 py-4 text-sm text-gray-600">
        {{ $category->parent?->name ?? 'Root' }}
    </td>
    <td class="px-6 py-4 text-center text-sm text-gray-600">
        {{ $category->sort_order }}
    </td>
    <td class="px-6 py-4 text-center">
        @if (auth('admin')->user()?->can('edit_category'))
            <button type="button"
                wire:click="setActive({{ $category->id }}, {{ $category->is_active ? 'false' : 'true' }})"
                class="rounded-full px-3 py-1 text-xs font-semibold
                    {{ $category->is_active
                        ? 'bg-green-100 text-green-700'
                        : 'bg-gray-100 text-gray-600' }}">
                {{ $category->is_active ? 'Hiện' : 'Ẩn' }}
            </button>
        @else
            <span class="text-xs font-semibold {{ $category->is_active ? 'text-green-600' : 'text-gray-400' }}">
                {{ $category->is_active ? 'Hiện' : 'Ẩn' }}
            </span>
        @endif
    </td>
    <td class="px-6 py-4 text-right text-sm">
        @if (auth('admin')->user()?->can('edit_category'))
            <a href="{{ route('admin.category.edit', $category->id) }}"
                class="mr-3 text-indigo-600 hover:text-indigo-900">Sửa</a>
        @endif

        @if (auth('admin')->user()?->can('delete_category'))
            <button type="button"
                wire:click="requestDelete({{ $category->id }})"
                class="text-red-600 hover:text-red-900">
                Xóa
            </button>
        @endif
    </td>
</tr>

@if ($hasChildren && $isExpanded)
    @foreach ($children as $child)
        @include('Category::livewire.categories.partials.category-row', [
            'category' => $child,
            'depth' => $depth + 1,
            'expandedCategoryIds' => $expandedCategoryIds,
            'visibleCategoryIds' => $visibleCategoryIds,
        ])
    @endforeach
@endif
