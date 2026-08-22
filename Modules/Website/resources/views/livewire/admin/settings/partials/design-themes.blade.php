@php($themes = $this->designThemes)
<div class="space-y-6"
     x-data="{
        modalOpen: false,
        action: '',
        title: '',
        message: '',
        confirm(action, title, message) {
            this.action = action;
            this.title = title;
            this.message = message;
            this.modalOpen = true;
        },
        async run() {
            const action = this.action;
            this.modalOpen = false;
            if (action === 'save') await $wire.saveDesignTheme();
            if (action === 'apply') await $wire.applyDesignTheme();
            if (action === 'update') await $wire.updateDesignTheme();
            if (action === 'rename') await $wire.renameDesignTheme();
            if (action === 'delete') await $wire.deleteDesignTheme();
            if (action === 'restore') await $wire.restoreDefaultDesignThemes();
            if (action === 'import') await $wire.importDesignTheme();
        }
     }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Website Design Themes</h2>
            <p class="mt-1 text-sm text-slate-500">Lưu, áp dụng, export và import nhanh Global Design Tokens. Theme hiện chưa chứa layout shell/PWA; các phần đó sẽ được mở rộng ở Phase 12C–12D.</p>
        </div>
        <button type="button" @click="confirm('restore', 'Khôi phục themes mặc định', 'Khôi phục lại 03 Website design themes mặc định? Custom themes hiện có vẫn được giữ nguyên.')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Khôi phục themes mặc định</button>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="space-y-4">
            <label class="block text-sm font-semibold text-gray-700">Tên theme
                <input wire:model="themeName" maxlength="80" placeholder="Ví dụ: Modern Blue" class="mt-1 block w-full rounded-lg border {{ $errors->has('themeName') ? 'border-red-400' : 'border-gray-300' }} bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                @error('themeName')<span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="block text-sm font-semibold text-gray-700">Theme đã lưu
                <select wire:model.live="selectedTheme" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="">-- Chọn Website design theme --</option>
                    @foreach($themes as $slug => $theme)
                        <option value="{{ $slug }}">{{ $theme['name'] ?? $slug }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="confirm('save', 'Lưu theme mới', 'Lưu cấu hình thiết kế hiện tại thành theme mới?')" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Lưu theme mới</button>
                <button type="button" @click="confirm('apply', 'Áp dụng theme', 'Nạp theme đã chọn vào form thiết kế? Storefront chỉ thay đổi sau khi bấm Lưu thay đổi.')" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Áp dụng</button>
                <button type="button" @click="confirm('update', 'Cập nhật theme', 'Ghi đè theme đã chọn bằng cấu hình thiết kế hiện tại?')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cập nhật</button>
                <button type="button" @click="confirm('rename', 'Đổi tên theme', 'Đổi tên theme đã chọn theo nội dung trong ô Tên theme?')" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Đổi tên</button>
                <button type="button" @click="confirm('delete', 'Xóa theme', 'Bạn chắc chắn muốn xóa Website design theme này?')" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Xóa</button>
            </div>

            @if($selectedTheme !== '' && isset($themes[$selectedTheme]))
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                    <div><strong>Schema:</strong> {{ $themes[$selectedTheme]['schema'] ?? '—' }}</div>
                    <div><strong>Version:</strong> {{ $themes[$selectedTheme]['version'] ?? '—' }}</div>
                    <div><strong>Cập nhật:</strong> {{ $themes[$selectedTheme]['updated_at'] ?? '—' }}</div>
                </div>
            @endif
        </div>

        <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div><p class="text-sm font-semibold text-gray-700">Export / Import JSON</p><p class="mt-1 text-xs text-gray-500">Export theme đã chọn hoặc dán JSON hợp lệ để import.</p></div>
                <div class="flex gap-2">
                    <button type="button" wire:click="exportDesignTheme" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Export JSON</button>
                    <button type="button" @click="confirm('import', 'Import theme', 'Import JSON hiện tại thành Website design theme mới?')" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Import JSON</button>
                </div>
            </div>
            <textarea wire:model="themeJson" rows="16" spellcheck="false" placeholder='{"schema":"flexbiz.website-design-theme", ...}' class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 font-mono text-xs text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
        </div>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800"><strong>Preview-first:</strong> Áp dụng theme chỉ nạp design vào form. Storefront chỉ đổi sau khi bấm <strong>Lưu thay đổi</strong>.</div>

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[10050] flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="modalOpen=false">
        <div @click.outside="modalOpen=false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-slate-900" x-text="title"></h3>
            <p class="mt-2 text-sm leading-6 text-slate-600" x-text="message"></p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="modalOpen=false" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Hủy</button>
                <button type="button" @click="run()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Xác nhận</button>
            </div>
        </div>
    </div>
</div>
