<div class="mx-auto max-w-6xl">
    <div class="mb-5 flex justify-end">
        <button type="button" wire:click="resetSection" wire:confirm="Khôi phục khu vực cấu hình này về mặc định?" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Khôi phục khu vực</button>
    </div>

    <form wire:submit="save" class="space-y-6">
        @if ($section === 'general')
            @php
                $spacingOptions = ['0' => '0 px', '1' => '4 px', '2' => '8 px', '3' => '12 px', '4' => '16 px', '5' => '20 px', '6' => '24 px', '8' => '32 px', '10' => '40 px', '12' => '48 px'];
                $containers = [
                    'narrow' => ['Narrow', 'Form, nội dung cần tập trung', 'w-1/2'],
                    '7xl' => ['7xl', 'Cân bằng cho phần lớn trang Admin', 'w-2/3'],
                    'screen-2xl' => ['Screen 2xl', 'Rộng, vẫn giữ gutter an toàn', 'w-5/6'],
                    'full' => ['Full width', 'Tối đa vùng làm việc dữ liệu', 'w-full'],
                ];
            @endphp

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_18rem] xl:items-start">
                <div class="space-y-6">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-base font-semibold text-slate-900">Workspace</h2>
                            <p class="mt-1 text-sm text-slate-500">Chọn cấu trúc, độ rộng và mật độ làm việc mặc định của Admin.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Preset</span>
                                <select wire:model="config.layout.preset" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="default">Default</option><option value="data-heavy">Data heavy</option><option value="focus">Focus</option><option value="settings">Settings</option>
                                </select>
                                <span class="mt-2 block text-xs leading-5 text-slate-500">Preset là điểm khởi đầu; các tham số bên dưới vẫn có thể tinh chỉnh riêng.</span>
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Density</span>
                                <select wire:model="config.layout.density" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="comfortable">Comfortable</option><option value="compact">Compact</option><option value="dense">Dense</option>
                                </select>
                            </label>
                        </div>

                        <fieldset class="mt-5">
                            <legend class="text-sm font-medium text-slate-700">Container</legend>
                            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach ($containers as $value => [$label, $description, $width])
                                    <label class="relative cursor-pointer rounded-lg border p-4 transition" :class="$wire.config.layout.container === '{{ $value }}' ? 'border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500' : 'border-slate-200 bg-white hover:border-slate-300'">
                                        <input type="radio" wire:model.live="config.layout.container" value="{{ $value }}" class="sr-only">
                                        <span class="flex items-center justify-between gap-3">
                                            <span class="text-sm font-semibold text-slate-900">{{ $label }}</span>
                                            <span class="h-2.5 w-14 overflow-hidden rounded-sm bg-slate-100"><span class="mx-auto block h-full {{ $width }} rounded-sm bg-indigo-400"></span></span>
                                        </span>
                                        <span class="mt-1.5 block text-xs leading-5 text-slate-500">{{ $description }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-base font-semibold text-slate-900">Content spacing</h2>
                            <p class="mt-1 text-sm text-slate-500">Khoảng cách dùng design scale an toàn để giữ responsive và nhịp giao diện nhất quán.</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ([
                                'content_padding_x' => 'Padding ngang Desktop',
                                'tablet_padding_x' => 'Padding ngang Tablet',
                                'mobile_padding_x' => 'Padding ngang Mobile',
                                'content_padding_top' => 'Padding phía trên',
                                'content_padding_bottom' => 'Padding phía dưới',
                                'section_gap' => 'Khoảng cách section',
                            ] as $key => $label)
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                                    <select wire:model="config.layout.spacing.{{ $key }}" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach ($spacingOptions as $value => $display)<option value="{{ $value }}">{{ $display }}</option>@endforeach
                                    </select>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-5">
                            <h2 class="text-base font-semibold text-slate-900">Surface</h2>
                            <p class="mt-1 text-sm text-slate-500">Ưu tiên semantic Design System; chỉ chọn surface cố định khi thật sự cần.</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block"><span class="text-sm font-medium text-slate-700">Page background</span><select wire:model="config.layout.surface.page_background" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="system">Theo Design System</option><option value="white">White</option><option value="slate-50">Slate 50</option></select></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Content surface</span><select wire:model="config.layout.surface.content_surface" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="transparent">Transparent</option><option value="system">Theo Design System</option><option value="white">White</option></select></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Border / divider</span><select wire:model="config.layout.surface.border" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="system">Theo Design System</option><option value="none">Không dùng border</option></select></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Default radius</span><select wire:model="config.layout.surface.radius" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="none">None</option><option value="sm">Small — 4 px</option><option value="md">Medium — 6 px</option><option value="lg">Large — 8 px</option></select></label>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Behavior</h2><p class="mt-1 text-sm text-slate-500">Các hành vi chung giúp workspace ổn định và dễ sử dụng lâu dài.</p></div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span><span class="block text-sm font-medium text-slate-700">Sticky Header</span><span class="mt-0.5 block text-xs text-slate-500">Giữ Header trong tầm nhìn khi cuộn.</span></span><input type="checkbox" wire:model="config.layout.sticky_header" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>
                            <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span><span class="block text-sm font-medium text-slate-700">Reduced motion</span><span class="mt-0.5 block text-xs text-slate-500">Tôn trọng trải nghiệm ít chuyển động.</span></span><input type="checkbox" wire:model="config.layout.behavior.reduced_motion" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4"><h2 class="text-base font-semibold text-slate-900">Language & display</h2><p class="mt-1 text-sm text-slate-500">Thiết lập hiển thị không thuộc presentation geometry.</p></div>
                        <label class="block max-w-sm"><span class="text-sm font-medium text-slate-700">Locale</span><select wire:model="config.locale" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="vi">Tiếng Việt</option><option value="en">English</option></select></label>
                    </section>
                </div>

                <aside class="xl:sticky xl:top-24">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3"><h2 class="text-sm font-semibold text-slate-900">Workspace preview</h2><span class="rounded-full bg-white px-2 py-1 text-[11px] font-medium text-slate-500 shadow-sm">A4 live preview</span></div>
                        <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <div class="h-5 border-b border-slate-200 bg-slate-50"></div>
                            <div class="flex h-36">
                                <div class="w-10 border-r border-slate-200 bg-slate-100"></div>
                                <div class="flex-1 p-3"><div class="h-2.5 w-2/3 rounded bg-slate-200"></div><div class="mt-2 h-1.5 w-1/2 rounded bg-slate-100"></div><div class="mt-4 h-16 rounded border border-slate-200 bg-slate-50"></div></div>
                            </div>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-slate-500">Preview tương tác sẽ được kích hoạt ở A4. A3 tập trung vào hierarchy và control ergonomics.</p>
                    </div>
                </aside>
            </div>
        @else
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                @if ($section === 'header')
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">@foreach (['config.header.search' => 'Tìm kiếm trên Header','config.header.notifications' => 'Thông báo','config.header.theme_switcher' => 'Theme switcher','config.header.user_menu' => 'User menu'] as $model => $label)<label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">{{ $label }}</span><input type="checkbox" wire:model="{{ $model }}" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>@endforeach</div>
                @elseif ($section === 'sidebar')
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">@foreach (['config.sidebar.enabled' => 'Bật Sidebar','config.sidebar.desktop_collapsible' => 'Cho phép thu gọn desktop','config.sidebar.mobile_drawer' => 'Drawer trên mobile','config.sidebar.persist_state' => 'Nhớ trạng thái Sidebar','config.sidebar.show_footer_profile' => 'Profile ở cuối Sidebar'] as $model => $label)<label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">{{ $label }}</span><input type="checkbox" wire:model="{{ $model }}" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>@endforeach</div>
                    <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4"><label class="block"><span class="text-sm font-medium text-slate-700">Ngưỡng bật tìm kiếm Sidebar</span><input type="number" min="4" max="50" wire:model="config.sidebar.navigation_search_threshold" class="mt-2 block w-full max-w-xs rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><span class="mt-2 block text-xs text-slate-500">Ô tìm chức năng chỉ xuất hiện khi số destination được phép đạt ngưỡng này. Mặc định 12 để Sidebar ít chức năng vẫn gọn.</span></label></div>
                @elseif ($section === 'footer')
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">@foreach (['config.layout.show_footer' => 'Hiển thị Footer','config.footer.show_app_name' => 'Hiển thị tên ứng dụng','config.footer.show_environment' => 'Hiển thị môi trường'] as $model => $label)<label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">{{ $label }}</span><input type="checkbox" wire:model="{{ $model }}" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>@endforeach</div>
                @elseif ($section === 'design')
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2"><label class="block"><span class="text-sm font-medium text-slate-700">Sidebar theme</span><select wire:model="config.theme.default" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">@foreach ($themes as $theme)<option value="{{ $theme }}">{{ $theme }}</option>@endforeach</select></label><label class="block"><span class="text-sm font-medium text-slate-700">Accent</span><select wire:model="config.theme.accent" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="blue">Blue</option><option value="indigo">Indigo</option><option value="emerald">Emerald</option><option value="rose">Rose</option><option value="amber">Amber</option></select></label></div>
                @elseif ($section === 'navigation')
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2"><label class="block"><span class="text-sm font-medium text-slate-700">Menu cache TTL</span><input type="number" min="60" max="86400" wire:model="config.navigation.cache_ttl" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label><label class="block"><span class="text-sm font-medium text-slate-700">Navigation depth</span><input type="number" min="1" max="3" wire:model="config.navigation.max_depth" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label></div>
                @endif
            </section>
        @endif

        @if ($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Vui lòng kiểm tra lại các trường cấu hình.</div>@endif
        <div class="flex justify-end border-t border-slate-200 pt-5"><button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"><span wire:loading.remove wire:target="save">Lưu {{ $sectionTitle }}</span><span wire:loading wire:target="save">Đang lưu...</span></button></div>
    </form>
</div>
