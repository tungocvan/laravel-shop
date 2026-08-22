<div class="space-y-6">
    @php
        $fieldClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100';
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h3 class="text-lg font-bold text-gray-900">Thông tin chung & Topbar</h3>
            <p class="mt-1 text-sm text-gray-500">Quản trị Brand Header, thông tin liên hệ và các link điều hướng Topbar.</p>
        </div>

        <form wire:submit.prevent="save" class="space-y-6">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="font-semibold text-gray-900">Logo Brand Header</div>
                        <p class="mt-1 text-sm text-gray-500">Nếu chưa upload logo riêng, Header tự động dùng logo Website mặc định.</p>
                    </div>
                    @if($current_brand_logo)
                        <button type="button" wire:click="removeBrandLogo" class="self-start rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Xóa logo Header</button>
                    @endif
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Logo đang sử dụng</div>
                        @php($activeLogo = $current_brand_logo ?: $fallback_site_logo)
                        @if($activeLogo)
                            <img src="{{ str_starts_with($activeLogo, 'http') ? $activeLogo : asset('storage/'.$activeLogo) }}" alt="Header logo" class="max-h-16 max-w-full object-contain">
                            <div class="mt-2 text-xs text-gray-500">{{ $current_brand_logo ? 'Logo Header riêng' : 'Fallback từ site_logo' }}</div>
                        @else
                            <div class="text-sm text-gray-400">Chưa có logo. Frontend sẽ dùng Brand Name.</div>
                        @endif
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <label class="block text-sm font-semibold text-gray-700">Upload logo Header mới</label>
                        <input type="file" wire:model="brand_logo_upload" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-3 text-sm text-gray-600 hover:border-blue-400">
                        <p class="mt-2 text-xs text-gray-400">JPG, PNG hoặc WebP · tối đa 3MB.</p>
                        @error('brand_logo_upload')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                        @if($brand_logo_upload)
                            <div class="mt-3"><div class="mb-1 text-xs font-medium text-gray-500">Preview file mới</div><img src="{{ $brand_logo_upload->temporaryUrl() }}" alt="Preview Header logo" class="max-h-16 max-w-full object-contain"></div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Tên thương hiệu <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="brand_name" placeholder="{{ $fallback_site_name ?: 'Tên Website' }}" class="{{ $fieldClass }}">
                    @error('brand_name')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Hotline Topbar</label>
                    <input type="text" wire:model="hotline" class="{{ $fieldClass }}">
                    @error('hotline')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Email hỗ trợ</label>
                    <input type="email" wire:model="email" class="{{ $fieldClass }}">
                    @error('email')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Link Trợ giúp</label>
                    <input type="text" wire:model="help_url" placeholder="/help hoặc https://..." class="{{ $fieldClass }}">
                    @error('help_url')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700">Link Theo dõi đơn hàng</label>
                    <input type="text" wire:model="order_tracking_url" placeholder="/account/orders" class="{{ $fieldClass }}">
                    @error('order_tracking_url')<span class="mt-1 block text-xs text-red-500">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="sticky bottom-4 z-10 flex justify-end rounded-xl border border-gray-200 bg-white/95 p-3 shadow-lg backdrop-blur">
                <button type="submit" wire:loading.attr="disabled" wire:target="save,brand_logo_upload" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50">Lưu thông tin Header</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-amber-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-gray-900">Script nâng cao</h3>
        <p class="mt-1 text-sm text-amber-700">Cấu hình tin cậy dành cho Analytics/GTM. Chỉ tài khoản có quyền quản lý Website settings mới lưu được.</p>
        <form wire:submit.prevent="saveAdvanced" class="mt-4 space-y-4">
            <textarea wire:model="header_script" rows="8" class="{{ $fieldClass }} font-mono" placeholder="<script>...</script>"></textarea>
            @error('header_script')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
            <button type="submit" wire:loading.attr="disabled" wire:target="saveAdvanced" class="rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Lưu script nâng cao</button>
        </form>
    </div>
</div>
