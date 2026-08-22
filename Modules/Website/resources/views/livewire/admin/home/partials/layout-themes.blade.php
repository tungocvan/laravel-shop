@php($themes = $this->layoutThemes)
<section class="mx-auto max-w-6xl rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="mb-5">
        <h2 class="text-lg font-bold text-gray-900">Homepage Layout Themes</h2>
        <p class="mt-1 text-sm text-gray-500">Lưu và phục hồi bố cục + presentation. Theme không chứa sản phẩm, danh mục, Banner content, Newsletter hoặc Trust Badges content.</p>
    </div>

    @error('theme')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700">Tên theme</label>
                <input type="text" wire:model="themeName" maxlength="80" placeholder="Ví dụ: Commerce Classic" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700">Theme đã lưu</label>
                <select wire:model.live="selectedTheme" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="">-- Chọn Homepage theme --</option>
                    @foreach($themes as $slug => $theme)
                        <option value="{{ $slug }}">{{ $theme['name'] ?? $slug }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="saveTheme" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Lưu theme mới</button>
                <button type="button" wire:click="applyTheme" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Áp dụng</button>
                <button type="button" wire:click="updateTheme" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cập nhật</button>
                <button type="button" wire:click="renameTheme" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Đổi tên</button>
                <button type="button" wire:click="deleteTheme" wire:confirm="Bạn chắc chắn muốn xóa Homepage theme này?" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Xóa</button>
            </div>

            @if($selectedTheme !== '' && isset($themes[$selectedTheme]))
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                    <div><strong>Version:</strong> {{ $themes[$selectedTheme]['version'] ?? '?' }}</div>
                    <div><strong>Sections:</strong> {{ count($themes[$selectedTheme]['layout']['section_order'] ?? []) }}</div>
                    <div><strong>Cập nhật:</strong> {{ $themes[$selectedTheme]['updated_at'] ?? '—' }}</div>
                </div>
            @endif
        </div>

        <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Export / Import JSON</label>
                    <p class="mt-1 text-xs text-gray-500">Export theme đã chọn hoặc dán JSON hợp lệ để import thành theme mới.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="exportTheme" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Export JSON</button>
                    <button type="button" wire:click="importTheme" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Import JSON</button>
                </div>
            </div>
            <textarea wire:model="themeJson" rows="12" spellcheck="false" placeholder='{"schema":"flexbiz.homepage-layout-theme", ...}' class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 font-mono text-xs text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
        </div>
    </div>

    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
        <strong>Preview-first:</strong> Áp dụng theme chỉ nạp vào Builder và Responsive Preview. Frontend chỉ thay đổi sau khi bấm <strong>Lưu thay đổi</strong> ở Homepage Builder.
    </div>
</section>
