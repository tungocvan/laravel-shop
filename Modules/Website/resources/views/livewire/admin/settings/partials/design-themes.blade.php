@php($themes = $this->designThemes)
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Website Design Themes</h2>
        <p class="mt-1 text-sm text-slate-500">Lưu, áp dụng, export và import nhanh Global Design Tokens. Theme hiện chưa chứa layout shell/PWA; các phần đó sẽ được mở rộng ở Phase 12C–12D.</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="space-y-4">
            <label class="block text-sm font-semibold text-gray-700">Tên theme
                <input wire:model="themeName" maxlength="80" placeholder="Ví dụ: Modern Blue" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
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
                <button type="button" wire:click="saveDesignTheme" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Lưu theme mới</button>
                <button type="button" wire:click="applyDesignTheme" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Áp dụng</button>
                <button type="button" wire:click="updateDesignTheme" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cập nhật</button>
                <button type="button" wire:click="renameDesignTheme" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Đổi tên</button>
                <button type="button" wire:click="deleteDesignTheme" wire:confirm="Bạn chắc chắn muốn xóa Website design theme này?" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Xóa</button>
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
                    <button type="button" wire:click="importDesignTheme" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Import JSON</button>
                </div>
            </div>
            <textarea wire:model="themeJson" rows="16" spellcheck="false" placeholder='{"schema":"flexbiz.website-design-theme", ...}' class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 font-mono text-xs text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
        </div>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800"><strong>Preview-first:</strong> Áp dụng theme chỉ nạp design vào form. Storefront chỉ đổi sau khi bấm <strong>Lưu thay đổi</strong>.</div>
</div>
