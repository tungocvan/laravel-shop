<div class="space-y-8">
    @unless($canUpdate)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Bạn đang ở chế độ chỉ xem. Cần quyền <code>system.settings.update</code> để lưu thay đổi.
        </div>
    @endunless

    <div>
        <h3 class="mb-4 text-sm font-semibold text-gray-900">SEO mặc định</h3>

        <div class="space-y-6">
            <div>
                <label class="text-sm font-medium text-gray-700">Meta Title</label>
                <input type="text" wire:model.defer="settings.seo_title" @disabled(! $canUpdate)
                       placeholder="Nhập tiêu đề SEO..."
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:bg-gray-100" />
                @error('settings.seo_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500">Khuyến nghị: 50 - 60 ký tự</p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Meta Description</label>
                <textarea rows="4" wire:model.defer="settings.seo_description" @disabled(! $canUpdate)
                          placeholder="Mô tả ngắn gọn nội dung website..."
                          class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 disabled:bg-gray-100"></textarea>
                @error('settings.seo_description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500">Plain text. Khuyến nghị: 120 - 160 ký tự.</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border bg-gray-50 p-4">
        <p class="mb-2 text-xs text-gray-500">Preview Google:</p>
        <div>
            <p class="line-clamp-1 text-sm font-medium text-blue-600">{{ $settings['seo_title'] ?: 'Tiêu đề website của bạn' }}</p>
            <p class="text-xs text-green-700">{{ config('app.url') }}</p>
            <p class="line-clamp-2 text-sm text-gray-600">{{ $settings['seo_description'] ?: 'Mô tả website sẽ hiển thị tại đây...' }}</p>
        </div>
    </div>

    <div class="border-t pt-6">
        <h3 class="mb-4 text-sm font-semibold text-gray-900">Mạng xã hội</h3>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-gray-700">Facebook</label>
                <input type="url" wire:model.defer="settings.social_facebook" @disabled(! $canUpdate)
                       placeholder="https://facebook.com/..."
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 disabled:bg-gray-100" />
                @error('settings.social_facebook') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Zalo</label>
                <input type="text" wire:model.defer="settings.social_zalo" @disabled(! $canUpdate)
                       placeholder="SĐT hoặc link OA"
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 disabled:bg-gray-100" />
                @error('settings.social_zalo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="border-t pt-6">
        <div class="mb-2 flex items-center justify-between gap-3">
            <label class="text-sm font-medium text-gray-900">Header Scripts</label>
            <span class="rounded bg-red-50 px-2 py-1 text-xs font-semibold text-red-700">Trusted production code</span>
        </div>
        <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Nội dung này được render trực tiếp trong <code>&lt;head&gt;</code> của Website public. Chỉ lưu mã đã được kiểm tra như Google Analytics/Pixel.
        </div>
        <textarea wire:model.defer="settings.header_script" rows="7" @disabled(! $canUpdate)
                  placeholder="<script>...</script>"
                  class="w-full rounded-xl border border-gray-300 px-4 py-3 font-mono text-sm disabled:bg-gray-100"></textarea>
        @error('settings.header_script') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        <p class="mt-1 text-xs text-gray-500">Tối đa 20.000 ký tự. Nội dung script không được ghi vào application log.</p>
    </div>

    @if($canUpdate)
        <div class="flex justify-end border-t pt-6">
            <button type="button" wire:click="save"
                    wire:confirm="Header Script có thể thực thi mã trên toàn bộ Website public. Bạn xác nhận lưu cấu hình SEO và Header Script hiện tại?"
                    wire:loading.attr="disabled" wire:target="save"
                    class="flex h-[48px] items-center rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Lưu SEO</span>
                <span wire:loading wire:target="save">Đang lưu...</span>
            </button>
        </div>
    @endif
</div>
