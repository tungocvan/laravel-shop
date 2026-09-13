@php
    $control = 'mt-2 block h-10 w-full rounded-lg border-slate-300 bg-white px-3 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $toggle = 'h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
    $colorLabels = ['surface_base'=>'Nền workspace','surface_raised'=>'Surface nổi','text_primary'=>'Text chính','text_secondary'=>'Text phụ','text_muted'=>'Text muted','border_subtle'=>'Border','accent'=>'Accent','focus_ring'=>'Focus ring','success'=>'Success','warning'=>'Warning','danger'=>'Danger','info'=>'Info'];
    $spacingLabels = ['1'=>'4 px','2'=>'8 px','3'=>'12 px','4'=>'16 px','6'=>'24 px','8'=>'32 px'];
    $c = fn($key,$fallback='white') => $colorOptions[data_get($config,'design.colors.'.$key,$fallback)] ?? '#fff';
    $headerThemeColor=$c('header_background'); $footerThemeColor=$c('footer_background'); $contentThemeColor=$c('content_background');
    $sidebarHeaderColor=$c('sidebar_header_background'); $sidebarNavigationColor=$c('sidebar_navigation_background','slate-50'); $sidebarFooterColor=$c('sidebar_footer_background');
    $headerMode=data_get($config,'header.presentation.background','system'); $footerMode=data_get($config,'footer.presentation.background','system'); $contentMode=data_get($config,'layout.surface.content_surface','transparent'); $sidebarMode=data_get($config,'sidebar.presentation.background','theme');
    $sidebarCustomBackground=data_get($config,'sidebar.presentation.custom_background','#0f172a');
    $sidebarPreviewNavigation = match($sidebarMode) { 'light' => '#ffffff', 'dark' => '#020617', 'custom' => $sidebarCustomBackground, default => $sidebarNavigationColor };
    $sidebarPreviewHeader = match($sidebarMode) { 'light' => '#f8fafc', 'dark' => '#020617', 'custom' => $sidebarCustomBackground, default => $sidebarHeaderColor };
    $sidebarPreviewFooter = match($sidebarMode) { 'light' => '#f8fafc', 'dark' => '#020617', 'custom' => $sidebarCustomBackground, default => $sidebarFooterColor };
@endphp

<div
    class="mx-auto max-w-[94rem] space-y-4"
    x-data="{
        section: 'overview',
        advancedMenu: false,
        saveAsOpen: false,
        init() {
            const sync = () => {
                const hash = window.location.hash.replace('#', '');
                this.section = hash === 'sidebar-menu' ? 'menu' : (['overview','colors','typography','sidebar','menu','header','content'].includes(hash) ? hash : 'overview');
            };
            sync();
            window.addEventListener('hashchange', sync);
        },
        openSection(name, hash = null) {
            this.section = name;
            history.replaceState(null, '', hash ? '#' + hash : '#' + name);
        }
    }"
    x-init="init()"
