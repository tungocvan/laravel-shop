<div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    @if (session('success'))
        <div class="m-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <section class="border-b border-gray-200 p-4 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-bold text-gray-900">Google Drive Backup</h2>
                    @if ($driveStatus['connected'])
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">ĐÃ KẾT NỐI</span>
                    @else
                        <span class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-bold text-gray-500">CHƯA KẾT NỐI</span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($driveStatus['connected'])
                        Kho riêng tư {{ $driveStatus['folder_name'] }}/database/YYYY/MM. Mọi thao tác dùng reference do server cấp.
                    @else
                        Kết nối tại Cấu hình hệ thống → Quản lý ENV → Lưu trữ Cloud.
                    @endif
                </p>
            </div>

            @if ($driveStatus['connected'] && $capabilities['backup'])
                <button
                    type="button"
                    onclick="backupOperationStart('Backup & Upload Google Drive')"
                    wire:click="backupAndUpload"
                    wire:confirm="Tạo backup database mới và đưa vào hàng đợi Google Drive?"
                    wire:loading.attr="disabled"
                    wire:target="backupAndUpload"
                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="backupAndUpload">BACKUP & UPLOAD</span>
                    <span wire:loading wire:target="backupAndUpload">ĐANG TẠO BACKUP...</span>
                </button>
            @endif
        </div>

        @if ($driveStatus['connected'])
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    'queued' => ['Chờ upload', 'border-amber-100 bg-amber-50 text-amber-700'],
                    'processing' => ['Đang xử lý', 'border-blue-100 bg-blue-50 text-blue-700'],
                    'uploaded' => ['Thành công', 'border-emerald-100 bg-emerald-50 text-emerald-700'],
                    'failed' => ['Thất bại', 'border-red-100 bg-red-50 text-red-700'],
                ] as $state => [$label, $classes])
                    <div class="rounded-xl border p-3 {{ $classes }}">
                        <div class="text-xs font-bold uppercase">{{ $label }}</div>
                        <div class="mt-1 text-xl font-black">{{ $driveCounts[$state] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <p class="mt-4 text-xs leading-5 text-amber-700">
            Backup local luôn độc lập. Cloud thất bại không xóa file local. Danh sách Drive chỉ tải khi trang render hoặc người vận hành chủ động làm mới, không gọi lại theo polling.
        </p>
    </section>

    @if ($driveStatus['connected'] && $canBrowseRemote)
        <section class="border-b border-gray-200 bg-sky-50/40 p-4 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Backup thuộc namespace database trên Google Drive</h2>
                    <p class="mt-1 text-xs text-gray-500">Tải về local trước khi restore. Thao tác trực tiếp “Tải & Restore” đã được loại bỏ.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="$refresh" class="rounded-lg border border-sky-200 bg-white px-3 py-2 text-xs font-bold text-sky-700">LÀM MỚI</button>
                    <button type="button" wire:click="$toggle('showDriveBackups')" class="rounded-lg border border-sky-200 bg-white px-3 py-2 text-xs font-bold text-sky-700">
                        {{ $showDriveBackups ? 'ẨN DANH SÁCH' : 'HIỆN DANH SÁCH' }}
                    </button>
                </div>
            </div>

            @if ($showDriveBackups)
                @if ($remoteBackupsUnavailable)
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Tạm thời không thể đọc danh sách backup Google Drive. Vui lòng kiểm tra log hệ thống.
                    </div>
                @endif

                <div class="mt-4 max-h-[360px] divide-y divide-gray-100 overflow-y-auto rounded-xl border border-sky-100 bg-white">
                    @forelse ($remoteBackups as $remote)
                        <article class="flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="break-all text-sm font-semibold text-gray-900">{{ $remote['name'] }}</div>
                                <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-500">
                                    <span>{{ $remote['year'] }}/{{ $remote['month'] }}</span>
                                    <span>{{ number_format($remote['size'] / 1024, 2) }} KB</span>
                                    @if ($remote['modified_at'])
                                        <span>{{ \Carbon\Carbon::parse($remote['modified_at'])->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-2">
                                <a href="{{ $remote['url'] }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700">Mở Drive</a>
                                @if ($capabilities['download'])
                                    <button
                                        type="button"
                                        onclick="backupOperationStart('Tải backup Google Drive về Local')"
                                        wire:click="downloadRemoteBackup('{{ $remote['reference'] }}')"
                                        wire:confirm="Tải backup này từ Google Drive về kho local? Database hiện tại không bị thay đổi."
                                        wire:loading.attr="disabled"
                                        class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 disabled:opacity-50"
                                    >Tải về Local</button>
                                @endif
                                @if ($capabilities['destroy'])
                                    <button
                                        type="button"
                                        onclick="backupOperationStart('Xóa backup trên Google Drive')"
                                        wire:click="deleteRemoteBackup('{{ $remote['reference'] }}')"
                                        wire:confirm="Xóa vĩnh viễn backup này khỏi Google Drive? File local không bị xóa."
                                        wire:loading.attr="disabled"
                                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 disabled:opacity-50"
                                    >Xóa Drive</button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-400">Chưa tìm thấy backup hợp lệ trong vùng Google Drive được phép.</div>
                    @endforelse
                </div>
            @endif
        </section>
    @endif

    @if ($capabilities['restore'])
        <section class="border-b border-gray-200 p-4 sm:p-6">
            <div class="rounded-xl border border-dashed border-gray-300 p-4">
                <h2 class="text-sm font-semibold text-gray-800">Upload backup từ máy tính</h2>
                <p class="mt-1 text-xs text-gray-500">Chỉ nhận file SQL tối đa 20 MB. URL Drive công khai legacy không còn được hỗ trợ.</p>
                <input type="file" wire:model="sqlFile" accept=".sql,application/sql,text/plain" class="mt-3 block w-full text-sm text-gray-600">
                @error('sqlFile')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="button" onclick="backupOperationStart('Upload backup từ máy tính')" wire:click="uploadSql" wire:loading.attr="disabled" class="mt-3 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50">TẢI FILE LÊN</button>
            </div>
        </section>
    @endif

    <section>
        <div class="border-b border-gray-200 bg-gray-50/60 p-4 sm:px-6">
            <h2 class="font-semibold text-gray-700">Lịch sử backup local gần đây</h2>
            <p class="mt-1 text-xs text-gray-500">
                Hiển thị tối đa {{ $backupHistoryLimit }} bản trong kho riêng tư và kho legacy tương thích.
                @if ($backupHistoryTruncated)
                    Các bản cũ hơn không được tải vào giao diện.
                @endif
            </p>
        </div>

        <div class="max-h-[600px] divide-y divide-gray-100 overflow-y-auto">
            @forelse ($backups as $file)
                @php($driveState = $file['google_drive']['status'] ?? '')
                <article class="p-4 hover:bg-gray-50 sm:px-6">
                    <p class="break-all text-sm font-medium text-gray-900">{{ $file['name'] }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                        <span>{{ number_format($file['size'] / 1024, 2) }} KB</span>
                        <span>{{ \Carbon\Carbon::createFromTimestamp($file['time'])->diffForHumans() }}</span>
                        @if ($driveState === 'queued')
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 font-semibold text-amber-700">Chờ upload Drive</span>
                        @elseif ($driveState === 'processing')
                            <span class="rounded-full bg-blue-50 px-2 py-0.5 font-semibold text-blue-700">Đang upload</span>
                        @elseif ($driveState === 'uploaded')
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">Google Drive ✓</span>
                        @elseif ($driveState === 'failed')
                            <span class="rounded-full bg-red-50 px-2 py-0.5 font-semibold text-red-700">Upload thất bại</span>
                        @endif
                    </div>

                    @if ($driveState === 'failed')
                        <div class="mt-2 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-xs text-red-700">
                            {{ $file['google_drive']['error'] }}
                        </div>
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($capabilities['download'])
                            <a href="{{ route('admin.system.database.download', ['filename' => $file['id']]) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700">Download</a>
                        @endif
                        @if ($driveStatus['connected'] && $capabilities['backup'] && ! in_array($driveState, ['queued', 'processing'], true))
                            <button type="button" onclick="backupOperationStart('Upload backup lên Google Drive')" wire:click="{{ $driveState === 'failed' ? 'retryGoogleDriveUpload' : 'uploadToGoogleDrive' }}('{{ $file['id'] }}')" wire:loading.attr="disabled" class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 disabled:opacity-50">
                                {{ $driveState === 'failed' ? 'Thử lại Drive' : 'Upload Drive' }}
                            </button>
                        @endif
                        @if ($capabilities['download'] && $file['size'] <= \Modules\System\Jobs\SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES)
                            <button type="button" wire:click="openEmailModal('{{ $file['id'] }}')" class="rounded-lg bg-violet-50 px-3 py-2 text-xs text-violet-700">Gửi email</button>
                        @endif
                        @if ($capabilities['restore'] && $file['is_full'])
                            <button type="button" onclick="backupOperationStart('Restore database từ backup local')" wire:click="restoreBackup('{{ $file['id'] }}')" wire:confirm="CẢNH BÁO: Restore database từ backup local đã chọn?" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white">RESTORE</button>
                        @endif
                        @if ($capabilities['destroy'])
                            <button type="button" onclick="backupOperationStart('Xóa backup local')" wire:click="deleteBackup('{{ $file['id'] }}')" wire:confirm="Xóa vĩnh viễn backup local đã chọn? Bản Drive không bị xóa." class="rounded-lg bg-gray-100 px-3 py-2 text-xs text-red-600">Xóa local</button>
                        @endif
                    </div>
                </article>
            @empty
                <div class="p-10 text-center text-sm text-gray-400">Chưa có bản backup local nào.</div>
            @endforelse
        </div>
    </section>

    @if ($showEmailModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-bold text-gray-900">Gửi backup qua email</h2>
                <p class="mt-1 break-all text-sm text-gray-500">{{ $emailBackupName }}</p>
                <label class="mt-4 block text-sm font-semibold text-gray-700" for="backup-email">Email nhận file</label>
                <input id="backup-email" type="email" wire:model="backupEmail" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                @error('backupEmail')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="$set('showEmailModal', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Hủy</button>
                    <button type="button" onclick="backupOperationStart('Gửi backup qua email')" wire:click="sendBackupEmail" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white">Gửi file SQL</button>
                </div>
            </div>
        </div>
    @endif

    <div wire:loading.flex wire:target="backupAndUpload,downloadRemoteBackup,deleteRemoteBackup,uploadSql,uploadToGoogleDrive,retryGoogleDriveUpload,restoreBackup,deleteBackup,sendBackupEmail" class="fixed inset-0 z-[90] items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div>
            <h3 id="backup-operation-loading-title" class="mt-4 text-base font-bold text-gray-900">Đang thực hiện thao tác</h3>
            <p class="mt-2 text-sm text-gray-500">Vui lòng chờ hệ thống xử lý và không gửi lại thao tác.</p>
        </div>
    </div>

    <div id="backup-operation-result-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div id="backup-operation-result-icon" class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl font-black text-emerald-700">✓</div>
            <h3 id="backup-operation-result-title" class="mt-4 text-center text-lg font-bold text-gray-900">Thao tác thành công</h3>
            <p id="backup-operation-result-message" class="mt-2 whitespace-pre-line break-words text-center text-sm leading-6 text-gray-600"></p>
            <div class="mt-5 flex justify-center">
                <button type="button" onclick="backupOperationClose()" class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-black">ĐÓNG</button>
            </div>
        </div>
    </div>

    <script>
        window.backupOperationStart = function (title) {
            const element = document.getElementById('backup-operation-loading-title');
            if (element) element.textContent = title || 'Đang thực hiện thao tác';
        };
        window.backupOperationResult = function (type, message) {
            const modal = document.getElementById('backup-operation-result-modal');
            const icon = document.getElementById('backup-operation-result-icon');
            const title = document.getElementById('backup-operation-result-title');
            const text = document.getElementById('backup-operation-result-message');
            if (!modal || !icon || !title || !text) return;
            const failed = type === 'error';
            icon.textContent = failed ? '!' : '✓';
            icon.className = 'mx-auto flex h-12 w-12 items-center justify-center rounded-full text-2xl font-black ' + (failed ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700');
            title.textContent = failed ? 'Thao tác thất bại' : 'Thao tác thành công';
            text.textContent = message || (failed ? 'Không thể hoàn tất thao tác.' : 'Đã hoàn tất thao tác.');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
        window.backupOperationClose = function () {
            const modal = document.getElementById('backup-operation-result-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };
        if (!window.__backupOperationModalBound) {
            window.__backupOperationModalBound = true;
            document.addEventListener('livewire:init', () => {
                Livewire.on('backup-operation-finished', (event) => {
                    backupOperationResult(event.type, event.message);
                });
            });
        }
    </script>
</div>
