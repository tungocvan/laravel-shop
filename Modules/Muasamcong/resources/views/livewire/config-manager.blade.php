<div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
    @if (session('error'))
        <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div>
            <label for="msc-origin" class="text-sm font-medium text-gray-700">MUASAMCONG_ORIGIN</label>
            <input id="msc-origin" type="url" wire:model.live="form.origin" autocomplete="url"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            @error('form.origin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="msc-user-agent" class="text-sm font-medium text-gray-700">MUASAMCONG_USER_AGENT</label>
            <input id="msc-user-agent" type="text" wire:model.live="form.user_agent" autocomplete="off"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            @error('form.user_agent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="msc-timeout" class="text-sm font-medium text-gray-700">MUASAMCONG_TIMEOUT (giây)</label>
            <input id="msc-timeout" type="number" min="1" max="120" wire:model.live="form.timeout"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            @error('form.timeout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="msc-page-size" class="text-sm font-medium text-gray-700">MUASAMCONG_PAGE_SIZE</label>
            <input id="msc-page-size" type="number" min="1" max="100" wire:model.live="form.page_size"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            @error('form.page_size') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 md:col-span-2">
            <input type="checkbox" wire:model.live="form.verify_ssl"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span>
                <span class="block text-sm font-medium text-gray-700">MUASAMCONG_VERIFY_SSL</span>
                <span class="block text-xs text-gray-500">Nên luôn bật trong môi trường production.</span>
            </span>
        </label>

        <div class="md:col-span-2">
            <label for="msc-pricing-endpoint" class="text-sm font-medium text-gray-700">MUASAMCONG_PRICING_ENDPOINT</label>
            <input id="msc-pricing-endpoint" type="url" wire:model.live="form.pricing_endpoint" autocomplete="off"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            @error('form.pricing_endpoint') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label for="msc-contractor-endpoint" class="text-sm font-medium text-gray-700">MUASAMCONG_CONTRACTOR_ENDPOINT</label>
            <input id="msc-contractor-endpoint" type="url" wire:model.live="form.contractor_endpoint" autocomplete="off"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            @error('form.contractor_endpoint') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="msc-portal-referer" class="text-sm font-medium text-gray-700">MUASAMCONG_PORTAL_REFERER</label>
            <input id="msc-portal-referer" type="url" wire:model.live="form.portal_referer" autocomplete="off"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            @error('form.portal_referer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="msc-pricing-referer" class="text-sm font-medium text-gray-700">MUASAMCONG_PRICING_REFERER</label>
            <input id="msc-pricing-referer" type="url" wire:model.live="form.pricing_referer" autocomplete="off"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            @error('form.pricing_referer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="msc-smart-token" class="text-sm font-medium text-gray-700">MUASAMCONG_SMART_TOKEN</label>
            <input id="msc-smart-token" type="password" wire:model.live="form.smart_token"
                autocomplete="new-password" placeholder="Để trống nếu không thay đổi"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            <p class="mt-1 text-xs text-gray-500">
                {{ $hasSmartToken ? 'Đã cấu hình token trên server.' : 'Chưa cấu hình token.' }}
                Giá trị hiện tại không được tải xuống trình duyệt.
            </p>
            @error('form.smart_token') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="msc-session-cookie" class="text-sm font-medium text-gray-700">MUASAMCONG_SESSION_COOKIE</label>
            <input id="msc-session-cookie" type="password" wire:model.live="form.session_cookie"
                autocomplete="new-password" placeholder="Để trống nếu không thay đổi"
                class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            <p class="mt-1 text-xs text-gray-500">
                {{ $hasSessionCookie ? 'Đã cấu hình cookie trên server.' : 'Chưa cấu hình cookie.' }}
                Giá trị hiện tại không được tải xuống trình duyệt.
            </p>
            @error('form.session_cookie') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if ($tokenTestMessage !== '')
            <div role="status"
                class="rounded-xl border px-4 py-3 text-sm md:col-span-2 {{ $tokenTestStatus === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                {{ $tokenTestMessage }}
            </div>
        @endif

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end md:col-span-2">
            <button type="button" wire:click="testToken" wire:loading.attr="disabled"
                wire:target="testToken,save"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-white px-5 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="testToken">Kiểm tra token</span>
                <span wire:loading wire:target="testToken">Đang kiểm tra…</span>
            </button>

            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Lưu cấu hình</span>
                <span wire:loading wire:target="save">Đang lưu…</span>
            </button>
        </div>
    </form>
</div>
