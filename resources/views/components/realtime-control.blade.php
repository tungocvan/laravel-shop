@props(['enabled' => false, 'status' => []])

@php
    $state = $status['status'] ?? 'offline';
    $stateLabel = match ($state) {
        'online' => 'Online',
        'disabled' => 'Đã tắt',
        default => 'Offline',
    };
    $stateClass = match ($state) {
        'online' => 'bg-emerald-100 text-emerald-800',
        'disabled' => 'bg-slate-100 text-slate-700',
        default => 'bg-red-100 text-red-800',
    };
@endphp

<section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold text-gray-900">Realtime / Socket.IO</h3>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stateClass }}">{{ $stateLabel }}</span>
            </div>
            <p class="mt-1 text-sm text-gray-600">
                Tắt để frontend không tải Socket.IO client và backend không gửi realtime event. Không cần build lại frontend.
            </p>
            @if (! empty($status['url']))
                <p class="mt-2 break-all font-mono text-xs text-gray-500">Health: {{ $status['url'] }}</p>
            @endif
            @if (($status['clients'] ?? null) !== null)
                <p class="mt-1 text-xs text-gray-500">Client đang kết nối: {{ $status['clients'] }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <button type="button" wire:click="refreshRealtimeStatus"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Kiểm tra lại
            </button>
            <button type="button" wire:click="toggleRealtime" wire:loading.attr="disabled"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-60 {{ $enabled ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                {{ $enabled ? 'Tắt realtime' : 'Bật realtime' }}
            </button>
        </div>
    </div>
</section>
