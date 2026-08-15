<div class="rounded-2xl border border-violet-100 bg-violet-50/50 p-5 shadow-sm">
    <div wire:loading.flex wire:target="runNow,setupEnvironmentDefaults" class="fixed inset-0 z-[100] items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-violet-100 border-t-violet-600"></div>
            <h3 class="mt-4 text-lg font-bold text-slate-900">Đang xử lý Automatic Invoice Backup</h3>
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
        <div class="flex flex-wrap gap-2">
            @if(auth('admin')->user()?->can('invoices-configure'))
                @if(!$environment['protected'])
                    <button wire:click="setupEnvironmentDefaults" class="h-10 rounded-xl border border-violet-200 bg-white px-4 text-sm font-semibold text-violet-700 hover:bg-violet-50">Thiết lập biến .env</button>
                @endif
            @endif
            @if(auth('admin')->user()?->can('invoices-download'))
                <button wire:click="runNow" class="h-10 rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white hover:bg-violet-700">Backup file mới ngay</button>
            @endif
        </div>
    </div>

    @if($notice)<div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $notice }}</div>@endif
    @if($error)<div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $error }}</div>@endif

    <div class="mt-4 rounded-xl border {{ $environment['configured'] ? 'border-emerald-100 bg-emerald-50/60' : 'border-amber-200 bg-amber-50' }} p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm font-bold {{ $environment['configured'] ? 'text-emerald-800' : 'text-amber-900' }}">Cấu hình môi trường</p>
                @if($environment['configured'])
                    <p class="mt-1 text-xs text-emerald-700">Đã phát hiện đầy đủ các biến cấu hình automatic backup.</p>
                @elseif($environment['protected'])
                    <p class="mt-1 text-xs text-amber-800">Đang chạy trên {{ $environment['docker'] ? 'Docker/container' : 'production' }}. Vì an toàn, hệ thống không sửa .env từ giao diện. Hãy cấu hình biến tại .env của VPS, docker-compose.yml / env_file hoặc secret rồi recreate/restart container.</p>
                @else
                    <p class="mt-1 text-xs text-amber-800">Thiếu {{ count($environment['missing']) }} biến. Nút “Thiết lập biến .env” chỉ bổ sung key còn thiếu và không ghi đè cấu hình hiện có.</p>
                @endif
            </div>
            @if(!$environment['configured'])<span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800">Thiếu {{ count($environment['missing']) }}</span>@endif
        </div>

        @if(!$environment['configured'])
            <div class="mt-3 grid gap-3 lg:grid-cols-[1fr_1.2fr]">
                <div class="rounded-xl bg-white p-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Biến còn thiếu</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">@foreach($environment['missing'] as $key)<code class="rounded bg-gray-100 px-2 py-1 text-[11px] text-gray-700">{{ $key }}</code>@endforeach</div>
                </div>
                <div class="rounded-xl bg-slate-950 p-3 text-slate-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Cấu hình đề xuất</p>
                    <pre class="mt-2 overflow-x-auto whitespace-pre-wrap text-[11px] leading-5">{{ $environment['snippet'] }}</pre>
                </div>
            </div>
            @if($environment['protected'])
                <div class="mt-3 rounded-xl border border-amber-200 bg-white px-4 py-3 text-xs text-gray-700">
                    <strong>Docker/VPS:</strong> sau khi thêm biến, chạy <code>docker compose up -d --force-recreate</code> (hoặc restart service/container phù hợp), sau đó trong container chạy <code>php artisan optimize:clear</code>. Không nên chỉnh `.env` bên trong container vì thay đổi có thể mất khi container được tạo lại.
                </div>
            @endif
        @endif
    </div>

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
