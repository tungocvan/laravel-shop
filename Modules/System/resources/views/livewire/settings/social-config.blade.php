<div class="p-6 bg-white">
    <div class="mb-8 border-b pb-4"><h3 class="text-xl font-bold text-gray-800 uppercase">SEO & Social Integration</h3>@unless($canUpdate)<p class="mt-1 text-xs font-bold text-amber-700">Tài khoản hiện tại chỉ có quyền xem.</p>@endunless</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
        <div class="space-y-3">
            <h4 class="font-bold text-sm">Google Authentication</h4>
            <input type="text" wire:model="form.GOOGLE_CLIENT_ID" placeholder="Google Client ID" @disabled(!$canUpdate) class="w-full border rounded px-3 py-2">@error('form.GOOGLE_CLIENT_ID')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <input type="password" wire:model="form.GOOGLE_CLIENT_SECRET" placeholder="{{ ($configuredSecrets['google'] ?? false) ? 'Đã cấu hình — nhập để thay thế' : 'Google Client Secret' }}" @disabled(!$canUpdate) class="w-full border rounded px-3 py-2">@error('form.GOOGLE_CLIENT_SECRET')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <input type="url" wire:model="form.GOOGLE_REDIRECT" placeholder="Google Redirect URL" @disabled(!$canUpdate) class="w-full border rounded px-3 py-2">@error('form.GOOGLE_REDIRECT')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="space-y-3">
            <h4 class="font-bold text-sm">Facebook Authentication</h4>
            <input type="text" wire:model="form.FACEBOOK_CLIENT_ID" placeholder="Facebook App ID" @disabled(!$canUpdate) class="w-full border rounded px-3 py-2">@error('form.FACEBOOK_CLIENT_ID')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <input type="password" wire:model="form.FACEBOOK_CLIENT_SECRET" placeholder="{{ ($configuredSecrets['facebook'] ?? false) ? 'Đã cấu hình — nhập để thay thế' : 'Facebook App Secret' }}" @disabled(!$canUpdate) class="w-full border rounded px-3 py-2">@error('form.FACEBOOK_CLIENT_SECRET')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <input type="url" wire:model="form.FACEBOOK_REDIRECT_URI" placeholder="Facebook Redirect URL" @disabled(!$canUpdate) class="w-full border rounded px-3 py-2">@error('form.FACEBOOK_REDIRECT_URI')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t">
            <div><label class="text-xs font-bold">TinyMCE API Key</label><input type="password" wire:model="form.TINYMCE_API_KEY" placeholder="{{ ($configuredSecrets['tinymce'] ?? false) ? 'Đã cấu hình — nhập để thay thế' : 'TinyMCE API Key' }}" @disabled(!$canUpdate) class="w-full border rounded px-3 py-2">@error('form.TINYMCE_API_KEY')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="text-xs font-bold">Google Analytics ID (GA4)</label><input type="text" wire:model="form.GOOGLE_ANALYTICS_ID" placeholder="G-XXXXXXXXXX" @disabled(!$canUpdate) class="w-full border rounded px-3 py-2">@error('form.GOOGLE_ANALYTICS_ID')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
        </div>
    </div>

    <div class="mt-10 flex justify-end"><button wire:click="save" wire:confirm="Lưu thay đổi OAuth/Social vào .env?" wire:loading.attr="disabled" wire:target="save" @disabled(!$canUpdate) class="px-10 py-3 bg-indigo-600 text-white font-black text-xs rounded-xl disabled:opacity-50">CẬP NHẬT CẤU HÌNH SOCIAL</button></div>
</div>
