<div>
    <div class="max-w-5xl mx-auto">

        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    Cấu hình hệ thống
                </h2>
                <p class="mt-1 text-sm text-gray-500">Quản lý thông tin website, hình ảnh thương hiệu và các mã nhúng.</p>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                @foreach([
                    'general' => 'Thông tin chung',
                    'images' => 'Hình ảnh & Logo',
                    'seo' => 'SEO & Mạng xã hội',
                    'custom' => 'Cấu hình mở rộng'
                ] as $key => $label)
                    <button
                        wire:click="setTab('{{ $key }}')"
                        class="{{ $activeTab === $key ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors focus:outline-none">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        <form wire:submit="save">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-6 md:p-8 relative min-h-[400px]">

                <div wire:loading.flex wire:target="setTab" class="absolute inset-0 bg-white/80 z-10 items-center justify-center backdrop-blur-[1px]">
                    <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>

                @if($activeTab === 'general')
                    <div class="space-y-6 animate-fadeIn">
                        <div class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                            <div class="sm:col-span-4">
                                <label class="block text-sm font-medium leading-6 text-gray-900">Tên cửa hàng (Site Name)</label>
                                <div class="mt-2">
                                    <input type="text" wire:model="settings.site_name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-sm font-medium leading-6 text-gray-900">Hotline</label>
                                <div class="mt-2">
                                    <input type="text" wire:model="settings.site_hotline" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-sm font-medium leading-6 text-gray-900">Email liên hệ</label>
                                <div class="mt-2">
                                    <input type="email" wire:model="settings.site_email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>

                            <div class="sm:col-span-6">
                                <label class="block text-sm font-medium leading-6 text-gray-900">Địa chỉ kho/văn phòng</label>
                                <div class="mt-2">
                                    <input type="text" wire:model="settings.site_address" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end border-t border-gray-900/10 pt-6">
                            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
                                <svg wire:loading wire:target="save" class="-ml-1 mr-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="save">Lưu cấu hình chung</span>
                                <span wire:loading wire:target="save">Đang lưu...</span>
                            </button>
                        </div>
                    </div>
                @endif

                @if($activeTab === 'images')
                    <div class="space-y-8 animate-fadeIn">

                        <div class="col-span-full">
                            <label class="block text-sm font-medium leading-6 text-gray-900">Logo Website</label>
                            <div class="mt-2 flex items-center gap-x-8">
                                <div class="shrink-0 relative group">
                                    @if($new_logo)
                                        <img src="{{ $new_logo->temporaryUrl() }}" class="h-24 w-auto object-contain rounded-lg border border-gray-200 p-2 bg-gray-50">
                                        <div class="absolute top-0 right-0 -mt-2 -mr-2 bg-green-500 text-white text-xs px-1.5 py-0.5 rounded-full">New</div>
                                    @elseif($site_logo)
                                        <img src="{{ asset('storage/'.$site_logo) }}" class="h-24 w-auto object-contain rounded-lg border border-gray-200 p-2 bg-gray-50">
                                    @else
                                        <div class="h-24 w-24 bg-gray-100 rounded-lg flex items-center justify-center text-xs text-gray-400 border border-dashed border-gray-300">No Logo</div>
                                    @endif
                                </div>

                                <div>
                                    <label for="logo-upload" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer transition">
                                        <span>Chọn ảnh mới</span>
                                        <input id="logo-upload" type="file" wire:model="new_logo" class="sr-only" accept="image/png, image/jpeg, image/svg+xml">
                                    </label>
                                    <p class="mt-2 text-xs leading-5 text-gray-500">PNG, JPG, SVG. Khuyên dùng nền trong suốt.</p>
                                    <div wire:loading wire:target="new_logo" class="text-xs text-indigo-600 mt-1">Đang tải ảnh lên...</div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100"></div>

                        <div class="col-span-full">
                            <label class="block text-sm font-medium leading-6 text-gray-900">Favicon (Icon trên tab trình duyệt)</label>
                            <div class="mt-2 flex items-center gap-x-8">
                                <div class="shrink-0 relative">
                                    @if($new_favicon && !$errors->has('new_favicon'))
                                        <div class="h-12 w-12 bg-indigo-50 rounded flex items-center justify-center text-xs font-medium text-indigo-600 border border-indigo-200">
                                            Icon mới
                                        </div>
                                    @elseif($site_favicon)
                                        <img src="{{ asset('storage/'.$site_favicon) }}" class="h-12 w-12 object-contain rounded border border-gray-200 p-1">
                                    @else
                                        <div class="h-12 w-12 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-400 border border-dashed">No Icon</div>
                                    @endif
                                </div>

                                <div>
                                    <label for="favicon-upload" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer transition">
                                        <span>Chọn icon mới</span>
                                        <input id="favicon-upload" type="file" wire:model="new_favicon" class="sr-only" accept=".png,.ico,image/png,image/x-icon,image/vnd.microsoft.icon">
                                    </label>
                                    <p class="mt-2 text-xs leading-5 text-gray-500">PNG hoặc ICO, ảnh vuông 32x32 hoặc 64x64, tối đa 512 KB.</p>
                                    <div wire:loading wire:target="new_favicon" class="text-xs text-indigo-600 mt-1">Đang tải ảnh lên...</div>
                                    @error('new_favicon')
                                        <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>
                @endif

                @if($activeTab === 'seo')
                    <div class="space-y-6 animate-fadeIn">

                        <div class="sm:col-span-4">
                            <label class="block text-sm font-medium leading-6 text-gray-900">Meta Title (Tiêu đề mặc định)</label>
                            <div class="mt-2">
                                <input type="text" wire:model="settings.seo_title" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <div class="col-span-full">
                            <label class="block text-sm font-medium leading-6 text-gray-900">Meta Description (Mô tả mặc định)</label>
                            <div class="mt-2">
                                {{-- <textarea wire:model="settings.seo_description" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea> --}}
                                <div wire:key="editor-seo_description">
                                    <x-editor
                                        wire:model="settings.seo_description"
                                        mode="full"
                                        height="300px"
                                        required="true"
                                    />
                                </div> 
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-6">
                            <h3 class="text-base font-semibold text-gray-900 mb-4">Liên kết Mạng xã hội</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Facebook Link</label>
                                    <input type="text" wire:model="settings.social_facebook" placeholder="https://facebook.com/..." class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Zalo OA / SĐT Zalo</label>
                                    <input type="text" wire:model="settings.social_zalo" placeholder="0912..." class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-6">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-medium text-gray-900">Header Scripts</label>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Chèn mã Google Analytics, Pixel...</span>
                            </div>
                            <div class="mt-2">
                                <textarea wire:model="settings.header_script" rows="5" placeholder="<script>...</script>" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 font-mono text-xs sm:text-sm sm:leading-6"></textarea>
                            </div>
                        </div>
                    </div>
                @endif

                @if($activeTab === 'custom')
                    <div class="space-y-8 animate-fadeIn">

                        <div class="bg-indigo-50/50 rounded-xl p-5 border border-indigo-100 border-dashed">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="p-1 bg-indigo-600 rounded text-white">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Tạo trường cấu hình mới</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                                <div class="md:col-span-4">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Tên hiển thị (Label) <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="newField.label" placeholder="VD: Banner Quảng Cáo" class="block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">
                                </div>

                                <div class="md:col-span-4">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Mã (Key) <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="newField.key" placeholder="VD: home_banner" class="block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">
                                    @error('newField.key') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Loại dữ liệu</label>
                                    <select wire:model="newField.type" class="block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">
                                        <option value="text">Văn bản ngắn (Input)</option>
                                        <option value="textarea">Văn bản dài (Textarea)</option>
                                        <option value="image">Hình ảnh đơn (Image)</option>
                                        <option value="html">Soạn thảo văn bản (CKEditor)</option>
                                        <option value="gallery">Album nhiều ảnh (Gallery)</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-transparent mb-1">Action</label>
                                    <button wire:click="addField" type="button" class="w-full rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                        Thêm mới
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-base font-semibold text-gray-900 mb-4 border-b pb-2">Danh sách cấu hình tùy chỉnh</h3>

                            <div class="space-y-6">
                                @forelse($customSettings as $setting)
                                    <div class="relative bg-white p-5 rounded-lg border border-gray-200 shadow-sm group hover:border-indigo-300 hover:shadow-md transition duration-200">

                                        <button
                                            wire:click="deleteField({{ $setting->id }})"
                                            wire:confirm="CẢNH BÁO: Bạn có chắc chắn muốn xóa trường này? Dữ liệu bên trong sẽ bị mất vĩnh viễn!"
                                            type="button"
                                            class="absolute top-3 right-3 p-1.5 rounded-full text-gray-300 hover:text-red-600 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition"
                                            title="Xóa cấu hình này">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>

                                        <div class="mb-3">
                                            <label class="block text-sm font-semibold text-gray-900">
                                                {{ $setting->label }}
                                            </label>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 font-mono">
                                                    Key: {{ $setting->key }}
                                                </span>
                                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                    {{ ucfirst($setting->type) }}
                                                </span>
                                            </div>
                                        </div>

                                        @if($setting->type === 'text')
                                            <input type="text" wire:model="dynamicValues.{{ $setting->id }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">

                                            @elseif($setting->type === 'textarea')
                                            <textarea wire:model="dynamicValues.{{ $setting->id }}" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm"></textarea>

                                        @elseif($setting->type === 'html')
                                            <div wire:key="editor-{{ $setting->id }}">
                                                <x-editor
                                                    wire:model="dynamicValues.{{ $setting->id }}"
                                                    id="editor-{{ $setting->id }}"
                                                    mode="full"
                                                    height="300px"
                                                    required="true"
                                                />
                                            </div> 

                                            @elseif($setting->type === 'gallery')
                                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">

                                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-4">

                                                    {{-- A. Hiển thị ảnh ĐÃ CÓ trong Database --}}
                                                    @if(!empty($dynamicValues[$setting->id]) && is_array($dynamicValues[$setting->id]))
                                                        @foreach($dynamicValues[$setting->id] as $index => $imagePath)
                                                            <div class="group relative aspect-square bg-white border rounded-lg overflow-hidden shadow-sm">
                                                                <img src="{{ asset('storage/'.$imagePath) }}" class="w-full h-full object-cover">

                                                                <button type="button"
                                                                    wire:click="removeGalleryImage({{ $setting->id }}, {{ $index }})"
                                                                    class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition shadow-md hover:bg-red-600">
                                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    @endif

                                                    {{-- B. Hiển thị ảnh ĐANG UPLOAD (Preview) --}}
                                                    @if(!empty($galleryUploads[$setting->id]))
                                                        @foreach($galleryUploads[$setting->id] as $photo)
                                                            <div class="relative aspect-square bg-indigo-50 border border-indigo-200 rounded-lg overflow-hidden">
                                                                <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover opacity-70">

                                                                <div class="absolute inset-0 flex items-center justify-center">
                                                                    <span class="bg-black/50 text-white text-xs px-2 py-1 rounded">Mới</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif

                                                    {{-- C. Nút bấm Upload (Luôn nằm cuối) --}}
                                                    <label class="cursor-pointer hover:bg-gray-100 transition flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg aspect-square text-gray-400 hover:text-indigo-600 hover:border-indigo-400">
                                                        <svg class="w-8 h-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                        <span class="text-xs font-medium">Thêm ảnh</span>

                                                        <input type="file"
                                                            wire:model="galleryUploads.{{ $setting->id }}"
                                                            multiple
                                                            class="hidden"
                                                            accept="image/*">
                                                    </label>
                                                </div>

                                                <div wire:loading wire:target="galleryUploads.{{ $setting->id }}" class="text-xs text-indigo-600 font-medium flex items-center animate-pulse">
                                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    Đang tải ảnh lên...
                                                </div>

                                                <p class="text-xs text-gray-500 mt-2">Hỗ trợ JPG, PNG. Bấm nút <b>Lưu cấu hình</b> để hoàn tất việc thêm ảnh mới.</p>
                                            </div>

                                        @elseif($setting->type === 'image')
                                            <div class="flex items-start gap-6 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                                <div class="shrink-0">
                                                    @if($setting->value)
                                                        <img src="{{ asset('storage/'.$setting->value) }}" class="h-20 w-20 object-cover rounded border bg-white" title="Ảnh hiện tại">
                                                    @else
                                                        <div class="h-20 w-20 flex items-center justify-center bg-gray-200 rounded text-xs text-gray-500">Trống</div>
                                                    @endif
                                                </div>

                                                <div class="flex-1">
                                                    <input type="file" wire:model="dynamicImages.{{ $setting->id }}" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer">
                                                    @if(isset($dynamicImages[$setting->id]))
                                                        <p class="text-xs text-green-600 mt-2 font-medium flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                            Đã chọn ảnh mới. Bấm "Lưu cấu hình" để cập nhật.
                                                        </p>
                                                    @else
                                                        <p class="text-xs text-gray-500 mt-2">Chọn file để thay thế ảnh cũ.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                @empty
                                    <div class="text-center py-10 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Chưa có cấu hình tùy chỉnh</h3>
                                        <p class="mt-1 text-sm text-gray-500">Hãy thêm các trường như Banner, Link Youtube, Thông báo...</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                    </div>
                @endif

                @if($activeTab !== 'general')
                    <div class="mt-8 flex items-center justify-end gap-x-6 border-t border-gray-900/10 pt-6">
                        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-md bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 flex items-center disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="save">Lưu cấu hình</span>
                            <span wire:loading wire:target="save">Đang lưu...</span>
                        </button>
                    </div>
                @endif

            </div>
        </form>
    </div>
    
    @once        
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    @endonce
</div>
