@php
    $control = 'mt-2 block h-10 w-full rounded-lg border-slate-300 bg-white px-3 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $toggle = 'h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
    $colorLabels = [
        'surface_base' => 'Nền workspace', 'surface_raised' => 'Surface nổi', 'text_primary' => 'Text chính', 'text_secondary' => 'Text phụ', 'text_muted' => 'Text muted', 'border_subtle' => 'Border',
        'accent' => 'Accent', 'focus_ring' => 'Focus ring', 'success' => 'Success', 'warning' => 'Warning', 'danger' => 'Danger', 'info' => 'Info',
    ];
    $spacingLabels = ['1' => '4 px', '2' => '8 px', '3' => '12 px', '4' => '16 px', '6' => '24 px', '8' => '32 px'];
    $headerBg = data_get($config, 'header.presentation.background', 'system');
    $footerBg = data_get($config, 'footer.presentation.background', 'system');
    $surfaceBase = $colorOptions[data_get($config, 'design.colors.surface_base', 'slate-50')] ?? '#f8fafc';
    $surfaceRaised = $colorOptions[data_get($config, 'design.colors.surface_raised', 'white')] ?? '#ffffff';
    $textPrimary = $colorOptions[data_get($config, 'design.colors.text_primary', 'slate-900')] ?? '#0f172a';
    $textMuted = $colorOptions[data_get($config, 'design.colors.text_muted', 'slate-500')] ?? '#64748b';
    $border = $colorOptions[data_get($config, 'design.colors.border_subtle', 'slate-200')] ?? '#e2e8f0';
    $accent = $colorOptions[data_get($config, 'design.colors.accent', 'indigo-600')] ?? '#4f46e5';
@endphp

