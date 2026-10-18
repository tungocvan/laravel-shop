<div class="w-full px-3 py-5 sm:px-6"
    x-data="{ tab: @entangle('activeTab'), previewDevice: 'desktop', drag: null, showLayoutTools: false, showThemeLibrary: false, showAccountSettings: false, showTopbarSettings: true, showAppearanceSettings: false, pharmaVisibilityOverrides: {} }">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Thiết kế Header Website</h2>
        <p class="text-sm text-gray-500">Chọn giao diện, xem trước theo thiết bị và chỉ mở các thiết lập chuyên sâu khi cần.</p>
    </div>

    <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        <label for="header-editing-template" class="block text-sm font-semibold text-gray-800">Template đang quản trị</label>
        <select id="header-editing-template" wire:model.live="editingTemplate" class="mt-2 w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
            <option value="default">Default — Website bán hàng</option>
            <option value="pharma">Pharma — Website dược phẩm</option>
        </select>
        <p class="mt-2 text-xs text-gray-500">Đang xuất bản: {{ $publishedTemplate === 'pharma' ? 'Pharma' : 'Default' }}. Chuyển workspace chỉ thay đổi cấu hình đang chỉnh sửa, không xuất bản template khác.</p>
    </div>
    <div class="flex flex-wrap border-b border-gray-200 mb-6">
        <button @click="tab = 'general'" :class="tab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-2 px-6 border-b-2 font-medium text-sm transition-colors">Thông tin chung</button>
        <button @click="tab = 'menu'" :class="tab === 'menu' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-2 px-6 border-b-2 font-medium text-sm transition-colors">Cấu trúc Menu</button>
        <button @click="tab = 'builder'" :class="tab === 'builder' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="py-2 px-6 border-b-2 font-medium text-sm transition-colors">Bố cục Header</button>
    </div>

    <div x-show="tab === 'general'" x-cloak>
        @livewire('website.admin.header.general-settings', key('header-general-settings'))
        @if($editingTemplate === 'pharma')
            <section class="mb-5 rounded-xl border border-slate-200 bg-white p-4 sm:p-5" x-data="{ brandDevice: 'desktop' }">
                <h3 class="text-base font-bold text-slate-900">Thương hiệu & Slogan</h3>
                <p class="mt-1 text-xs text-slate-500">Logo và tên thương hiệu tiếp tục dùng dữ liệu tại Thông tin chung phía trên. Slogan có thể thay đổi riêng cho Pharma.</p>
                <label class="mt-4 block text-sm font-semibold text-slate-700">Nội dung slogan
                    <textarea wire:model="brandAppearance.slogan" maxlength="180" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                </label>
                <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="Thiết bị typography thương hiệu">
                    <button type="button" @click="brandDevice = 'desktop'" :aria-pressed="(brandDevice === 'desktop').toString()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" :class="brandDevice === 'desktop' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700'">Desktop</button>
                    <button type="button" @click="brandDevice = 'mobile'" :aria-pressed="(brandDevice === 'mobile').toString()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" :class="brandDevice === 'mobile' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700'">Mobile</button>
                </div>
                @foreach(['desktop' => 'Desktop', 'mobile' => 'Mobile'] as $device => $label)
                    <div x-show="brandDevice === '{{ $device }}'" x-cloak class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach(['name' => 'Tên thương hiệu', 'slogan' => 'Slogan'] as $part => $partLabel)
                            <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <h4 class="text-sm font-semibold text-slate-800">{{ $partLabel }} · {{ $label }}</h4>
                                <label class="block text-xs font-medium">Font family
                                    <select wire:model="brandAppearance.typography.{{ $device }}.{{ $part }}.font_family" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-2">
                                        @foreach(['inherit' => 'Kế thừa', 'Arial, sans-serif' => 'Arial', 'Roboto, sans-serif' => 'Roboto', 'Inter, sans-serif' => 'Inter', 'system-ui, sans-serif' => 'System UI', 'Georgia, serif' => 'Georgia'] as $fontValue => $fontLabel)
                                            <option value="{{ $fontValue }}">{{ $fontLabel }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block text-xs font-medium">Font size (px)
                                    <input type="number" min="9" max="32" wire:model="brandAppearance.typography.{{ $device }}.{{ $part }}.font_size" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-2">
                                </label>
                                <label class="block text-xs font-medium">Font weight
                                    <select wire:model="brandAppearance.typography.{{ $device }}.{{ $part }}.font_weight" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-2">
                                        @foreach(['400','500','600','700','800'] as $weight)
                                            <option value="{{ $weight }}">{{ $weight }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="flex items-center gap-3 text-xs font-medium">Màu chữ
                                    <input type="color" wire:model="brandAppearance.typography.{{ $device }}.{{ $part }}.color" class="h-10 w-16 cursor-pointer">
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                @error('brandAppearance')<p class="mt-3 text-sm text-red-700">{{ $message }}</p>@enderror
                <div class="mt-4 flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="resetBrandAppearance" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Khôi phục mặc định</button>
                    <button type="button" wire:click="saveBrandAppearance" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Lưu thương hiệu & Slogan</button>
                </div>
            </section>
        @endif
        @if($editingTemplate === 'pharma')
            <section class="rounded-xl border border-teal-200 bg-white p-4">
                <button type="button" @click="showTopbarSettings = !showTopbarSettings" :aria-expanded="showTopbarSettings.toString()" aria-controls="header-topbar-settings" class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-gray-800">
                    <span>Thanh thông tin Pharma <span class="ml-2 text-xs font-normal text-gray-500">Nội dung, liên hệ và hiển thị theo thiết bị</span></span>
                    <span x-text="showTopbarSettings ? 'Thu gọn −' : 'Cấu hình +'"></span>
                </button>
                <div id="header-topbar-settings" x-show="showTopbarSettings" class="mt-4 space-y-4">
                <div>
                    <h4 class="text-base font-bold text-gray-900">Topbar Pharma · Nội dung & hiển thị</h4>
                    <p class="mt-1 text-xs text-gray-600">Hotline và email được quản lý tại Thông tin chung phía trên. Nội dung bên trái và tùy chọn hiển thị áp dụng cho Topbar Pharma. Bên phải sử dụng hai liên kết chung: Theo dõi đơn hàng và Trợ giúp.</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" wire:model="topbarSettings.enabled" class="rounded border-gray-300"> Hiển thị Topbar Pharma</label>
                <div class="grid gap-3 rounded-lg border border-teal-100 bg-teal-50/50 p-3 sm:grid-cols-2">
                    <label class="block text-sm font-medium">Thiết bị hiển thị Topbar
                        <select wire:change="setTopbarDeviceVisibility($event.target.value)" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="both" @selected(($topbarSettings['show_desktop'] ?? true) && ($topbarSettings['show_mobile'] ?? false))>Hiển thị cả 2</option>
                            <option value="desktop" @selected(($topbarSettings['show_desktop'] ?? true) && !($topbarSettings['show_mobile'] ?? false))>Hiển thị Desktop</option>
                            <option value="mobile" @selected(!($topbarSettings['show_desktop'] ?? true) && ($topbarSettings['show_mobile'] ?? false))>Hiển thị Mobile</option>
                            <option value="hidden" @selected(!($topbarSettings['show_desktop'] ?? true) && !($topbarSettings['show_mobile'] ?? false))>Ẩn trên cả 2</option>
                        </select>
                    </label>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-medium">Nội dung bên trái
                        <input type="text" wire:model="topbarSettings.left_text" maxlength="180" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition hover:border-slate-400 focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                    </label>
                </div>
                <div class="flex flex-wrap items-center gap-5">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="topbarSettings.show_hotline" class="rounded border-gray-300"> Hiện hotline</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="topbarSettings.show_email" class="rounded border-gray-300"> Hiện email</label>
                    <button type="button" wire:click="saveTopbarSettings" class="ml-auto rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white">Lưu Topbar Pharma</button>
                </div>
                <p class="text-xs text-slate-500">Hotline, Email hỗ trợ, Link Trợ giúp và Link Theo dõi đơn hàng được lưu bằng nút “Lưu thông tin Header” ở phần Thông tin chung. Nút “Lưu Topbar Pharma” chỉ lưu nội dung bên trái và tùy chọn hiển thị.</p>
                </div>
            </section>
        @endif
    </div>

    <div x-show="tab === 'menu'" x-cloak>
        @if($editingTemplate === 'pharma')
            <section class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" x-data="{ menuDevice: 'primary' }" aria-label="Thiết lập giao diện Menu điều hướng">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Thiết lập giao diện Menu điều hướng</h3>
                        <p class="mt-1 text-xs text-slate-500">Chỉ thay đổi kiểu hiển thị, không thay đổi nội dung hoặc thứ tự menu. Chọn thiết bị để cấu hình độc lập.</p>
                    </div>
                    <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1" role="group" aria-label="Thiết bị menu">
                        <button type="button" @click="menuDevice = 'primary'" :aria-pressed="(menuDevice === 'primary').toString()" :class="menuDevice === 'primary' ? 'bg-blue-600 text-white' : 'text-slate-700'" class="rounded-md px-3 py-2 text-xs font-semibold">Desktop</button>
                        <button type="button" @click="menuDevice = 'mobile'" :aria-pressed="(menuDevice === 'mobile').toString()" :class="menuDevice === 'mobile' ? 'bg-blue-600 text-white' : 'text-slate-700'" class="rounded-md px-3 py-2 text-xs font-semibold">Mobile</button>
                    </div>
                </div>
                @foreach(['primary' => 'Desktop', 'mobile' => 'Mobile'] as $device => $deviceLabel)
                    <div x-show="menuDevice === '{{ $device }}'" x-cloak class="mt-4 space-y-4">
                        @php
                            $style = $presentation['menu_style'][$device] ?? [];
                        @endphp
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <p class="mb-3 text-xs font-semibold text-slate-500">Xem trước · {{ $deviceLabel }}</p>
                            <nav aria-label="Xem trước menu {{ $deviceLabel }}" class="flex flex-wrap items-center" style="gap: {{ (int) ($style['item_gap'] ?? 20) }}px; font-family: {{ $style['font_family'] ?? 'inherit' }}; font-size: {{ (int) ($style['font_size'] ?? 14) }}px; font-weight: {{ $style['font_weight'] ?? '600' }}; color: {{ $style['text_color'] ?? '#0f172a' }};">
                                @foreach(['Trang chủ', 'Sản phẩm', 'Giới thiệu', 'Liên hệ'] as $previewItem)
                                    <span class="px-2 py-1" style="border-radius: {{ (int) ($style['radius'] ?? 8) }}px">{{ $previewItem }}</span>
                                @endforeach
                            </nav>
                            <p class="mt-2 text-xs text-slate-400">Bản xem trước minh họa typography và khoảng cách. Hover và trạng thái đang chọn áp dụng trên website sau khi lưu.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <label class="block text-sm font-medium text-slate-700">Font family
                                <select wire:model.live="presentation.menu_style.{{ $device }}.font_family" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                    @foreach(['inherit' => 'Kế thừa Header', 'Arial, sans-serif' => 'Arial', 'Roboto, sans-serif' => 'Roboto', 'Inter, sans-serif' => 'Inter', 'system-ui, sans-serif' => 'System UI', 'Georgia, serif' => 'Georgia'] as $fontValue => $fontLabel)
                                        <option value="{{ $fontValue }}">{{ $fontLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block text-sm font-medium text-slate-700">Font size (px)
                                <input type="number" min="12" max="22" step="1" wire:model.live.debounce.300ms="presentation.menu_style.{{ $device }}.font_size" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            </label>
                            <label class="block text-sm font-medium text-slate-700">Font weight
                                <select wire:model.live="presentation.menu_style.{{ $device }}.font_weight" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                    @foreach(['400' => '400 · Regular', '500' => '500 · Medium', '600' => '600 · Semibold', '700' => '700 · Bold'] as $weightValue => $weightLabel)
                                        <option value="{{ $weightValue }}">{{ $weightLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block text-sm font-medium text-slate-700">Khoảng cách mục (px)
                                <input type="number" min="0" max="40" step="1" wire:model.live.debounce.300ms="presentation.menu_style.{{ $device }}.item_gap" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            </label>
                            <label class="block text-sm font-medium text-slate-700">Bo góc (px)
                                <input type="number" min="0" max="24" step="1" wire:model.live.debounce.300ms="presentation.menu_style.{{ $device }}.radius" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            </label>
                            <label class="block text-sm font-medium text-slate-700">Hiệu ứng hover
                                <select wire:model.live="presentation.menu_style.{{ $device }}.hover_effect" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                                    <option value="underline">Gạch chân</option><option value="color">Đổi màu chữ</option><option value="background">Đổi màu nền</option><option value="none">Không hiệu ứng</option>
                                </select>
                            </label>
                            @foreach(['text_color' => 'Màu chữ', 'hover_color' => 'Màu Hover', 'active_color' => 'Màu mục đang chọn'] as $colorKey => $colorLabel)
                                <label class="block text-sm font-medium text-slate-700">{{ $colorLabel }}
                                    <div class="mt-1 flex items-center gap-2 rounded-lg border border-slate-300 px-2 py-1">
                                        <input type="color" wire:model.live="presentation.menu_style.{{ $device }}.{{ $colorKey }}" class="h-9 w-12 cursor-pointer border-0 bg-transparent">
                                        <span class="text-xs text-slate-500">{{ $style[$colorKey] ?? '' }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @error('menuAppearance')<p class="mt-3 text-sm text-red-700">{{ $message }}</p>@enderror
                <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" wire:click="resetMenuAppearance" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Khôi phục mặc định</button>
                    <button type="button" wire:click="saveMenuAppearance" wire:loading.attr="disabled" wire:target="saveMenuAppearance" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Lưu thiết lập Menu</button>
                </div>
            </section>
        @endif
        @livewire('website.admin.header.menu-manager', ['editingTemplate' => $editingTemplate], key('header-menu-manager-'.$editingTemplate))
    </div>

    <div x-show="tab === 'builder'" x-cloak class="space-y-8 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Không gian thiết kế Header</h3>
                <p class="text-sm text-gray-500">Xem trước trước khi lưu. Các công cụ sắp xếp kỹ thuật được thu gọn trong mục Bố cục nâng cao.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="resetBuilder" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Khôi phục mặc định</button>
                @if($editingTemplate === 'pharma')
                    <button type="button" @click="$wire.savePharmaBuilder(pharmaVisibilityOverrides).then(() => { pharmaVisibilityOverrides = {}; })" wire:loading.attr="disabled" wire:target="savePharmaBuilder" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Lưu bố cục</button>
                @else
                    <button type="button" wire:click="saveBuilder" wire:loading.attr="disabled" wire:target="saveBuilder" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Lưu bố cục</button>
                @endif
            </div>
        </div>

        @error('builder')
            <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <section class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <button type="button" @click="showThemeLibrary = !showThemeLibrary" :aria-expanded="showThemeLibrary.toString()" aria-controls="header-theme-library" class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-gray-800">
                <span>Thư viện mẫu Header <span class="ml-2 text-xs font-normal text-gray-500">Chọn, lưu và quản lý các mẫu đã tạo</span></span>
                <span x-text="showThemeLibrary ? 'Thu gọn −' : 'Mở thư viện +'"></span>
            </button>
            <div id="header-theme-library" x-show="showThemeLibrary" x-cloak class="mt-4">
                @include('Website::livewire.admin.header.partials.theme-manager')
            </div>
        </section>
        @include('Website::livewire.admin.header.partials.builder-preview')
        <section class="rounded-xl border border-gray-200 bg-white p-4">
            <button type="button" @click="showLayoutTools = !showLayoutTools" :aria-expanded="showLayoutTools.toString()" aria-controls="header-layout-tools" class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-gray-800">
                <span>Bố cục nâng cao <span class="ml-2 text-xs font-normal text-gray-500">Sắp xếp, chọn thiết bị hiển thị và chuyển vị trí thành phần</span></span>
                <span x-text="showLayoutTools ? 'Thu gọn −' : 'Mở công cụ +'"></span>
            </button>
            <div id="header-layout-tools" x-show="showLayoutTools" x-cloak class="mt-4">

        @if($editingTemplate === 'pharma')
            <section class="mb-4 rounded-xl border border-blue-100 bg-blue-50/40 p-4" aria-label="Ẩn hiện thành phần thương hiệu">
                <h4 class="mb-2 text-sm font-bold text-slate-800">Logo / Tên thương hiệu / Slogan — Ẩn/hiện độc lập</h4>
                <p class="mb-3 text-xs text-slate-500">Tương thích layout brand hiện tại; thay đổi chỉ áp dụng sau khi lưu cấu hình thương hiệu ở tab Thông tin chung.</p>
                <div class="grid gap-3 md:grid-cols-3">
                    @foreach(['logo' => 'Logo', 'name' => 'Tên thương hiệu', 'slogan' => 'Slogan'] as $part => $partLabel)
                        <label class="block text-xs font-semibold text-slate-700">{{ $partLabel }}
                            <select wire:model="brandAppearance.visibility.{{ $part }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                <option value="both">Desktop + Mobile</option>
                                <option value="desktop">Chỉ Desktop</option>
                                <option value="mobile">Chỉ Mobile</option>
                                <option value="hidden">Ẩn hoàn toàn</option>
                            </select>
                        </label>
                    @endforeach
                </div>
                <button type="button" wire:click="saveBrandAppearance" class="mt-3 rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white">Lưu ẩn/hiện thương hiệu</button>
            </section>
            <div class="space-y-2" aria-label="Danh sách thành phần Header Pharma">
                <p class="text-xs text-slate-500">Mỗi chức năng chỉ có một thành phần. Chọn thiết bị hiển thị và vị trí; Menu di động chỉ hỗ trợ Mobile.</p>
                <p class="text-xs text-slate-500">Chọn trạng thái không tải lại giao diện. Nhấn <strong>Lưu bố cục</strong> để áp dụng và cập nhật bản xem trước.</p>
                @foreach($builderSlotNames as $slotKey => $slotLabel)
                    @foreach($builderSlots[$slotKey] ?? [] as $index => $item)
                        @php
                            $definition = $headerComponents[$item['type']] ?? [];
                            $enabled = $item['enabled'] ?? true;
                            $deviceVisibility = $item['config']['visibility'] ?? ($enabled ? (str_starts_with($slotKey, 'mobile.') ? 'mobile' : 'desktop') : 'hidden');
                            $isDrawer = ($item['type'] ?? '') === 'mobile-menu';
                            $isTopbar = ($item['type'] ?? '') === 'topbar';
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm {{ $enabled ? '' : 'opacity-65' }}" wire:key="pharma-component-{{ str_replace('.', '-', $slotKey) }}-{{ $index }}-{{ $item['type'] ?? 'unknown' }}">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <span class="text-slate-400" aria-hidden="true">⋮⋮</span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900">{{ $definition['label'] ?? ($item['type'] ?? 'Component') }}</div>
                                        <div class="text-xs text-slate-500">{{ $isDrawer ? 'Menu mở trên điện thoại' : ($isTopbar ? 'Thanh thông tin phía trên' : ($item['type'] === 'search' ? 'Tìm kiếm dùng chung cho Desktop và Mobile' : $slotLabel)) }}</div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:w-[440px]">
                                    <label class="block text-xs font-medium text-slate-600">Hiển thị
                                        <select x-bind:value="pharmaVisibilityOverrides[@js($item['type'])] ?? @js($deviceVisibility)"
                                                x-on:change="pharmaVisibilityOverrides[@js($item['type'])] = $event.target.value"
                                                aria-label="Hiển thị {{ $definition['label'] ?? ($item['type'] ?? 'thành phần') }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm">
                                            @if(!$isDrawer)
                                                <option value="both" @selected($deviceVisibility === 'both')>Desktop + Mobile</option>
                                                <option value="desktop" @selected($deviceVisibility === 'desktop')>Chỉ Desktop</option>
                                            @endif
                                            <option value="mobile" @selected($deviceVisibility === 'mobile')>Chỉ Mobile</option>
                                            <option value="hidden" @selected($deviceVisibility === 'hidden')>Ẩn hoàn toàn</option>
                                        </select>
                                    </label>
                                    <label class="block text-xs font-medium text-slate-600">Vị trí
                                        @if($isTopbar || $isDrawer)
                                            <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">{{ $isTopbar ? 'Trên cùng' : 'Menu di động' }}</div>
                                        @else
                                            <select wire:change="setPharmaComponentPosition('{{ $slotKey }}', {{ $index }}, $event.target.value)" aria-label="Vị trí {{ $definition['label'] ?? ($item['type'] ?? 'thành phần') }}" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm">
                                                <option value="desktop.main.left" @selected($slotKey === 'desktop.main.left')>Bên trái</option>
                                                <option value="desktop.main.center" @selected($slotKey === 'desktop.main.center')>Ở giữa</option>
                                                <option value="desktop.main.right" @selected($slotKey === 'desktop.main.right')>Bên phải</option>
                                            </select>
                                        @endif
                                    </label>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="moveUp('{{ $slotKey }}', {{ $index }})" @disabled($index === 0) aria-label="Đưa {{ $definition['label'] ?? 'component' }} lên" class="rounded-lg border border-slate-200 px-3 py-2 text-xs disabled:opacity-30">↑</button>
                                    <button type="button" wire:click="moveDown('{{ $slotKey }}', {{ $index }})" @disabled($index === count($builderSlots[$slotKey] ?? []) - 1) aria-label="Đưa {{ $definition['label'] ?? 'component' }} xuống" class="rounded-lg border border-slate-200 px-3 py-2 text-xs disabled:opacity-30">↓</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            @foreach($builderSlotNames as $slotKey => $slotLabel)
                <section class="rounded-xl border border-gray-200 bg-gray-50 p-4 transition"
                    :class="drag ? 'border-blue-200' : ''"
                    wire:key="builder-slot-{{ str_replace('.', '-', $slotKey) }}">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-gray-800">{{ $slotLabel }}</h4>
                            <span class="text-[11px] text-gray-400">Thả component vào vị trí mong muốn</span>
                        </div>
                        <span class="rounded-full bg-white px-2 py-1 text-xs font-medium text-gray-500">{{ count($builderSlots[$slotKey] ?? []) }}</span>
                    </div>

                    <div class="space-y-3">
                        @foreach($builderSlots[$slotKey] ?? [] as $index => $item)
                            @php
                                $definition = $headerComponents[$item['type']] ?? [];
                                $allowedSlots = $definition['allowed_slots'] ?? [];
                                $enabled = $item['enabled'] ?? true;
                            @endphp
                            <div draggable="true"
                                @dragstart="drag = { slot: '{{ $slotKey }}', index: {{ $index }} }; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.setData('text/plain', 'header-component')"
                                @dragend="drag = null"
                                @dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                                @drop.prevent="if (drag) { $wire.moveComponentByDrag(drag.slot, drag.index, '{{ $slotKey }}', {{ $index }}); drag = null }"
                                class="cursor-grab rounded-lg border bg-white p-3 shadow-sm transition active:cursor-grabbing {{ $enabled ? 'border-gray-200' : 'border-dashed border-gray-300 opacity-60' }}"
                                wire:key="builder-item-{{ str_replace('.', '-', $slotKey) }}-{{ $index }}-{{ $item['type'] ?? 'unknown' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-start gap-2">
                                        <span class="mt-0.5 select-none text-gray-300" title="Kéo để di chuyển">⋮⋮</span>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-gray-900">{{ $definition['label'] ?? ($item['type'] ?? 'Unknown') }}</div>
                                            <div class="mt-1 text-xs text-gray-500">{{ $item['type'] ?? '' }}</div>
                                        </div>
                                    </div>
                                    <select wire:change="setComponentVisibility('{{ $slotKey }}', {{ $index }}, $event.target.value)" aria-label="Thiết bị hiển thị {{ $definition['label'] ?? ($item['type'] ?? 'thành phần') }}" class="w-40 shrink-0 rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs">
                                        @php($deviceVisibility = $item['config']['visibility'] ?? ($enabled ? (str_starts_with($slotKey, 'mobile.') ? 'mobile' : 'desktop') : 'hidden'))
                                        <option value="both" @selected($deviceVisibility === 'both')>Desktop + Mobile</option>
                                        <option value="desktop" @selected($deviceVisibility === 'desktop')>Chỉ Desktop</option>
                                        <option value="mobile" @selected($deviceVisibility === 'mobile')>Chỉ Mobile</option>
                                        <option value="hidden" @selected($deviceVisibility === 'hidden')>Ẩn hoàn toàn</option>
                                    </select>
                                </div>

                                <div class="mt-3 flex items-center gap-2">
                                    <button type="button" wire:click="moveUp('{{ $slotKey }}', {{ $index }})" @disabled($index === 0) class="rounded border border-gray-200 px-2 py-1 text-xs disabled:opacity-30">↑</button>
                                    <button type="button" wire:click="moveDown('{{ $slotKey }}', {{ $index }})" @disabled($index === count($builderSlots[$slotKey] ?? []) - 1) class="rounded border border-gray-200 px-2 py-1 text-xs disabled:opacity-30">↓</button>

                                    @if(count($allowedSlots) > 1)
                                        <select wire:change="moveComponent('{{ $slotKey }}', {{ $index }}, $event.target.value)" class="ml-auto rounded-lg border border-gray-300 bg-white py-1.5 pl-2 pr-7 text-xs shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                            <option value="{{ $slotKey }}">Chuyển vị trí…</option>
                                            @foreach($allowedSlots as $targetSlot)
                                                @if($targetSlot !== $slotKey)
                                                    <option value="{{ $targetSlot }}">{{ $builderSlotNames[$targetSlot] ?? $targetSlot }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div @dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                            @drop.prevent="if (drag) { $wire.moveComponentByDrag(drag.slot, drag.index, '{{ $slotKey }}', {{ count($builderSlots[$slotKey] ?? []) }}); drag = null }"
                            class="rounded-lg border border-dashed border-gray-300 px-3 py-3 text-center text-xs text-gray-400 transition"
                            :class="drag ? 'border-blue-400 bg-blue-50 text-blue-600' : ''">
                            {{ empty($builderSlots[$slotKey] ?? []) ? 'Slot trống · thả component vào đây' : 'Thả vào cuối slot' }}
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
        @endif

        </div>
        </section>

        @if($editingTemplate === 'pharma')
            <section id="header-actions-panel" class="rounded-xl border border-emerald-200 bg-white p-4" aria-label="Hành động Header Pharma">
                <div class="mb-4 border-b border-emerald-100 pb-3">
                    <h4 class="text-base font-bold text-slate-900">Hành động Header</h4>
                    <p class="mt-1 text-xs text-slate-600">Quản lý hành động tài khoản của Header Pharma ngay trong Bố cục Header. Wishlist và Giỏ hàng thuộc cấu hình Header Default, không áp dụng cho Pharma.</p>
                </div>
                <button type="button" @click="showAccountSettings = !showAccountSettings" :aria-expanded="showAccountSettings.toString()" aria-controls="header-account-settings" class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-gray-800">
                    <span>Tài khoản & đăng nhập <span class="ml-2 text-xs font-normal text-gray-500">Kiểu hiển thị và các liên kết tài khoản</span></span>
                    <span x-text="showAccountSettings ? 'Thu gọn −' : 'Cấu hình +'"></span>
                </button>
                <div id="header-account-settings" x-show="showAccountSettings" x-cloak class="mt-4 space-y-4">
                <div>
                    <h4 class="text-base font-bold text-gray-900">Account Dropdown · Góc phải Header</h4>
                    <p class="mt-1 text-xs text-gray-600">Component độc lập với Menu điều hướng. Tổng quan tài khoản và Đăng xuất là mục bắt buộc; các mục khác chỉ hiện khi route khả dụng và người dùng có quyền.</p>
                </div>
                <label class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-900"><input type="checkbox" wire:model="accountSettings.enabled" class="h-4 w-4 rounded border-gray-300"> Hiển thị Tài khoản trên Header Pharma (Desktop và Mobile)</label>
                <p class="text-xs text-gray-500">Tắt để ẩn nút tài khoản trên desktop và các hành động tài khoản trong mobile drawer. Lưu Account Dropdown để áp dụng.</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium text-gray-700">Kiểu hiển thị
                        <select wire:model="accountSettings.style" class="mt-1 w-full rounded-lg border-gray-300 bg-white text-sm">
                            <option value="avatar_name">Avatar + tên + mũi tên</option>
                            <option value="avatar">Chỉ avatar + mũi tên</option>
                            <option value="button">Nút Tài khoản</option>
                        </select>
                    </label>
                    <div class="space-y-2 text-sm">
                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="accountSettings.show_name" class="rounded border-gray-300"> Hiển thị tên người dùng</label>
                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="accountSettings.show_profile" class="rounded border-gray-300"> Thông tin cá nhân (nếu có route)</label>
                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="accountSettings.show_password" class="rounded border-gray-300"> Đổi mật khẩu (nếu có route)</label>
                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="accountSettings.show_orders" class="rounded border-gray-300"> Đơn hàng của tôi (nếu có route)</label>
                        <label class="flex items-center gap-2"><input type="checkbox" wire:model="accountSettings.show_admin" class="rounded border-gray-300"> Quản trị (nếu có quyền)</label>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3 border-t border-emerald-100 pt-3">
                    <span class="text-xs text-gray-600">Desktop và Mobile: góc phải · Đăng xuất dùng POST + CSRF.</span>
                    <button type="button" wire:click="saveAccountSettings" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Lưu Account Dropdown</button>
                </div>
                </div>
            </section>
        @endif



        <section class="rounded-xl border border-gray-200 bg-white p-4">
            <button type="button" @click="showAppearanceSettings = !showAppearanceSettings" :aria-expanded="showAppearanceSettings.toString()" aria-controls="header-appearance-settings" class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-gray-800">
                <span>Giao diện & kích thước <span class="ml-2 text-xs font-normal text-gray-500">Màu sắc, chiều cao và tùy chọn nâng cao</span></span>
                <span x-text="showAppearanceSettings ? 'Thu gọn −' : 'Cấu hình +'"></span>
            </button>
            <div id="header-appearance-settings" x-show="showAppearanceSettings" x-cloak class="mt-4">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h4 class="font-bold text-gray-900">Thiết lập giao diện Header</h4>
                    <p class="text-sm text-gray-500">Thay đổi bên dưới được phản ánh trực tiếp trong Responsive Preview trước khi lưu.</p>
                </div>
                <div class="inline-flex rounded-lg bg-gray-100 p-1">
                    <button type="button" wire:click="$set('presentation.mode', 'basic')" class="rounded-md px-3 py-1.5 text-sm font-semibold {{ ($presentation['mode'] ?? 'basic') === 'basic' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500' }}">Basic</button>
                    <button type="button" wire:click="$set('presentation.mode', 'advanced')" class="rounded-md px-3 py-1.5 text-sm font-semibold {{ ($presentation['mode'] ?? 'basic') === 'advanced' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500' }}">Advanced</button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <label class="text-sm font-medium text-gray-700">Chiều rộng nội dung
                    <select wire:model.live="presentation.container" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <option value="compact">Compact · 1024px</option><option value="standard">Standard · 1280px</option><option value="wide">Wide · 1440px</option><option value="full">Full width</option>
                    </select>
                </label>
                <label class="text-sm font-medium text-gray-700">Kích thước Header
                    <select wire:model.live="presentation.size" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <option value="compact">Compact</option><option value="normal">Normal</option><option value="comfortable">Comfortable</option>
                    </select>
                </label>
                @if($editingTemplate === 'pharma')
                    <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-4 sm:col-span-2" aria-label="Khoảng cách Header đến Banner">
                        <h4 class="text-sm font-bold text-slate-800">Khoảng cách Header → Banner</h4>
                        <p class="mt-1 text-xs text-slate-500">Khoảng cách ngoài Header, không thay đổi chiều cao hoặc padding của logo và menu. Mặc định 0px.</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="block text-xs font-semibold text-slate-700">Desktop (0–120px)
                                <input type="number" min="0" max="120" step="1" wire:model.live.debounce.300ms="presentation.banner_gap.desktop" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            </label>
                            <label class="block text-xs font-semibold text-slate-700">Mobile (0–80px)
                                <input type="number" min="0" max="80" step="1" wire:model.live.debounce.300ms="presentation.banner_gap.mobile" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            </label>
                        </div>
                        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white">
                            <div class="bg-slate-100 px-3 py-2 text-center text-xs font-semibold text-slate-600">HEADER</div>
                            <div class="bg-white" style="height:{{ max(0, min(120, (int) ($presentation['banner_gap']['desktop'] ?? 0))) }}px"></div>
                            <div class="bg-emerald-100 px-3 py-2 text-center text-xs font-semibold text-emerald-800">BANNER</div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Xem trước minh họa Desktop. Nhấn “Lưu bố cục” để xuất bản cả hai giá trị; Header Themes sẽ lưu theo snapshot Presentation.</p>
                    </div>
                @endif
                <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700"><input type="checkbox" wire:model.live="presentation.sticky" class="rounded border-gray-300 text-blue-600"> Sticky Header</label>
                <label class="text-sm font-medium text-gray-700">Shadow
                    <select wire:model.live="presentation.shadow" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="none">None</option><option value="soft">Soft</option><option value="medium">Medium</option></select>
                </label>
            </div>

            <div class="mt-5 rounded-xl border border-gray-200 p-4">
                <label class="flex items-center gap-3 text-sm font-semibold text-gray-700"><input type="checkbox" wire:model.live="presentation.inherit_colors" class="rounded border-gray-300 text-blue-600"> Kế thừa màu Global Website Theme</label>
                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    @foreach(['background' => 'Background','foreground' => 'Text','accent' => 'Accent','border' => 'Border','topbar_background' => 'Topbar BG','topbar_foreground' => 'Topbar Text'] as $colorKey => $colorLabel)
                        <label class="text-xs font-medium text-gray-600">{{ $colorLabel }}<input type="color" wire:model.live="presentation.colors.{{ $colorKey }}" class="mt-1 h-10 w-full rounded border border-gray-200 bg-white p-1"></label>
                    @endforeach
                </div>
            </div>

            @if(($presentation['mode'] ?? 'basic') === 'advanced')
                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 rounded-xl border border-amber-200 bg-amber-50/40 p-4">
                    @foreach(['container_width' => ['Container max width', 960, 1920],'desktop_height' => ['Desktop height', 52, 120],'tablet_height' => ['Tablet height', 52, 120],'mobile_height' => ['Mobile height', 52, 120],'topbar_height' => ['Topbar height', 24, 56],'logo_max_height' => ['Logo max height', 24, 72],'search_max_width' => ['Search max width', 320, 900]] as $field => [$label, $min, $max])
                        <label class="text-sm font-medium text-gray-700">{{ $label }}
                            <div class="relative mt-1"><input type="number" min="{{ $min }}" max="{{ $max }}" wire:model.live.debounce.250ms="presentation.custom.{{ $field }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-10 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><span class="absolute right-3 top-3 text-xs text-gray-400">px</span></div>
                        </label>
                    @endforeach
                </div>
            @endif
            </div>
        </section>
    </div>
</div>
