@php
    $select = 'mt-1.5 block h-10 w-full rounded-lg border-slate-300 bg-white px-3 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<section
    id="sidebar-menu"
    x-show="section === 'menu'"
    x-cloak
    x-data="{ designerTarget: 'item', previewState: 'normal' }"
    class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white shadow-sm"
>
    <datalist id="admin-menu-color-presets">
        @foreach($colorOptions as $name => $hex)
            <option value="{{ $name }}">{{ $hex }}</option>
        @endforeach
    </datalist>

    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-semibold text-slate-950">Sidebar Menu Designer</h2>
                    <span class="rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-indigo-700 ring-1 ring-indigo-200">Visual states</span>
                </div>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">Thiết kế Menu cha và SubMenu theo cùng mô hình Normal → Hover → Active. Mỗi màu có thể dùng preset hoặc mã HEX tùy chỉnh.</p>
            </div>
            <button type="button" @click="advancedMenu = !advancedMenu" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <span x-text="advancedMenu ? 'Ẩn thiết lập nâng cao' : 'Thiết lập nâng cao'"></span>
            </button>
        </div>

        <div class="mt-5 inline-flex rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Loại menu">
            <button type="button" @click="designerTarget='item'" class="rounded-lg px-4 py-2 text-sm font-semibold transition" :class="designerTarget==='item' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'">Menu Item / Menu cha</button>
            <button type="button" @click="designerTarget='submenu'" class="rounded-lg px-4 py-2 text-sm font-semibold transition" :class="designerTarget==='submenu' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'">SubMenu Item</button>
        </div>
    </div>

    <div class="grid gap-5 p-5 sm:p-6 xl:grid-cols-[minmax(0,1fr)_19rem]">
        <div class="min-w-0 space-y-5">
            <div x-show="designerTarget==='item'" x-cloak class="space-y-5">
                <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                    <div class="mb-4"><h3 class="text-sm font-semibold text-slate-900">Typography & Icon</h3><p class="mt-1 text-xs text-slate-500">Các thông số nền tảng dùng cho Menu thường và Menu cha.</p></div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="text-xs font-semibold text-slate-700">Font Family<select wire:model.live="config.design.sidebar_menu.item.font_family" class="{{ $select }}">@foreach($menuFontFamilyOptions as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Font Size<select wire:model.live="config.design.sidebar_menu.item.font_size" class="{{ $select }}">@foreach($menuFontSizeOptions as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Font Weight<select wire:model.live="config.design.sidebar_menu.item.font_weight" class="{{ $select }}">@foreach(['normal'=>'Regular 400','medium'=>'Medium 500','semibold'=>'Semibold 600','bold'=>'Bold 700'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Icon Size<select wire:model.live="config.design.sidebar_menu.item.icon_size" class="{{ $select }}">@foreach(['16','18','20','22','24'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                    </div>
                </section>

                <section>
                    <div class="mb-3"><h3 class="text-sm font-semibold text-slate-900">Visual States</h3><p class="mt-1 text-xs text-slate-500">Hover của Menu cha giờ được cấu hình độc lập; Active luôn ưu tiên khi Menu đang mở/đang chứa mục active.</p></div>
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="mb-4"><span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Normal</span><p class="mt-1 text-xs text-slate-400">Trạng thái mặc định.</p></div>
                            <div class="space-y-4">
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Text Color','model'=>'config.design.sidebar_menu.item.title_color','fallback'=>'slate-900','fallbackHex'=>'#0f172a'])
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Icon Color','model'=>'config.design.sidebar_menu.item.icon_color','fallback'=>'slate-400','fallbackHex'=>'#94a3b8'])
                                <label class="block text-xs font-semibold text-slate-700">Background<select wire:model.live="config.design.sidebar_menu.item.background_mode" class="{{ $select }}"><option value="transparent">Trong suốt</option><option value="color">Dùng màu nền</option></select></label>
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Background Color','model'=>'config.design.sidebar_menu.item.background_color','fallback'=>'slate-50','fallbackHex'=>'#f8fafc'])
                                <label class="block text-xs font-semibold text-slate-700">Icon Background<select wire:model.live="config.design.sidebar_menu.item.icon_background_mode" class="{{ $select }}"><option value="transparent">Trong suốt</option><option value="color">Dùng màu nền</option></select></label>
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Icon Background Color','model'=>'config.design.sidebar_menu.item.icon_background_color','fallback'=>'slate-100','fallbackHex'=>'#f1f5f9'])
                            </div>
                        </div>

                        <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-4 shadow-sm">
                            <div class="mb-4"><span class="text-[11px] font-bold uppercase tracking-wider text-sky-700">Hover</span><p class="mt-1 text-xs text-sky-700/70">Áp dụng cho Menu thường và Menu cha.</p></div>
                            <div class="space-y-4">
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Text Color','model'=>'config.design.sidebar_menu.item.hover_title_color','fallback'=>'slate-900','fallbackHex'=>'#0f172a'])
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Icon Color','model'=>'config.design.sidebar_menu.item.hover_icon_color','fallback'=>'indigo-600','fallbackHex'=>'#4f46e5'])
                                <label class="block text-xs font-semibold text-slate-700">Background<select wire:model.live="config.design.sidebar_menu.item.hover_background_mode" class="{{ $select }}"><option value="transparent">Trong suốt</option><option value="color">Dùng màu nền</option></select></label>
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Background Color','model'=>'config.design.sidebar_menu.item.hover_background_color','fallback'=>'slate-100','fallbackHex'=>'#f1f5f9'])
                            </div>
                        </div>

                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-4 shadow-sm">
                            <div class="mb-4"><span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Active</span><p class="mt-1 text-xs text-indigo-700/70">Menu hiện tại hoặc Menu cha đang mở.</p></div>
                            <div class="space-y-4">
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Text Color','model'=>'config.design.sidebar_menu.active.title_color','fallback'=>'white','fallbackHex'=>'#ffffff'])
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Icon Color','model'=>'config.design.sidebar_menu.active.icon_color','fallback'=>'white','fallbackHex'=>'#ffffff'])
                                <label class="block text-xs font-semibold text-slate-700">Background<select wire:model.live="config.design.sidebar_menu.active.menu_background_mode" class="{{ $select }}"><option value="transparent">Trong suốt</option><option value="color">Dùng màu nền</option></select></label>
                                @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Background Color','model'=>'config.design.sidebar_menu.active.menu_background_color','fallback'=>'indigo-600','fallbackHex'=>'#4f46e5'])
                                <label class="block text-xs font-semibold text-slate-700">Font Weight<select wire:model.live="config.design.sidebar_menu.active.font_weight" class="{{ $select }}">@foreach(['medium'=>'Medium 500','semibold'=>'Semibold 600','bold'=>'Bold 700'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                            </div>
                        </div>
                    </div>
                </section>

                <section x-show="advancedMenu" x-collapse class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Layout & Active Border</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="text-xs font-semibold text-slate-700">Min Height<select wire:model.live="config.design.sidebar_menu.item.item_height" class="{{ $select }}">@foreach(['40','44','48','52'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Padding X<select wire:model.live="config.design.sidebar_menu.item.padding_x" class="{{ $select }}">@foreach(['8','10','12','14','16'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Padding Y<select wire:model.live="config.design.sidebar_menu.item.padding_y" class="{{ $select }}">@foreach(['4','6','8','10','12'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Icon ↔ Title Gap<select wire:model.live="config.design.sidebar_menu.item.content_gap" class="{{ $select }}">@foreach(['8','10','12','14','16'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Item Gap<select wire:model.live="config.design.sidebar_menu.item.item_gap" class="{{ $select }}">@foreach(['0','2','4','6','8'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Active Border Color','model'=>'config.design.sidebar_menu.active.menu_border_color','fallback'=>'indigo-600','fallbackHex'=>'#4f46e5'])
                        <label class="text-xs font-semibold text-slate-700">Border Width<select wire:model.live="config.design.sidebar_menu.active.menu_border_width" class="{{ $select }}">@foreach(['0'=>'None','1'=>'1 px','2'=>'2 px','3'=>'3 px'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Border Style<select wire:model.live="config.design.sidebar_menu.active.menu_border_style" class="{{ $select }}">@foreach(['solid'=>'Solid','dashed'=>'Dashed','dotted'=>'Dotted','double'=>'Double'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                    </div>
                </section>
            </div>

            <div x-show="designerTarget==='submenu'" x-cloak class="space-y-5">
                <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                    <div class="mb-4"><h3 class="text-sm font-semibold text-slate-900">Typography</h3><p class="mt-1 text-xs text-slate-500">Kiểu chữ riêng cho các mục cấp hai.</p></div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <label class="text-xs font-semibold text-slate-700">Font Family<select wire:model.live="config.design.sidebar_menu.submenu.font_family" class="{{ $select }}">@foreach($menuFontFamilyOptions as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Font Size<select wire:model.live="config.design.sidebar_menu.submenu.font_size" class="{{ $select }}">@foreach($menuFontSizeOptions as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Font Weight<select wire:model.live="config.design.sidebar_menu.submenu.font_weight" class="{{ $select }}">@foreach(['normal'=>'Regular 400','medium'=>'Medium 500','semibold'=>'Semibold 600'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                    </div>
                </section>

                <section>
                    <div class="mb-3"><h3 class="text-sm font-semibold text-slate-900">Visual States</h3><p class="mt-1 text-xs text-slate-500">Ba trạng thái được bố trí giống Menu Item để dễ phối màu.</p></div>
                    <div class="grid gap-4 lg:grid-cols-3">
                        @foreach([
                            'normal'=>['label'=>'Normal','tone'=>'slate','mode'=>'background_mode','background'=>'background_color','text'=>'title_color'],
                            'hover'=>['label'=>'Hover','tone'=>'sky','mode'=>'hover_background_mode','background'=>'hover_background_color','text'=>'hover_title_color'],
                            'active'=>['label'=>'Active','tone'=>'indigo','mode'=>'active_background_mode','background'=>'active_background_color','text'=>'active_title_color'],
                        ] as $stateKey=>$state)
                            <div class="rounded-xl border p-4 shadow-sm {{ $stateKey==='hover' ? 'border-sky-200 bg-sky-50/40' : ($stateKey==='active' ? 'border-indigo-200 bg-indigo-50/40' : 'border-slate-200 bg-white') }}">
                                <div class="mb-4"><span class="text-[11px] font-bold uppercase tracking-wider {{ $stateKey==='hover' ? 'text-sky-700' : ($stateKey==='active' ? 'text-indigo-700' : 'text-slate-500') }}">{{ $state['label'] }}</span></div>
                                <div class="space-y-4">
                                    @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Text Color','model'=>'config.design.sidebar_menu.submenu.'.$state['text'],'fallback'=>$stateKey==='normal'?'slate-500':($stateKey==='hover'?'slate-700':'indigo-600'),'fallbackHex'=>$stateKey==='normal'?'#64748b':($stateKey==='hover'?'#334155':'#4f46e5')])
                                    <label class="block text-xs font-semibold text-slate-700">Background<select wire:model.live="config.design.sidebar_menu.submenu.{{ $state['mode'] }}" class="{{ $select }}"><option value="transparent">Trong suốt</option><option value="color">Dùng màu nền</option></select></label>
                                    @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Background Color','model'=>'config.design.sidebar_menu.submenu.'.$state['background'],'fallback'=>$stateKey==='normal'?'slate-50':($stateKey==='hover'?'slate-100':'indigo-100'),'fallbackHex'=>$stateKey==='normal'?'#f8fafc':($stateKey==='hover'?'#f1f5f9':'#e0e7ff')])
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <input type="hidden" wire:model="config.design.sidebar_menu.submenu.icon_color">
                </section>

                <section x-show="advancedMenu" x-collapse class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Layout & Active Border</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="text-xs font-semibold text-slate-700">Indent<select wire:model.live="config.design.sidebar_menu.submenu.indent" class="{{ $select }}">@foreach(['20','24','28','32','36'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Min Height<select wire:model.live="config.design.sidebar_menu.submenu.item_height" class="{{ $select }}">@foreach(['32','36','40','44'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Padding X<select wire:model.live="config.design.sidebar_menu.submenu.padding_x" class="{{ $select }}">@foreach(['8','10','12','14','16'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Padding Y<select wire:model.live="config.design.sidebar_menu.submenu.padding_y" class="{{ $select }}">@foreach(['2','4','6','8','10'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Border Offset<select wire:model.live="config.design.sidebar_menu.submenu.offset" class="{{ $select }}">@foreach(['8','10','12','14','16'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">SubMenu Gap<select wire:model.live="config.design.sidebar_menu.submenu.item_gap" class="{{ $select }}">@foreach(['0','2','4','6'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Group Gap<select wire:model.live="config.design.sidebar_menu.group.gap" class="{{ $select }}">@foreach(['2','4','6','8','12'] as $v)<option value="{{ $v }}">{{ $v }} px</option>@endforeach</select></label>
                        @include('Admin::livewire.settings.partials.menu-color-control', ['label'=>'Active Border Color','model'=>'config.design.sidebar_menu.active.submenu_border_color','fallback'=>'indigo-600','fallbackHex'=>'#4f46e5'])
                        <label class="text-xs font-semibold text-slate-700">Border Width<select wire:model.live="config.design.sidebar_menu.active.submenu_border_width" class="{{ $select }}">@foreach(['0'=>'None','1'=>'1 px','2'=>'2 px','3'=>'3 px'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                        <label class="text-xs font-semibold text-slate-700">Border Style<select wire:model.live="config.design.sidebar_menu.active.submenu_border_style" class="{{ $select }}">@foreach(['solid'=>'Solid','dashed'=>'Dashed','dotted'=>'Dotted','double'=>'Double'] as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                    </div>
                </section>
            </div>
        </div>

        <aside class="xl:sticky xl:top-20">
            <div class="rounded-xl border border-slate-200 bg-slate-950 p-4 text-white shadow-sm">
                <div class="flex items-center justify-between gap-3"><div><h3 class="text-sm font-semibold">Live Menu Preview</h3><p class="mt-1 text-[11px] text-slate-400">Xem đồng thời Normal / Hover / Active</p></div><span class="rounded-full bg-emerald-400/10 px-2 py-1 text-[10px] font-semibold text-emerald-300">LIVE</span></div>
                <div class="mt-4 space-y-2 rounded-xl bg-white/5 p-3">
                    <div class="rounded-lg px-3 py-2 text-xs" style="color:var(--admin-sidebar-menu-title-color);background:var(--admin-sidebar-menu-background)"><span class="font-semibold">Normal</span><span class="ml-2 opacity-70">Menu Item</span></div>
                    <div class="rounded-lg px-3 py-2 text-xs" style="color:var(--admin-sidebar-menu-hover-title-color);background:var(--admin-sidebar-menu-hover-background)"><span class="font-semibold">Hover</span><span class="ml-2 opacity-70">Menu Item</span></div>
                    <div class="rounded-lg px-3 py-2 text-xs" style="color:var(--admin-sidebar-active-title-color);background:var(--admin-sidebar-menu-active-background)"><span class="font-semibold">Active</span><span class="ml-2 opacity-70">Menu Item</span></div>
                    <div class="my-3 border-t border-white/10"></div>
                    <div class="ml-3 rounded-lg px-3 py-2 text-xs" style="color:var(--admin-sidebar-submenu-title-color);background:var(--admin-sidebar-submenu-background)">Normal · SubMenu</div>
                    <div class="ml-3 rounded-lg px-3 py-2 text-xs" style="color:var(--admin-sidebar-submenu-hover-title-color);background:var(--admin-sidebar-submenu-hover-background)">Hover · SubMenu</div>
                    <div class="ml-3 rounded-lg px-3 py-2 text-xs" style="color:var(--admin-sidebar-submenu-active-title-color);background:var(--admin-sidebar-submenu-active-background)">Active · SubMenu</div>
                </div>
                <p class="mt-3 text-[10px] leading-4 text-slate-400">Preset và HEX custom đều được sanitize trước khi phát CSS variable.</p>
            </div>
        </aside>
    </div>
</section>
