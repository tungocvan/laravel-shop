<div class="space-y-10">
    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-gray-900">Tạo cấu hình mới</h3>
            @unless($canUpdate)
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">Chỉ xem</span>
            @endunless
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-4">
                <input type="text" wire:model.defer="newField.label" placeholder="Label" @disabled(!$canUpdate)
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 disabled:bg-gray-100">
                @error('newField.label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-4">
                <input type="text" wire:model.defer="newField.key" placeholder="Key" @disabled(!$canUpdate)
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 disabled:bg-gray-100">
                @error('newField.key') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-2">
                <select wire:model.defer="newField.type" @disabled(!$canUpdate)
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 disabled:bg-gray-100">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="image">Image</option>
                    <option value="html">Editor</option>
                    <option value="gallery">Gallery</option>
                </select>
                @error('newField.type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-12 md:col-span-2">
                <button type="button" wire:click="addField" wire:loading.attr="disabled" wire:target="addField"
                    @disabled(!$canUpdate)
                    class="h-[48px] w-full rounded-xl bg-indigo-600 text-white disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="addField">Thêm</span>
                    <span wire:loading wire:target="addField">Đang thêm...</span>
                </button>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        @forelse($customSettings as $setting)
            <div class="group relative rounded-2xl border bg-white p-5">
                @if($canUpdate)
                    <button type="button" wire:click="deleteField({{ $setting['id'] }})"
                        wire:confirm="Xóa cấu hình {{ $setting['label'] }}? File hình ảnh thuộc cấu hình này cũng sẽ được dọn sau khi xóa thành công."
                        wire:loading.attr="disabled" wire:target="deleteField({{ $setting['id'] }})"
                        class="absolute right-3 top-3 text-gray-300 opacity-0 transition hover:text-red-500 group-hover:opacity-100 disabled:opacity-40">
                        ✕
                    </button>
                @endif

                <div class="mb-4">
                    <div class="font-semibold text-gray-900">{{ $setting['label'] }}</div>
                    <div class="text-xs text-gray-500">{{ $setting['key'] }} • {{ $setting['type'] }}</div>
                </div>

                @switch($setting['type'])
                    @case('text')
                        <input type="text" wire:model.defer="dynamicValues.{{ $setting['id'] }}" @disabled(!$canUpdate)
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 disabled:bg-gray-100">
                    @break

                    @case('textarea')
                        <textarea rows="3" wire:model.defer="dynamicValues.{{ $setting['id'] }}" @disabled(!$canUpdate)
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 disabled:bg-gray-100"></textarea>
                    @break

                    @case('html')
                        <div class="mb-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Nội dung HTML đặc quyền. Chỉ lưu markup tin cậy; nơi render raw HTML phải có chính sách sanitize/escape phù hợp.
                        </div>
                        <div wire:key="editor-{{ $setting['id'] }}">
                            <x-editor wire:model="dynamicValues.{{ $setting['id'] }}" label="{{ $setting['label'] }}" mode="full" height="300px" required />
                        </div>
                    @break

                    @case('image')
                        <div class="space-y-2">
                            <div class="flex items-center gap-4">
                                @if ($setting['value'])
                                    <img src="{{ asset('storage/' . $setting['value']) }}" class="h-20 w-20 rounded object-cover" alt="">
                                @endif
                                <input type="file" wire:model="dynamicImages.{{ $setting['id'] }}" accept="image/jpeg,image/png,image/webp" @disabled(!$canUpdate)>
                            </div>
                            <p class="text-xs text-gray-500">JPG, PNG hoặc WebP. Tối đa 5 MB.</p>
                            @error('dynamicImages.'.$setting['id']) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @break

                    @case('gallery')
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach ($dynamicValues[$setting['id']] ?? [] as $index => $img)
                                    <div class="relative">
                                        <img src="{{ asset('storage/' . $img) }}" class="rounded-xl" alt="">
                                        @if($canUpdate)
                                            <button type="button" wire:click="removeGalleryImage({{ $setting['id'] }}, {{ $index }})"
                                                class="absolute right-1 top-1 rounded bg-red-500 px-1 text-xs text-white">x</button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <input type="file" wire:model="galleryUploads.{{ $setting['id'] }}" multiple accept="image/jpeg,image/png,image/webp" @disabled(!$canUpdate)>
                            <p class="text-xs text-gray-500">Tối đa 20 ảnh/lần lưu, mỗi ảnh tối đa 5 MB. Không hỗ trợ SVG.</p>
                            @error('galleryUploads.'.$setting['id']) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('galleryUploads.'.$setting['id'].'.*') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @break
                @endswitch
            </div>
        @empty
            <div class="py-10 text-center text-gray-500">Chưa có cấu hình</div>
        @endforelse
    </div>

    <div class="flex justify-end">
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
            @disabled(!$canUpdate)
            class="h-[48px] rounded-xl bg-indigo-600 px-6 text-white disabled:cursor-not-allowed disabled:opacity-50">
            <span wire:loading.remove wire:target="save">Lưu toàn bộ</span>
            <span wire:loading wire:target="save">Đang lưu...</span>
        </button>
    </div>
</div>
