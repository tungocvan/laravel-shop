<div class="p-6 bg-white">
    <div class="flex items-center justify-between mb-8 pb-4 border-b">
        <div><h3 class="text-xl font-bold text-gray-800">Cấu hình Ví MoMo</h3>@unless($canUpdate)<p class="mt-1 text-xs font-bold text-amber-700">Tài khoản hiện tại chỉ có quyền xem.</p>@endunless</div>
        <span class="text-xs text-gray-500">{{ $statusMessage }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2"><label class="block text-sm font-bold mb-2">MoMo API Endpoint (HTTPS momo.vn)</label><input type="url" wire:model="form.MOMO_ENDPOINT" @disabled(!$canUpdate) class="w-full px-4 py-3 rounded-xl border">@error('form.MOMO_ENDPOINT')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="block text-sm font-bold mb-2">Partner Code</label><input type="text" wire:model="form.MOMO_PARTNER_CODE" @disabled(!$canUpdate) class="w-full px-4 py-3 rounded-xl border">@error('form.MOMO_PARTNER_CODE')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="block text-sm font-bold mb-2">Access Key mới</label><input type="password" wire:model="form.MOMO_ACCESS_KEY" placeholder="Để trống để giữ Access Key hiện tại" @disabled(!$canUpdate) class="w-full px-4 py-3 rounded-xl border">@error('form.MOMO_ACCESS_KEY')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div class="md:col-span-2"><label class="block text-sm font-bold mb-2">Secret Key mới</label><input type="password" wire:model="form.MOMO_SECRET_KEY" placeholder="Để trống để giữ Secret Key hiện tại" @disabled(!$canUpdate) class="w-full px-4 py-3 rounded-xl border">@error('form.MOMO_SECRET_KEY')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
    </div>

    <div class="mt-10 flex gap-4">
        <button wire:click="testEndpoint" wire:loading.attr="disabled" wire:target="testEndpoint" @disabled(!$canUpdate) class="px-6 py-3 bg-gray-100 font-bold rounded-xl disabled:opacity-50">Test Connection</button>
        <button wire:click="save" wire:confirm="Lưu thay đổi cấu hình MoMo vào .env?" wire:loading.attr="disabled" wire:target="save" @disabled(!$canUpdate) class="px-10 py-3 bg-pink-600 text-white font-bold rounded-xl disabled:opacity-50">Lưu cấu hình MoMo</button>
    </div>
</div>
