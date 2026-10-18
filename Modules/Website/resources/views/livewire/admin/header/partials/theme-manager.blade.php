<section class="rounded-xl border border-violet-200 bg-violet-50/40 p-4 shadow-sm">
    @php($fieldClass = 'mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition hover:border-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100')
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h3 class="font-bold text-gray-900">Header Layout Themes</h3>
            <p class="mt-1 text-sm text-gray-500">Theme lưu bố cục, presentation, tài khoản, Topbar và thương hiệu. Nạp để chỉnh sửa; chỉ nút <strong>Áp dụng lên website</strong> mới xuất bản toàn bộ profile.</p>
        </div>
        <span class="self-start rounded-full bg-white px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">{{ count($layoutThemes) }}/20 themes</span>
    </div>

    @if($editingTemplate === 'pharma')
        <div class="mt-4 grid gap-3 md:grid-cols-2" aria-label="Mẫu Header có sẵn">
            <div class="rounded-xl border border-emerald-200 bg-white p-4">
                <div class="mb-2 h-2 rounded-full bg-emerald-700"></div>
                <h4 class="text-sm font-bold text-slate-900">Pharma Classic</h4>
                @if($activeTheme && ($layoutThemes[$activeTheme]['starter_profile'] ?? '') === 'pharma-classic')<p class="mt-1 text-xs font-bold text-emerald-700">● Đang áp dụng qua {{ $layoutThemes[$activeTheme]['name'] ?? $activeTheme }}</p>@elseif($starterProfile === 'pharma-classic')<p class="mt-1 text-xs font-semibold text-slate-600">Đang xem trước</p>@endif
                <p class="mt-1 text-xs text-slate-500">Xanh dược phẩm, bố cục cân đối, menu rõ ràng.</p>
                <button type="button" wire:click="previewStarterTheme('pharma-classic')" class="mt-3 rounded-lg border border-emerald-600 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-50">Thử mẫu Pharma Classic</button>
            </div>
            <div class="rounded-xl border border-blue-200 bg-white p-4">
                <div class="mb-2 h-2 rounded-full bg-blue-700"></div>
                <h4 class="text-sm font-bold text-slate-900">FlexBiz Modern</h4>
                @if($activeTheme && ($layoutThemes[$activeTheme]['starter_profile'] ?? '') === 'flexbiz-modern')<p class="mt-1 text-xs font-bold text-blue-700">● Đang áp dụng qua {{ $layoutThemes[$activeTheme]['name'] ?? $activeTheme }}</p>@elseif($starterProfile === 'flexbiz-modern')<p class="mt-1 text-xs font-semibold text-slate-600">Đang xem trước</p>@endif
                <p class="mt-1 text-xs text-slate-500">Xanh công nghệ, nội dung rộng, điểm nhấn hiện đại.</p>
                <button type="button" wire:click="previewStarterTheme('flexbiz-modern')" class="mt-3 rounded-lg border border-blue-600 px-3 py-2 text-xs font-semibold text-blue-800 hover:bg-blue-50">Thử mẫu FlexBiz Modern</button>
            </div>
        </div>
        <p class="mt-2 text-xs text-violet-700">Thử mẫu chỉ cập nhật Builder/Preview, không đổi website đang xuất bản. Có thể lưu thành theme mới bằng biểu mẫu bên dưới.</p>
    @endif

    @error('theme')<div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>@enderror
    @error('themeName')<div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>@enderror

    <div class="mt-4 grid gap-4 xl:grid-cols-[1.2fr_1fr]">
        <div class="rounded-lg border border-violet-100 bg-white p-4">
            @if($activeTheme && isset($layoutThemes[$activeTheme]))
                <div class="mb-4 rounded-xl border-2 border-emerald-500 bg-emerald-50 p-3" role="status" aria-label="Profile Header đang áp dụng">
                    <span class="text-xs font-bold uppercase tracking-wide text-emerald-800">● Đang áp dụng trên website</span>
                    <p class="mt-1 font-bold text-emerald-950">{{ $layoutThemes[$activeTheme]['name'] ?? $activeTheme }}</p>
                    <button type="button" wire:click="selectTheme(@js($activeTheme))" class="mt-2 rounded-lg border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-800">Chọn profile này để cập nhật</button>
                </div>
            @else
                <p class="mb-4 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">Chưa có profile nào được đánh dấu đang áp dụng. Header hiện sử dụng cấu hình đã lưu trực tiếp.</p>
            @endif
            <label class="block text-sm font-semibold text-gray-700">Theme đã lưu</label>
            <select wire:change="selectTheme($event.target.value)" class="{{ $fieldClass }}">
                <option value="">Chọn Header theme...</option>
                @foreach($layoutThemes as $slug => $theme)
                    <option value="{{ $slug }}" @selected($selectedTheme === $slug)>{{ $theme['name'] ?? $slug }}{{ $activeTheme === $slug ? ' · ĐANG ÁP DỤNG' : '' }}</option>
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
                @if($editingTemplate === 'pharma')
                    <button type="button" wire:click="publishTheme" wire:confirm="Áp dụng toàn bộ bố cục, tài khoản, Topbar và giao diện của profile này lên website?" @disabled(!$selectedTheme) class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white disabled:opacity-40">Áp dụng lên website</button>
                @endif
                <button type="button" wire:click="updateTheme" @disabled(!$selectedTheme || ($editingTemplate === 'pharma' && $activeTheme !== $selectedTheme)) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40">Cập nhật tham số profile</button>
                <button type="button" wire:click="deleteTheme" wire:confirm="Xóa Header theme này?" @disabled(!$selectedTheme) class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 disabled:opacity-40">Xóa</button>
            </div>
        </div>

        <div class="rounded-lg border border-violet-100 bg-white p-4">
            <label class="block text-sm font-semibold text-gray-700">Tên theme</label>
            <input type="text" wire:model="themeName" maxlength="60" placeholder="Ví dụ: Shop Header Standard" class="{{ $fieldClass }}">
            <p class="mt-2 text-xs text-gray-400">Profile lưu bố cục, giao diện, trạng thái tài khoản, Topbar và thiết lập thương hiệu. Nội dung menu, hotline/email và logo được quản lý riêng.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" wire:click="saveTheme" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Lưu thành theme mới</button>
                <button type="button" wire:click="renameTheme" @disabled(!$selectedTheme) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40">Đổi tên theme</button>
            </div>
        </div>
    </div>
</section>
