<div class="space-y-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Giao diện đăng nhập</h2>
            <p class="mt-1 text-sm text-gray-500">Chọn theme, branding và hình ảnh cho từng cổng đăng nhập. Thay đổi chỉ ảnh hưởng presentation, không thay đổi guard hoặc chính sách xác thực.</p>
        </div>

        <div class="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1" role="group" aria-label="Cổng đăng nhập">
            <button type="button" wire:click="$set('target', 'admin')" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $target === 'admin' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500' }}">Admin</button>
            <button type="button" wire:click="$set('target', 'client')" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $target === 'client' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500' }}">Client / PWA</button>
        </div>
    </div>

    @unless($canUpdate)
        <p class="rounded-xl bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-700">Tài khoản hiện tại chỉ có quyền xem.</p>
    @endunless

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-2">
        <form wire:submit="save" class="space-y-6">
            <fieldset @disabled(!$canUpdate) class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Theme</label>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        @foreach($themes as $value => $label)
                            <label class="cursor-pointer rounded-xl border p-4 transition {{ ($settings['theme'] ?? '') === $value ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:model.live="settings.theme" value="{{ $value }}" class="sr-only">
                                <span class="block text-sm font-semibold text-gray-900">{{ $label }}</span>
                                <span class="mt-1 block text-xs text-gray-500">{{ match($value) { 'classic-card' => 'Card trung tâm, quen thuộc và gọn.', 'split-brand' => 'Branding lớn bên trái, form bên phải.', 'hero-overlay' => 'Ảnh nền toàn màn hình với card nổi.', default => 'Tối giản cho hệ thống nội bộ.' } }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Dòng tên 1</label>
                        <input type="text" wire:model.live.debounce.300ms="settings.title_line_1" class="mt-2 block w-full rounded-xl border-gray-300">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Dòng tên 2</label>
                        <input type="text" wire:model.live.debounce.300ms="settings.title_line_2" class="mt-2 block w-full rounded-xl border-gray-300">
                        @error('settings.title_line_2')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Mô tả</label>
                        <textarea wire:model.live.debounce.300ms="settings.description" rows="2" class="mt-2 block w-full rounded-xl border-gray-300"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Màu chủ đạo</label>
                        <div class="mt-2 flex items-center gap-3">
                            <input type="color" wire:model.live="settings.primary_color" class="h-11 w-16 rounded-lg border border-gray-300 bg-white p-1">
                            <input type="text" wire:model.live.debounce.300ms="settings.primary_color" class="block w-full rounded-xl border-gray-300 font-mono text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Độ tối ảnh nền: {{ $settings['overlay_opacity'] ?? 55 }}%</label>
                        <input type="range" min="0" max="90" step="5" wire:model.live="settings.overlay_opacity" class="mt-4 w-full">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Footer / Copyright</label>
                        <input type="text" wire:model.live.debounce.300ms="settings.footer" class="mt-2 block w-full rounded-xl border-gray-300">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <label class="block text-sm font-semibold text-gray-700">Logo đăng nhập</label>
                        <input type="file" wire:model="newLogo" accept="image/png,image/jpeg,image/webp" class="mt-3 block w-full text-sm">
                        <div class="mt-3 flex items-center justify-between gap-3">
                            @if($newLogo)
                                <img src="{{ $newLogo->temporaryUrl() }}" class="h-14 w-14 rounded-lg object-contain" alt="Preview logo">
                            @elseif($settings['logo_url'] ?? null)
                                <img src="{{ $settings['logo_url'] }}" class="h-14 w-14 rounded-lg object-contain" alt="Logo hiện tại">
                            @endif
                            <button type="button" wire:click="removeAsset('logo')" class="text-xs font-semibold text-red-600">Xóa logo riêng</button>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4">
                        <label class="block text-sm font-semibold text-gray-700">Ảnh nền</label>
                        <input type="file" wire:model="newBackground" accept="image/png,image/jpeg,image/webp" class="mt-3 block w-full text-sm">
                        <div class="mt-3 flex items-center justify-between gap-3">
                            @if($newBackground)
                                <img src="{{ $newBackground->temporaryUrl() }}" class="h-14 w-24 rounded-lg object-cover" alt="Preview ảnh nền">
                            @elseif($settings['background_url'] ?? null)
                                <img src="{{ $settings['background_url'] }}" class="h-14 w-24 rounded-lg object-cover" alt="Ảnh nền hiện tại">
                            @endif
                            <button type="button" wire:click="removeAsset('background')" class="text-xs font-semibold text-red-600">Xóa ảnh nền</button>
                        </div>
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-xl border border-gray-200 p-4">
                    <input type="checkbox" wire:model.live="settings.show_google" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span><span class="block text-sm font-semibold text-gray-800">Hiển thị Google Workspace</span><span class="block text-xs text-gray-500">Ẩn/hiện nút Google ở giao diện đăng nhập.</span></span>
                </label>
            </fieldset>

            <div class="flex justify-end border-t pt-5">
                <button type="submit" @disabled(!$canUpdate) wire:loading.attr="disabled" wire:target="save,newLogo,newBackground" class="h-11 rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">Lưu giao diện đăng nhập</span>
                    <span wire:loading wire:target="save">Đang lưu...</span>
                </button>
            </div>
        </form>

        <div class="xl:sticky xl:top-6 xl:self-start">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Live Preview</h3>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500">{{ $target === 'admin' ? '/admin/login' : '/login' }}</span>
            </div>

            @php
                $previewBackground = $newBackground ? $newBackground->temporaryUrl() : ($settings['background_url'] ?? null);
                $previewLogo = $newLogo ? $newLogo->temporaryUrl() : ($settings['logo_url'] ?? null);
                $theme = $settings['theme'] ?? 'classic-card';
                $primary = $settings['primary_color'] ?? '#0f172a';
                $opacity = ((int) ($settings['overlay_opacity'] ?? 55)) / 100;
            @endphp

            <div class="relative min-h-[520px] overflow-hidden rounded-2xl border border-gray-200 bg-slate-100 shadow-inner" @if($previewBackground) style="background-image: url('{{ $previewBackground }}'); background-size: cover; background-position: center;" @endif>
                @if($previewBackground)<div class="absolute inset-0 bg-black" style="opacity: {{ $opacity }}"></div>@endif
                <div class="relative z-10 flex min-h-[520px] {{ $theme === 'split-brand' ? 'items-stretch' : 'items-center justify-center' }} p-5">
                    @if($theme === 'split-brand')
                        <div class="hidden w-1/2 flex-col justify-end p-6 text-white sm:flex">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em]">{{ $settings['title_line_1'] ?? '' }}</p>
                            <p class="mt-2 text-2xl font-bold">{{ $settings['title_line_2'] ?? '' }}</p>
                            <p class="mt-2 text-sm text-white/80">{{ $settings['description'] ?? '' }}</p>
                        </div>
                    @endif

                    <div class="{{ $theme === 'minimal' ? 'bg-transparent shadow-none' : ($theme === 'hero-overlay' ? 'bg-white/90 backdrop-blur' : 'bg-white') }} {{ $theme === 'split-brand' ? 'ml-auto w-full sm:w-1/2' : 'w-full max-w-sm' }} rounded-2xl p-6 shadow-xl">
                        <div class="text-center">
                            @if($previewLogo)<img src="{{ $previewLogo }}" class="mx-auto h-16 w-16 object-contain" alt="Logo preview">@endif
                            @if($theme !== 'split-brand')
                                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $settings['title_line_1'] ?? '' }}</p>
                                <p class="mt-1 font-bold text-gray-900">{{ $settings['title_line_2'] ?? '' }}</p>
                                <p class="mt-2 text-xs text-gray-500">{{ $settings['description'] ?? '' }}</p>
                            @endif
                        </div>
                        <div class="mt-5 space-y-3">
                            <div class="h-10 rounded-lg border border-gray-200 bg-white"></div>
                            <div class="h-10 rounded-lg border border-gray-200 bg-white"></div>
                            <div class="h-10 rounded-lg" style="background-color: {{ $primary }}"></div>
                            @if($settings['show_google'] ?? true)<div class="h-10 rounded-lg border border-gray-200 bg-white"></div>@endif
                        </div>
                        @if($settings['footer'] ?? '')<p class="mt-5 text-center text-[10px] text-gray-400">{{ $settings['footer'] }}</p>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
