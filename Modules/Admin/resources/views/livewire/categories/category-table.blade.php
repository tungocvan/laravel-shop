<div>
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Quản lý Danh mục</h2>
            <p class="mt-1 text-sm text-gray-500">Phân loại dữ liệu cho hệ thống website.</p>
        </div>
        <a href="{{ route('admin.product-categories.create') }}"
            class="inline-flex items-center gap-x-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                    clip-rule="evenodd" />
            </svg>
            Thêm danh mục
        </a>
    </div>

    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button wire:click="setType('product')"
                class="{{ $type === 'product' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors flex items-center gap-2">
                <span>🛍️</span> Danh mục Sản phẩm
            </button>
            <button wire:click="setType('post')"
                class="{{ $type === 'post' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors flex items-center gap-2">
                <span>📝</span> Danh mục Bài viết
            </button>
        </nav>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden relative">
        <div wire:loading.flex wire:target="setType, delete, toggleStatus"
            class="absolute inset-0 bg-white/60 z-10 items-center justify-center backdrop-blur-[1px]">
            <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Tên
                            danh mục</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Thứ
                            tự</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">
                            Trạng thái</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Hành
                            động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($categories as $parent)

                        <tr class="hover:bg-gray-50/50 transition group">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 mr-4">
                                        @php
                                            $image = $parent->image;
                                            if ($image && str_starts_with($image, 'http')) {
                                                // Là link đầy đủ
                                                $src = $image;
                                            } elseif ($image && file_exists(storage_path('app/public/' . $image))) {
                                                // Ảnh local tồn tại
                                                $src = asset('storage/' . $image);
                                            } else {
                                                // Không tồn tại → dùng ảnh mặc định
                                                $src = 'https://placehold.co/100';
                                            }
                                        @endphp
                                        <img class="h-10 w-10 rounded-lg object-cover border border-gray-200"
                                            src="{{ $src }}">
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 group-hover:text-indigo-600 transition">
                                            {{ $parent->name }}</div>
                                        <div class="text-xs text-gray-500 font-mono">/{{ $parent->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-gray-500">{{ $parent->sort_order }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="toggleStatus({{ $parent->id }})"
                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $parent->is_active ? 'bg-green-500' : 'bg-gray-200' }}">
                                    <span
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $parent->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <a href="{{ route('admin.product-categories.edit', $parent->id) }}"
                                    class="text-indigo-600 hover:text-indigo-900 mr-3">Sửa</a>
                                <button wire:confirm="Xóa danh mục này sẽ ảnh hưởng đến sản phẩm?"
                                    wire:click="delete({{ $parent->id }})"
                                    class="text-red-600 hover:text-red-900">Xóa</button>
                            </td>
                        </tr>

                        @foreach ($parent->children as $child)
                            <tr class="bg-slate-50 hover:bg-slate-100 transition">
                                <td class="px-6 py-3 pl-16">
                                    <div class="flex items-center relative">
                                        <div class="absolute -left-6 top-1/2 w-4 h-px bg-gray-300"></div>
                                        <div class="absolute -left-6 bottom-1/2 w-px h-[200%] bg-gray-300"></div>

                                        <div class="h-8 w-8 flex-shrink-0 mr-3">
                                            @if ($child->image)
                                                <img class="h-8 w-8 rounded-md object-cover"
                                                    src="{{ asset('storage/' . $child->image) }}">
                                            @endif
                                        </div>
                                        <span class="text-sm text-gray-700 font-medium">{{ $child->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-center text-sm text-gray-500">{{ $child->sort_order }}</td>
                                <td class="px-6 py-3 text-center">
                                    <button wire:click="toggleStatus({{ $child->id }})"
                                        class="text-xs font-semibold {{ $child->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                        {{ $child->is_active ? 'Hiện' : 'Ẩn' }}
                                    </button>
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-medium">
                                    <a href="{{ route('admin.product-categories.edit', $child->id) }}"
                                        class="text-indigo-600 hover:text-indigo-900 mr-2">Sửa</a>
                                    <button wire:confirm="Xóa?" wire:click="delete({{ $child->id }})"
                                        class="text-red-600 hover:text-red-900">Xóa</button>
                                </td>
                            </tr>

                            @foreach ($child->children as $grandChild)
                                <tr class="bg-white hover:bg-gray-50 transition">
                                    <td class="px-6 py-2 pl-28">
                                        <div class="flex items-center relative">
                                            <div class="absolute -left-6 top-1/2 w-4 h-px bg-gray-200"></div>
                                            <div class="absolute -left-6 bottom-1/2 w-px h-[200%] bg-gray-200"></div>
                                            <span class="text-sm text-gray-500 italic">{{ $grandChild->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-2 text-center text-xs text-gray-400">
                                        {{ $grandChild->sort_order }}</td>
                                    <td class="px-6 py-2 text-center">
                                        <div
                                            class="h-2 w-2 rounded-full {{ $grandChild->is_active ? 'bg-green-400' : 'bg-gray-300' }} mx-auto">
                                        </div>
                                    </td>
                                    <td class="px-6 py-2 text-right text-xs font-medium">
                                        <a href="{{ route('admin.product-categories.edit', $grandChild->id) }}"
                                            class="text-indigo-600 hover:text-indigo-900 mr-2">Sửa</a>
                                        <button wire:confirm="Xóa?" wire:click="delete({{ $grandChild->id }})"
                                            class="text-red-600 hover:text-red-900">Xóa</button>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach

                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic">Chưa có danh mục nào
                                cho loại này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
