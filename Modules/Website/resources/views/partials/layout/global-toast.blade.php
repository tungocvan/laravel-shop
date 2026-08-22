<div x-data="{ open: false, type: 'success', message: '' }"
    @notify.window="type = $event.detail?.[0]?.type ?? $event.detail?.type ?? 'success'; message = $event.detail?.[0]?.message ?? $event.detail?.message ?? ''; open = true; setTimeout(() => open = false, 4000)"
    @alert.window="type = $event.detail?.[0]?.type ?? 'success'; message = $event.detail?.[0]?.message ?? ''; open = true; setTimeout(() => open = false, 4000)"
    x-show="open"
    x-transition
    role="status"
    aria-live="polite"
    class="fixed bottom-4 left-4 right-4 z-[10000] rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-xl sm:left-auto sm:max-w-sm"
    :class="type === 'error' ? 'bg-red-600' : (type === 'warning' ? 'bg-amber-500' : 'bg-emerald-600')"
    style="display:none">
    <span x-text="message"></span>
    <button type="button" @click="open=false" aria-label="Đóng thông báo" class="float-right ml-4">×</button>
</div>
