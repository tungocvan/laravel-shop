<div class="space-y-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">PWA & Browser Appearance</h2>
            <p class="mt-1 text-sm text-slate-500">Thiết lập metadata hiển thị trên trình duyệt và khi Website được cài lên thiết bị. Manifest và Service Worker vẫn dùng path hệ thống an toàn.</p>
        </div>
        <button type="button" wire:click="resetAppearance" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Khôi phục mặc định</button>
    </div>

    <section class="grid gap-5 md:grid-cols-2">
        <label class="{{ $labelClass }}">Application name
            <input wire:model="appearance.application_name" maxlength="120" class="{{ $fieldClass }}" placeholder="{{ $siteName }}">
            @error('appearance.application_name')<span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="{{ $labelClass }}">Apple Web App title
            <input wire:model="appearance.apple_title" maxlength="60" class="{{ $fieldClass }}" placeholder="{{ $siteName }}">
            @error('appearance.apple_title')<span class="mt-1 block text-xs font-medium text-red-600">{{ $message }}</span>@enderror
        </label>
    </section>

    <section class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <label class="{{ $labelClass }}">Theme color
            <div class="mt-1 flex gap-2">
                <input type="color" wire:model="appearance.theme_color" class="h-11 w-14 cursor-pointer rounded-lg border border-gray-300 bg-white p-1">
                <input wire:model="appearance.theme_color" class="block min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>
        </label>
        <label class="{{ $labelClass }}">Background color
            <div class="mt-1 flex gap-2">
                <input type="color" wire:model="appearance.background_color" class="h-11 w-14 cursor-pointer rounded-lg border border-gray-300 bg-white p-1">
                <input wire:model="appearance.background_color" class="block min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>
        </label>
        <label class="{{ $labelClass }}">Apple status bar
            <select wire:model="appearance.apple_status_bar_style" class="{{ $fieldClass }}">
                <option value="default">Default</option>
                <option value="black">Black</option>
                <option value="black-translucent">Black translucent</option>
            </select>
        </label>
    </section>

    <section class="grid gap-4 md:grid-cols-2">
        <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div><div class="font-semibold text-slate-900">Manifest</div><div class="mt-1 text-xs leading-5 text-slate-500">Cho phép trình duyệt đọc manifest hệ thống tại <code>/manifest.webmanifest</code>.</div></div>
            <input type="checkbox" wire:model="appearance.manifest_enabled" class="mt-1 h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
        </label>
        <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div><div class="font-semibold text-slate-900">Service Worker</div><div class="mt-1 text-xs leading-5 text-slate-500">Bật đăng ký Service Worker hệ thống tại <code>/service-worker.js</code>.</div></div>
            <input type="checkbox" wire:model="appearance.service_worker_enabled" class="mt-1 h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
        </label>
    </section>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        <div class="font-semibold text-slate-900">Browser preview</div>
        <div class="mt-3 flex items-center gap-3">
            <div class="h-8 w-8 rounded-lg border border-slate-200" style="background: {{ $appearance['theme_color'] ?? '#0f172a' }}"></div>
            <div><div class="font-semibold text-slate-900">{{ $appearance['application_name'] ?? $siteName }}</div><div class="text-xs text-slate-500">Theme {{ $appearance['theme_color'] ?? '#0f172a' }} · Background {{ $appearance['background_color'] ?? '#ffffff' }}</div></div>
        </div>
    </div>
</div>
