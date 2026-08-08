<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="text-sm font-medium text-gray-700">Tìm kiếm</label>
                <input type="text" wire:model.live="search" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Tên Page hoặc Page ID">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                <select wire:model.live="isActive" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option>
                    <option value="1">Đang bật</option>
                    <option value="0">Đang tắt</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Hiển thị</label>
                <select wire:model.live="perPage" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Fanpage</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Token</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Trạng thái</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($pages as $page)
                        <tr wire:key="facebook-page-{{ $page->id }}">
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900">{{ $page->page_name }}</div>
                                <div class="text-sm text-gray-500">{{ $page->page_id }} · {{ $page->page_category ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">{{ $page->masked_page_access_token }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $page->is_active ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-gray-50 text-gray-700 ring-gray-600/20' }}">{{ $page->is_active ? 'Đang bật' : 'Đang tắt' }}</span>
                                @if ($page->is_default)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20">Mặc định</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right text-sm">
                                <button wire:click="verify({{ $page->id }})" wire:loading.attr="disabled" class="font-semibold text-indigo-600 hover:text-indigo-500">Kiểm tra</button>
                                <button wire:click="setDefault({{ $page->id }})" wire:loading.attr="disabled" class="ml-3 font-semibold text-indigo-600 hover:text-indigo-500">Mặc định</button>
                                <button wire:click="toggle({{ $page->id }}, {{ $page->is_active ? 'false' : 'true' }})" wire:loading.attr="disabled" class="ml-3 font-semibold text-gray-700 hover:text-gray-900">{{ $page->is_active ? 'Tắt' : 'Bật' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">Chưa có Fanpage. Hãy kết nối Facebook và đồng bộ Page.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($perPage !== 'All' && $pages->hasPages())
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">{{ $pages->links() }}</div>
        @endif
    </div>
</div>
