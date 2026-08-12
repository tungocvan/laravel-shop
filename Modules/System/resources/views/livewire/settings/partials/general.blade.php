<form wire:submit="save" class="space-y-6 animate-fadeIn">
    @unless($canUpdate)<p class="text-xs font-bold text-amber-700">Tài khoản hiện tại chỉ có quyền xem.</p>@endunless
    <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
        <div class="sm:col-span-4"><label class="block text-sm font-medium">Tên cửa hàng (Site Name)</label><input type="text" wire:model="settings.site_name" @disabled(!$canUpdate) class="mt-2 block w-full rounded-md border py-1.5 px-3">@error('settings.site_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div class="sm:col-span-3"><label class="block text-sm font-medium">Hotline</label><input type="text" wire:model="settings.site_hotline" @disabled(!$canUpdate) class="mt-2 block w-full rounded-md border py-1.5 px-3">@error('settings.site_hotline')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div class="sm:col-span-3"><label class="block text-sm font-medium">Email liên hệ</label><input type="email" wire:model="settings.site_email" @disabled(!$canUpdate) class="mt-2 block w-full rounded-md border py-1.5 px-3">@error('settings.site_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div class="sm:col-span-6"><label class="block text-sm font-medium">Địa chỉ kho/văn phòng</label><input type="text" wire:model="settings.site_address" @disabled(!$canUpdate) class="mt-2 block w-full rounded-md border py-1.5 px-3">@error('settings.site_address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
    </div>
    <div class="flex justify-end border-t pt-6"><button type="submit" wire:loading.attr="disabled" wire:target="save" @disabled(!$canUpdate) class="h-12 rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white disabled:opacity-50"><span wire:loading.remove wire:target="save">Lưu cấu hình chung</span><span wire:loading wire:target="save">Đang lưu...</span></button></div>
</form>
