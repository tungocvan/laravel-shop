<div class="bg-white border border-gray-200 rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 bg-gray-50/50">
        <h3 class="font-semibold text-gray-700 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Lịch sử Backup
        </h3>
    </div>

    <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
        @forelse($backups as $file)
            <div class="p-4 hover:bg-gray-50 transition-colors group">
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-900 break-all">{{ $file['name'] }}</p>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span>{{ number_format($file['size'] / 1024, 2) }} KB</span>
                            <span>{{ \Carbon\Carbon::createFromTimestamp($file['created_at'])->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    {{-- Nút Download thông qua Controller chuẩn --}}
                    <a href="{{ route('admin.database.download', $file['name']) }}"
                        class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs font-medium hover:bg-blue-100">
                        Download
                    </a>

                    {{-- Nút Restore với xác nhận --}}
                    <button wire:click="restore('{{ $file['name'] }}')"
                        wire:confirm="CẢNH BÁO TỐI CAO: Hệ thống sẽ ghi đè toàn bộ dữ liệu hiện tại bằng file '{{ $file['name'] }}'. Bạn đã sao lưu dữ liệu hiện tại chưa?"
                        class="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-bold hover:bg-red-700 transition-colors">
                        RESTORE
                    </button>
                </div>
            </div>
        @empty
            <div class="p-8 text-center">
                <p class="text-sm text-gray-400">Chưa có bản backup nào được lưu.</p>
            </div>
        @endforelse
    </div>
</div>
