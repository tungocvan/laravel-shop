<div class="mx-auto max-w-5xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Cấu hình giao diện Admin</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý layout, sidebar, header, theme và navigation mà không cần sửa file PHP.</p>
        </div>

        <button
            type="button"
            wire:click="resetConfig"
            wire:confirm="Khôi phục toàn bộ cấu hình Admin về mặc định trong file config?"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            Khôi phục mặc định
        </button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-slate-900">Layout</h2>
                <p class="mt-1 text-sm text-slate-500">Điều khiển bố cục tổng thể và mật độ hiển thị.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Preset</span>
                    <select wire:model="config.layout.preset" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="default">Default</option>
                        <option value="data-heavy">Data heavy</option>
                        <option value="focus">Focus</option>
                        <option value="settings">Settings</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Container</span>
                    <select wire:model="config.layout.container" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="7xl">7xl</option>
                        <option value="screen-2xl">Screen 2xl</option>
                        <option value="narrow">Narrow</option>
                        <option value="full">Full width</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Density</span>
                    <select wire:model="config.layout.density" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="comfortable">Comfortable</option>
                        <option value="compact">Compact</option>
                        <option value="dense">Dense</option>
                    </select>
                </label>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3">
                    <span class="text-sm font-medium text-slate-700">Sticky header</span>
                    <input type="checkbox" wire:model="config.layout.sticky_header" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                </label>

                <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3">
                    <span class="text-sm font-medium text-slate-700">Hiển thị footer</span>
                    <input type="checkbox" wire:model="config.layout.show_footer" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-slate-900">Sidebar & Header</h2>
                <p class="mt-1 text-sm text-slate-500">Bật tắt các vùng chính của admin shell.</p>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @foreach ([
                    'config.sidebar.enabled' => 'Bật sidebar',
                    'config.sidebar.desktop_collapsible' => 'Cho phép thu gọn desktop',
                    'config.sidebar.mobile_drawer' => 'Drawer trên mobile',
                    'config.sidebar.persist_state' => 'Nhớ trạng thái sidebar',
                    'config.sidebar.show_footer_profile' => 'Profile ở cuối sidebar',
                    'config.header.search' => 'Tìm kiếm trên header',
                    'config.header.notifications' => 'Thông báo',
                    'config.header.theme_switcher' => 'Theme switcher',
                    'config.header.user_menu' => 'User menu',
                ] as $model => $label)
                    <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3">
                        <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                        <input type="checkbox" wire:model="{{ $model }}" class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </label>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-slate-900">Theme & Navigation</h2>
                <p class="mt-1 text-sm text-slate-500">Theme được lưu vào DB và áp dụng cho sidebar.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Sidebar theme</span>
                    <select wire:model="config.theme.default" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($themes as $theme)
                            <option value="{{ $theme }}">{{ $theme }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Accent</span>
                    <select wire:model="config.theme.accent" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="blue">Blue</option>
                        <option value="indigo">Indigo</option>
                        <option value="emerald">Emerald</option>
                        <option value="rose">Rose</option>
                        <option value="amber">Amber</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Locale</span>
                    <select wire:model="config.locale" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="vi">Tiếng Việt</option>
                        <option value="en">English</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Menu cache TTL</span>
                    <input type="number" min="60" max="86400" wire:model="config.navigation.cache_ttl" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Navigation depth</span>
                    <input type="number" min="1" max="3" wire:model="config.navigation.max_depth" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </label>
            </div>
        </section>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                Vui lòng kiểm tra lại các trường cấu hình.
            </div>
        @endif

        <div class="flex justify-end border-t border-slate-200 pt-5">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">Lưu cấu hình</span>
                <span wire:loading wire:target="save">Đang lưu...</span>
            </button>
        </div>
    </form>
</div>