<div class="mx-auto max-w-[90rem]">
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex items-center gap-2"><h2 class="text-lg font-semibold text-slate-900">Theme Editor</h2><span class="rounded-full bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 ring-1 ring-indigo-200">Whole Admin</span></div>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">Theme điều khiển semantic colors và presentation của Sidebar, Header, Content surface và Footer. Các giá trị được giới hạn theo token an toàn để tránh phá contrast/UX.</p>
            </div>
            <button type="button" wire:click="restoreDefaultTheme" wire:confirm="Khôi phục Theme mặc định Professional Indigo?" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Khôi phục Theme mặc định</button>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach($profiles as $key => $profile)
                <button type="button" wire:click="selectTheme('{{ $key }}')" class="rounded-xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 {{ $selectedTheme === $key ? 'border-indigo-500 bg-indigo-50/60 ring-1 ring-indigo-500' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                    <span class="flex items-center justify-between gap-3"><span class="text-sm font-semibold text-slate-900">{{ $profile['label'] }}</span>@if($profile['built_in'])<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">System</span>@else<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Custom</span>@endif</span>
                    <span class="mt-1.5 block text-xs leading-5 text-slate-500">{{ $profile['description'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <form wire:submit="saveTheme" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
            <div class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Semantic colors</h2><p class="mt-1 text-sm leading-6 text-slate-500">Đây là lớp màu chung của toàn bộ Admin. Sidebar/Header/Footer sẽ kế thừa khi chọn chế độ System hoặc Theme.</p></div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($colorLabels as $key => $label)
                            <label class="block"><span class="text-sm font-medium text-slate-700">{{ $label }}</span><div class="relative"><select wire:model.live="config.design.colors.{{ $key }}" class="{{ $control }} pr-10">@foreach($colorOptions as $name => $hex)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select><span class="pointer-events-none absolute right-3 top-1/2 mt-1 h-5 w-5 -translate-y-1/2 rounded-md border border-black/10" style="background: {{ $colorOptions[data_get($config, 'design.colors.'.$key)] ?? '#fff' }}"></span></div></label>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Typography & shape</h2><p class="mt-1 text-sm leading-6 text-slate-500">Giữ typography restrained và radius nhất quán để Admin nhìn chuyên nghiệp.</p></div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="block"><span class="text-sm font-medium text-slate-700">Body size</span><select wire:model.live="config.design.typography.body_size" class="{{ $control }}"><option value="xs">12 px</option><option value="sm">14 px — khuyến nghị</option><option value="base">16 px</option><option value="lg">18 px</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Page title</span><select wire:model.live="config.design.typography.page_title_size" class="{{ $control }}"><option value="lg">18 px</option><option value="2xl">24 px — khuyến nghị</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Heading weight</span><select wire:model.live="config.design.typography.heading_weight" class="{{ $control }}"><option value="medium">Medium</option><option value="semibold">Semibold — khuyến nghị</option><option value="bold">Bold</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Panel radius</span><select wire:model.live="config.design.radius.panel" class="{{ $control }}"><option value="sm">4 px</option><option value="md">6 px</option><option value="lg">8 px — khuyến nghị</option><option value="xl">12 px</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Control radius</span><select wire:model.live="config.design.radius.control" class="{{ $control }}"><option value="sm">4 px</option><option value="md">6 px</option><option value="lg">8 px — khuyến nghị</option><option value="xl">12 px</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Overlay radius</span><select wire:model.live="config.design.radius.overlay" class="{{ $control }}"><option value="md">6 px</option><option value="lg">8 px</option><option value="xl">12 px — khuyến nghị</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Control spacing</span><select wire:model.live="config.design.spacing.control" class="{{ $control }}">@foreach($spacingLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Section spacing</span><select wire:model.live="config.design.spacing.section" class="{{ $control }}">@foreach($spacingLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    </div>
                    <input type="hidden" wire:model="config.design.typography.font_family"><input type="hidden" wire:model="config.design.spacing.tight"><input type="hidden" wire:model="config.design.spacing.content">
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Sidebar Theme</h2><p class="mt-1 text-sm leading-6 text-slate-500">Palette menu và surface Sidebar. Các behavior/width vẫn thuộc trang thiết lập Sidebar, không bị Theme ghi đè.</p></div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="block"><span class="text-sm font-medium text-slate-700">Menu palette</span><select wire:model.live="config.theme.default" class="{{ $control }}">@foreach($sidebarPalettes as $palette)<option value="{{ $palette }}">{{ $palette }}</option>@endforeach</select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Sidebar background</span><select wire:model.live="config.sidebar.presentation.background" class="{{ $control }}"><option value="theme">Theo palette Sidebar</option><option value="system">Theo semantic surface</option><option value="white">White</option><option value="dark">Dark</option></select></label>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Header presentation</h2><p class="mt-1 text-sm leading-6 text-slate-500">Theme chỉ quản presentation, không thay Brand, Header Actions hay UserMenu.</p></div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <label class="block"><span class="text-sm font-medium text-slate-700">Background</span><select wire:model.live="config.header.presentation.background" class="{{ $control }}"><option value="system">System — khuyến nghị</option><option value="white">White</option><option value="transparent">Transparent</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Mode</span><select wire:model.live="config.header.presentation.mode" class="{{ $control }}"><option value="balanced">Balanced — khuyến nghị</option><option value="compact">Compact</option><option value="action-heavy">Action heavy</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Shadow</span><select wire:model.live="config.header.presentation.shadow" class="{{ $control }}"><option value="subtle">Subtle — khuyến nghị</option><option value="none">None</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Divider</span><select wire:model.live="config.header.presentation.divider" class="{{ $control }}"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Padding ngang</span><select wire:model.live="config.header.presentation.padding_x" class="{{ $control }}">@foreach(['3'=>'12 px','4'=>'16 px','5'=>'20 px','6'=>'24 px','8'=>'32 px'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3 lg:self-end"><span><span class="block text-sm font-medium text-slate-800">Backdrop blur</span><span class="mt-0.5 block text-xs text-slate-500">Giữ Header nhẹ khi có nội dung cuộn.</span></span><input type="checkbox" wire:model.live="config.header.presentation.backdrop_blur" class="{{ $toggle }}"></label>
                    </div>
                    <input type="hidden" wire:model="config.header.presentation.action_gap">
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Content & Footer</h2><p class="mt-1 text-sm leading-6 text-slate-500">Giữ workspace trung tính và Footer nhẹ hơn Content để không cạnh tranh thị giác.</p></div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <label class="block"><span class="text-sm font-medium text-slate-700">Page background</span><select wire:model.live="config.layout.surface.page_background" class="{{ $control }}"><option value="system">System — khuyến nghị</option><option value="white">White</option><option value="slate-50">Slate 50</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Content surface</span><select wire:model.live="config.layout.surface.content_surface" class="{{ $control }}"><option value="transparent">Transparent — khuyến nghị</option><option value="system">System</option><option value="white">White</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Footer background</span><select wire:model.live="config.footer.presentation.background" class="{{ $control }}"><option value="system">System — khuyến nghị</option><option value="transparent">Transparent</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Footer divider</span><select wire:model.live="config.footer.presentation.divider" class="{{ $control }}"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                        <label class="block"><span class="text-sm font-medium text-slate-700">Footer alignment</span><select wire:model.live="config.footer.presentation.alignment" class="{{ $control }}"><option value="split">Split — khuyến nghị</option><option value="center">Center</option></select></label>
                        <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3 lg:self-end"><span><span class="block text-sm font-medium text-slate-800">Compact Footer</span><span class="mt-0.5 block text-xs text-slate-500">Giữ Footer thấp và tinh gọn.</span></span><input type="checkbox" wire:model.live="config.footer.presentation.compact" class="{{ $toggle }}"></label>
                    </div>
                    <input type="hidden" wire:model="config.layout.surface.border"><input type="hidden" wire:model="config.layout.surface.radius"><input type="hidden" wire:model="config.theme.dark_mode"><input type="hidden" wire:model="config.theme.accent">
                </section>

                <section class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <label class="block min-w-0 flex-1"><span class="text-sm font-semibold text-slate-800">Lưu thành Theme mới</span><input type="text" wire:model="newThemeName" maxlength="80" placeholder="Ví dụ: INAFO Professional" class="{{ $control }}"><span class="mt-1.5 block text-xs text-slate-500">Theme mới lưu snapshot các tham số presentation hiện tại; không lưu nội dung Brand/Menu/User.</span></label>
                        <button type="button" wire:click="saveAsTheme" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Lưu thành Theme mới</button>
                    </div>
                    @error('newThemeName')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </section>
            </div>

            <aside class="xl:sticky xl:top-24" aria-label="Theme preview">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3"><div><h2 class="text-sm font-semibold text-slate-900">Admin preview</h2><p class="mt-0.5 text-xs text-slate-500">Sidebar + Header + Content + Footer.</p></div><span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200">Live</span></div>
                    <div class="mt-4 overflow-hidden rounded-xl border shadow-sm" style="border-color: {{ $border }}; background: {{ $surfaceBase }}; color: {{ $textPrimary }}">
                        <div class="flex h-56">
                            <div class="w-16 shrink-0 border-r p-2" style="border-color: {{ $border }}; background: {{ data_get($config,'sidebar.presentation.background') === 'dark' ? '#020617' : $surfaceRaised }}"><div class="mx-auto h-8 w-8 rounded-lg" style="background: {{ $accent }}"></div><div class="mt-4 space-y-2"><div class="h-7 rounded-lg" style="background: {{ $accent }}"></div><div class="h-7 rounded-lg bg-black/5"></div><div class="h-7 rounded-lg bg-black/5"></div></div></div>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <div class="flex h-11 items-center justify-between border-b px-3" style="border-color: {{ $border }}; background: {{ $headerBg === 'transparent' ? 'transparent' : $surfaceRaised }}"><div class="h-2.5 w-24 rounded" style="background: {{ $textMuted }}"></div><div class="flex gap-1"><span class="h-6 w-6 rounded-md" style="background: color-mix(in srgb, {{ $accent }} 14%, transparent)"></span><span class="h-6 w-6 rounded-md" style="background: color-mix(in srgb, {{ $accent }} 14%, transparent)"></span></div></div>
                                <div class="min-h-0 flex-1 p-3"><div class="h-3 w-2/3 rounded" style="background: {{ $textPrimary }}; opacity:.75"></div><div class="mt-2 h-2 w-1/2 rounded" style="background: {{ $textMuted }}; opacity:.5"></div><div class="mt-4 grid grid-cols-2 gap-2"><div class="h-14 rounded-lg border" style="border-color: {{ $border }}; background: {{ $surfaceRaised }}"></div><div class="h-14 rounded-lg border" style="border-color: {{ $border }}; background: {{ $surfaceRaised }}"></div></div></div>
                                <div class="flex h-9 items-center justify-between border-t px-3 text-[8px]" style="border-color: {{ $border }}; background: {{ $footerBg === 'transparent' ? 'transparent' : $surfaceRaised }}; color: {{ $textMuted }}"><span>© Admin</span><span>23/08/2026 · 14:30</span></div>
                            </div>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs"><div><dt class="text-slate-400">Theme</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ $profiles[$selectedTheme]['label'] ?? $selectedTheme }}</dd></div><div><dt class="text-slate-400">Sidebar</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ data_get($config,'theme.default') }}</dd></div><div><dt class="text-slate-400">Accent</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ data_get($config,'design.colors.accent') }}</dd></div><div><dt class="text-slate-400">Header</dt><dd class="mt-0.5 font-semibold text-slate-700">{{ data_get($config,'header.presentation.background') }}</dd></div></dl>
                </div>
            </aside>
        </div>

        @if($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Có tham số Theme chưa hợp lệ. Vui lòng kiểm tra các trường vừa chỉnh.</div>@endif
        <div class="sticky bottom-4 z-20 flex justify-end"><button type="submit" wire:loading.attr="disabled" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/15 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-60"><span wire:loading.remove wire:target="saveTheme">Lưu & áp dụng Theme</span><span wire:loading wire:target="saveTheme">Đang áp dụng...</span></button></div>
    </form>
</div>
