<div wire:poll.5s="$refresh" class="space-y-5">
    @if (session('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('message') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if (session('queue_message'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800">
            {{ session('queue_message') }}
        </div>
    @endif

    @php
        $pendingTotal = collect($queues)->sum(fn ($queue) => (int) ($queue['status']['pending'] ?? 0));
        $reservedTotal = collect($queues)->sum(fn ($queue) => (int) ($queue['status']['reserved'] ?? 0));
        $failedTotal = collect($queues)->sum(fn ($queue) => (int) ($queue['status']['failed'] ?? 0));
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 lg:flex-row lg:items-center lg:justify-between sm:px-6">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-xl font-bold text-slate-900">Queue Manager</h3>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Tự làm mới mỗi 5 giây</span>
                </div>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                    Theo dõi toàn bộ queue đang khai báo hoặc đang có dữ liệu trong hệ thống. Không phụ thuộc PM2; dùng được cho Docker, Supervisor hoặc worker chạy trực tiếp.
                </p>
            </div>

            <button type="button"
                wire:click="restartWorkers"
                wire:confirm="Gửi tín hiệu restart đến toàn bộ Laravel queue workers? Worker sẽ kết thúc an toàn sau job hiện tại và tiến trình quản lý bên ngoài phải tự khởi động lại worker."
                wire:loading.attr="disabled"
                wire:target="restartWorkers"
                @disabled(! $canManageQueues)
                class="inline-flex h-10 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
                Restart queue workers
            </button>
        </div>

        <div class="grid grid-cols-2 gap-px bg-slate-200 lg:grid-cols-4">
            <div class="bg-white p-4 sm:p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Queues</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ count($queues) }}</div>
            </div>
            <div class="bg-white p-4 sm:p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-600">Pending</div>
                <div class="mt-2 text-2xl font-bold text-amber-900">{{ $pendingTotal }}</div>
            </div>
            <div class="bg-white p-4 sm:p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-blue-600">Đang xử lý</div>
                <div class="mt-2 text-2xl font-bold text-blue-900">{{ $reservedTotal }}</div>
            </div>
            <div class="bg-white p-4 sm:p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">Failed</div>
                <div class="mt-2 text-2xl font-bold text-rose-900">{{ $failedTotal }}</div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">Trạng thái Queue</h3>
                <p class="mt-1 text-sm text-slate-500">Queue mặc định, queue do Module khai báo và queue được phát hiện từ jobs/failed_jobs đều xuất hiện tại đây.</p>
            </div>
            <div class="text-xs text-slate-400">Quyền restart: system.settings.update</div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($queues as $queue)
                @php
                    $status = $queue['status'];
                    $state = $status['state'] ?? 'idle';
                    $stateLabel = match ($state) {
                        'attention' => 'Cần xử lý',
                        'processing' => 'Đang chạy',
                        'waiting' => 'Đang chờ',
                        default => 'Rảnh',
                    };
                    $stateClass = match ($state) {
                        'attention' => 'bg-rose-100 text-rose-800',
                        'processing' => 'bg-blue-100 text-blue-800',
                        'waiting' => 'bg-amber-100 text-amber-800',
                        default => 'bg-emerald-100 text-emerald-800',
                    };
                @endphp

                <article class="px-5 py-5 sm:px-6" wire:key="queue-{{ $queue['name'] }}">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-slate-900">{{ $queue['name'] }}</h4>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $stateClass }}">{{ $stateLabel }}</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $queue['module'] }}</span>
                                @if (($queue['source'] ?? null) === 'runtime')
                                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700">Runtime discovered</span>
                                @elseif (($queue['source'] ?? null) === 'module')
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">Module registry</span>
                                @endif
                            </div>
                            @if ($queue['description'])
                                <p class="mt-1 text-sm text-slate-500">{{ $queue['description'] }}</p>
                            @endif
                        </div>

                        <button type="button"
                            wire:click="probe('{{ $queue['name'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="probe('{{ $queue['name'] }}')"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                            Kiểm tra worker
                        </button>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div class="rounded-xl border border-amber-100 bg-amber-50/70 p-3">
                            <div class="text-xs font-medium text-amber-700">Pending</div>
                            <div class="mt-1 text-xl font-bold text-amber-900">{{ $status['pending'] }}</div>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-blue-50/70 p-3">
                            <div class="text-xs font-medium text-blue-700">Đang xử lý</div>
                            <div class="mt-1 text-xl font-bold text-blue-900">{{ $status['reserved'] }}</div>
                        </div>
                        <div class="rounded-xl border border-rose-100 bg-rose-50/70 p-3">
                            <div class="text-xs font-medium text-rose-700">Failed</div>
                            <div class="mt-1 text-xl font-bold text-rose-900">{{ $status['failed'] }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="text-xs font-medium text-slate-600">Worker probe gần nhất</div>
                            <div class="mt-1 break-words text-xs font-semibold text-slate-800">{{ $status['last_probe_at'] ?: 'Chưa xác nhận' }}</div>
                        </div>
                    </div>

                    <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50/60">
                        <summary class="cursor-pointer px-4 py-3 text-xs font-semibold text-slate-600">Cấu hình worker</summary>
                        <div class="border-t border-slate-200 px-4 py-3">
                            <code class="block overflow-x-auto rounded-lg bg-slate-950 px-3 py-2 text-xs text-slate-100">{{ $queue['command'] }}</code>
                        </div>
                    </details>
                </article>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    Chưa phát hiện queue nào trong registry hoặc dữ liệu runtime.
                </div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-4">
            <h3 class="text-base font-bold text-slate-900">Realtime / Socket.IO</h3>
            <p class="mt-1 text-sm text-slate-500">Quản lý feature switch realtime độc lập với queue worker và công cụ quản lý tiến trình của môi trường triển khai.</p>
        </div>
        <x-realtime-control :enabled="$realtimeEnabled" :status="$realtimeStatus" :can-update="$canUpdateRealtime" />
    </section>
</div>
