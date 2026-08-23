@php
    $control = 'mt-2 block h-10 w-full rounded-lg border-slate-300 bg-white px-3 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $toggle = 'h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
    $background = data_get($config, 'sidebar.presentation.background', 'theme');
    $previewSurface = match ($background) {
        'white' => 'bg-white text-slate-800',
        'dark' => 'bg-slate-950 text-slate-100',
        'system' => 'bg-slate-50 text-slate-800',
        default => 'bg-indigo-50 text-slate-800',
    };
@endphp

<div class="mx-auto max-w-7xl">
    <div class="mb-5 flex items-center justify-between gap-4">
        <div><p class="text-sm text-slate-500">Quản lý các vùng hiển thị chính của Sidebar mà không làm thay đổi cấu trúc menu và permission.</p></div>
        <button type="button" wire:click="resetSection" wire:confirm="Khôi phục Sidebar về mặc định?" class="inline-flex h-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Khôi phục Sidebar</button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
            <div class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Sidebar behavior</h2><p class="mt-1 text-sm leading-6 text-slate-500">Các hành vi nền tảng đã ổn định, chỉ giữ những tùy chọn thực sự hữu ích.</p></div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ([
                            'config.sidebar.enabled' => ['Bật Sidebar', 'Hiển thị vùng điều hướng chính.'],
                            'config.sidebar.desktop_collapsible' => ['Thu gọn trên desktop', 'Giữ chế độ expanded / collapsed hiện tại.'],
                            'config.sidebar.mobile_drawer' => ['Drawer trên mobile', 'Mở Sidebar dạng overlay ở màn hình nhỏ.'],
                            'config.sidebar.persist_state' => ['Nhớ trạng thái', 'Ghi nhớ collapse và fullscreen toggle.'],
                        ] as $model => [$label, $description])
                            <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"><span><span class="block text-sm font-medium text-slate-800">{{ $label }}</span><span class="mt-0.5 block text-xs text-slate-500">{{ $description }}</span></span><input type="checkbox" wire:model.live="{{ $model }}" class="{{ $toggle }}"></label>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex items-start justify-between gap-5"><div><h2 class="text-base font-semibold text-slate-900">Header Sidebar</h2><p class="mt-1 text-sm leading-6 text-slate-500">Quản lý vùng nhận diện ở đầu Sidebar.</p></div><input type="checkbox" wire:model.live="config.sidebar.header.enabled" class="{{ $toggle }}"></div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Mark / Logo</span><input type="checkbox" wire:model.live="config.sidebar.header.show_mark" class="{{ $toggle }}"></label>
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Title</span><input type="checkbox" wire:model.live="config.sidebar.header.show_title" class="{{ $toggle }}"></label>
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Subtitle</span><input type="checkbox" wire:model.live="config.sidebar.header.show_subtitle" class="{{ $toggle }}"></label>
                    </div>
                    @if(data_get($config, 'sidebar.header.show_subtitle', true))
                        <label class="mt-4 block"><span class="text-sm font-medium text-slate-700">Nội dung Subtitle</span><input type="text" maxlength="80" wire:model.live.debounce.300ms="config.sidebar.header.subtitle" class="{{ $control }}" placeholder="Không gian quản trị"></label>
                    @endif
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex items-start justify-between gap-5"><div><h2 class="text-base font-semibold text-slate-900">Tìm chức năng Sidebar</h2><p class="mt-1 text-sm leading-6 text-slate-500">Cho phép bật/tắt hoàn toàn ô tìm kiếm. Khi bật, ngưỡng giúp Sidebar ít menu vẫn gọn.</p></div><input type="checkbox" wire:model.live="config.sidebar.search.enabled" class="{{ $toggle }}"></div>
                    @if(data_get($config, 'sidebar.search.enabled', true))
                        <label class="block max-w-md"><span class="text-sm font-medium text-slate-700">Ngưỡng hiển thị tìm kiếm</span><input type="number" min="4" max="50" wire:model.live="config.sidebar.navigation_search_threshold" class="{{ $control }}"><span class="mt-1.5 block text-xs text-slate-500">Ví dụ 12: chỉ hiện ô tìm khi người dùng có từ 12 destination trở lên.</span></label>
                    @endif
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex items-start justify-between gap-5"><div><h2 class="text-base font-semibold text-slate-900">Footer Sidebar</h2><p class="mt-1 text-sm leading-6 text-slate-500">Vùng thông tin tài khoản ở cuối Sidebar.</p></div><input type="checkbox" wire:model.live="config.sidebar.footer.enabled" class="{{ $toggle }}"></div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Avatar</span><input type="checkbox" wire:model.live="config.sidebar.footer.show_avatar" class="{{ $toggle }}"></label>
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Tên người dùng</span><input type="checkbox" wire:model.live="config.sidebar.footer.show_name" class="{{ $toggle }}"></label>
                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3"><span class="text-sm font-medium text-slate-700">Subtitle</span><input type="checkbox" wire:model.live="config.sidebar.footer.show_subtitle" class="{{ $toggle }}"></label>
                    </div>
                    @if(data_get($config, 'sidebar.footer.show_subtitle', true))
                        <label class="mt-4 block"><span class="text-sm font-medium text-slate-700">Nội dung Subtitle</span><input type="text" maxlength="80" wire:model.live.debounce.300ms="config.sidebar.footer.subtitle" class="{{ $control }}" placeholder="Tài khoản quản trị"></label>
                    @endif
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="text-base font-semibold text-slate-900">Sidebar background</h2><p class="mt-1 text-sm leading-6 text-slate-500">Chọn surface có tương phản an toàn. Màu active menu vẫn theo Sidebar Theme hiện tại.</p></div>
                    <fieldset><legend class="sr-only">Màu nền Sidebar</legend><div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            'theme' => ['Theo Theme', 'Giữ palette của Sidebar Theme', 'bg-indigo-100'],
                            'system' => ['System', 'Theo surface của Design System', 'bg-slate-100'],
                            'white' => ['White', 'Sáng, trung tính', 'bg-white border'],
                            'dark' => ['Dark', 'Slate 950', 'bg-slate-950'],
                        ] as $value => [$label, $description, $swatch])
                            <label class="cursor-pointer rounded-lg border p-4 transition" :class="$wire.config.sidebar.presentation.background === '{{ $value }}' ? 'border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500' : 'border-slate-200 hover:border-slate-300'"><input type="radio" wire:model.live="config.sidebar.presentation.background" value="{{ $value }}" class="sr-only"><span class="flex items-center gap-3"><span class="h-8 w-8 shrink-0 rounded-lg {{ $swatch }}"></span><span><span class="block text-sm font-semibold text-slate-800">{{ $label }}</span><span class="mt-0.5 block text-xs text-slate-500">{{ $description }}</span></span></span></label>
                        @endforeach
                    </div></fieldset>
                </section>
            </div>

            <aside class="xl:sticky xl:top-24" aria-label="Xem trước Sidebar">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3"><div><h2 class="text-sm font-semibold text-slate-900">Sidebar preview</h2><p class="mt-0.5 text-xs text-slate-500">Xem nhanh hierarchy trước khi lưu.</p></div><span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200">Live</span></div>
                    <div class="mt-4 flex h-[28rem] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex w-48 flex-col {{ $previewSurface }}">
                            @if(data_get($config, 'sidebar.header.enabled', true))
                                <div class="flex min-h-16 items-center gap-2 border-b border-current/10 px-3">@if(data_get($config,'sidebar.header.show_mark',true))<span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-[10px] font-bold text-indigo-700">A</span>@endif<div class="min-w-0">@if(data_get($config,'sidebar.header.show_title',true))<div class="truncate text-xs font-semibold">Admin</div>@endif @if(data_get($config,'sidebar.header.show_subtitle',true))<div class="truncate text-[9px] opacity-55">{{ data_get($config,'sidebar.header.subtitle') }}</div>@endif</div></div>
                            @endif
                            @if(data_get($config,'sidebar.search.enabled',true))<div class="px-3 pt-3"><div class="h-8 rounded-lg border border-current/15 bg-white/80 px-2 pt-2 text-[9px] text-slate-400">Tìm chức năng...</div></div>@endif
                            <div class="flex-1 space-y-2 px-3 py-3"><div class="h-8 rounded-lg bg-indigo-100/80"></div><div class="h-8 rounded-lg bg-current/5"></div><div class="h-8 rounded-lg bg-current/5"></div><div class="h-8 rounded-lg bg-current/5"></div></div>
                            @if(data_get($config,'sidebar.footer.enabled',true))<div class="flex items-center gap-2 border-t border-current/10 p-3">@if(data_get($config,'sidebar.footer.show_avatar',true))<span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-[10px] font-bold text-indigo-700">T</span>@endif<div class="min-w-0">@if(data_get($config,'sidebar.footer.show_name',true))<div class="truncate text-xs font-semibold">Từ Ngọc Vân</div>@endif @if(data_get($config,'sidebar.footer.show_subtitle',true))<div class="truncate text-[9px] opacity-55">{{ data_get($config,'sidebar.footer.subtitle') }}</div>@endif</div></div>@endif
                        </div>
                        <div class="flex-1 bg-slate-100 p-3"><div class="h-5 rounded bg-white"></div><div class="mt-3 h-20 rounded bg-white"></div></div>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Fullscreen toggle vẫn hoạt động độc lập; khi ẩn Sidebar, Header/Content/Footer tiếp tục full width.</p>
                </div>
            </aside>
        </div>

        @if ($errors->any())<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">Có cấu hình Sidebar chưa hợp lệ. Vui lòng kiểm tra lại.</div>@endif
        <div class="sticky bottom-4 z-20 flex justify-end"><button type="submit" wire:loading.attr="disabled" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/15 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-60"><span wire:loading.remove wire:target="save">Lưu Sidebar</span><span wire:loading wire:target="save">Đang lưu...</span></button></div>
    </form>
</div>
