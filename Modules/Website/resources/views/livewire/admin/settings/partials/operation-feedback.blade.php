<div x-data="{ open: false, type: 'success', title: '', message: '' }"
     @operation-feedback.window="type = $event.detail?.type ?? $event.detail?.[0]?.type ?? 'success'; title = $event.detail?.title ?? $event.detail?.[0]?.title ?? ''; message = $event.detail?.message ?? $event.detail?.[0]?.message ?? ''; open = true"
     x-show="open" x-cloak
     class="fixed inset-0 z-[10100] flex items-center justify-center bg-slate-900/50 p-4"
     @keydown.escape.window="open=false">
    <div @click.outside="open=false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                 :class="type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                <span x-text="type === 'success' ? '✓' : '!'" class="text-lg font-bold"></span>
            </div>
            <div class="min-w-0">
                <h3 class="text-lg font-bold text-slate-900" x-text="title"></h3>
                <p class="mt-1 text-sm leading-6 text-slate-600" x-text="message"></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="button" @click="open=false" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Đóng</button>
        </div>
    </div>
</div>
