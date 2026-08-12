<div class="space-y-8">
    @unless($canUpdate)<p class="text-xs font-bold text-amber-700">Tài khoản hiện tại chỉ có quyền xem.</p>@endunless
    <div>
        <label class="block text-sm font-medium text-gray-900">Logo Website</label>
        <div class="mt-3 flex items-center gap-6">
            <div>@if($new_logo)<img src="{{ $new_logo->temporaryUrl() }}" class="h-24 w-auto object-contain rounded-xl border p-2">@elseif($site_logo)<img src="{{ asset('storage/'.$site_logo) }}" class="h-24 w-auto object-contain rounded-xl border p-2">@else<div class="h-24 w-24 flex items-center justify-center text-xs text-gray-400 border border-dashed rounded-xl">No Logo</div>@endif</div>
            <div class="space-y-2">
                <label class="h-12 inline-flex items-center px-5 cursor-pointer rounded-xl border text-sm @unless($canUpdate) opacity-50 pointer-events-none @endunless">Chọn ảnh mới<input type="file" wire:model="new_logo" class="hidden" accept="image/png,image/jpeg" @disabled(!$canUpdate)></label>
                <div wire:loading wire:target="new_logo" class="text-xs text-indigo-600">Đang tải ảnh...</div>
                @error('new_logo')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                @if($site_logo)<button type="button" wire:click="remove('logo')" wire:confirm="Xóa logo hiện tại?" wire:loading.attr="disabled" wire:target="remove" @disabled(!$canUpdate) class="block text-xs text-red-500 hover:underline disabled:opacity-50">Xóa logo</button>@endif
                <p class="text-xs text-gray-500">PNG/JPG, tối đa 2 MB. Nền trong suốt khuyến nghị.</p>
            </div>
        </div>
    </div>
    <div class="border-t"></div>
    <div>
        <label class="block text-sm font-medium text-gray-900">Favicon (icon tab trình duyệt)</label>
        <div class="mt-3 flex items-center gap-6">
            <div>@if($new_favicon && strtolower($new_favicon->getClientOriginalExtension()) !== 'ico')<img src="{{ $new_favicon->temporaryUrl() }}" class="h-14 w-14 object-contain rounded-lg border p-1">@elseif($site_favicon)<img src="{{ asset('storage/'.$site_favicon) }}" class="h-14 w-14 object-contain rounded-lg border p-1">@else<div class="h-14 w-14 flex items-center justify-center text-xs text-gray-400 border border-dashed rounded-lg">No Icon</div>@endif</div>
            <div class="space-y-2">
                <label class="h-12 inline-flex items-center px-5 cursor-pointer rounded-xl border text-sm @unless($canUpdate) opacity-50 pointer-events-none @endunless">Chọn icon<input type="file" wire:model="new_favicon" class="hidden" accept=".png,.ico,image/png,image/x-icon" @disabled(!$canUpdate)></label>
                <div wire:loading wire:target="new_favicon" class="text-xs text-indigo-600">Đang tải icon...</div>
                @error('new_favicon')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                @if($site_favicon)<button type="button" wire:click="remove('favicon')" wire:confirm="Xóa favicon hiện tại?" wire:loading.attr="disabled" wire:target="remove" @disabled(!$canUpdate) class="block text-xs text-red-500 hover:underline disabled:opacity-50">Xóa icon</button>@endif
                <p class="text-xs text-gray-500">PNG/ICO, tối đa 1 MB.</p>
            </div>
        </div>
    </div>
    <div class="pt-6 border-t flex justify-end"><button wire:click="save" wire:loading.attr="disabled" wire:target="save" @disabled(!$canUpdate) class="h-12 px-6 bg-indigo-600 text-white rounded-xl text-sm font-semibold disabled:opacity-50"><span wire:loading.remove wire:target="save">Lưu thay đổi</span><span wire:loading wire:target="save">Đang lưu...</span></button></div>
</div>
