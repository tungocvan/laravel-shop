<div class="space-y-5">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-900">Full Database Backup</h2>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $driveReady ? 'bg-emerald-50 text-emerald-700' : ($driveConnected ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                        {{ $driveReady ? 'DRIVE API READY' : ($driveConnected ? 'DRIVE CẦN XỬ LÝ' : 'DRIVE CHƯA KẾT NỐI') }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500">Một Backup Catalog duy nhất cho Local ↔ Google Drive. Restore chỉ thực hiện từ bản Local đã xác minh.</p>
            </div>
            @if($capabilities['backup'])
                <div class="flex flex-wrap gap-2">
                    <button wire:click="createBackup" wire:loading.attr="disabled" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50">+ Tạo Full Backup</button>
                    @if($driveReady)<button wire:click="createBackupAndUpload" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white disabled:opacity-50">Backup & Upload Drive</button>@endif
                    <button wire:click="$refresh" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Làm mới</button>
                </div>
            @endif
        </div>

        @if(!$driveReady)
            <div class="mt-4 rounded-xl border px-4 py-4 {{ $driveReadiness['code'] === 'scope_insufficient' ? 'border-amber-200 bg-amber-50' : 'border-red-200 bg-red-50' }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-bold {{ $driveReadiness['code'] === 'scope_insufficient' ? 'text-amber-900' : 'text-red-800' }}">
                            {{ $driveReadiness['code'] === 'scope_insufficient' ? 'Google Drive thiếu quyền OAuth' : 'Google Drive chưa sẵn sàng' }}
                        </p>
                        <p class="mt-1 text-sm {{ $driveReadiness['code'] === 'scope_insufficient' ? 'text-amber-800' : 'text-red-700' }}">{{ $driveReadiness['message'] }}</p>
                        @if($driveReadiness['httpStatus'])<p class="mt-1 text-xs font-mono text-gray-500">HTTP {{ $driveReadiness['httpStatus'] }}@if($driveReadiness['reason']) · {{ $driveReadiness['reason'] }}@endif</p>@endif
                    </div>
                    @if($driveReadiness['code'] === 'scope_insufficient' && $capabilities['reauthorizeDrive'])
                        <a href="{{ route('admin.system.settings.cloud.google.connect') }}" class="shrink-0 rounded-xl bg-amber-600 px-4 py-2.5 text-center text-sm font-bold text-white hover:bg-amber-700">Cấp lại quyền Google Drive</a>
                    @endif
                </div>
            </div>
        @elseif($remoteUnavailable)
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Google Drive API đã sẵn sàng nhưng chưa đọc được Backup Catalog. File Local vẫn hoạt động độc lập; hãy bấm Làm mới hoặc kiểm tra log.</div>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div><h3 class="font-bold text-gray-900">Backup Catalog</h3><p class="mt-1 text-xs text-gray-500">LOCAL ONLY · DRIVE ONLY · LOCAL + DRIVE được hợp nhất theo tên backup.</p></div>
            <div class="flex flex-wrap items-center gap-2"><span class="text-sm font-semibold text-gray-600">Đã chọn: {{ count($selectedNames) }}</span>@if($capabilities['destroy'])<button wire:click="openDelete" @disabled(count($selectedNames)===0) class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 disabled:opacity-40">Xóa file đã chọn</button>@endif</div>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($catalog as $item)
                @php($location = $item['local'] && $item['remote'] ? 'LOCAL + DRIVE' : ($item['local'] ? 'LOCAL ONLY' : 'DRIVE ONLY'))
                <article class="p-5 hover:bg-gray-50" wire:key="full-catalog-{{ sha1($item['name']) }}"><div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"><div class="flex min-w-0 gap-3">@if($capabilities['destroy'])<input type="checkbox" value="{{ $item['name'] }}" wire:model.live="selectedNames" class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600">@endif<div class="min-w-0"><p class="break-all text-sm font-bold text-gray-900">{{ $item['name'] }}</p><div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500"><span>{{ number_format($item['size']/1024/1024,2) }} MB</span>@if($item['time'])<span>{{ \Carbon\Carbon::createFromTimestamp($item['time'])->format('d/m/Y H:i:s') }}</span>@endif<span class="rounded-full px-2 py-0.5 font-bold {{ $location === 'LOCAL + DRIVE' ? 'bg-emerald-50 text-emerald-700' : ($location === 'DRIVE ONLY' ? 'bg-violet-50 text-violet-700' : 'bg-gray-100 text-gray-600') }}">{{ $location }}</span></div></div></div>
                    <div class="flex shrink-0 flex-wrap gap-2">@if($item['local'] && $capabilities['download'])<a href="{{ route('admin.system.database.download',['filename'=>$item['local']['id']]) }}" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700">Download</a>@endif @if($item['local'] && !$item['remote'] && $driveReady && $capabilities['backup'])<button wire:click="uploadLocal('{{ $item['local']['id'] }}')" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Upload Drive</button>@endif @if($item['remote'])<a href="{{ $item['remote']['url'] }}" target="_blank" rel="noopener" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700">Mở Drive</a>@endif @if(!$item['local'] && $item['remote'] && $capabilities['download'])<button wire:click="downloadRemote('{{ $item['remote']['reference'] }}')" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700">Tải về Local</button>@endif @if($item['local'] && $capabilities['restore'])<button wire:click="openRestore('{{ $item['local']['id'] }}')" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white">Restore</button>@endif</div></div></article>
            @empty<div class="p-10 text-center text-sm text-gray-400">Chưa có Full Backup. Nhấn “+ Tạo Full Backup” để tạo bản đầu tiên.</div>@endforelse
        </div>
    </div>

    @if($showRestoreModal)<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true"><div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"><div class="rounded-xl border border-red-200 bg-red-50 p-4"><p class="text-xs font-bold uppercase text-red-700">Restore Database</p><h3 class="mt-1 break-all text-base font-bold text-gray-900">{{ $restoreName }}</h3></div><div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-800"><strong>Safety Backup được tạo tự động.</strong> Nếu restore thất bại, hệ thống sẽ cố phục hồi database về trạng thái trước thao tác.</div><p class="mt-4 text-sm text-gray-600">Restore thay đổi toàn bộ database hiện tại. Chỉ tiếp tục khi bạn chắc chắn đã chọn đúng file.</p><div class="mt-6 flex justify-end gap-2"><button wire:click="$set('showRestoreModal',false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold">Hủy</button><button wire:click="confirmRestore" wire:loading.attr="disabled" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">Xác nhận Restore</button></div></div></div>@endif

    @if($showDeleteModal)<div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true"><div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"><h3 class="text-lg font-bold text-gray-900">Xóa {{ count($selectedNames) }} backup đã chọn</h3><p class="mt-1 text-sm text-gray-500">Chọn chính xác vị trí cần xóa. Vị trí không chọn sẽ được giữ nguyên.</p><div class="mt-5 space-y-3"><label class="flex items-center gap-3 rounded-xl border border-gray-200 p-4"><input type="checkbox" wire:model="deleteLocal" class="rounded border-gray-300"><span><strong class="block text-sm text-gray-900">Xóa bản Local</strong><span class="text-xs text-gray-500">Không ảnh hưởng bản Google Drive.</span></span></label><label class="flex items-center gap-3 rounded-xl border border-gray-200 p-4"><input type="checkbox" wire:model="deleteDrive" class="rounded border-gray-300"><span><strong class="block text-sm text-gray-900">Xóa bản Google Drive</strong><span class="text-xs text-gray-500">Không ảnh hưởng bản Local.</span></span></label></div><div class="mt-6 flex justify-end gap-2"><button wire:click="$set('showDeleteModal',false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold">Hủy</button><button wire:click="confirmDelete" wire:loading.attr="disabled" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">Xóa tại vị trí đã chọn</button></div></div></div>@endif

    <div wire:loading.flex wire:target="createBackup,createBackupAndUpload,uploadLocal,downloadRemote,confirmRestore,confirmDelete" class="fixed inset-0 z-[110] items-center justify-center bg-black/50 p-4"><div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl"><div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-indigo-600"></div><h3 class="mt-4 font-bold text-gray-900">Đang xử lý Backup Workspace…</h3><p class="mt-2 text-sm text-gray-500">Vui lòng chờ, không gửi lại thao tác.</p></div></div>
</div>
