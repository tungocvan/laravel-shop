<div class="space-y-6" @if ($syncId && in_array($syncState, ['queued', 'processing'], true)) wire:poll.2s="pollStatus" @endif>
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="grid gap-4 p-6 md:grid-cols-3">
            <div>
                <label class="text-sm font-medium text-gray-700">Từ ngày</label>
                <input type="date" wire:model.live="start_date" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
                @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Đến ngày</label>
                <input type="date" wire:model.live="end_date" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
                @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Loại hóa đơn</label>
                <select wire:model.live="vatIn" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">
                    <option value="0">Bán ra</option>
                    <option value="1">Mua vào</option>
                </select>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 px-6 py-4">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model.live="useQueue" class="rounded border-gray-300"> Xử lý qua queue
            </label>
            <button wire:click="run" wire:loading.attr="disabled" class="h-11 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white disabled:opacity-50">
                <span wire:loading.remove wire:target="run">Chạy đồng bộ</span>
                <span wire:loading wire:target="run">Đang xử lý…</span>
            </button>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-gray-900">Trạng thái đồng bộ</p>
                <p class="mt-1 text-sm text-gray-600">Queue chỉ là trạng thái đã xếp hàng; khối này sẽ tự cập nhật đến khi hoàn tất hoặc thất bại.</p>
            </div>
            @php
                $stateLabel = match ($syncState) {
                    'queued' => 'Đang chờ queue',
                    'processing' => 'Đang xử lý',
                    'completed' => 'Hoàn tất',
                    'failed' => 'Thất bại',
                    default => 'Chưa chạy',
                };
                $stateClass = match ($syncState) {
                    'completed' => 'bg-emerald-50 text-emerald-700',
                    'failed' => 'bg-red-50 text-red-700',
                    'queued', 'processing' => 'bg-amber-50 text-amber-700',
                    default => 'bg-gray-100 text-gray-600',
                };
            @endphp
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $stateClass }}">{{ $stateLabel }}</span>
        </div>

        @if (in_array($syncState, ['queued', 'processing'], true))
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full w-2/3 animate-pulse rounded-full bg-indigo-500"></div>
            </div>
        @endif

        @if ($syncMessage)
            <p class="mt-3 text-sm text-gray-700">{{ $syncMessage }}</p>
        @endif
        @if ($syncFile)
            <p class="mt-2 break-all text-xs text-gray-500">File: {{ basename($syncFile) }}</p>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Import vào Database</h3>
                    <p class="mt-1 text-sm text-gray-500">Chọn file Excel đã đồng bộ từ GDT hoặc upload file XLSX/CSV từ máy tính.</p>
                </div>
                <button wire:click="refreshAvailableFiles" class="h-10 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700">Làm mới danh sách</button>
            </div>
        </div>

        <div class="grid gap-6 p-6 lg:grid-cols-2">
            <div>
                <p class="mb-3 text-sm font-semibold text-gray-900">File đã đồng bộ</p>
                <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                    @forelse ($availableFiles as $file)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 hover:bg-gray-50">
                            <input type="radio" wire:model.live="selectedFile" value="{{ $file['name'] }}" class="mt-1 border-gray-300 text-indigo-600">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-gray-800">{{ $file['name'] }}</span>
                                <span class="mt-1 block text-xs text-gray-500">{{ number_format($file['size'] / 1024, 1) }} KB · {{ $file['modified_at'] }}</span>
                            </span>
                        </label>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">Chưa có file {{ $vatIn ? 'mua vào' : 'bán ra' }} đã đồng bộ.</div>
                    @endforelse
                </div>
                <button wire:click="importSelectedFile" wire:loading.attr="disabled" @disabled(!$selectedFile)
                    class="mt-4 h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">
                    <span wire:loading.remove wire:target="importSelectedFile">Import file đã chọn</span>
                    <span wire:loading wire:target="importSelectedFile">Đang import…</span>
                </button>
                @error('selectedFile') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                <p class="text-sm font-semibold text-gray-900">Upload file để import</p>
                <p class="mt-1 text-xs text-gray-500">Hỗ trợ XLSX/CSV, tối đa 20 MB. File upload tạm sẽ bị xóa sau khi import.</p>
                <input type="file" wire:model="uploadFile" accept=".xlsx,.csv"
                    class="mt-4 block w-full rounded-xl border border-gray-300 bg-white p-3 text-sm text-gray-700">
                @error('uploadFile') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                @if ($uploadFile)
                    <p class="mt-2 truncate text-xs text-gray-600">Đã chọn: {{ $uploadFile->getClientOriginalName() }}</p>
                @endif
                <button wire:click="importUploadedFile" wire:loading.attr="disabled" @disabled(!$uploadFile)
                    class="mt-4 h-11 rounded-xl bg-slate-800 px-5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">
                    <span wire:loading.remove wire:target="importUploadedFile">Upload & Import</span>
                    <span wire:loading wire:target="importUploadedFile">Đang upload/import…</span>
                </button>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-gray-950 p-5 font-mono text-xs text-gray-200 shadow-sm">
        <p class="mb-3 font-sans text-sm font-semibold text-white">Nhật ký xử lý</p>
        <div class="max-h-80 space-y-1 overflow-y-auto">
            @forelse ($logs as $line)
                <div>{{ $line }}</div>
            @empty
                <div class="text-gray-400">Chưa có tác vụ.</div>
            @endforelse
        </div>
    </div>
</div>
