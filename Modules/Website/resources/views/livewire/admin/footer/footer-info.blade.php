<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
    @php
        $fieldClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100';
        $checkClass = 'h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500';
    @endphp

    <form wire:submit.prevent="save" class="space-y-8">
        <div>
            <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4">Thông tin thương hiệu</h4>

            <div class="mb-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div><div class="font-semibold text-gray-900">Logo Brand Footer</div><p class="mt-1 text-sm text-gray-500">Nếu chưa upload logo riêng, Footer tự động dùng logo Website mặc định.</p></div>
                    @if($current_brand_logo)<button type="button" wire:click="removeBrandLogo" class="self-start rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Xóa logo Footer</button>@endif
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Logo đang sử dụng</div>
                        @php($activeLogo = $current_brand_logo ?: $fallback_site_logo)
                        @if($activeLogo)<img src="{{ str_starts_with($activeLogo, 'http') ? $activeLogo : asset('storage/'.$activeLogo) }}" alt="Footer logo" class="max-h-16 max-w-full object-contain"><div class="mt-2 text-xs text-gray-500">{{ $current_brand_logo ? 'Logo Footer riêng' : 'Fallback từ site_logo' }}</div>@else<div class="text-sm text-gray-400">Chưa có logo. Frontend sẽ hiển thị ký tự Brand.</div>@endif
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <label class="block text-sm font-semibold text-gray-700">Upload logo Footer mới</label>
                        <input type="file" wire:model="brand_logo_upload" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-3 text-sm text-gray-600 hover:border-blue-400">
                        <p class="mt-2 text-xs text-gray-400">JPG, PNG hoặc WebP · tối đa 3MB.</p>
                        @error('brand_logo_upload')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                        @if($brand_logo_upload)<div class="mt-3"><div class="mb-1 text-xs font-medium text-gray-500">Preview file mới</div><img src="{{ $brand_logo_upload->temporaryUrl() }}" alt="Preview Footer logo" class="max-h-16 max-w-full object-contain"></div>@endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div><label class="block text-sm font-semibold text-gray-700">Tên Brand Footer</label><input type="text" wire:model="brand_name" placeholder="{{ $fallback_site_name ?: 'Tên Website' }}" class="{{ $fieldClass }}"><p class="mt-1 text-xs text-gray-500">Nếu để trống, frontend tự động dùng <code>site_name</code>.</p></div>
                <div><label class="block text-sm font-semibold text-gray-700">Mô tả ngắn</label><textarea wire:model="brand_description" rows="3" class="{{ $fieldClass }}"></textarea></div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><label class="block text-sm font-semibold text-gray-700">Địa chỉ</label><input type="text" wire:model="address" class="{{ $fieldClass }}"></div>
                    <div><label class="block text-sm font-semibold text-gray-700">Email</label><input type="text" wire:model="email" class="{{ $fieldClass }}"></div>
                    <div><label class="block text-sm font-semibold text-gray-700">Hotline</label><input type="text" wire:model="phone" class="{{ $fieldClass }}"></div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
            <div class="mb-5"><h4 class="font-bold text-gray-900">Tải ứng dụng</h4><p class="mt-1 text-sm text-gray-500">Quản trị nội dung hiển thị ở component App Install của Footer.</p></div>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="block text-sm font-semibold text-gray-700">Tiêu đề khu vực</label><input type="text" wire:model="app_title" class="{{ $fieldClass }}"></div>
                <div><label class="block text-sm font-semibold text-gray-700">Tiêu đề nút PWA</label><input type="text" wire:model="app_button_title" class="{{ $fieldClass }}"></div>
                <div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700">Mô tả khu vực</label><textarea wire:model="app_description" rows="3" class="{{ $fieldClass }}"></textarea></div>
                <div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700">Mô tả nút PWA</label><input type="text" wire:model="app_button_subtitle" class="{{ $fieldClass }}"></div>
                <div><label class="block text-sm font-semibold text-gray-700">App Store URL</label><input type="text" wire:model="appstore_url" placeholder="https://apps.apple.com/..." class="{{ $fieldClass }}"></div>
                <div><label class="block text-sm font-semibold text-gray-700">Google Play URL</label><input type="text" wire:model="playstore_url" placeholder="https://play.google.com/..." class="{{ $fieldClass }}"></div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
            <div class="mb-5"><h4 class="font-bold text-gray-900">Footer Bottom</h4><p class="mt-1 text-sm text-gray-500">Quản trị hàng cuối Footer: copyright, legal links và trust/payment badges.</p></div>

            <div><label class="block text-sm font-semibold text-gray-700">Copyright Text</label><input type="text" wire:model="copyright" class="{{ $fieldClass }}"></div>

            <div class="mt-7 rounded-xl border border-gray-200 bg-white p-4">
                <div class="mb-4 flex items-center justify-between gap-3"><div><div class="font-semibold text-gray-900">Legal Links</div><div class="text-xs text-gray-500">Privacy, Terms, Cookie hoặc các link pháp lý khác.</div></div><button type="button" wire:click="addLegalLink" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">+ Thêm link</button></div>
                <div class="space-y-3">
                    @forelse($legal_links as $index => $legal)
                        <div wire:key="footer-legal-{{ $index }}" class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-12 md:items-end">
                            <div class="md:col-span-3"><label class="block text-xs font-semibold text-gray-600">Tên link</label><input type="text" wire:model="legal_links.{{ $index }}.label" placeholder="VD: Chính sách bảo mật" class="{{ $fieldClass }}"></div>
                            <div class="md:col-span-5"><label class="block text-xs font-semibold text-gray-600">URL</label><input type="text" wire:model="legal_links.{{ $index }}.url" placeholder="/privacy" class="{{ $fieldClass }}"></div>
                            <label class="flex items-center gap-2 pb-3 text-xs font-medium text-gray-600 md:col-span-2"><input type="checkbox" wire:model="legal_links.{{ $index }}.new_tab" class="{{ $checkClass }}"> Mở tab mới</label>
                            <label class="flex items-center gap-2 pb-3 text-xs font-medium text-gray-600 md:col-span-1"><input type="checkbox" wire:model="legal_links.{{ $index }}.enabled" class="{{ $checkClass }}"> Bật</label>
                            <button type="button" wire:click="removeLegalLink({{ $index }})" class="mb-1 rounded-lg border border-red-200 bg-white px-2 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 md:col-span-1">Xóa</button>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center text-sm text-gray-500">Chưa có Legal Link. Nhấn <strong>+ Thêm link</strong> để tạo.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-5 rounded-xl border border-gray-200 bg-white p-4">
                <div class="mb-4 flex items-center justify-between gap-3"><div><div class="font-semibold text-gray-900">Trust / Payment Badges</div><div class="text-xs text-gray-500">Logo thanh toán, chứng nhận hoặc đối tác hiển thị phía phải Footer Bottom.</div></div><button type="button" wire:click="addTrustBadge" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">+ Thêm badge</button></div>
                <div class="space-y-3">
                    @forelse($trust_badges as $index => $badge)
                        <div wire:key="footer-badge-{{ $index }}" class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-12 md:items-end">
                            <div class="md:col-span-2"><label class="block text-xs font-semibold text-gray-600">Tên badge</label><input type="text" wire:model="trust_badges.{{ $index }}.label" placeholder="VD: Visa" class="{{ $fieldClass }}"></div>
                            <div class="md:col-span-5"><label class="block text-xs font-semibold text-gray-600">Image URL</label><input type="text" wire:model="trust_badges.{{ $index }}.image_url" placeholder="https://.../visa.svg" class="{{ $fieldClass }}"></div>
                            <div class="md:col-span-3"><label class="block text-xs font-semibold text-gray-600">Link khi click</label><input type="text" wire:model="trust_badges.{{ $index }}.url" placeholder="Tùy chọn" class="{{ $fieldClass }}"></div>
                            <label class="flex items-center gap-2 pb-3 text-xs font-medium text-gray-600 md:col-span-1"><input type="checkbox" wire:model="trust_badges.{{ $index }}.enabled" class="{{ $checkClass }}"> Bật</label>
                            <button type="button" wire:click="removeTrustBadge({{ $index }})" class="mb-1 rounded-lg border border-red-200 bg-white px-2 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 md:col-span-1">Xóa</button>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center text-sm text-gray-500">Chưa có badge. Nhấn <strong>+ Thêm badge</strong> để tạo.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="sticky bottom-4 z-10 flex justify-end rounded-xl border border-gray-200 bg-white/95 p-3 shadow-lg backdrop-blur">
            <button type="submit" wire:loading.attr="disabled" wire:target="save,brand_logo_upload" class="rounded-lg bg-blue-600 px-6 py-2.5 font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50">Lưu thông tin Footer</button>
        </div>
    </form>
</div>
