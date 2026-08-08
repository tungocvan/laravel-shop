<form wire:submit="save" class="space-y-6 animate-fadeIn">
    <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

        <div class="sm:col-span-4">
            <label class="block text-sm font-medium leading-6 text-gray-900">Tên cửa hàng (Site Name)</label>
            <div class="mt-2">
                <input type="text" wire:model="settings.site_name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                @error('settings.site_name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="sm:col-span-3">
            <label class="block text-sm font-medium leading-6 text-gray-900">Hotline</label>
            <div class="mt-2">
                <input type="text" wire:model="settings.site_hotline" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>
        </div>

        <div class="sm:col-span-3">
            <label class="block text-sm font-medium leading-6 text-gray-900">Email liên hệ</label>
            <div class="mt-2">
                <input type="email" wire:model="settings.site_email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                @error('settings.site_email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="sm:col-span-6">
            <label class="block text-sm font-medium leading-6 text-gray-900">Địa chỉ kho/văn phòng</label>
            <div class="mt-2">
                <input type="text" wire:model="settings.site_address" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>
        </div>
    </div>

    <div class="flex justify-end border-t border-gray-200 pt-6">
        <button type="submit" wire:loading.attr="disabled" wire:target="save"
            class="inline-flex h-12 items-center rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
            <svg wire:loading wire:target="save" class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">Lưu cấu hình chung</span>
            <span wire:loading wire:target="save">Đang lưu...</span>
        </button>
    </div>
</form>
