<div wire:poll.5s="$refresh" class="space-y-6">
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
        $runtimeItems = $processStatus['items'] ?? [];
    @endphp

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 to-slate-800 px-5 py-5 text-white sm:px-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">System Runtime Operations</div>
                    <h3 class="mt-1 text-xl font-bold">Queue Manager</h3>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        Một workspace để theo dõi queue, Realtime / Socket.IO và các tiến trình PM2 của Laravel Shop.
                        Trạng thái tự cập nhật mỗi 5 giây; thao tác runtime chỉ dành cho quản trị viên có quyền cập nhật hệ thống.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-white/10 px-3 py-1.5">PM2 {{ (int) ($processStatus['online'] ?? 0) }}/{{ (int) ($processStatus['total'] ?? 0) }} online</span>
                    <span class="rounded-full bg-white/10 px-3 py-1.5">{{ count($queues) }} queue registry</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-px bg-slate-200 lg:grid-cols-4">
            <div class="bg-white p-4 sm:p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">PM2 Online</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ (int) ($processStatus['online'] ?? 0) }}<span class="text-sm font-medium text-slate-400"> / {{ (int) ($processStatus['total'] ?? 0) }}</span></div>
            </div>
            <div class="bg-white p-4 sm:p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-600">Pending Jobs</div>
                <div class="mt-2 text-2xl font-bold text-amber-900">{{ $pendingTotal }}</div>
            </div>
            <div class="bg-white p-4 sm:p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-blue-600">Đang xử lý</div>
                <div class="mt-2 text-2xl font-bold text-blue-900">{{ $reservedTotal }}</div>
            </div>
            <div class="bg-white p-4 sm:p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">Failed Jobs</div>
                <div class="mt-2 text-2xl font-bold text-rose-900">{{ $failedTotal }}</div>
            </div>
        </div>
    </section>

    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-bold text-slate-900">Dịch vụ nền PM2</h3>
                    @if ($processStatus['available'] ?? false)
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">PM2 reachable</span>
                    @else
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">PM2 unavailable</span>
                    @endif
                </div>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                    Chỉ quản lý bốn runtime đã allowlist của Laravel Shop: Queue mặc định, Request Queue, Scheduler và Socket.IO.
                    Các tiến trình PM2 khác trên máy chủ không được hiển thị hoặc điều khiển từ trình duyệt.
                </p>
            </div>
            <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-500">
                Quyền điều khiển: <span class="font-mono font-semibold text-slate-700">system.modules.update</span>
            </div>
        </div>

        @if (! ($processStatus['available'] ?? false))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                Laravel chưa đọc được PM2 daemon. Kiểm tra quyền của user chạy PHP-FPM, biến <code>SYSTEM_PM2_HOME</code> hoặc đường dẫn <code>SYSTEM_PM2_BINARY</code>.
                Hệ thống không sử dụng <code>sudo</code> và không tự nâng quyền từ web.
            </div>
        @elseif ($runtimeItems === [])
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                PM2 đang hoạt động nhưng chưa tìm thấy runtime Laravel Shop trong allowlist.
            </div>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($runtimeItems as $process)
                    @php
                        $online = ($process['status'] ?? 'unknown') === 'online';
                        $memoryMb = round(((int) ($process['memory_bytes'] ?? 0)) / 1024 / 1024, 1);
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5" wire:key="pm2-{{ $process['name'] }}">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-bold text-slate-900">{{ $process['role'] }}</h4>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $online ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ strtoupper($process['status'] ?? 'unknown') }}
                                    </span>
                                </div>
                                <div class="mt-1 break-all font-mono text-xs text-slate-500">{{ $process['name'] }}</div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($online)
                                    <button type="button"
                                        wire:click="processAction('{{ $process['name'] }}', 'restart')"
                                        wire:confirm="Restart {{ $process['name'] }}? Tiến trình có thể gián đoạn trong vài giây."
                                        wire:loading.attr="disabled"
                                        wire:target="processAction('{{ $process['name'] }}', 'restart')"
                                        @disabled(! $canManageProcesses)
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50">
                                        Restart
                                    </button>
                                    <button type="button"
                                        wire:click="processAction('{{ $process['name'] }}', 'stop')"
                                        wire:confirm="Dừng {{ $process['name'] }}? Chức năng liên quan sẽ ngừng cho đến khi tiến trình được bật lại."
                                        wire:loading.attr="disabled"
                                        wire:target="processAction('{{ $process['name'] }}', 'stop')"
                                        @disabled(! $canManageProcesses)
                                        class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                                        Stop
                                    </button>
                                @else
                                    <button type="button"
                                        wire:click="processAction('{{ $process['name'] }}', 'start')"
                                        wire:confirm="Bật lại {{ $process['name'] }}?"
                                        wire:loading.attr="disabled"
                                        wire:target="processAction('{{ $process['name'] }}', 'start')"
                                        @disabled(! $canManageProcesses)
                                        class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                                        Start
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-xl bg-white p-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">PM2 ID</div>
                                <div class="mt-1 font-bold text-slate-800">{{ $process['pm_id'] ?? '—' }}</div>
                            </div>
                            <div class="rounded-xl bg-white p-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">CPU</div>
                                <div class="mt-1 font-bold text-slate-800">{{ $process['cpu'] }}%</div>
                            </div>
                            <div class="rounded-xl bg-white p-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Memory</div>
                                <div class="mt-1 font-bold text-slate-800">{{ $memoryMb }} MB</div>
                            </div>
                            <div class="rounded-xl bg-white p-3">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Restarts</div>
                                <div class="mt-1 font-bold text-slate-800">{{ $process['restarts'] }}</div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="space-y-4">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Realtime / Socket.IO</h3>
            <p class="mt-1 text-sm text-slate-500">Feature switch của ứng dụng được quản lý độc lập với trạng thái tiến trình Socket.IO trong PM2.</p>
        </div>
        <x-realtime-control :enabled="$realtimeEnabled" :status="$realtimeStatus" :can-update="$canUpdateRealtime" />
    </section>

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Queue workloads</h3>
                <p class="mt-1 text-sm text-slate-500">Theo dõi backlog theo queue registry của từng Module và gửi probe để xác nhận worker đang tiêu thụ job.</p>
            </div>
            <div class="text-xs text-slate-400">Auto refresh 5s</div>
        </div>

        <div class="space-y-4">
            @forelse ($queues as $queue)
                @php($status = $queue['status'])
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-slate-900">{{ $queue['name'] }}</h4>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $queue['module'] }}</span>
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{{ $queue['workers'] }} worker</span>
                            </div>
                            @if ($queue['description'])
                                <p class="mt-1 text-sm text-slate-500">{{ $queue['description'] }}</p>
                            @endif
                        </div>

                        <button type="button"
                            wire:click="probe('{{ $queue['name'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="probe('{{ $queue['name'] }}')"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                            Kiểm tra worker
                        </button>
                    </div>

                    <div class="p-5">
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                            <div class="rounded-xl bg-amber-50 p-3">
                                <div class="text-xs font-medium text-amber-700">Pending</div>
                                <div class="mt-1 text-xl font-bold text-amber-900">{{ $status['pending'] }}</div>
                            </div>
                            <div class="rounded-xl bg-blue-50 p-3">
                                <div class="text-xs font-medium text-blue-700">Đang xử lý</div>
                                <div class="mt-1 text-xl font-bold text-blue-900">{{ $status['reserved'] }}</div>
                            </div>
                            <div class="rounded-xl bg-rose-50 p-3">
                                <div class="text-xs font-medium text-rose-700">Failed</div>
                                <div class="mt-1 text-xl font-bold text-rose-900">{{ $status['failed'] }}</div>
                            </div>
                            <div class="rounded-xl bg-emerald-50 p-3">
                                <div class="text-xs font-medium text-emerald-700">Probe gần nhất</div>
                                <div class="mt-1 break-words text-xs font-semibold text-emerald-900">{{ $status['last_probe_at'] ?: 'Chưa xác nhận' }}</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Worker command</div>
                            <code class="mt-2 block overflow-x-auto rounded-xl bg-slate-950 px-4 py-3 text-xs text-slate-100">{{ $queue['command'] }}</code>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                    Chưa có Module đang bật khai báo queue riêng.
                </div>
            @endforelse
        </div>
    </section>
</div>
