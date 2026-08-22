<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
    <form wire:submit.prevent="save" class="space-y-8">
        <div>
            <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4">Thông tin thương hiệu</h4>

            <div class="mb-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="font-semibold text-gray-900">Logo Brand Footer</div>
                        <p class="mt-1 text-sm text-gray-500">Nếu chưa upload logo riêng, Footer tự động dùng logo Website mặc định.</p>
                    </div>
                    @if($current_brand_logo)
                        <button type="button" wire:click="removeBrandLogo" class="self-start rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Xóa logo Footer</button>
                    @endif
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Logo đang sử dụng</div>
                        @php($activeLogo = $current_brand_logo ?: $fallback_site_logo)
                        @if($activeLogo)
                            <img src="{{ str_starts_with($activeLogo, 'http') ? $activeLogo : asset('storage/'.$activeLogo) }}" alt="Footer logo" class="max-h-16 max-w-full object-contain">
                            <div class="mt-2 text-xs text-gray-500">{{ $current_brand_logo ? 'Logo Footer riêng' : 'Fallback từ site_logo' }}</div>
                        @else
                            <div class="text-sm text-gray-400">Chưa có logo. Frontend sẽ hiển thị ký tự Brand.</div>
                        @endif
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <label class="block text-sm font-medium text-gray-700">Upload logo Footer mới</label>
                        <input type="file" wire:model="brand_logo_upload" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-gray-600">
                        <p class="mt-2 text-xs text-gray-400">JPG, PNG hoặc WebP · tối đa 3MB.</p>
                        @error('brand_logo_upload')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror

                        @if($brand_logo_upload)
                            <div class="mt-3">
                                <div class="mb-1 text-xs font-medium text-gray-500">Preview file mới</div>
                                <img src="{{ $brand_logo_upload->temporaryUrl() }}" alt="Preview Footer logo" class="max-h-16 max-w-full object-contain">
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tên Brand Footer</label>
                    <input type="text" wire:model="brand_name" placeholder="{{ $fallback_site_name ?: 'Tên Website' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Nếu để trống, frontend tự động dùng <code>site_name</code>.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mô tả ngắn</label>
                    <textarea wire:model="brand_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700">Địa chỉ</label><input type="text" wire:model="address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Email</label><input type="text" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">Hotline</label><input type="text" wire:model="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
            <div class="mb-4">
                <h4 class="font-bold text-gray-900">Tải ứng dụng</h4>
                <p class="mt-1 text-sm text-gray-500">Quản trị nội dung hiển thị ở component App Install của Footer.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="block text-sm font-medium text-gray-700">Tiêu đề khu vực</label><input type="text" wire:model="app_title" class="mt-1 block w-full rounded-md border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700">Tiêu đề nút PWA</label><input type="text" wire:model="app_button_title" class="mt-1 block w-full rounded-md border-gray-300"></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Mô tả khu vực</label><textarea wire:model="app_description" rows="2" class="mt-1 block w-full rounded-md border-gray-300"></textarea></div>
                <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700">Mô tả nút PWA</label><input type="text" wire:model="app_button_subtitle" class="mt-1 block w-full rounded-md border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700">App Store URL</label><input type="text" wire:model="appstore_url" class="mt-1 block w-full rounded-md border-gray-300"></div>
                <div><label class="block text-sm font-medium text-gray-700">Google Play URL</label><input type="text" wire:model="playstore_url" class="mt-1 block w-full rounded-md border-gray-300"></div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
            <div class="mb-5">
                <h4 class="font-bold text-gray-900">Footer Bottom</h4>
                <p class="mt-1 text-sm text-gray-500">Quản trị hàng cuối Footer: copyright, legal links và trust/payment badges.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Copyright Text</label>
                <input type="text" wire:model="copyright" class="mt-1 block w-full rounded-md border-gray-300">
            </div>

            <div class="mt-6">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div><div class="font-semibold text-gray-900">Legal Links</div><div class="text-xs text-gray-500">Privacy, Terms, Cookie hoặc các link pháp lý khác.</div></div>
                    <button type="button" wire:click="addLegalLink" class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">+ Thêm link</button>
                </div>
                <div class="space-y-3">
                    @foreach($legal_links as $index => $legal)
                        <div wire:key="footer-legal-{{ $index }}" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-3 md:grid-cols-12 md:items-center">
                            <input type="text" wire:model="legal_links.{{ $index }}.label" placeholder="Tên link" class="rounded-md border-gray-300 md:col-span-3">
                            <input type="text" wire:model="legal_links.{{ $index }}.url" placeholder="URL" class="rounded-md border-gray-300 md:col-span-5">
                            <label class="inline-flex items-center gap-2 text-xs text-gray-600 md:col-span-2"><input type="checkbox" wire:model="legal_links.{{ $index }}.new_tab" class="rounded"> Tab mới</label>
                            <label class="inline-flex items-center gap-2 text-xs text-gray-600 md:col-span-1"><input type="checkbox" wire:model="legal_links.{{ $index }}.enabled" class="rounded"> Bật</label>
                            <button type="button" wire:click="removeLegalLink({{ $index }})" class="text-sm font-semibold text-red-500 md:col-span-1">Xóa</button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div><div class="font-semibold text-gray-900">Trust / Payment Badges</div><div class="text-xs text-gray-500">Logo thanh toán, chứng nhận hoặc đối tác hiển thị phía phải Footer Bottom.</div></div>
                    <button type="button" wire:click="addTrustBadge" class="rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50">+ Thêm badge</button>
                </div>
                <div class="space-y-3">
                    @foreach($trust_badges as $index => $badge)
                        <div wire:key="footer-badge-{{ $index }}" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-3 md:grid-cols-12 md:items-center">
                            <input type="text" wire:model="trust_badges.{{ $index }}.label" placeholder="Tên badge" class="rounded-md border-gray-300 md:col-span-2">
                            <input type="text" wire:model="trust_badges.{{ $index }}.image_url" placeholder="Image URL" class="rounded-md border-gray-300 md:col-span-5">
                            <input type="text" wire:model="trust_badges.{{ $index }}.url" placeholder="Link khi click (tùy chọn)" class="rounded-md border-gray-300 md:col-span-3">
                            <label class="inline-flex items-center gap-2 text-xs text-gray-600 md:col-span-1"><input type="checkbox" wire:model="trust_badges.{{ $index }}.enabled" class="rounded"> Bật</label>
                            <button type="button" wire:click="removeTrustBadge({{ $index }})" class="text-sm font-semibold text-red-500 md:col-span-1">Xóa</button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="pt-2 text-right">
            <button type="submit" wire:loading.attr="disabled" wire:target="save,brand_logo_upload" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition shadow-sm font-bold disabled:opacity-50">Lưu thông tin</button>
        </div>
    </form>
</div>
