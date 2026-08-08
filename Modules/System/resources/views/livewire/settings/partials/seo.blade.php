<div class="space-y-8">

    <!-- META SEO -->
    <div>
        <h3 class="text-sm font-semibold text-gray-900 mb-4">
            SEO mặc định
        </h3>

        <div class="space-y-6">

            <!-- TITLE -->
            <div>
                <label class="text-sm font-medium text-gray-700">
                    Meta Title
                </label>

                <input type="text"
                       wire:model.defer="settings.seo_title"
                       placeholder="Nhập tiêu đề SEO..."
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3
                              focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" />

                <p class="text-xs text-gray-500 mt-1">
                    Khuyến nghị: 50 - 60 ký tự
                </p>
            </div>

            <!-- DESCRIPTION -->
            <div>
                <label class="text-sm font-medium text-gray-700">
                    Meta Description
                </label>

                <div class="mt-1" wire:key="editor-seo_description">
                    <x-editor
                        wire:model="settings.seo_description"
                        mode="full"
                        height="250px"
                    />
                </div>

                <p class="text-xs text-gray-500 mt-1">
                    Khuyến nghị: 120 - 160 ký tự
                </p>
            </div>

        </div>
    </div>

    <!-- PREVIEW GOOGLE -->
    <div class="border rounded-xl p-4 bg-gray-50">
        <p class="text-xs text-gray-500 mb-2">Preview Google:</p>

        <div>
            <p class="text-blue-600 text-sm font-medium line-clamp-1">
                {{ $settings['seo_title'] ?: 'Tiêu đề website của bạn' }}
            </p>

            <p class="text-green-700 text-xs">
                {{ config('app.url') }}
            </p>

            <p class="text-gray-600 text-sm line-clamp-2">
                {!! $settings['seo_description'] ?: 'Mô tả website sẽ hiển thị tại đây...' !!}
            </p>
        </div>
    </div>

    <!-- SOCIAL -->
    <div class="border-t pt-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">
            Mạng xã hội
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <label class="text-sm font-medium text-gray-700">
                    Facebook
                </label>

                <input type="text"
                       wire:model.defer="settings.social_facebook"
                       placeholder="https://facebook.com/..."
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3" />
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">
                    Zalo
                </label>

                <input type="text"
                       wire:model.defer="settings.social_zalo"
                       placeholder="SĐT hoặc link OA"
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3" />
            </div>

        </div>
    </div>

    <!-- HEADER SCRIPT -->
    <div class="border-t pt-6">

        <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-gray-900">
                Header Scripts
            </label>

            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                Google Analytics, Pixel...
            </span>
        </div>

        <textarea
            wire:model.defer="settings.header_script"
            rows="5"
            placeholder="<script>...</script>"
            class="w-full rounded-xl border border-gray-300 px-4 py-3 font-mono text-sm">
        </textarea>

    </div>

    <!-- ACTION -->
    <div class="pt-6 border-t flex justify-end">
        <button wire:click="save"
                wire:loading.attr="disabled"
                class="h-[48px] px-6 bg-indigo-600 text-white rounded-xl text-sm font-semibold
                       hover:bg-indigo-500 transition flex items-center
                       disabled:opacity-50">

            <svg wire:loading wire:target="save"
                 class="animate-spin h-4 w-4 mr-2"
                 viewBox="0 0 24 24"></svg>

            <span wire:loading.remove wire:target="save">
                Lưu SEO
            </span>

            <span wire:loading wire:target="save">
                Đang lưu...
            </span>
        </button>
    </div>

</div>
