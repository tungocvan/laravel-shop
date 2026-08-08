<div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    @if (session('error'))
        <div class="mb-4 rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="mb-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <details class="mb-6 rounded-xl border border-gray-200 bg-gray-50">
        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-800">
            Cấu hình kết nối GDT
        </summary>

        <form wire:submit="saveGdtConfig" class="grid grid-cols-1 gap-4 border-t border-gray-200 p-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="gdt-base-url" class="text-sm font-medium text-gray-700">GDT_API_BASE_URL</label>
                <input id="gdt-base-url" type="url" wire:model.live="gdtConfig.base_url"
                    autocomplete="url"
                    class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('gdtConfig.base_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="gdt-username" class="text-sm font-medium text-gray-700">GDT_API_USERNAME</label>
                <input id="gdt-username" type="text" wire:model.live="gdtConfig.username"
                    autocomplete="username"
                    class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('gdtConfig.username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="gdt-password" class="text-sm font-medium text-gray-700">GDT_API_PASSWORD</label>
                <input id="gdt-password" type="password" wire:model.live="gdtConfig.password"
                    autocomplete="new-password" placeholder="Để trống nếu không thay đổi"
                    class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <p class="mt-1 text-xs text-gray-500">Mật khẩu hiện tại không được tải xuống trình duyệt.</p>
                @error('gdtConfig.password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="gdt-timeout" class="text-sm font-medium text-gray-700">GDT_API_TIMEOUT (giây)</label>
                <input id="gdt-timeout" type="number" min="1" max="120" wire:model.live="gdtConfig.timeout"
                    class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('gdtConfig.timeout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="gdt-token-ttl" class="text-sm font-medium text-gray-700">GDT_TOKEN_TTL (giây)</label>
                <input id="gdt-token-ttl" type="number" min="60" max="604800" wire:model.live="gdtConfig.token_ttl"
                    class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('gdtConfig.token_ttl') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="gdt-cache-key" class="text-sm font-medium text-gray-700">GDT_TOKEN_CACHE_KEY</label>
                <input id="gdt-cache-key" type="text" wire:model.live="gdtConfig.cache_key"
                    autocomplete="off"
                    class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('gdtConfig.cache_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-3 self-end rounded-xl border border-gray-200 bg-white px-4 py-3">
                <input type="checkbox" wire:model.live="gdtConfig.verify_ssl"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm font-medium text-gray-700">GDT_API_VERIFY_SSL</span>
            </label>

            <div class="md:col-span-2 flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="saveGdtConfig"
                    class="h-11 rounded-xl bg-slate-800 px-5 text-sm font-semibold text-white hover:bg-slate-900 disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveGdtConfig">Lưu cấu hình GDT</span>
                    <span wire:loading wire:target="saveGdtConfig">Đang lưu…</span>
                </button>
            </div>
        </form>
    </details>

    @if ($authenticated)
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-medium text-emerald-700">Đã có phiên đăng nhập GDT trên server.</p>
            <button wire:click="deleteToken" wire:loading.attr="disabled"
                class="h-11 rounded-xl bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">
                Xóa phiên đăng nhập
            </button>
        </div>
    @else
        <form wire:submit="login" class="space-y-4">
            <div>
                <label class="text-sm font-medium text-gray-700">Captcha</label>
                <div class="mt-2 min-h-16 rounded-xl border border-gray-200 bg-gray-50 p-3">
                    @if ($captchaSvg)
                        {!! $captchaSvg !!}
                    @else
                        <span class="text-sm text-gray-500">Không thể tải captcha.</span>
                    @endif
                </div>
            </div>
            <div>
                <label for="cvalue" class="text-sm font-medium text-gray-700">Mã xác nhận</label>
                <input id="cvalue" type="text" wire:model.live="cvalue" autocomplete="off"
                    class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('cvalue') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled"
                class="h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="login">Đăng nhập GDT</span>
                <span wire:loading wire:target="login">Đang xác thực…</span>
            </button>
        </form>
    @endif
</div>
