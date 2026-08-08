<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-4 border-b border-gray-200 flex flex-wrap justify-between items-center gap-4 bg-gray-50">
        <div class="flex items-center gap-2">
            <input type="checkbox" wire:model.live="selectAll"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-medium text-gray-700">Chọn tất cả</span>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="backupFull" wire:loading.attr="disabled"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg shadow-sm hover:bg-indigo-700 text-sm font-medium flex items-center gap-2 transition-all">
                <svg wire:loading.remove wire:target="backupFull" class="w-4 h-4" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>

                <span wire:loading wire:target="backupFull"
                    class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>

                <span>Full Backup</span>
            </button>

            <button wire:click="openRestoreModal" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Restore Database
            </button>

            <div class="relative w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="block w-full pl-10 sm:text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Tìm kiếm bảng...">
            </div>


        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">#
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên bảng
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số dòng
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dung
                        lượng (MB)</th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động
                        nhanh</th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quản lý
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tables as $table)
                    <tr class="hover:bg-gray-50 transition-colors" wire:key="row-{{ $table['name'] }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" value="{{ $table['name'] }}" wire:model.live="selectedTables"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $table['name'] }}
                            @if ($table['is_protected'])
                                <span
                                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Protected</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ number_format($table['rows']) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $table['size_mb'] }} MB
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            @if (!$table['has_backup'])
                                <button wire:click="exportTable('{{ $table['name'] }}')" wire:loading.attr="disabled"
                                    class="text-indigo-600 hover:text-indigo-900 inline-flex items-center gap-1">
                                    <svg wire:loading.remove wire:target="exportTable('{{ $table['name'] }}')"
                                        class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span wire:loading wire:target="exportTable('{{ $table['name'] }}')"
                                        class="animate-spin w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full"></span>
                                    Export
                                </button>
                            @else
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.system.database.download', ['filename' => $table['backup_file']]) }}"
                                        target="_blank"
                                        class="text-green-600 hover:text-green-900 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4">
                                            </path>
                                        </svg>
                                        SQL
                                    </a>

                                    <button wire:click="restoreTable('{{ $table['name'] }}')"
                                        wire:confirm="CẢNH BÁO: Restore sẽ ghi đè dữ liệu. Tiếp tục?"
                                        class="text-amber-600 hover:text-amber-900 flex items-center gap-1">
                                        <span wire:loading wire:target="restoreTable('{{ $table['name'] }}')"
                                            class="animate-spin w-4 h-4 border-2 border-amber-600 border-t-transparent rounded-full"></span>
                                        <svg wire:loading.remove wire:target="restoreTable('{{ $table['name'] }}')"
                                            class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                        Restore
                                    </button>

                                    <button wire:click="exportTable('{{ $table['name'] }}')"
                                        class="text-gray-400 hover:text-indigo-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if (!$table['is_protected'])
                                <div class="flex items-center justify-end gap-3">
                                    <button wire:click="truncateTable('{{ $table['name'] }}')"
                                        wire:confirm="NGUY HIỂM: Xóa sạch dữ liệu bảng {{ $table['name'] }}?"
                                        class="text-gray-400 hover:text-orange-600" title="Truncate">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                    <button wire:click="dropTable('{{ $table['name'] }}')"
                                        wire:confirm="CỰC KỲ NGUY HIỂM: XÓA BẢNG {{ $table['name'] }}?"
                                        class="text-gray-400 hover:text-red-600" title="Drop Table">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">Không tìm thấy dữ liệu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showRestoreModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div x-data="{ progress: 0, interval: null, start() { this.progress = 0;
                    this.interval = setInterval(() => { if (this.progress < 90) { this.progress += Math.random() * 7; } }, 400); }, finish() { this.progress = 100;
                    clearInterval(this.interval); } }" x-init=" $watch('$wire.isRestoring', value => { if (value) start();
                         else finish(); });"
                class="bg-white rounded-xl w-full max-w-lg p-6 shadow-xl">
                <h2 class="text-lg font-bold text-red-600 mb-3"> ⚠️ Restore Database </h2>
                <p class="text-sm text-gray-600 mb-4"> Toàn bộ dữ liệu hiện tại sẽ bị xóa. Không thể hoàn tác. </p>
                <select wire:model="selectedBackupFile" class="w-full border rounded-lg p-2 mb-4">
                    <option value="">-- Chọn file backup --</option>
                    @foreach ($backupFiles as $file)
                        <option value="{{ $file['path'] }}"> {{ $file['name'] }}
                            ({{ number_format($file['size'] / 1024, 2) }} KB) </option>
                    @endforeach
                </select> <!-- PROGRESS --> <template x-if="$wire.isRestoring">
                    <div class="mb-4">
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-red-500 h-3 rounded-full transition-all duration-500"
                                :style="'width:' + progress + '%'"></div>
                        </div>
                        <p class="text-xs mt-2 text-gray-600"> Đang restore... <span
                                x-text="Math.floor(progress)"></span>% </p>
                    </div>
                </template> <!-- ACTION -->
                <div class="flex justify-end gap-2"> <button wire:click="$set('showRestoreModal', false)"
                        class="px-4 py-2 bg-gray-200 rounded-lg"> Cancel </button> <button
                        wire:click="restoreDatabase" wire:loading.attr="disabled" @disabled($isRestoring)
                        onclick="return confirm('⚠️ XÓA toàn bộ dữ liệu. Tiếp tục?')"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg flex items-center gap-2"> <span
                            wire:loading.remove>Restore</span> <span wire:loading class="flex items-center gap-2">
                            <span
                                class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                            Đang chạy... </span> </button> </div>
            </div>
        </div>
    @endif

    @if (count($selectedTables) > 0)
        <div class="bg-indigo-50 px-4 py-3 border-t border-indigo-100 flex items-center justify-between">
            <span class="text-sm text-indigo-700 font-medium">Đã chọn {{ count($selectedTables) }} bảng</span>
            <button
                class="px-3 py-1 bg-white border border-indigo-200 text-indigo-700 rounded text-sm hover:bg-indigo-50">Export
                Selected</button>
        </div>
    @endif

    <div wire:loading wire:target="backupFull, exportTable, restoreTable"
        class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">

        <div class="fixed inset-0 bg-gray-900/75 transition-opacity backdrop-blur-sm"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">

                        <div
                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10
                        {{-- Nếu là Restore thì màu Vàng, còn lại là màu Xanh --}}
                        bg-indigo-100 wire:loading.class.remove='bg-indigo-100' wire:target='restoreTable'
                        wire:loading.class='bg-amber-100' wire:target='restoreTable'">

                            <svg wire:loading.remove wire:target="restoreTable"
                                class="animate-spin h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>

                            <svg wire:loading wire:target="restoreTable" class="animate-spin h-6 w-6 text-amber-600"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>

                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">

                            <div wire:loading.remove wire:target="restoreTable">
                                <h3 class="text-base font-semibold leading-6 text-indigo-600" id="modal-title">
                                    Đang Xuất Dữ Liệu (Exporting)...
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Hệ thống đang đóng gói dữ liệu và tạo file SQL.
                                        <br>Vui lòng chờ trong giây lát.
                                    </p>
                                </div>
                            </div>

                            <div wire:loading wire:target="restoreTable">
                                <h3 class="text-base font-semibold leading-6 text-amber-600" id="modal-title">
                                    Đang Phục Hồi Dữ Liệu (Restoring)...
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Hệ thống đang nạp dữ liệu từ file Backup vào Database.
                                        <br>Quá trình này có thể mất vài phút.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div
                                        class="h-2.5 rounded-full animate-pulse w-2/3
                                    bg-indigo-600
                                    wire:loading.class.remove='bg-indigo-600' wire:target='restoreTable'
                                    wire:loading.class='bg-amber-600' wire:target='restoreTable'">
                                    </div>
                                </div>
                                <p class="text-xs text-red-500 mt-2 font-medium">⚠️ Vui lòng KHÔNG tắt trình duyệt lúc
                                    này.</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
