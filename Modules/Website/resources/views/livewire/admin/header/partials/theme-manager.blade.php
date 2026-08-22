<section class="rounded-xl border border-violet-200 bg-violet-50/40 p-4 shadow-sm">
    @php($fieldClass = 'mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition hover:border-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100')
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h3 class="font-bold text-gray-900">Header Layout Themes</h3>
            <p class="mt-1 text-sm text-gray-500">Lưu snapshot bố cục + presentation. Nạp theme chỉ thay đổi Builder/Preview; frontend chỉ đổi sau khi bấm <strong>Lưu bố cục</strong>.</p>
        </div>
        <span class="self-start rounded-full bg-white px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">{{ count($layoutThemes) }}/20 themes</span>
    </div>

    @error('theme')<div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>@enderror
    @error('themeName')<div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>@enderror

    <div class="mt-4 grid gap-4 xl:grid-cols-[1.2fr_1fr]">
        <div class="rounded-lg border border-violet-100 bg-white p-4">
            <label class="block text-sm font-semibold text-gray-700">Theme đã lưu</label>
            <select wire:change="selectTheme($event.target.value)" class="{{ $fieldClass }}">
                <option value="">Chọn Header theme...</option>
                @foreach($layoutThemes as $slug => $theme)
                    <option value="{{ $slug }}" @selected($selectedTheme === $slug)>{{ $theme['name'] ?? $slug }}</option>
                @endforeach
            </select>
            @if($selectedTheme && isset($layoutThemes[$selectedTheme]))
                <div class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">
                    <div><strong>Version:</strong> {{ $layoutThemes[$selectedTheme]['version'] ?? 1 }}</div>
                    @if(!empty($layoutThemes[$selectedTheme]['updated_at']))<div class="mt-1"><strong>Cập nhật:</strong> {{ $layoutThemes[$selectedTheme]['updated_at'] }}</div>@endif
                </div>
            @endif
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" wire:click="applyTheme" @disabled(!$selectedTheme) class="rounded-lg bg-violet-600 px-3 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-40">Nạp / Áp dụng</button>
                <button type="button" wire:click="updateTheme" @disabled(!$selectedTheme) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40">Cập nhật theme</button>
                <button type="button" wire:click="deleteTheme" wire:confirm="Xóa Header theme này?" @disabled(!$selectedTheme) class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 disabled:opacity-40">Xóa</button>
            </div>
        </div>

        <div class="rounded-lg border border-violet-100 bg-white p-4">
            <label class="block text-sm font-semibold text-gray-700">Tên theme</label>
            <input type="text" wire:model="themeName" maxlength="60" placeholder="Ví dụ: Shop Header Standard" class="{{ $fieldClass }}">
            <p class="mt-2 text-xs text-gray-400">Theme không lưu menu content, hotline, email, brand name/logo hoặc script.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" wire:click="saveTheme" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Lưu thành theme mới</button>
                <button type="button" wire:click="renameTheme" @disabled(!$selectedTheme) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40">Đổi tên theme</button>
            </div>
        </div>
    </div>
</section>
