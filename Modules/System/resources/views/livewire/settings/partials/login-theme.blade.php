<div class="space-y-6">
    <div class="flex flex-col gap-4 border-b border-gray-200 pb-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-2xl">
            <h2 class="text-lg font-semibold text-gray-900">Giao diện đăng nhập</h2>
            <p class="mt-1 text-sm leading-6 text-gray-500">Thiết lập theme và nhận diện thương hiệu cho từng cổng đăng nhập. Các thay đổi chỉ tác động đến phần trình bày.</p>
        </div>
        <div class="inline-flex self-start rounded-xl border border-gray-200 bg-gray-100 p-1">
            <button type="button" wire:click="setTarget('admin')" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $target === 'admin' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600' }}">Admin</button>
            <button type="button" wire:click="setTarget('client')" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $target === 'client' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600' }}">Client / PWA</button>
        </div>
    </div>

    @unless($canUpdate)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">Tài khoản hiện tại chỉ có quyền xem cấu hình.</div>
    @endunless

    <div class="grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]">
        <form wire:submit="save" class="min-w-0 space-y-5">
            <fieldset @disabled(!$canUpdate) class="space-y-5">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Mẫu giao diện</h3>
                    <p class="mt-1 text-sm text-gray-500">Chọn bố cục nền tảng. Nội dung và hình ảnh được giữ nguyên khi đổi theme.</p>
                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach($themes as $value => $label)
                            @php
                                $descriptions = [
                                    'classic-card' => 'Card đăng nhập trung tâm, cân đối và quen thuộc.',
                                    'split-brand' => 'Không gian thương hiệu lớn kết hợp form đăng nhập.',
                                    'hero-overlay' => 'Ảnh nền toàn màn hình với card nổi phía trên.',
                                    'minimal' => 'Tối giản, tập trung vào thao tác đăng nhập.',
                                ];
                            @endphp
                            <label class="cursor-pointer rounded-xl border p-4 transition {{ ($settings['theme'] ?? '') === $value ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100' : 'border-gray-200 hover:border-indigo-300' }}">
                                <input type="radio" wire:model.live="settings.theme" value="{{ $value }}" class="sr-only">
                                <span class="flex items-start justify-between gap-3">
                                    <span><span class="block text-sm font-semibold text-gray-900">{{ $label }}</span><span class="mt-1 block text-xs leading-5 text-gray-500">{{ $descriptions[$value] }}</span></span>
                                    <span class="mt-0.5 h-5 w-5 shrink-0 rounded-full border-4 {{ ($settings['theme'] ?? '') === $value ? 'border-indigo-600 bg-white' : 'border-gray-300 bg-white' }}"></span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Nội dung thương hiệu</h3>
                    <p class="mt-1 text-sm text-gray-500">Thông tin hiển thị trực tiếp trên màn hình đăng nhập.</p>
                    <div class="mt-5 space-y-5">
                        <div><label for="login-title-1" class="block text-sm font-semibold text-gray-700">Dòng tên 1</label><input id="login-title-1" type="text" wire:model.live.debounce.300ms="settings.title_line_1" class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('settings.title_line_1')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="login-title-2" class="block text-sm font-semibold text-gray-700">Dòng tên 2</label><input id="login-title-2" type="text" wire:model.live.debounce.300ms="settings.title_line_2" class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('settings.title_line_2')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="login-description" class="block text-sm font-semibold text-gray-700">Mô tả</label><textarea id="login-description" rows="3" wire:model.live.debounce.300ms="settings.description" class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>@error('settings.description')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="login-footer" class="block text-sm font-semibold text-gray-700">Footer / Copyright</label><input id="login-footer" type="text" wire:model.live.debounce.300ms="settings.footer" class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('settings.footer')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror</div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Màu sắc & hiệu ứng</h3>
                    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700">Màu chủ đạo</label>
                            <div class="mt-2 flex overflow-hidden rounded-xl border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-indigo-100"><input type="color" wire:model.live="settings.primary_color" class="h-12 w-14 border-0 border-r border-gray-200 p-2"><input type="text" wire:model.live.debounce.300ms="settings.primary_color" class="min-w-0 flex-1 border-0 px-4 py-3 font-mono text-sm uppercase focus:ring-0"></div>
                        </div>
                        <div><div class="flex justify-between"><label class="text-sm font-semibold text-gray-700">Độ tối ảnh nền</label><span class="rounded-lg bg-gray-100 px-2 py-1 text-xs font-semibold">{{ $settings['overlay_opacity'] ?? 55 }}%</span></div><input type="range" min="0" max="90" step="5" wire:model.live="settings.overlay_opacity" class="mt-4 h-2 w-full accent-indigo-600"></div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Hình ảnh</h3>
                    <p class="mt-1 text-sm text-gray-500">PNG, JPG hoặc WebP. Logo tối đa 3 MB, ảnh nền tối đa 6 MB.</p>
                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4" wire:loading.class="opacity-75" wire:target="newLogo">
                            <div class="flex items-start gap-4">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white">
                                    @if($settings['logo_url'] ?? null)<img src="{{ $settings['logo_url'] }}" class="h-full w-full object-contain p-2" alt="Logo hiện tại">@else<span class="text-xs font-medium text-gray-400">Chưa có</span>@endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-800">Logo đăng nhập</p>
                                    <p class="mt-1 text-xs leading-5 text-gray-500">Ảnh hiện tại được giữ cho đến khi bạn bấm Lưu.</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <label class="cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"><span wire:loading.remove wire:target="newLogo">{{ $newLogo ? 'Chọn lại' : 'Thay ảnh' }}</span><span wire:loading wire:target="newLogo">Đang tải...</span><input type="file" wire:model="newLogo" accept="image/png,image/jpeg,image/webp" class="sr-only"></label>
                                        @if($settings['logo_url'] ?? null)<button type="button" wire:click="removeAsset('logo')" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Xóa</button>@endif
                                    </div>
                                    @if($newLogo)<p class="mt-2 text-xs font-semibold text-emerald-700">Đã chọn logo mới. Ảnh sẽ áp dụng sau khi lưu.</p>@endif
                                </div>
                            </div>
                            @error('newLogo')<p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4" wire:loading.class="opacity-75" wire:target="newBackground">
                            <div class="flex items-start gap-4">
                                <div class="flex h-20 w-28 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white">
                                    @if($settings['background_url'] ?? null)<img src="{{ $settings['background_url'] }}" class="h-full w-full object-cover" alt="Ảnh nền hiện tại">@else<span class="text-xs font-medium text-gray-400">Chưa có</span>@endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-800">Ảnh nền</p>
                                    <p class="mt-1 text-xs leading-5 text-gray-500">Khuyến nghị ảnh ngang, tối thiểu 1600 × 900 px.</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <label class="cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"><span wire:loading.remove wire:target="newBackground">{{ $newBackground ? 'Chọn lại' : 'Thay ảnh' }}</span><span wire:loading wire:target="newBackground">Đang tải...</span><input type="file" wire:model="newBackground" accept="image/png,image/jpeg,image/webp" class="sr-only"></label>
                                        @if($settings['background_url'] ?? null)<button type="button" wire:click="removeAsset('background')" class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Xóa</button>@endif
                                    </div>
                                    @if($newBackground)<p class="mt-2 text-xs font-semibold text-emerald-700">Đã chọn ảnh nền mới. Ảnh sẽ áp dụng sau khi lưu.</p>@endif
                                </div>
                            </div>
                            @error('newBackground')<p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-base font-semibold text-gray-900">Tùy chọn đăng nhập</h3>
                    <label class="mt-4 flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-gray-200 bg-gray-50/70 p-4"><span><span class="block text-sm font-semibold text-gray-800">Hiển thị Google Workspace</span><span class="mt-1 block text-xs text-gray-500">Chỉ thay đổi hiển thị nút đăng nhập, không thay đổi OAuth.</span></span><span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center"><input type="checkbox" wire:model.live="settings.show_google" class="peer sr-only"><span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-600"></span><span class="absolute left-1 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span></span></label>
                </section>
            </fieldset>

            <div class="sticky bottom-3 z-20 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-gray-500">Cấu hình cho <span class="font-semibold text-gray-700">{{ $target === 'admin' ? 'Admin' : 'Client / PWA' }}</span>.</p><button type="submit" @disabled(!$canUpdate) wire:loading.attr="disabled" wire:target="save,newLogo,newBackground" class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="save">Lưu giao diện đăng nhập</span><span wire:loading wire:target="save">Đang lưu...</span></button></div>
        </form>

        <aside class="min-w-0 2xl:sticky 2xl:top-6 2xl:self-start">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3"><div><h3 class="text-sm font-semibold text-gray-900">Live Preview</h3><p class="mt-0.5 text-xs text-gray-500">Preview dùng ảnh đã lưu; ảnh mới áp dụng sau khi lưu.</p></div><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">{{ $target === 'admin' ? '/admin/login' : '/login' }}</span></div>
                @php
                    $previewBackground = $settings['background_url'] ?? null;
                    $previewLogo = $settings['logo_url'] ?? null;
                    $theme = $settings['theme'] ?? 'classic-card';
                    $primary = $settings['primary_color'] ?? '#0f172a';
                    $opacity = ((int) ($settings['overlay_opacity'] ?? 55)) / 100;
                @endphp
                <div class="relative min-h-[560px] overflow-hidden rounded-2xl border border-gray-200 bg-slate-100 shadow-inner" @if($previewBackground) style="background-image: url('{{ $previewBackground }}'); background-size: cover; background-position: center;" @endif>
                    @if($previewBackground)<div class="absolute inset-0 bg-black" style="opacity: {{ $opacity }}"></div>@endif
                    <div class="relative z-10 flex min-h-[560px] {{ $theme === 'split-brand' ? 'items-stretch' : 'items-center justify-center' }} p-6">
                        @if($theme === 'split-brand')<div class="hidden w-1/2 flex-col justify-end p-6 text-white sm:flex"><p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ $settings['title_line_1'] ?? '' }}</p><p class="mt-2 text-2xl font-bold">{{ $settings['title_line_2'] ?? '' }}</p><p class="mt-2 text-sm text-white/80">{{ $settings['description'] ?? '' }}</p></div>@endif
                        <div class="{{ $theme === 'minimal' ? 'bg-transparent shadow-none' : ($theme === 'hero-overlay' ? 'bg-white/90 backdrop-blur' : 'bg-white') }} {{ $theme === 'split-brand' ? 'ml-auto w-full sm:w-1/2' : 'w-full max-w-sm' }} rounded-2xl p-6 shadow-xl">
                            <div class="text-center">@if($previewLogo)<img src="{{ $previewLogo }}" class="mx-auto h-16 w-16 object-contain" alt="Logo preview">@endif @if($theme !== 'split-brand')<p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $settings['title_line_1'] ?? '' }}</p><p class="mt-1 font-bold text-gray-900">{{ $settings['title_line_2'] ?? '' }}</p><p class="mt-2 text-xs text-gray-500">{{ $settings['description'] ?? '' }}</p>@endif</div>
                            <div class="mt-5 space-y-3"><div class="h-11 rounded-xl border border-gray-300 bg-white"></div><div class="h-11 rounded-xl border border-gray-300 bg-white"></div><div class="h-11 rounded-xl" style="background-color: {{ $primary }}"></div>@if($settings['show_google'] ?? true)<div class="h-11 rounded-xl border border-gray-300 bg-white"></div>@endif</div>
                            @if($settings['footer'] ?? '')<p class="mt-5 text-center text-[10px] text-gray-400">{{ $settings['footer'] }}</p>@endif
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>