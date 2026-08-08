<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Danh sách sản phẩm</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý kho hàng, giá bán và phân loại sản phẩm.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button wire:click="export" wire:loading.attr="disabled" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </button>

            <button wire:click="$set('showImportModal', true)" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Import
            </button>

            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Thêm sản phẩm
            </a>        
        </div>
    </div>

    <div class="mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 relative">

            <div wire:loading.flex wire:target="search, category_id, perPage, deleteSelected, applyCategories"
                 class="absolute inset-0 bg-white/60 z-20 items-center justify-center backdrop-blur-[1px]">
                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>

            @if(count($selected) > 0)
                <div class="p-3 bg-indigo-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 animate-fade-in">
                    <div class="flex items-center gap-3">
                        <button wire:click="$set('selected', [])" class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-100 transition" title="Hủy chọn">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <span class="text-sm font-semibold text-indigo-900">
                            Đã chọn <span class="font-bold text-indigo-700 text-base mx-1">{{ count($selected) }}</span> sản phẩm
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button wire:click="openCategoryModal" class="flex items-center px-3 py-2 bg-white border border-indigo-200 text-indigo-700 rounded-lg text-sm font-medium shadow-sm hover:bg-indigo-50 transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            Gán danh mục
                        </button>

                        <button wire:click="deleteSelected" wire:confirm="Xóa vĩnh viễn {{ count($selected) }} sản phẩm đã chọn?" class="flex items-center px-3 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-medium shadow-sm hover:bg-red-50 transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Xóa
                        </button>
                    </div>
                </div>

            @else
                <div class="p-2 flex flex-col md:flex-row gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Tìm kiếm theo tên, mã SKU..."
                            class="block w-full pl-10 pr-3 py-2 border-transparent bg-transparent focus:border-transparent focus:ring-0 text-gray-900 placeholder-gray-400 sm:text-sm h-10 transition"
                        >
                        @if($search)
                            <button wire:click="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 cursor-pointer">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>

                    <div class="hidden md:block w-px bg-gray-200 my-1"></div>

                    <div class="flex gap-2 overflow-x-auto pb-1 md:pb-0">
                        <div class="relative min-w-[160px]">
                            <select wire:model.live="category_id" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                                <option value="">📂 Tất cả danh mục</option>
                                @foreach($this->categories as $cat)
                                    <option value="{{ $cat->id }}" class="{{ $cat->parent_id == null ? 'font-bold text-gray-900' : 'text-gray-600' }}">
                                        {{ $cat->view_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="relative min-w-[100px]">
                            <select wire:model.live="perPage" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                                <option value="10">Hiển thị: 10</option>
                                <option value="25">Hiển thị: 25</option>
                                <option value="50">Hiển thị: 50</option>
                                <option value="100">Hiển thị: 100</option>
                                <option value="all">Tất cả</option>
                            </select>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden relative">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th scope="col" class="px-4 py-3 w-10 text-center">
                            <input type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                        </th>

                        @foreach(['title' => 'Sản phẩm', 'regular_price' => 'Giá bán', 'stock_qty' => 'Tồn kho', 'is_active' => 'Trạng thái'] as $field => $label)
                            <th scope="col" wire:click="sortBy('{{ $field }}')"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide cursor-pointer group hover:bg-gray-100 transition select-none {{ $sortColumn === $field ? 'text-indigo-600 font-bold bg-indigo-50' : 'text-gray-500' }} {{ in_array($field, ['regular_price', 'stock_qty']) ? 'text-right' : '' }} {{ $field === 'is_active' ? 'text-center' : '' }}">
                                <div class="flex items-center gap-1 {{ in_array($field, ['regular_price', 'stock_qty']) ? 'justify-end' : '' }} {{ $field === 'is_active' ? 'justify-center' : '' }}">
                                    {{ $label }}
                                    @if($sortColumn === $field)
                                        <svg class="w-3 h-3 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }} transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300 group-hover:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Danh mục</th>
                        <th scope="col" class="relative px-6 py-3 text-right"><span class="sr-only">Hành động</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($products as $item)
                        <tr class="hover:bg-gray-50 transition duration-150 {{ in_array($item->id, $selected) ? 'bg-indigo-50/40' : '' }}">
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" wire:model.live="selected" value="{{ $item->id }}" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-12 w-12 flex-shrink-0 rounded-lg border border-gray-200 bg-gray-100 overflow-hidden relative group">

                                        <img class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                             src="{{ $item->image ? (Illuminate\Support\Str::startsWith($item->image, ['http']) ? $item->image : asset('storage/'.$item->image)) : 'https://placehold.co/100' }}"
                                             alt="">

                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900 line-clamp-1 hover:text-indigo-600 cursor-pointer transition" title="{{ $item->title }}">
                                            {{ $item->title }}
                                        </div>
                                        <div class="text-xs text-gray-500 font-mono mt-0.5">ID: {{ $item->id }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                @if($item->sale_price > 0 && $item->sale_price < $item->regular_price)
                                    <div class="text-sm font-bold text-red-600">{{ number_format($item->sale_price) }} ₫</div>
                                    <div class="text-xs text-gray-400 line-through">{{ number_format($item->regular_price) }} ₫</div>
                                @else
                                    <div class="text-sm font-semibold text-gray-900">{{ number_format($item->regular_price) }} ₫</div>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                @if($item->stock_qty > 0)
                                    <span class="text-sm font-medium text-gray-900">{{ $item->stock_qty }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Hết hàng</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button wire:click="toggleStatus({{ $item->id }})"
                                    class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $item->is_active ? 'bg-green-500' : 'bg-gray-200' }}">
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $item->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>

                             <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($item->categories as $cat)
                                        <span class="inline-flex items-center gap-x-0.5 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 hover:bg-blue-100 transition">
                                            {{ $cat->name }}
                                            <button type="button" wire:click="removeCategory({{ $item->id }}, {{ $cat->id }})" wire:confirm="Gỡ danh mục này?" class="group relative -mr-1 h-3.5 w-3.5 rounded-sm hover:bg-blue-600/20 flex items-center justify-center">
                                                <svg viewBox="0 0 14 14" class="h-3 w-3 stroke-blue-700/50 group-hover:stroke-blue-700/75"><path d="M4 4l6 6m0-6l-6 6" /></svg>
                                            </button>
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400 italic">Chưa phân loại</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <button wire:click="duplicate({{ $item->id }})" class="text-gray-400 hover:text-indigo-600 transition" title="Nhân bản">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </button>
                                    <a href="{{ route('admin.products.edit', $item->id) }}" class="text-gray-400 hover:text-blue-600 transition" title="Sửa">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <button wire:confirm="Xóa sản phẩm này?" wire:click="delete({{ $item->id }})" class="text-gray-400 hover:text-red-600 transition" title="Xóa">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                    <a href="{{ route('admin.products.commissions', $item->id) }}" 
                                        class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition" 
                                        title="Cấu hình hoa hồng">
                                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                         </svg>
                                     </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    </div>
                                    <h3 class="text-sm font-medium text-gray-900">Không tìm thấy sản phẩm</h3>
                                    <p class="mt-1 text-sm text-gray-500">Thử tìm kiếm từ khóa khác hoặc thêm sản phẩm mới.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-gray-50 border-t border-gray-200 px-4 py-3 sm:px-6">
            {{ $products->links() }}
        </div>
    </div>

    <div x-data="{ show: @entangle('showImportModal') }" x-show="show" style="display: none;" class="relative z-50">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="show = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all w-full max-w-lg">
                    <div class="px-6 py-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="h-6 w-6 text-indigo-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            Import Sản phẩm từ Excel
                        </h3>
                        <div class="space-y-4">
                            <label class="block w-full rounded-xl border-2 border-dashed border-gray-300 p-8 text-center hover:bg-gray-50 hover:border-indigo-400 cursor-pointer transition">
                                <span class="text-sm text-gray-600 font-medium" x-text="$wire.importFile ? 'Đã chọn: ' + $wire.importFile.name : 'Click để chọn file .xlsx'"></span>
                                <p class="text-xs text-gray-400 mt-1">Hỗ trợ file Excel chuẩn theo mẫu.</p>
                                <input type="file" wire:model="importFile" class="hidden" accept=".xlsx,.xls,.csv">
                            </label>
                            @error('importFile') <p class="text-red-500 text-xs font-semibold">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                        <button type="button" @click="show = false" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Hủy bỏ</button>
                        <button type="button" wire:click="import" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 rounded-lg text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-70">
                            <span wire:loading.remove wire:target="import">Tiến hành Import</span>
                            <span wire:loading wire:target="import">Đang tải...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-data="{ show: @entangle('showCategoryModal') }" x-show="show" style="display: none;" class="relative z-50">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="show = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all w-full max-w-lg">
                    <div class="px-6 py-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Gán danh mục hàng loạt</h3>
                        <p class="text-sm text-gray-500 mb-4">Áp dụng cho <strong>{{ count($selected) }}</strong> sản phẩm đã chọn.</p>

                        <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-2 bg-gray-50 custom-scrollbar space-y-1">
                            @foreach($this->categories as $cat)
                                <label class="flex items-center space-x-3 p-2 hover:bg-white hover:shadow-sm rounded-md cursor-pointer transition">
                                    <input type="checkbox" wire:model="bulkCategoryIds" value="{{ $cat->id }}" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                    <span class="text-sm text-gray-700 font-medium">{{ $cat->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('bulkCategoryIds') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                        <button type="button" @click="show = false" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Hủy</button>
                        <button type="button" wire:click="applyCategories" wire:loading.attr="disabled" class="px-4 py-2 bg-indigo-600 rounded-lg text-sm font-bold text-white hover:bg-indigo-700">
                            <span wire:loading.remove wire:target="applyCategories">Áp dụng</span>
                            <span wire:loading wire:target="applyCategories">Xử lý...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
