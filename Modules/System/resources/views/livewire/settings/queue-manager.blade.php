<div wire:poll.5s="$refresh" class="space-y-5">
    @if (session('queue_message'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            {{ session('queue_message') }}
        </div>
    @endif

    <div>
        <h3 class="text-lg font-semibold text-gray-900">Queue Manager</h3>
        <p class="mt-1 text-sm text-gray-500">
            Theo dõi các queue do Module khai báo. System chỉ quản lý registry, health và số job; Docker/PM2 chịu trách nhiệm chạy worker.
        </p>
    </div>

    <div class="space-y-4">
        @forelse ($queues as $queue)
            @php($status = $queue['status'])
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-semibold text-gray-900">{{ $queue['name'] }}</h4>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-700">{{ $queue['module'] }}</span>
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs text-blue-700">{{ $queue['workers'] }} worker</span>
                        </div>
                        @if ($queue['description'])
                            <p class="mt-1 text-sm text-gray-500">{{ $queue['description'] }}</p>
                        @endif
                    </div>

                    <button type="button"
                        wire:click="probe('{{ $queue['name'] }}')"
                        wire:loading.attr="disabled"
                        wire:target="probe('{{ $queue['name'] }}')"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50">
                        Kiểm tra worker
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-xl bg-amber-50 p-3">
                        <div class="text-xs text-amber-700">Pending</div>
                        <div class="mt-1 text-xl font-bold text-amber-900">{{ $status['pending'] }}</div>
                    </div>
                    <div class="rounded-xl bg-blue-50 p-3">
                        <div class="text-xs text-blue-700">Đang xử lý</div>
                        <div class="mt-1 text-xl font-bold text-blue-900">{{ $status['reserved'] }}</div>
                    </div>
                    <div class="rounded-xl bg-rose-50 p-3">
                        <div class="text-xs text-rose-700">Failed</div>
                        <div class="mt-1 text-xl font-bold text-rose-900">{{ $status['failed'] }}</div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-3">
                        <div class="text-xs text-emerald-700">Probe gần nhất</div>
                        <div class="mt-1 text-xs font-semibold text-emerald-900 break-words">
                            {{ $status['last_probe_at'] ?: 'Chưa xác nhận' }}
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Worker command</div>
                    <code class="mt-2 block overflow-x-auto rounded-xl bg-gray-950 px-4 py-3 text-xs text-gray-100">{{ $queue['command'] }}</code>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                Chưa có Module đang bật khai báo queue riêng.
            </div>
        @endforelse
    </div>
</div>
