<div class="space-y-5">
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <form wire:submit="search" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label for="hsmt-from-date" class="text-sm font-medium text-gray-700">Từ ngày</label>
                <input id="hsmt-from-date" type="date" wire:model.live="from_date" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('from_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="hsmt-to-date" class="text-sm font-medium text-gray-700">Đến ngày</label>
                <input id="hsmt-to-date" type="date" wire:model.live="to_date" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('to_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="hsmt-keyword" class="text-sm font-medium text-gray-700">Từ khóa</label>
                <input id="hsmt-keyword" type="search" wire:model.live="keyword" placeholder="Tên gói thầu..." class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('keyword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="search" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="search">Tìm kiếm</span>
                <span wire:loading wire:target="search">Đang tìm...</span>
            </button>
        </form>
    </div>

    @if ($error)
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    @if ($total > 0)
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">Tìm thấy <strong>{{ number_format($total, 0, ',', '.') }}</strong> kết quả; đang hiển thị trang đầu.</div>
    @endif

    @if ($results !== [])
        <div class="flex justify-end">
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" @disabled($selected === []) class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="exportExcel">Xuất Excel ({{ count($selected) }} mục)</span>
                <span wire:loading wire:target="exportExcel">Đang xuất...</span>
            </button>
        </div>
    @endif

    <div wire:loading.flex wire:target="search" class="items-center justify-center rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500">Đang tải dữ liệu...</div>

    <div wire:loading.remove wire:target="search" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if ($results !== [])
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3"><input type="checkbox" wire:model.live="selectAll" aria-label="Chọn tất cả kết quả đang hiển thị" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></th>
                            <th class="px-4 py-3">Tên gói thầu</th>
                            <th class="px-4 py-3">Mã TBMT</th>
                            <th class="px-4 py-3">Ngày đăng tải</th>
                            <th class="px-4 py-3">Đóng thầu</th>
                            <th class="px-4 py-3">Bên mời thầu</th>
                            <th class="px-4 py-3">Địa điểm</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @foreach ($results as $item)
                            @php($notifyNo = is_scalar($item['notifyNo'] ?? null) ? (string) $item['notifyNo'] : '')
                            <tr wire:key="hsmt-{{ md5($notifyNo . '-' . $loop->index) }}" class="hover:bg-gray-50">
                                <td class="px-4 py-3"><input type="checkbox" wire:model.live="selected" value="{{ $notifyNo }}" @disabled($notifyNo === '') aria-label="Chọn {{ $notifyNo ?: 'dòng không có mã' }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>
                                <td class="min-w-64 px-4 py-3 font-medium text-gray-900">{{ is_array($item['bidName'] ?? null) ? ($item['bidName'][0] ?? '-') : ($item['bidName'] ?? '-') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $notifyNo ?: '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item['publicDate'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item['bidOpenDate'] ?? '-' }}</td>
                                <td class="min-w-48 px-4 py-3">{{ $item['investorName'] ?? '-' }}</td>
                                <td class="min-w-48 px-4 py-3">{{ $item['locations'][0]['districtName'] ?? '-' }} / {{ $item['locations'][0]['provName'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center text-sm text-gray-500">Chưa có dữ liệu. Hãy chọn khoảng ngày và nhập từ khóa để tra cứu.</div>
        @endif
    </div>
</div>
