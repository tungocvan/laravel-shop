<div class="bg-white border border-gray-200 rounded-lg shadow-sm">
    @if (session('success'))
        <div class="m-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="border-b border-gray-200 p-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-gray-900">Google Drive Backup</h3>
                    @if($driveStatus['connected'] ?? false)
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">ĐÃ KẾT NỐI</span>
                    @else
                        <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-[10px] font-bold text-gray-500">CHƯA KẾT NỐI</span>
                    @endif
                </div>
                @if($driveStatus['connected'] ?? false)
                    <p class="mt-1 text-xs text-gray-500">{{ $driveStatus['email'] ?: 'Google Drive' }} · Thư mục: {{ $driveStatus['folder_name'] ?? 'Laravel-Backup' }}/database/YYYY/MM</p>
                @else
                    <p class="mt-1 text-xs text-gray-500">Kết nối Google Drive tại Cấu hình hệ thống → Quản lý ENV → Lưu trữ Cloud.</p>
                @endif
            </div>

            @if($driveStatus['connected'] ?? false)
                <button type="button" wire:click="backupAndUpload" wire:loading.attr="disabled" wire:target="backupAndUpload"
                    wire:confirm="Tạo một backup database mới trên máy chủ và đưa file đó vào hàng đợi upload Google Drive?"
                    class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="backupAndUpload">BACKUP & UPLOAD GOOGLE DRIVE</span>
                    <span wire:loading wire:target="backupAndUpload">ĐANG TẠO BACKUP...</span>
                </button>
            @endif
        </div>
        <p class="mt-3 text-xs text-amber-700">Backup local luôn được giữ độc lập. Nếu upload cloud thất bại, file backup trên máy chủ không bị xóa và có thể upload lại sau.</p>
    </div>

    <div class="grid gap-4 border-b border-gray-200 p-4 lg:grid-cols-2">
        <div class="rounded-lg border border-dashed border-gray-300 p-4">
            <h3 class="text-sm font-semibold text-gray-800">Upload backup từ máy tính</h3>
            <p class="mt-1 text-xs text-gray-500">Chấp nhận full database backup định dạng .sql, tối đa 20 MB.</p>
            <input type="file" wire:model="sqlFile" accept=".sql,application/sql,text/plain" class="mt-3 block w-full text-sm text-gray-600 file:mr-3 file:rounded file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700">
            @error('sqlFile')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            <button type="button" wire:click="uploadSql" wire:loading.attr="disabled" class="mt-3 rounded bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="uploadSql,sqlFile">TẢI FILE LÊN</span><span wire:loading wire:target="uploadSql,sqlFile">ĐANG TẢI...</span>
            </button>
        </div>

        <div class="rounded-lg border border-dashed border-gray-300 p-4">
            <h3 class="text-sm font-semibold text-gray-800">Nhập backup từ link Google Drive</h3>
            <p class="mt-1 text-xs text-gray-500">Chức năng tương thích cũ: file chia sẻ “Bất kỳ ai có liên kết”. Không liên quan đến OAuth backup tự động phía trên.</p>
            <input type="url" wire:model="googleDriveUrl" placeholder="https://drive.google.com/file/d/.../view" class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('googleDriveUrl')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            <button type="button" wire:click="importFromGoogleDrive" wire:loading.attr="disabled" class="mt-3 rounded bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="importFromGoogleDrive">TẢI TỪ GOOGLE DRIVE</span><span wire:loading wire:target="importFromGoogleDrive">ĐANG TẢI...</span>
            </button>
        </div>
    </div>

    <div class="p-4 border-b border-gray-200 bg-gray-50/50">
        <h3 class="font-semibold text-gray-700 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Lịch sử Backup gần đây</h3>
        <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">@foreach ($backupDirectories as $directory)<code class="rounded bg-white px-2 py-1 ring-1 ring-gray-200">{{ $directory }}</code>@endforeach</div>
        @if ($backupHistoryTruncated)<p class="mt-2 text-xs text-amber-700">Đang hiển thị {{ $backupHistoryLimit }} bản backup gần nhất. Các file cũ hơn vẫn được giữ.</p>@else<p class="mt-2 text-xs text-gray-500">Hiển thị tối đa {{ $backupHistoryLimit }} bản backup gần nhất.</p>@endif
    </div>

    <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
        @forelse($backups as $file)
            <div class="p-4 hover:bg-gray-50 transition-colors group">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 break-all">{{ $file['name'] }}</p>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                            <span>{{ number_format($file['size'] / 1024, 2) }} KB</span>
                            <span>{{ \Carbon\Carbon::createFromTimestamp($file['time'])->diffForHumans() }}</span>
                            @if(!empty($file['google_drive']['id']))
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">Google Drive ✓</span>
                                @if(!empty($file['google_drive']['uploaded_at']))<span>Upload: {{ \Carbon\Carbon::parse($file['google_drive']['uploaded_at'])->diffForHumans() }}</span>@endif
                            @elseif($driveStatus['connected'] ?? false)
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500">Chưa upload Drive</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 opacity-100 transition-opacity lg:opacity-80 lg:group-hover:opacity-100">
                    <a href="{{ route('admin.system.database.download', $file['name']) }}" class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs font-medium hover:bg-blue-100">Download</a>

                    @if($driveStatus['connected'] ?? false)
                        <button wire:click="uploadToGoogleDrive('{{ $file['name'] }}')" wire:loading.attr="disabled" wire:target="uploadToGoogleDrive('{{ $file['name'] }}')"
                            class="rounded bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100 disabled:opacity-50">
                            <span wire:loading.remove wire:target="uploadToGoogleDrive('{{ $file['name'] }}')">{{ !empty($file['google_drive']['id']) ? 'Upload lại Drive' : 'Upload Drive' }}</span>
                            <span wire:loading wire:target="uploadToGoogleDrive('{{ $file['name'] }}')">Đang đưa vào queue...</span>
                        </button>
                    @endif

                    @if ($file['size'] <= \Modules\System\Jobs\SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES)
                        <button wire:click="openEmailModal('{{ $file['name'] }}')" class="rounded bg-violet-50 px-2 py-1 text-xs font-medium text-violet-700 hover:bg-violet-100">Gửi email</button>
                    @else<span class="rounded bg-gray-100 px-2 py-1 text-[10px] text-gray-500">Không gửi email: trên 10MB</span>@endif

                    @if ($file['is_full'])
                        <button wire:click="restoreBackup('{{ $file['name'] }}')" wire:confirm="CẢNH BÁO: Hệ thống sẽ tự tạo một backup an toàn, sau đó ghi đè database hiện tại bằng '{{ $file['name'] }}'. Tiếp tục?" wire:loading.attr="disabled" class="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-bold hover:bg-red-700 transition-colors disabled:opacity-50">
                            <span wire:loading.remove wire:target="restoreBackup('{{ $file['name'] }}')">RESTORE</span><span wire:loading wire:target="restoreBackup('{{ $file['name'] }}')">ĐANG RESTORE...</span>
                        </button>
                    @else<span class="rounded bg-amber-50 px-2 py-1 text-[10px] font-medium text-amber-700">Backup một bảng</span>@endif

                    <button wire:click="deleteBackup('{{ $file['name'] }}')" wire:confirm="Xóa vĩnh viễn file backup local '{{ $file['name'] }}'? Bản đã upload Google Drive (nếu có) không bị xóa." wire:loading.attr="disabled" class="px-2 py-1 rounded bg-gray-100 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-50">Xóa local</button>
                </div>
            </div>
        @empty
            <div class="p-8 text-center"><p class="text-sm text-gray-400">Chưa có bản backup nào được lưu.</p></div>
        @endforelse
    </div>

    @if ($showEmailModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-gray-900">Gửi backup qua email</h2><p class="mt-1 break-all text-sm text-gray-500">{{ $emailBackupFile }}</p>
                <div class="mt-5"><label for="backup-recipient-email" class="mb-1 block text-sm font-medium text-gray-700">Email nhận file</label><input id="backup-recipient-email" type="email" wire:model="backupEmail" wire:keydown.enter.prevent="sendBackupEmail" placeholder="admin@example.com" class="w-full rounded-lg border px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500 @error('backupEmail') border-red-500 @else border-gray-300 @enderror">@error('backupEmail')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror @error('emailBackupFile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="$set('showEmailModal', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Hủy</button><button type="button" wire:click="sendBackupEmail" wire:loading.attr="disabled" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-50"><span wire:loading.remove wire:target="sendBackupEmail">Gửi file SQL</span><span wire:loading wire:target="sendBackupEmail">Đang đưa vào hàng đợi...</span></button></div>
            </div>
        </div>
    @endif
</div>
