<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <input type="text" wire:model.live="search" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Tìm nội dung, tiêu đề, Facebook ID">
            <select wire:model.live="status" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <option value="">Tất cả trạng thái</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="perPage" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Bài đăng</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Page</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Trạng thái</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($posts as $post)
                        <tr wire:key="facebook-post-{{ $post->id }}">
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900">{{ $post->title ?: 'Bài #' . $post->id }}</div>
                                <div class="mt-1 max-w-xl truncate text-sm text-gray-500">{{ $post->message ?: $post->link_url }}</div>
                                <div class="mt-1 text-xs text-gray-400">{{ $post->post_type_label }} · {{ $post->scheduled_at?->format('Y-m-d H:i') ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">{{ $post->page?->page_name ?? '-' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-600">{{ $post->status_label }}</td>
                            <td class="px-4 py-4 text-right text-sm">
                                <a href="{{ route('admin.facebook.posts.show', ['id' => $post->id]) }}" class="font-semibold text-gray-700 hover:text-gray-900">Xem</a>
                                @if (in_array($post->status, ['draft', 'scheduled', 'failed']))
                                    <button wire:click="publish({{ $post->id }})" wire:loading.attr="disabled" class="ml-3 font-semibold text-indigo-600 hover:text-indigo-500">Đăng</button>
                                @endif
                                @if ($post->status === 'failed')
                                    <button wire:click="retry({{ $post->id }})" wire:loading.attr="disabled" class="ml-3 font-semibold text-amber-600 hover:text-amber-500">Retry</button>
                                @endif
                                <button wire:click="duplicate({{ $post->id }})" wire:loading.attr="disabled" class="ml-3 font-semibold text-gray-700 hover:text-gray-900">Nhân bản</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">Chưa có bài đăng Facebook.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($perPage !== 'All' && $posts->hasPages())
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">{{ $posts->links() }}</div>
        @endif
    </div>
</div>
