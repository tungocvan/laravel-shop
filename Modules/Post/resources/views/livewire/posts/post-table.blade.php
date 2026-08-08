<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
    @php($adminUser = auth('admin')->user())

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Bài viết & Tin tức</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý nội dung blog của hệ thống.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($adminUser?->can('view_post'))
                <button wire:click="export" wire:loading.attr="disabled" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export
                </button>
            @endif

            @if($adminUser?->can('create_post'))
                <button wire:click="$toggle('isImporting')" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Import
                </button>

                <a href="{{ route('admin.posts.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Viết bài mới
                </a>
            @endif
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($isImporting)
        <div class="mb-6">
            @livewire('shared.import-export.panel', [
                'serviceClass' => \Modules\Post\Services\ImportExport::class,
                'title' => 'Import / Export bài viết',
                'description' => 'Nhập file CSV/XLSX theo template chuẩn. Export dùng bộ lọc hiện tại.',
                'filters' => [
                    'search' => $search,
                    'category_id' => $filterCategory,
                    'status' => $filterStatus,
                    'ids' => $selected,
                ],
            ], key('post-import-export-' . md5(json_encode([$search, $filterCategory, $filterStatus, $selected]))))
        </div>
    @endif

    <div class="mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if(count($selected) > 0)
                <div class="p-3 bg-indigo-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <button wire:click="resetSelection" class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-100 transition" title="Hủy chọn">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>

                        <span class="text-sm font-semibold text-indigo-900">
                            Đã chọn <span class="font-bold text-indigo-700 text-base mx-1">{{ count($selected) }}</span> bài viết trên trang hiện tại
                        </span>
                    </div>

                    @if($adminUser?->can('delete_post'))
                        <button
                            wire:click="deleteSelected"
                            wire:confirm="Bạn có chắc chắn muốn xóa {{ count($selected) }} bài viết này không?"
                            class="inline-flex items-center justify-center px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-bold shadow-sm hover:bg-red-50 hover:border-red-300 hover:text-red-700 transition"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Xóa đã chọn
                        </button>
                    @endif
                </div>
            @else
                <div class="p-2 flex flex-col lg:flex-row gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 0114 0z"/></svg>
                        </div>
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Tìm kiếm theo tiêu đề hoặc slug..."
                            class="block w-full pl-10 pr-3 py-2 border-transparent bg-transparent focus:border-transparent focus:ring-0 text-gray-900 placeholder-gray-400 sm:text-sm h-10"
                        >
                    </div>

                    <div class="hidden lg:block w-px bg-gray-200 my-1"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <select wire:model.live="filterCategory" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                            <option value="">Danh mục</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="filterStatus" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                            <option value="">Trạng thái</option>
                            <option value="published">Đã xuất bản</option>
                            <option value="draft">Bản nháp</option>
                            <option value="hidden">Đang ẩn</option>
                        </select>

                        <select wire:model.live="perPage" class="block w-full pl-3 pr-8 py-2 text-sm text-gray-600 bg-gray-50 border-0 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 transition cursor-pointer h-10 font-medium">
                            @foreach($perPageOptions as $option)
                                <option value="{{ $option }}">{{ $option }}/trang</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50/50">
                <tr>
                    <th scope="col" class="px-4 py-4 text-left w-10">
                        <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bài viết</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Danh mục</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày đăng</th>
                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Hành động</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($posts as $post)
                    <tr class="hover:bg-gray-50 transition {{ in_array((string) $post->id, $selected, true) ? 'bg-indigo-50/50' : '' }}">
                        <td class="px-4 py-4">
                            <input type="checkbox" wire:model.live="selected" value="{{ $post->id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-start">
                                <div class="h-12 w-16 flex-shrink-0 rounded bg-gray-100 border overflow-hidden">
                                    @if($post->thumbnail)
                                        @if(\Illuminate\Support\Str::startsWith($post->thumbnail, ['http://', 'https://']))
                                            <img src="{{ $post->thumbnail }}" class="h-full w-full object-cover" alt="">
                                        @else
                                            <img src="{{ asset('storage/' . $post->thumbnail) }}" class="h-full w-full object-cover" alt="">
                                        @endif
                                    @else
                                        <div class="h-full w-full flex items-center justify-center text-xs text-gray-400">IMG</div>
                                    @endif
                                </div>
                                <div class="ml-3 min-w-0">
                                    <div class="text-sm font-bold text-gray-900 line-clamp-1" title="{{ $post->name }}">{{ $post->name }}</div>
                                    <div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-2">
                                        <span>{{ $post->author->name ?? 'Admin' }}</span>
                                        @if($post->is_featured)
                                            <span class="text-amber-600 font-bold bg-amber-50 px-1 rounded border border-amber-100">HOT</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse($post->categories as $cat)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">{{ $cat->name }}</span>
                                @empty
                                    <span class="text-xs text-gray-400">-</span>
                                @endforelse
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($post->status === 'published')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">Xuất bản</span>
                            @elseif($post->status === 'draft')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10">Nháp</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10">Ẩn</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center text-xs text-gray-500">
                            {{ $post->created_at?->format('d/m/Y') }}
                        </td>

                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex justify-end items-center gap-3">
                                @if($adminUser?->can('edit_post'))
                                    <a href="{{ route('admin.posts.edit', $post->id) }}" class="text-gray-400 hover:text-indigo-600" title="Sửa">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                @endif

                                @if($adminUser?->can('create_post'))
                                    <button wire:click="clone({{ $post->id }})" class="text-gray-400 hover:text-green-600" title="Nhân bản bài viết">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    </button>
                                @endif

                                @if($adminUser?->can('delete_post'))
                                    <button wire:confirm="Xóa bài viết này?" wire:click="delete({{ $post->id }})" class="text-gray-400 hover:text-red-600" title="Xóa">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            Chưa có bài viết phù hợp với bộ lọc hiện tại.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $posts->links() }}
    </div>
</div>
