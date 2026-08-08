<div class="space-y-10">

    <!-- CREATE FIELD -->
    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">
            Tạo cấu hình mới
        </h3>

        <div class="grid grid-cols-12 gap-4">

            <div class="col-span-4">
                <input type="text" wire:model.defer="newField.label" placeholder="Label"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3">
            </div>

            <div class="col-span-4">
                <input type="text" wire:model.defer="newField.key" placeholder="Key"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3">
            </div>

            <div class="col-span-2">
                <select wire:model.defer="newField.type" class="w-full rounded-xl border border-gray-300 px-4 py-3">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="image">Image</option>
                    <option value="html">Editor</option>
                    <option value="gallery">Gallery</option>
                </select>
            </div>

            <div class="col-span-2">
                <button wire:click="addField" class="h-[48px] w-full bg-indigo-600 text-white rounded-xl">
                    Thêm
                </button>
            </div>

        </div>
    </div>

    <!-- LIST -->
    <div class="space-y-6">

        @forelse($customSettings as $setting)
            <div class="border rounded-2xl p-5 bg-white relative group">

                <!-- DELETE -->
                <button wire:click="deleteField({{ $setting->id }})"
                    class="absolute top-3 right-3 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100">
                    ✕
                </button>

                <!-- LABEL -->
                <div class="mb-4">
                    <div class="font-semibold text-gray-900">
                        {{ $setting->label }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $setting->key }} • {{ $setting->type }}
                    </div>
                </div>

                <!-- TYPE SWITCH -->
                @switch($setting->type)
                    {{-- TEXT --}}
                    @case('text')
                        <input type="text" wire:model.defer="dynamicValues.{{ $setting->id }}"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3">
                    @break

                    {{-- TEXTAREA --}}
                    @case('textarea')
                        <textarea rows="3" wire:model.defer="dynamicValues.{{ $setting->id }}"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3"></textarea>
                    @break

                    {{-- HTML --}}
                    @case('html')
                        <div wire:key="editor-{{ $setting->id }}">
                            <x-editor wire:model="dynamicValues.{{ $setting->id }}" label="{{ $setting->label }}"
                                mode="full" height="300px" required />
                        </div>
                    @break

                    {{-- IMAGE --}}
                    @case('image')
                        <div class="flex gap-4 items-center">
                            @if ($setting->value)
                                <img src="{{ asset('storage/' . $setting->value) }}" class="h-20 w-20 object-cover rounded">
                            @endif

                            <input type="file" wire:model="dynamicImages.{{ $setting->id }}">
                        </div>
                    @break

                    {{-- GALLERY --}}
                    @case('gallery')
                        <div class="grid grid-cols-4 gap-4">

                            @foreach ($dynamicValues[$setting->id] ?? [] as $index => $img)
                                <div class="relative">
                                    <img src="{{ asset('storage/' . $img) }}" class="rounded-xl">

                                    <button wire:click="removeGalleryImage({{ $setting->id }}, {{ $index }})"
                                        class="absolute top-1 right-1 bg-red-500 text-white text-xs px-1 rounded">
                                        x
                                    </button>
                                </div>
                            @endforeach

                            <input type="file" wire:model="galleryUploads.{{ $setting->id }}" multiple>
                        </div>
                    @break
                @endswitch

            </div>

            @empty
                <div class="text-center text-gray-500 py-10">
                    Chưa có cấu hình
                </div>
            @endforelse

        </div>

        <!-- SAVE -->
        <div class="flex justify-end">
            <button wire:click="save" class="h-[48px] px-6 bg-indigo-600 text-white rounded-xl">
                Lưu toàn bộ
            </button>
        </div>

    </div>
