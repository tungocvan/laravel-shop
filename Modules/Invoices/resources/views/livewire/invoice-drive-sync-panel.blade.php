<div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 p-6">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Đồng bộ Local ↔ Google Drive</h3>
            <p class="mt-1 text-sm text-gray-500">Chủ động chọn file để upload lên Drive hoặc tải từ Drive về local. Không tự ghi đè file local đã tồn tại.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $driveConnected ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                {{ $driveConnected ? 'Google Drive đã kết nối' : 'Google Drive chưa kết nối' }}
            </span>
            <button wire:click="refreshFiles" wire:loading.attr="disabled" class="h-10 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700 disabled:opacity-50">
                Làm mới
            </button>
        </div>
    </div>

    @if($notice)
        <div class="mx-6 mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ $notice }}</div>
    @endif
    @if($error)
        <div class="mx-6 mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $error }}</div>
    @endif

    <div class="grid gap-6 p-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-gray-900">Local</p>
                    <p class="mt-1 text-xs text-gray-500">File trong kho đồng bộ trên server.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">{{ count($localFiles) }} file</span>
            </div>

            <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                @forelse($localFiles as $file)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 hover:border-indigo-200 hover:bg-indigo-50/30">
                        <input type="radio" wire:model.live="selectedLocalFile" value="{{ $file['token'] }}" class="mt-1 border-gray-300 text-indigo-600">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-gray-800">{{ $file['name'] }}</span>
                            <span class="mt-1 block text-xs text-gray-500">{{ $file['type_label'] }} · {{ number_format($file['size']/1024,1) }} KB · {{ $file['modified_at'] }}</span>
                        </span>
                    </label>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">Chưa có file XLSX ở local.</div>
                @endforelse
            </div>

            <button wire:click="uploadSelectedToDrive" wire:loading.attr="disabled" @disabled(!$selectedLocalFile || !$driveConnected)
                class="mt-4 h-11 w-full rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">
                <span wire:loading.remove wire:target="uploadSelectedToDrive">Upload file đã chọn lên Drive</span>
                <span wire:loading wire:target="uploadSelectedToDrive">Đang upload…</span>
            </button>
        </section>

        <section class="rounded-2xl border border-blue-100 bg-blue-50/30 p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-gray-900">Google Drive</p>
                    <p class="mt-1 text-xs text-gray-500">Thư mục Laravel-Backup/Invoices.</p>
                </div>
                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-blue-700">{{ count($driveFiles) }} file</span>
            </div>

            <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                @if(!$driveConnected)
                    <div class="rounded-xl border border-dashed border-blue-200 bg-white p-6 text-center text-sm text-gray-500">Hãy kết nối Google Drive trong System trước.</div>
                @else
                    @forelse($driveFiles as $file)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-blue-100 bg-white p-3 hover:border-blue-300">
                            <input type="radio" wire:model.live="selectedDriveFile" value="{{ $file['id'] }}" class="mt-1 border-gray-300 text-blue-600">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-gray-800">{{ $file['name'] }}</span>
                                <span class="mt-1 block text-xs text-gray-500">{{ $file['direction'] === 'vat_in' ? 'Mua vào' : 'Bán ra' }} · {{ number_format(($file['size'] ?? 0)/1024,1) }} KB</span>
                            </span>
                        </label>
                    @empty
                        <div class="rounded-xl border border-dashed border-blue-200 bg-white p-6 text-center text-sm text-gray-500">Chưa có file hóa đơn trong Laravel-Backup/Invoices.</div>
                    @endforelse
                @endif
            </div>

            <button wire:click="downloadSelectedFromDrive" wire:loading.attr="disabled" @disabled(!$selectedDriveFile || !$driveConnected)
                class="mt-4 h-11 w-full rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">
                <span wire:loading.remove wire:target="downloadSelectedFromDrive">Đồng bộ file đã chọn về Local</span>
                <span wire:loading wire:target="downloadSelectedFromDrive">Đang tải về…</span>
            </button>
            <p class="mt-2 text-xs text-gray-500">Nếu file cùng tên đã tồn tại ở local, hệ thống chỉ báo trạng thái và không tải đè.</p>
        </section>
    </div>
</div>
