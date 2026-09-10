<div class="space-y-5" @if ($syncId && in_array($syncState, ['queued', 'processing'], true)) wire:poll.2s="pollStatus" @endif>
    <div wire:loading.flex wire:target="backupFilesToEmail" class="fixed inset-0 z-[120] items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
            <h3 class="mt-4 text-lg font-bold text-slate-900">Đang tạo và gửi backup qua Email</h3>
            <p class="mt-2 text-sm text-slate-500">Hệ thống đang nén kho file và có thể chia thành nhiều email nếu dung lượng lớn. Vui lòng không refresh trang.</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Bước 1</p>
            <h2 class="mt-1 text-base font-semibold text-gray-900">Đồng bộ dữ liệu GDT</h2>
            <p class="mt-1 text-sm text-gray-500">Chọn khoảng thời gian và loại hóa đơn. GDT sync ghi trực tiếp danh sách hóa đơn đồng thời lưu RAW canonical.</p>
        </div>
        <div class="grid gap-4 p-5 md:grid-cols-3 sm:p-6">
            <div><label class="text-sm font-medium text-gray-700">Từ ngày</label><input type="date" wire:model.live="start_date" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">@error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-medium text-gray-700">Đến ngày</label><input type="date" wire:model.live="end_date" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm">@error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-sm font-medium text-gray-700">Loại hóa đơn</label><select wire:model.live="vatIn" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm"><option value="0">Bán ra</option><option value="1">Mua vào</option></select></div>
        </div>
        <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" wire:model.live="useQueue" class="rounded border-gray-300"> Xử lý qua queue</label>
            <button wire:click="run" wire:loading.attr="disabled" class="h-11 rounded-xl bg-emerald-600 px-6 text-sm font-semibold text-white disabled:opacity-50"><span wire:loading.remove wire:target="run">Chạy đồng bộ</span><span wire:loading wire:target="run">Đang xử lý…</span></button>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Bước 2 · Sức khỏe dữ liệu</p><h2 class="mt-1 text-base font-semibold text-gray-900">Trạng thái đồng bộ</h2><p class="mt-1 text-sm text-gray-500">Theo dõi đến khi hoàn tất, hoàn tất một phần hoặc thất bại.</p></div>
            @php($stateLabel=match($syncState){'queued'=>'Đang chờ queue','processing'=>'Đang xử lý','completed'=>'Hoàn tất','partial'=>'Hoàn tất một phần','failed'=>'Thất bại',default=>'Chưa chạy'})
            @php($stateClass=match($syncState){'completed'=>'bg-emerald-50 text-emerald-700','partial'=>'bg-amber-50 text-amber-800','failed'=>'bg-red-50 text-red-700','queued','processing'=>'bg-amber-50 text-amber-700',default=>'bg-gray-100 text-gray-600'})
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $stateClass }}">{{ $stateLabel }}</span>
        </div>
        @if(in_array($syncState,['queued','processing'],true))<div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full w-2/3 animate-pulse rounded-full bg-indigo-500"></div></div>@endif
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-gray-500">Kết quả</p><p class="mt-1 text-sm font-semibold {{ $syncState==='partial'?'text-amber-800':'text-gray-800' }}">{{ $syncMessage ?: 'Chưa có phiên đồng bộ mới.' }}</p></div>
            <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-gray-500">File export</p><p class="mt-1 truncate text-sm font-semibold {{ $syncFile?'text-emerald-700':'text-gray-500' }}">{{ $syncFile ?: 'Chưa tạo file' }}</p></div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Bước 3</p><h2 class="mt-1 text-base font-semibold text-gray-900">Kho file hóa đơn</h2><p class="mt-1 text-sm text-gray-500">Quản lý export/backup. Xóa file không xóa dữ liệu hóa đơn, RAW canonical, Inventory hoặc Partner.</p></div>
            <button wire:click="refreshAvailableFiles" class="h-10 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700">Làm mới</button>
        </div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ count($availableFiles) }} file</span><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">Đã chọn {{ count($selectedFiles) }}</span></div>
                <div class="flex flex-wrap gap-2"><button wire:click="selectAllAvailableFiles" @disabled(count($availableFiles)===0) class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 disabled:opacity-40">Chọn tất cả</button><button wire:click="clearFileSelection" @disabled(count($selectedFiles)===0) class="h-9 rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-600 disabled:opacity-40">Bỏ chọn</button></div>
            </div>

            <div class="mt-4 max-h-[28rem] space-y-2 overflow-y-auto pr-1">
                @forelse($availableFiles as $file)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 transition hover:border-indigo-200 hover:bg-indigo-50/30">
                        <input type="checkbox" wire:model.live="selectedFiles" value="{{ $file['token'] }}" class="mt-1 rounded border-gray-300 text-indigo-600">
                        <span class="min-w-0 flex-1"><span class="flex flex-wrap items-center gap-2"><span class="block truncate text-sm font-medium text-gray-800">{{ $file['name'] }}</span><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $file['direction']==='vat_in'?'bg-blue-50 text-blue-700':'bg-emerald-50 text-emerald-700' }}">{{ $file['type_label'] }}</span></span><span class="mt-1 block text-xs text-gray-500">{{ number_format($file['size']/1024,1) }} KB · {{ $file['modified_at'] }}</span></span>
                    </label>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">Chưa có file XLSX/CSV đúng quy ước trong kho.</div>
                @endforelse
            </div>

            <div class="sticky bottom-3 z-10 mt-4 overflow-hidden rounded-2xl border border-indigo-100 bg-white/95 shadow-lg backdrop-blur sm:static sm:shadow-none">
                <div class="border-b border-gray-100 px-4 py-3 sm:px-5">
                    <p class="text-sm font-semibold text-gray-900">Thao tác với file đã chọn</p>
                    <p class="mt-1 text-xs leading-5 text-gray-500">Import chỉ dành cho file upload/Drive/legacy. File tạo trực tiếp từ GDT đã được ghi vào danh sách hóa đơn và RAW canonical nên không cần import lại.</p>
                </div>
                <div class="grid gap-2 bg-gray-50/70 p-3 sm:grid-cols-3 sm:p-4">
                    <button wire:click="importSelectedFile" wire:confirm="Chỉ tiếp tục nếu đây là file upload thủ công, Google Drive hoặc dữ liệu legacy. File tạo trực tiếp từ GDT đã được ghi vào danh sách hóa đơn và không cần import lần nữa. Bạn có muốn tiếp tục?" wire:loading.attr="disabled" @disabled(count($selectedFiles)!==1) class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-center text-sm font-semibold text-white disabled:opacity-40"><span wire:loading.remove wire:target="importSelectedFile">Import vào danh sách hóa đơn</span><span wire:loading wire:target="importSelectedFile">Đang import…</span></button>
                    <button wire:click="downloadSelectedFile" wire:loading.attr="disabled" @disabled(count($selectedFiles)!==1) class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40">Download file</button>
                    <button wire:click="deleteSelectedFiles" wire:confirm="Xác nhận xóa {{ count($selectedFiles) }} file đã chọn khỏi kho file? Dữ liệu hóa đơn, RAW canonical, Inventory và Partner không bị xóa." wire:loading.attr="disabled" @disabled(count($selectedFiles)===0) class="inline-flex min-h-11 items-center justify-center rounded-xl border border-red-200 bg-white px-4 text-sm font-semibold text-red-600 hover:bg-red-50 disabled:opacity-40">Xóa file đã chọn ({{ count($selectedFiles) }})</button>
                </div>
            </div>
            @error('selectedFiles')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Công cụ phụ trợ</p><h2 class="mt-1 text-base font-semibold text-gray-900">Nhập file ngoài & backup nhanh</h2><p class="mt-1 text-sm text-gray-500">Các tác vụ này không thuộc luồng GDT chính. Dùng khi nhận file thủ công, file chia sẻ hoặc cần sao lưu ngay.</p></div>
        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-900">Upload từ máy tính</p><p class="mt-1 text-xs leading-5 text-gray-500">XLSX/CSV tối đa 20 MB. Tên phải bắt đầu bằng <code>vat_in_</code> hoặc <code>vat_out_</code>.</p>
                <input type="file" wire:model="uploadFile" accept=".xlsx,.csv" class="mt-4 block w-full rounded-xl border border-gray-300 bg-white p-3 text-sm text-gray-700">@error('uploadFile')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                @if($uploadFile)<p class="mt-2 truncate text-xs text-gray-600">{{ $uploadFile->getClientOriginalName() }}</p>@endif
                <button wire:click="stageUploadedFile" wire:loading.attr="disabled" @disabled(!$uploadFile) class="mt-4 h-11 w-full rounded-xl bg-slate-800 px-4 text-sm font-semibold text-white disabled:opacity-40"><span wire:loading.remove wire:target="stageUploadedFile">Đưa vào kho file</span><span wire:loading wire:target="stageUploadedFile">Đang upload…</span></button>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-4">
                <p class="text-sm font-semibold text-gray-900">Lấy file từ Google Drive</p><p class="mt-1 text-xs leading-5 text-gray-500">Dán link chia sẻ công khai. Hệ thống lấy tên file gốc và đưa file hợp lệ vào kho.</p>
                <input type="url" wire:model="googleDriveUrl" placeholder="https://drive.google.com/file/d/.../view" class="mt-4 h-11 w-full rounded-xl border border-blue-200 bg-white px-4 text-sm">@error('googleDriveUrl')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                <button wire:click="stageGoogleDriveFile" wire:loading.attr="disabled" @disabled(trim($googleDriveUrl)==='') class="mt-4 h-11 w-full rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white disabled:opacity-40"><span wire:loading.remove wire:target="stageGoogleDriveFile">Tải vào kho file</span><span wire:loading wire:target="stageGoogleDriveFile">Đang tải…</span></button>
            </div>

            <div class="rounded-2xl border border-violet-100 bg-violet-50/40 p-4">
                <p class="text-sm font-semibold text-gray-900">Backup nhanh qua Email</p><p class="mt-1 text-xs leading-5 text-gray-500">Gửi bản sao toàn bộ file vat_in_* / vat_out_* dạng ZIP. File trên server không bị xóa.</p>
                <input type="email" wire:model="backupEmail" placeholder="email-nhan-backup@example.com" class="mt-4 h-11 w-full rounded-xl border border-violet-200 bg-white px-4 text-sm">@error('backupEmail')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                <button wire:click="backupFilesToEmail" wire:loading.attr="disabled" @disabled(trim($backupEmail)==='' || count($availableFiles)===0) class="mt-4 h-11 w-full rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white disabled:opacity-40"><span wire:loading.remove wire:target="backupFilesToEmail">Gửi backup</span><span wire:loading wire:target="backupFilesToEmail">Đang gửi…</span></button>
            </div>
        </div>
    </section>

    <details class="group rounded-2xl border border-gray-200 bg-gray-950 shadow-sm" @if(in_array($syncState,['processing','partial','failed'],true)) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-white"><div><p class="text-sm font-semibold">Nhật ký xử lý</p><p class="mt-1 text-xs text-gray-400">Tự mở khi đang xử lý, đồng bộ một phần hoặc có lỗi.</p></div><span class="text-xs font-semibold text-gray-300 group-open:hidden">Mở nhật ký</span><span class="hidden text-xs font-semibold text-gray-300 group-open:inline">Thu gọn</span></summary>
        <div class="border-t border-gray-800 px-5 pb-5 pt-4 font-mono text-xs text-gray-200"><div class="max-h-80 space-y-1 overflow-y-auto">@forelse($logs as $line)<div>{{ $line }}</div>@empty<div class="text-gray-400">Chưa có tác vụ.</div>@endforelse</div></div>
    </details>
</div>