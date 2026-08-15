<div class="rounded-2xl border border-violet-100 bg-violet-50/50 p-5 shadow-sm">
    <div wire:loading.flex wire:target="runNow" class="fixed inset-0 z-[100] items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-violet-100 border-t-violet-600"></div>
            <h3 class="mt-4 text-lg font-bold text-slate-900">Đang backup file mới qua Email</h3>
            <p class="mt-2 text-sm text-slate-500">Vui lòng không đóng tab hoặc refresh cho đến khi hoàn tất.</p>
        </div>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-gray-900">Automatic Invoice Backup</h3>
                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">{{ $enabled ? 'Đang bật' : 'Đang tắt' }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-600">Chỉ gửi file mới hoặc file đã thay đổi kể từ lần backup thành công trước.</p>
        </div>
        @if(auth('admin')->user()?->can('invoices-download'))
            <button wire:click="runNow" class="h-10 rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white hover:bg-violet-700">Backup file mới ngay</button>
        @endif
    </div>

    @if($notice)<div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $notice }}</div>@endif
    @if($error)<div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $error }}</div>@endif

    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-white p-3"><p class="text-xs text-gray-500">Email nhận</p><p class="mt-1 truncate text-sm font-semibold text-gray-900">{{ $recipient !== '' ? $recipient : 'Chưa cấu hình' }}</p></div>
        <div class="rounded-xl bg-white p-3"><p class="text-xs text-gray-500">Lịch chạy</p><p class="mt-1 text-sm font-semibold text-gray-900">Ngày {{ $scheduleDay }} · {{ $scheduleTime }}</p></div>
        <div class="rounded-xl bg-white p-3"><p class="text-xs text-gray-500">File đang chờ backup</p><p class="mt-1 text-xl font-bold text-violet-700">{{ number_format($pendingCount) }}</p></div>
        <div class="rounded-xl bg-white p-3"><p class="text-xs text-gray-500">Cơ chế</p><p class="mt-1 text-sm font-semibold text-gray-900">Incremental</p></div>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-violet-100 bg-white">
        <div class="border-b border-gray-100 px-4 py-3 text-xs font-bold uppercase tracking-wide text-gray-500">5 lần backup gần nhất</div>
        <div class="divide-y divide-gray-100">
            @forelse($runs as $run)
                <div class="grid gap-2 px-4 py-3 text-sm sm:grid-cols-[120px_100px_1fr_100px] sm:items-center">
                    <span class="text-xs text-gray-500">{{ $run->created_at?->format('d/m/Y H:i') }}</span>
                    <span class="w-fit rounded-full px-2 py-1 text-[11px] font-semibold {{ $run->status === 'success' ? 'bg-emerald-50 text-emerald-700' : ($run->status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600') }}">{{ strtoupper($run->status) }}</span>
                    <span class="truncate text-xs text-gray-600" title="{{ $run->message }}">{{ $run->message ?: '-' }}</span>
                    <span class="text-right text-xs font-semibold text-gray-700">{{ $run->files_count }} file</span>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-500">Chưa có lịch sử automatic backup.</div>
            @endforelse
        </div>
    </div>
</div>
