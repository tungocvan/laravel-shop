<div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
        <div>
            <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">Scan & Sync</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quét filesystem, xem preview và chỉ áp dụng những thay đổi an toàn đã xác nhận.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="scan" wire:loading.attr="disabled" wire:target="scan"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <span wire:loading.remove wire:target="scan">Quét lại</span><span wire:loading wire:target="scan">Đang quét...</span>
            </button>
            @if ($plan)
                <button type="button" wire:click="selectSafe" class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300">Chọn thay đổi an toàn</button>
                <button type="button" wire:click="apply" wire:confirm="Áp dụng các thay đổi đã chọn? Missing/Ambiguous sẽ không bị xóa tự động." wire:loading.attr="disabled" wire:target="apply"
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="apply">Áp dụng đã chọn</span><span wire:loading wire:target="apply">Đang đồng bộ...</span>
                </button>
            @endif
        </div>
    </div>

    <div class="p-5">
        @if (session()->has('ebook_sync_success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">{{ session('ebook_sync_success') }}</div>
        @endif
        @error('selected') <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</div> @enderror

        @if (! $plan)
            <div class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center dark:border-gray-700">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Chưa có kết quả quét</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bấm “Quét lại” để so sánh filesystem với metadata hiện tại.</p>
            </div>
        @else
            <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ([['New', $plan['summary']['new']], ['Changed', $plan['summary']['changed']], ['Missing', $plan['summary']['missing']], ['Moved', $plan['summary']['moves']], ['Ambiguous', $plan['summary']['ambiguous']]] as [$label, $count])
                    <div class="rounded-lg border border-gray-200 px-3 py-3 dark:border-gray-700">
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</div>
                        <div class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $count }}</div>
                    </div>
                @endforeach
            </div>

            <div class="space-y-5">
                @php
                    $safeGroups = [
                        ['title' => 'Thư mục mới', 'items' => $plan['new_folders'], 'description' => 'Tạo metadata folder cho thư mục đã có trên filesystem.'],
                        ['title' => 'Tài liệu mới', 'items' => $plan['new_files'], 'description' => 'Tạo metadata cho Markdown mới.'],
                        ['title' => 'Nội dung thay đổi', 'items' => $plan['changed'], 'description' => 'Cập nhật hash/mtime theo file hiện tại; không ghi đè content.'],
                        ['title' => 'Di chuyển / đổi tên chắc chắn', 'items' => $plan['moves'], 'description' => 'Chỉ xuất hiện khi content hash khớp duy nhất.'],
                    ];
                @endphp

                @foreach ($safeGroups as $group)
                    @if ($group['items'] !== [])
                        <section>
                            <div class="mb-2">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $group['title'] }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $group['description'] }}</p>
                            </div>
                            <div class="divide-y divide-gray-100 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                                @foreach ($group['items'] as $item)
                                    <label class="flex cursor-pointer items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <input type="checkbox" wire:model="selected" value="{{ $item['key'] }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="min-w-0 text-sm text-gray-700 dark:text-gray-200">
                                            @if (isset($item['from']))
                                                <span class="block break-all"><span class="text-gray-500">Từ:</span> {{ $item['from'] }}</span>
                                                <span class="block break-all"><span class="text-gray-500">Đến:</span> {{ $item['to'] }}</span>
                                            @else
                                                <span class="block break-all">{{ $item['path'] }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach

                @if ($plan['missing_folders'] !== [] || $plan['missing_files'] !== [])
                    <section class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20">
                        <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">Missing — chỉ cảnh báo, không tự xóa</h3>
                        <div class="mt-2 space-y-1 text-sm text-amber-800 dark:text-amber-300">
                            @foreach ($plan['missing_folders'] as $item)<div class="break-all">Folder: {{ $item['path'] }}</div>@endforeach
                            @foreach ($plan['missing_files'] as $item)<div class="break-all">File: {{ $item['path'] }}</div>@endforeach
                        </div>
                    </section>
                @endif

                @if ($plan['ambiguous'] !== [])
                    <section class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/20">
                        <h3 class="text-sm font-bold text-red-900 dark:text-red-200">Ambiguous — cần xử lý thủ công</h3>
                        @foreach ($plan['ambiguous'] as $item)
                            <div class="mt-2 text-sm text-red-800 dark:text-red-300">{{ $item['missing']['path'] }} có nhiều hoặc không đủ tín hiệu để xác định move/rename an toàn.</div>
                        @endforeach
                    </section>
                @endif

                @if (array_sum($plan['summary']) === 0)
                    <div class="rounded-xl border border-dashed border-emerald-300 bg-emerald-50 px-6 py-8 text-center text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-300">Filesystem và metadata đang đồng bộ.</div>
                @endif
            </div>
        @endif
    </div>
</div>
