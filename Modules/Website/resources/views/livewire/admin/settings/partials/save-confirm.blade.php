<div x-data="{ open: false }"
     @website-save-confirm.window="open=true"
     x-show="open" x-cloak
     class="fixed inset-0 z-[10090] flex items-center justify-center bg-slate-900/50 p-4"
     @keydown.escape.window="open=false">
    <div @click.outside="open=false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-slate-900">Lưu thay đổi Website</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">Xác nhận lưu toàn bộ cấu hình hiện tại. Các thay đổi hợp lệ sẽ được áp dụng cho storefront.</p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" @click="open=false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Hủy</button>
            <button type="button" @click="open=false; $wire.save()" wire:loading.attr="disabled" wire:target="save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Xác nhận lưu</button>
        </div>
    </div>
</div>
