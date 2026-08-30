<div class="px-4 py-6 sm:px-6 lg:px-8">
    @php($adminUser = auth('admin')->user())
    @php($hasActiveFilters = $search !== '' || $filterCategory !== '' || $filterStatus !== '' || $perPage !== 10)

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Bài viết & Tin tức</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý nội dung blog, trạng thái xuất bản và metadata bài viết.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($adminUser?->can('view_post'))
                <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="export">Export</span>
                    <span wire:loading wire:target="export">Đang xuất...</span>
                </button>
            @endif

            @if($adminUser?->can('create_post'))
                <button type="button" wire:click="$toggle('isImporting')"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    {{ $isImporting ? 'Đóng Import' : 'Import' }}
                </button>

                <a href="{{ route('admin.posts.create') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-indigo-600 bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    + Viết bài mới
                </a>
            @endif
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">
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

    <section class="mb-5 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm" aria-label="Bộ lọc bài viết">
        @if(count($selected) > 0)
            <div class="flex flex-col gap-3 rounded-xl bg-indigo-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="resetSelection" class="rounded-lg p-2 text-indigo-600 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-200" title="Hủy chọn">
                        <span aria-hidden="true">×</span>
                    </button>
                    <span class="text-sm font-semibold text-indigo-900">
                        Đã chọn <strong>{{ count($selected) }}</strong> bài viết trên trang hiện tại
                    </span>
                </div>

                @if($adminUser?->can('delete_post'))
                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Bạn có chắc chắn muốn xóa {{ count($selected) }} bài viết này không?"
                        wire:loading.attr="disabled" wire:target="deleteSelected"
                        class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition hover:border-red-300 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-100 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="deleteSelected">Xóa đã chọn</span>
                        <span wire:loading wire:target="deleteSelected">Đang xóa...</span>
                    </button>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(18rem,1fr)_13rem_11rem_9rem_auto]">
                <div>
                    <label for="post-search" class="sr-only">Tìm kiếm bài viết</label>
                    <input id="post-search" wire:model.live.debounce.300ms="search" type="search"
                        placeholder="Tìm theo tiêu đề hoặc slug..."
                        class="h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </div>

                <div>
                    <label for="post-category-filter" class="sr-only">Lọc theo danh mục</label>
                    <select id="post-category-filter" wire:model.live="filterCategory"
                        class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="post-status-filter" class="sr-only">Lọc theo trạng thái</label>
                    <select id="post-status-filter" wire:model.live="filterStatus"
                        class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <option value="">Mọi trạng thái</option>
                        <option value="published">Đã xuất bản</option>
                        <option value="draft">Bản nháp</option>
                        <option value="hidden">Đang ẩn</option>
                    </select>
                </div>

                <div>
                    <label for="post-per-page" class="sr-only">Số bài trên trang</label>
                    <select id="post-per-page" wire:model.live="perPage"
                        class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}/trang</option>
                        @endforeach
                    </select>
                </div>

                @if($hasActiveFilters)
                    <button type="button" wire:click="resetFilters"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        Xóa bộ lọc
                    </button>
                @endif
            </div>
        @endif
    </section>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="w-10 px-4 py-3.5 text-left">
                            <input type="checkbox" wire:model.live="selectAll" aria-label="Chọn tất cả bài viết trên trang"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Bài viết</th>
                        <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Danh mục</th>
                        <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Trạng thái</th>
                        <th scope="col" class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Ngày đăng</th>
                        <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($posts as $post)
                        <tr class="transition hover:bg-gray-50 {{ in_array((string) $post->id, $selected, true) ? 'bg-indigo-50/60' : '' }}">
                            <td class="px-4 py-4 align-middle">
                                <input type="checkbox" wire:model.live="selected" value="{{ $post->id }}" aria-label="Chọn {{ $post->name }}"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex min-w-[18rem] items-center gap-3">
                                    <div class="h-12 w-16 flex-shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                                        @if($post->thumbnail)
                                            @if(\Illuminate\Support\Str::startsWith($post->thumbnail, ['http://', 'https://']))
                                                <img src="{{ $post->thumbnail }}" class="h-full w-full object-cover" alt="">
                                            @else
                                                <img src="{{ asset('storage/' . $post->thumbnail) }}" class="h-full w-full object-cover" alt="">
                                            @endif
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-xs font-medium text-gray-400">IMG</div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="max-w-xl truncate text-sm font-semibold text-gray-900" title="{{ $post->name }}">{{ $post->name }}</div>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                            <span>{{ $post->author->name ?? 'Admin' }}</span>
                                            @if($post->is_featured)
                                                <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 font-semibold text-amber-700">Nổi bật</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex min-w-[10rem] flex-wrap gap-1.5">
                                    @forelse($post->categories as $cat)
                                        <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600">{{ $cat->name }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400">Chưa phân loại</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="px-5 py-4 text-center">
                                @if($post->status === 'published')
                                    <span class="inline-flex rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">Xuất bản</span>
                                @elseif($post->status === 'draft')
                                    <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-600">Nháp</span>
                                @else
                                    <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Ẩn</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-center text-xs text-gray-500">
                                {{ ($post->published_at ?? $post->created_at)?->format('d/m/Y') }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if($adminUser?->can('edit_post'))
                                        <a href="{{ route('admin.posts.edit', $post->id) }}" class="rounded-lg p-2 text-gray-500 transition hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100" title="Sửa" aria-label="Sửa {{ $post->name }}">✎</a>
                                    @endif

                                    @if($adminUser?->can('create_post'))
                                        <button type="button" wire:click="clone({{ $post->id }})" wire:loading.attr="disabled" wire:target="clone({{ $post->id }})"
                                            class="rounded-lg p-2 text-gray-500 transition hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-100 disabled:opacity-50" title="Nhân bản" aria-label="Nhân bản {{ $post->name }}">⧉</button>
                                    @endif

                                    @if($adminUser?->can('delete_post'))
                                        <button type="button" wire:click="delete({{ $post->id }})" wire:confirm="Xóa bài viết này?" wire:loading.attr="disabled" wire:target="delete({{ $post->id }})"
                                            class="rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-100 disabled:opacity-50" title="Xóa" aria-label="Xóa {{ $post->name }}">⌫</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <p class="text-sm font-semibold text-gray-700">Không có bài viết phù hợp</p>
                                <p class="mt-1 text-sm text-gray-500">Thử thay đổi từ khóa hoặc xóa bộ lọc hiện tại.</p>
                                @if($hasActiveFilters)
                                    <button type="button" wire:click="resetFilters" class="mt-4 text-sm font-semibold text-indigo-600 hover:text-indigo-700">Xóa bộ lọc</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">
        {{ $posts->links('Post::vendor.pagination.admin-posts') }}
    </div>
</div>
