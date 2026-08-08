<div class="space-y-8">

    <!-- LOGO -->
    <div>
        <label class="block text-sm font-medium text-gray-900">
            Logo Website
        </label>

        <div class="mt-3 flex items-center gap-6">

            <!-- Preview -->
            <div class="relative">
                @if($new_logo)
                    <img src="{{ $new_logo->temporaryUrl() }}"
                         class="h-24 w-auto object-contain rounded-xl border border-gray-200 p-2 bg-gray-50">

                    <span class="absolute -top-2 -right-2 text-xs bg-green-500 text-white px-2 py-0.5 rounded-full">
                        New
                    </span>

                @elseif($site_logo)
                    <img src="{{ asset('storage/'.$site_logo) }}"
                         class="h-24 w-auto object-contain rounded-xl border border-gray-200 p-2 bg-gray-50">

                @else
                    <div class="h-24 w-24 flex items-center justify-center text-xs text-gray-400
                                border border-dashed border-gray-300 rounded-xl">
                        No Logo
                    </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="space-y-2">

                <!-- Upload -->
                <label class="h-[48px] inline-flex items-center px-5 cursor-pointer
                              rounded-xl border border-gray-300 text-sm font-medium
                              text-gray-700 bg-white hover:bg-gray-50 transition">
                    Chọn ảnh mới
                    <input type="file"
                           wire:model="new_logo"
                           class="hidden"
                           accept="image/png,image/jpeg,image/svg+xml">
                </label>

                <!-- Loading -->
                <div wire:loading wire:target="new_logo"
                     class="text-xs text-indigo-600">
                    Đang tải ảnh...
                </div>

                <!-- Remove -->
                @if($site_logo)
                    <button type="button"
                            wire:click="remove('logo')"
                            class="block text-xs text-red-500 hover:underline">
                        Xóa logo
                    </button>
                @endif

                <p class="text-xs text-gray-500">
                    PNG, JPG, SVG. Nền trong suốt khuyến nghị.
                </p>
            </div>
        </div>
    </div>

    <!-- DIVIDER -->
    <div class="border-t border-gray-100"></div>

    <!-- FAVICON -->
    <div>
        <label class="block text-sm font-medium text-gray-900">
            Favicon (icon tab trình duyệt)
        </label>

        <div class="mt-3 flex items-center gap-6">

            <!-- Preview -->
            <div>
                @if($new_favicon)
                    @if(strtolower($new_favicon->getClientOriginalExtension()) === 'ico')
                        <div class="h-14 w-14 flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-xs font-semibold text-indigo-600">
                            ICO mới
                        </div>
                    @else
                        <img src="{{ $new_favicon->temporaryUrl() }}"
                             class="h-14 w-14 object-contain rounded-lg border border-gray-200 p-1">
                    @endif

                @elseif($site_favicon)
                    <img src="{{ asset('storage/'.$site_favicon) }}"
                         class="h-14 w-14 object-contain rounded-lg border border-gray-200 p-1">

                @else
                    <div class="h-14 w-14 flex items-center justify-center text-xs text-gray-400
                                border border-dashed border-gray-300 rounded-lg">
                        No Icon
                    </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="space-y-2">

                <!-- Upload -->
                <label class="h-[48px] inline-flex items-center px-5 cursor-pointer
                              rounded-xl border border-gray-300 text-sm font-medium
                              text-gray-700 bg-white hover:bg-gray-50 transition">
                    Chọn icon
                    <input type="file"
                           wire:model="new_favicon"
                           class="hidden"
                           accept=".png,.ico,image/png,image/x-icon,image/vnd.microsoft.icon">
                </label>

                <!-- Loading -->
                <div wire:loading wire:target="new_favicon"
                     class="text-xs text-indigo-600">
                    Đang tải icon...
                </div>

                @error('new_favicon')
                    <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror

                <!-- Remove -->
                @if($site_favicon)
                    <button type="button"
                            wire:click="remove('favicon')"
                            class="block text-xs text-red-500 hover:underline">
                        Xóa icon
                    </button>
                @endif

                <p class="text-xs text-gray-500">
                    Ảnh vuông 32x32 hoặc 64x64 (PNG/ICO).
                </p>
            </div>
        </div>
    </div>

    <!-- ACTION -->
    <div class="pt-6 border-t border-gray-100 flex justify-end">
        <button wire:click="save"
                wire:loading.attr="disabled"
                class="h-[48px] px-6 bg-indigo-600 text-white rounded-xl text-sm font-semibold
                       hover:bg-indigo-500 transition flex items-center
                       disabled:opacity-50 disabled:cursor-not-allowed">

            <svg wire:loading wire:target="save"
                 class="animate-spin h-4 w-4 mr-2"
                 viewBox="0 0 24 24">
            </svg>

            <span wire:loading.remove wire:target="save">
                Lưu thay đổi
            </span>

            <span wire:loading wire:target="save">
                Đang lưu...
            </span>
        </button>
    </div>

</div>
