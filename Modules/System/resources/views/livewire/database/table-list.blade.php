<div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-2">
            <input type="checkbox" wire:model.live="selectAll"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span class="text-sm font-medium text-gray-700">Chọn tất cả</span>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            @if ($canBackup)
                <button type="button" wire:click="backupFull" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="backupFull">Full Backup</span>
                    <span wire:loading wire:target="backupFull">Đang backup...</span>
                </button>
            @endif

            @if ($canRestore)
                <button type="button" wire:click="openRestoreModal" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60">
                    Restore Database
                </button>
            @endif

            <select wire:model.live="moduleFilter"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 sm:w-52">
                <option value="">Tất cả Module</option>
                @foreach ($modules as $module)
                    <option value="{{ $module }}">{{ $module }}</option>
                @endforeach
            </select>

            <div class="relative w-full sm:w-64">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    placeholder="Tìm kiếm bảng...">
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-10 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">#</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tên bảng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Module</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Số dòng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Dung lượng (MB)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Backup / Import</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Quản lý</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($tables as $table)
                    <tr class="transition-colors hover:bg-gray-50" wire:key="row-{{ $table['name'] }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" value="{{ $table['name'] }}" wire:model.live="selectedTables"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $table['name'] }}
                            @if ($table['is_protected'])
                                <span class="ml-2 inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">Protected</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                {{ $table['module'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($table['rows']) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $table['size_mb'] }} MB</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex flex-wrap items-center gap-3">
                                @if ($canBackup)
                                    <button type="button" wire:click="exportTable('{{ $table['name'] }}')" wire:loading.attr="disabled"
                                        class="text-indigo-600 hover:text-indigo-900 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="exportTable('{{ $table['name'] }}')">Export</span>
                                        <span wire:loading wire:target="exportTable('{{ $table['name'] }}')">Đang export...</span>
                                    </button>
                                @endif

                                @if ($table['has_backup'])
                                    @if ($canDownload)
                                        <a href="{{ route('admin.system.database.download', ['filename' => $table['backup_id']]) }}"
                                            target="_blank" class="text-green-600 hover:text-green-900">SQL</a>
                                    @endif

                                    @if ($canRestore)
                                        <button type="button" wire:click="restoreTable('{{ $table['name'] }}')"
                                            wire:confirm="CẢNH BÁO: Restore sẽ ghi đè dữ liệu bảng {{ $table['name'] }}. Tiếp tục?"
                                            wire:loading.attr="disabled"
                                            class="text-amber-600 hover:text-amber-900 disabled:opacity-50">
                                            Restore
                                        </button>
                                    @endif
                                @endif

                                @if ($canRestore && ! $table['is_protected'])
                                    <button type="button" wire:click="openImportModal('{{ $table['name'] }}')"
                                        wire:loading.attr="disabled"
                                        class="text-sky-600 hover:text-sky-900 disabled:opacity-50">
                                        Import
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if ($canDestroy && ! $table['is_protected'])
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" wire:click="truncateTable('{{ $table['name'] }}')"
                                        wire:confirm="NGUY HIỂM: Xóa sạch dữ liệu bảng {{ $table['name'] }}?"
                                        wire:loading.attr="disabled"
                                        class="text-orange-600 hover:text-orange-800 disabled:opacity-50">Truncate</button>
                                    <button type="button" wire:click="dropTable('{{ $table['name'] }}')"
                                        wire:confirm="CỰC KỲ NGUY HIỂM: XÓA BẢNG {{ $table['name'] }}?"
                                        wire:loading.attr="disabled"
                                        class="text-red-600 hover:text-red-800 disabled:opacity-50">Drop</button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center">
                            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8">
                                <h3 class="text-sm font-semibold text-gray-900">Không tìm thấy dữ liệu</h3>
                                <p class="mt-1 text-sm text-gray-500">Không có bảng nào phù hợp với bộ lọc hiện tại.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($canBackup && count($selectedTables) > 0)
        <div class="flex flex-col gap-3 border-t border-indigo-100 bg-indigo-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-sm font-medium text-indigo-700">Đã chọn {{ count($selectedTables) }} bảng</span>
            <div class="flex items-center gap-3">
                @if ($canDownload && $selectedExportFile)
                    <a href="{{ route('admin.system.database.download', ['filename' => $selectedExportFile]) }}" target="_blank"
                        class="inline-flex items-center justify-center rounded-xl border border-green-200 bg-white px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-50">
                        Tải ZIP export
                    </a>
                @endif
                <button type="button" wire:click="exportSelected" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50">
                    <span wire:loading.remove wire:target="exportSelected">Export {{ count($selectedTables) }} bảng</span>
                    <span wire:loading wire:target="exportSelected">Đang tạo ZIP...</span>
                </button>
            </div>
        </div>
    @endif

    @if ($showRestoreModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-red-600">⚠️ Restore Database</h2>
                <p class="mt-2 text-sm text-gray-600">Toàn bộ dữ liệu hiện tại có thể bị ghi đè. Hệ thống sẽ tạo safety backup trước khi restore.</p>

                <div class="mt-5">
                    <label class="text-sm font-medium text-gray-700">File backup</label>
                    <select wire:model="selectedBackupFile" @disabled($isRestoring)
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:bg-gray-100">
                        <option value="">-- Chọn file backup --</option>
                        @foreach ($backupFiles as $file)
                            <option value="{{ $file['id'] }}">{{ $file['name'] }} ({{ number_format($file['size'] / 1024, 2) }} KB)</option>
                        @endforeach
                    </select>
                </div>

                @if ($isRestoring)
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <div class="flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-amber-600 border-t-transparent"></span>
                            Đang phục hồi dữ liệu. Không đóng cửa sổ hoặc gửi lại thao tác.
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeRestoreModal" @disabled($isRestoring)
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                        Đóng
                    </button>
                    <button type="button" wire:click="restoreDatabase"
                        wire:confirm="Xác nhận restore toàn bộ database?"
                        wire:loading.attr="disabled" @disabled($isRestoring)
                        class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="restoreDatabase">Restore</span>
                        <span wire:loading wire:target="restoreDatabase">Đang restore...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-gray-900">Import Table</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Bảng đích: <span class="font-semibold text-gray-900">{{ $importTargetTable }}</span>. Chỉ chấp nhận dump SQL của đúng một bảng này.
                </p>

                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Hệ thống sẽ tự tạo safety backup trước khi import. Nếu import lỗi, hệ thống sẽ cố gắng phục hồi dữ liệu cũ tự động.
                </div>

                <div class="mt-5">
                    <label class="text-sm font-medium text-gray-700">File SQL</label>
                    <input type="file" wire:model="importFile" accept=".sql" @disabled($isImporting)
                        class="mt-1 block w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 disabled:bg-gray-100">
                    @error('importFile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Tối đa 100 MB. Nên dùng file được tạo từ chức năng Export của chính bảng này.</p>
                </div>

                <div wire:loading wire:target="importFile" class="mt-3 text-sm text-indigo-600">Đang tải file lên...</div>

                @if ($isImporting)
                    <div class="mt-5 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
                        <div class="flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-sky-600 border-t-transparent"></span>
                            Đang import dữ liệu. Không đóng cửa sổ hoặc gửi lại thao tác.
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="closeImportModal" @disabled($isImporting)
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                        Hủy
                    </button>
                    <button type="button" wire:click="importTable"
                        wire:confirm="Import sẽ thay đổi dữ liệu bảng {{ $importTargetTable }}. Tiếp tục?"
                        wire:loading.attr="disabled" wire:target="importTable,importFile" @disabled($isImporting)
                        class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-500 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="importTable">Import</span>
                        <span wire:loading wire:target="importTable">Đang import...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div wire:loading wire:target="backupFull,exportTable,exportSelected,restoreTable,truncateTable,dropTable"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm">
        <div class="rounded-2xl bg-white px-6 py-5 shadow-xl">
            <div class="flex items-center gap-3 text-sm font-medium text-gray-700">
                <span class="h-5 w-5 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent"></span>
                Đang xử lý yêu cầu...
            </div>
        </div>
    </div>
</div>