>
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-semibold text-slate-950">Theme Editor</h2><span class="rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-indigo-700 ring-1 ring-indigo-200">Whole Admin</span></div>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">Chọn Theme, chỉnh đúng khu vực cần thiết và xem Preview trực tiếp. Sidebar Menu có designer riêng để phối Normal, Hover và Active dễ hơn.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="saveAsOpen = !saveAsOpen" class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Lưu thành Theme mới</button>
                <button type="button" wire:click="restoreDefaultTheme" wire:confirm="Khôi phục Theme mặc định Professional Indigo? Toàn bộ typography, spacing và trạng thái Sidebar Menu cũng sẽ trở về mặc định tối ưu." class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Khôi phục Theme mặc định</button>
            </div>
        </div>

        <div class="p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-4">
                @foreach($profiles as $key=>$profile)
                    <div class="rounded-xl border p-3 {{ $selectedTheme===$key?'border-indigo-500 bg-indigo-50/60 ring-1 ring-indigo-500':'border-slate-200 bg-white' }}">
                        <button type="button" wire:click="selectTheme('{{ $key }}')" class="block w-full text-left">
                            <span class="flex items-center justify-between gap-3"><span class="truncate text-sm font-semibold text-slate-900">{{ $profile['label'] }}</span><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $profile['built_in']?'bg-slate-100 text-slate-500':'bg-emerald-50 text-emerald-700' }}">{{ $profile['built_in']?'System':'Custom' }}</span></span>
                            <span class="mt-1 block truncate text-xs text-slate-500">{{ $profile['description'] }}</span>
                        </button>
                        <div class="mt-2 flex gap-2"><button type="button" wire:click="duplicateTheme('{{ $key }}')" class="text-xs font-semibold text-slate-600 hover:text-indigo-700">Nhân bản</button>@unless($profile['built_in'])<button type="button" wire:click="deleteTheme('{{ $key }}')" wire:confirm="Xóa Theme {{ $profile['label'] }}?" class="text-xs font-semibold text-rose-600">Xóa</button>@endunless</div>
                    </div>
                @endforeach
            </div>

            <div x-show="saveAsOpen" x-collapse class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/40 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end"><label class="flex-1"><span class="text-sm font-semibold text-slate-800">Tên Theme mới</span><input type="text" wire:model="newThemeName" maxlength="80" placeholder="Ví dụ: INAFO Professional" class="{{ $control }}"></label><button type="button" wire:click="saveAsTheme" class="h-10 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white">Lưu thành Theme mới</button></div>
            </div>
        </div>
    </section>

    <nav class="sticky top-0 z-20 overflow-x-auto rounded-xl border border-slate-200 bg-white/95 p-1.5 shadow-sm backdrop-blur" aria-label="Theme editor sections">
        <div class="flex min-w-max gap-1">
            @foreach(['overview'=>'Tổng quan','colors'=>'Màu sắc','typography'=>'Typography','sidebar'=>'Sidebar','menu'=>'Menu','header'=>'Header','content'=>'Content & Footer'] as $sectionKey=>$sectionLabel)
                <button type="button" @click="openSection('{{ $sectionKey }}', '{{ $sectionKey === 'menu' ? 'sidebar-menu' : $sectionKey }}')" class="rounded-lg px-3 py-2 text-sm font-semibold transition" :class="section === '{{ $sectionKey }}' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'">{{ $sectionLabel }}</button>
            @endforeach
        </div>
    </nav>

    <form wire:submit="saveTheme" class="space-y-4">
        <section x-show="section === 'overview'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Tổng quan Theme</h2><p class="mt-1 text-sm text-slate-500">Chọn một khu vực để chỉnh. Menu có designer riêng với preview trạng thái trực quan.</p></div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <button type="button" @click="openSection('colors')" class="rounded-xl border border-slate-200 p-4 text-left hover:border-indigo-300 hover:bg-indigo-50/30"><span class="text-sm font-semibold text-slate-900">Semantic colors</span><span class="mt-1 block text-xs text-slate-500">Accent, text, surface, trạng thái</span></button>
                <button type="button" @click="openSection('typography')" class="rounded-xl border border-slate-200 p-4 text-left hover:border-indigo-300 hover:bg-indigo-50/30"><span class="text-sm font-semibold text-slate-900">Typography & shape</span><span class="mt-1 block text-xs text-slate-500">Font, size, radius, spacing</span></button>
                <button type="button" @click="openSection('sidebar')" class="rounded-xl border border-slate-200 p-4 text-left hover:border-indigo-300 hover:bg-indigo-50/30"><span class="text-sm font-semibold text-slate-900">Sidebar Theme</span><span class="mt-1 block text-xs text-slate-500">Background mode và 3 vùng Sidebar</span></button>
                <button type="button" @click="openSection('menu', 'sidebar-menu')" class="rounded-xl border border-indigo-200 bg-indigo-50/30 p-4 text-left hover:bg-indigo-50"><span class="text-sm font-semibold text-indigo-900">Sidebar Menu Designer</span><span class="mt-1 block text-xs text-indigo-700/70">Normal, Hover, Active, custom HEX</span></button>
                <button type="button" @click="openSection('header')" class="rounded-xl border border-slate-200 p-4 text-left hover:border-indigo-300 hover:bg-indigo-50/30"><span class="text-sm font-semibold text-slate-900">Header presentation</span><span class="mt-1 block text-xs text-slate-500">Background, mode, shadow, divider</span></button>
                <button type="button" @click="openSection('content')" class="rounded-xl border border-slate-200 p-4 text-left hover:border-indigo-300 hover:bg-indigo-50/30"><span class="text-sm font-semibold text-slate-900">Content & Footer</span><span class="mt-1 block text-xs text-slate-500">Workspace surface và Footer</span></button>
            </div>
        </section>

        <section x-show="section === 'colors'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Semantic colors</h2><p class="mt-1 text-sm text-slate-500">Màu dùng chung cho text, border, accent và trạng thái.</p></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach($colorLabels as $key=>$label)<label><span class="text-sm font-medium text-slate-700">{{ $label }}</span><select wire:model.live="config.design.colors.{{ $key }}" class="{{ $control }}">@foreach($colorOptions as $name=>$hex)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>@endforeach</div>
        </section>

        <section x-show="section === 'typography'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Typography & shape</h2><p class="mt-1 text-sm text-slate-500">Thiết lập nền tảng dùng chung cho toàn bộ Admin.</p></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label>Font Family<select wire:model.live="config.design.typography.font_family" class="{{ $control }}">@foreach($fontFamilyOptions as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                <label>Body size<select wire:model.live="config.design.typography.body_size" class="{{ $control }}"><option value="xs">12 px</option><option value="sm">14 px</option><option value="base">16 px</option><option value="lg">18 px</option></select></label>
                <label>Page title<select wire:model.live="config.design.typography.page_title_size" class="{{ $control }}"><option value="lg">18 px</option><option value="2xl">24 px</option></select></label>
                <label>Heading weight<select wire:model.live="config.design.typography.heading_weight" class="{{ $control }}"><option value="medium">Medium</option><option value="semibold">Semibold</option><option value="bold">Bold</option></select></label>
                <label>Panel radius<select wire:model.live="config.design.radius.panel" class="{{ $control }}"><option value="sm">4 px</option><option value="md">6 px</option><option value="lg">8 px</option><option value="xl">12 px</option></select></label>
                <label>Control radius<select wire:model.live="config.design.radius.control" class="{{ $control }}"><option value="sm">4 px</option><option value="md">6 px</option><option value="lg">8 px</option><option value="xl">12 px</option></select></label>
                <label>Overlay radius<select wire:model.live="config.design.radius.overlay" class="{{ $control }}"><option value="md">6 px</option><option value="lg">8 px</option><option value="xl">12 px</option></select></label>
                <label>Control spacing<select wire:model.live="config.design.spacing.control" class="{{ $control }}">@foreach($spacingLabels as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                <label>Section spacing<select wire:model.live="config.design.spacing.section" class="{{ $control }}">@foreach($spacingLabels as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
            </div>
            <input type="hidden" wire:model="config.design.spacing.tight"><input type="hidden" wire:model="config.design.spacing.content">
        </section>

        <section x-show="section === 'sidebar'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Sidebar Theme</h2><p class="mt-1 text-sm leading-6 text-slate-500">Surface mode đồng bộ với Sidebar Visual System. Màu custom chi tiết được quản tại Cấu hình Sidebar.</p></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label><span class="text-sm font-medium text-slate-700">Menu palette</span><select wire:model.live="config.theme.default" class="{{ $control }}">@foreach($sidebarPalettes as $palette)<option value="{{ $palette }}">{{ $palette }}</option>@endforeach</select></label>
                <label><span class="text-sm font-medium text-slate-700">Surface mode</span><select wire:model.live="config.sidebar.presentation.background" class="{{ $control }}"><option value="theme">Theo Theme</option><option value="light">Light</option><option value="dark">Dark</option><option value="custom">Custom</option></select></label>
                <div></div>
                <label><span class="text-sm font-medium text-slate-700">Header background token</span><select wire:model.live="config.design.colors.sidebar_header_background" class="{{ $control }}">@foreach($colorOptions as $name=>$hex)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>
                <label><span class="text-sm font-medium text-slate-700">Navigation background token</span><select wire:model.live="config.design.colors.sidebar_navigation_background" class="{{ $control }}">@foreach($colorOptions as $name=>$hex)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>
                <label><span class="text-sm font-medium text-slate-700">Footer background token</span><select wire:model.live="config.design.colors.sidebar_footer_background" class="{{ $control }}">@foreach($colorOptions as $name=>$hex)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>
            </div>
            <div class="mt-5 grid grid-cols-3 overflow-hidden rounded-xl border border-slate-200 text-center text-xs font-semibold"><div class="p-4" style="background:{{ $sidebarPreviewHeader }}">Header</div><div class="p-4" style="background:{{ $sidebarPreviewNavigation }}">Navigation</div><div class="p-4" style="background:{{ $sidebarPreviewFooter }}">Footer</div></div>
        </section>

        @include('Admin::livewire.settings.partials.sidebar-menu-designer')

        <section x-show="section === 'header'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Header presentation</h2><p class="mt-1 text-sm text-slate-500">Điều chỉnh surface và mật độ Header mà không ảnh hưởng dữ liệu Header Actions/UserMenu.</p></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label>Background mode<select wire:model.live="config.header.presentation.background" class="{{ $control }}"><option value="system">Theme color</option><option value="white">White</option><option value="transparent">Transparent</option></select></label>
                <label>Theme color<select wire:model.live="config.design.colors.header_background" class="{{ $control }}">@foreach($colorOptions as $name=>$hex)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>
                <label>Mode<select wire:model.live="config.header.presentation.mode" class="{{ $control }}"><option value="balanced">Balanced</option><option value="compact">Compact</option><option value="action-heavy">Action heavy</option></select></label>
                <label>Shadow<select wire:model.live="config.header.presentation.shadow" class="{{ $control }}"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                <label>Divider<select wire:model.live="config.header.presentation.divider" class="{{ $control }}"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                <label>Padding ngang<select wire:model.live="config.header.presentation.padding_x" class="{{ $control }}">@foreach(['3'=>'12 px','4'=>'16 px','5'=>'20 px','6'=>'24 px','8'=>'32 px'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">Backdrop blur<input type="checkbox" wire:model.live="config.header.presentation.backdrop_blur" class="{{ $toggle }}"></label>
            </div>
            <input type="hidden" wire:model="config.header.presentation.action_gap">
        </section>

        <section x-show="section === 'content'" x-cloak class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Content & Footer</h2><p class="mt-1 text-sm text-slate-500">Quản lý workspace surface và Footer trong cùng một khu vực liên quan.</p></div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label>Page background<select wire:model.live="config.layout.surface.page_background" class="{{ $control }}"><option value="system">Theme color</option><option value="white">White</option><option value="slate-50">Slate 50</option></select></label>
                <label>Page theme color<select wire:model.live="config.design.colors.page_background" class="{{ $control }}">@foreach($surfaceColorOptions as $name=>$hex)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>
                <label>Content surface<select wire:model.live="config.layout.surface.content_surface" class="{{ $control }}"><option value="transparent">Transparent</option><option value="system">Theme color</option><option value="white">White</option></select></label>
                <label>Content theme color<select wire:model.live="config.design.colors.content_background" class="{{ $control }}">@foreach($surfaceColorOptions as $name=>$hex)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>
                <label>Footer background<select wire:model.live="config.footer.presentation.background" class="{{ $control }}"><option value="system">Theme color</option><option value="transparent">Transparent</option></select></label>
                <label>Footer theme color<select wire:model.live="config.design.colors.footer_background" class="{{ $control }}">@foreach($colorOptions as $name=>$hex)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>
                <label>Footer divider<select wire:model.live="config.footer.presentation.divider" class="{{ $control }}"><option value="subtle">Subtle</option><option value="none">None</option></select></label>
                <label>Footer alignment<select wire:model.live="config.footer.presentation.alignment" class="{{ $control }}"><option value="split">Split</option><option value="center">Center</option></select></label>
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">Compact Footer<input type="checkbox" wire:model.live="config.footer.presentation.compact" class="{{ $toggle }}"></label>
            </div>
            <input type="hidden" wire:model="config.layout.surface.border"><input type="hidden" wire:model="config.layout.surface.radius"><input type="hidden" wire:model="config.theme.dark_mode"><input type="hidden" wire:model="config.theme.accent">
        </section>

        @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">Có tham số Theme chưa hợp lệ. Kiểm tra các trường màu custom phải đúng dạng #RRGGBB.</div>@endif

        <div class="sticky bottom-4 z-30 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur">
            <span class="hidden text-xs text-slate-500 sm:block">Các thay đổi chỉ áp dụng sau khi lưu. Preview Menu cập nhật trực tiếp khi chỉnh.</span>
            <button type="submit" class="h-10 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Lưu & áp dụng Theme</button>
        </div>
    </form>
</div>
