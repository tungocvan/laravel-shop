                    @if(!in_array($slot, ['desktop.bottom.left', 'desktop.bottom.right'], true))
                    <div @dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                        @drop.prevent="if (drag) { $wire.moveComponentByDrag(drag.slot, drag.index, '{{ $slot }}', {{ count($builderSlots[$slot] ?? []) }}); drag = null }"
                        class="rounded-lg border border-dashed border-gray-300 px-4 py-4 text-center text-xs text-gray-400 transition"
                        :class="drag ? 'border-blue-400 bg-blue-50 text-blue-600' : ''">
                        {{ empty($builderSlots[$slot] ?? []) ? 'Vùng trống · thả component vào đây' : 'Thả vào cuối vùng' }}
                    </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <section x-show="studioPanel === 'style' || studioPanel === 'style'" x-cloak class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4">
            <h3 class="font-semibold text-slate-900">Thư viện giao diện Footer</h3>
            <p class="mt-1 text-sm text-slate-500">5 mẫu có sẵn, không phụ thuộc danh sách theme cá nhân đã lưu. Chọn mẫu để đổi màu Footer. Theme đã lưu riêng vẫn nằm trong phần quản lý bên dưới. Chỉ áp dụng lên Website sau khi bấm Cập nhật profile.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach($this->footerThemePresets() as $presetKey => $preset)
                <button type="button" wire:click="applyFooterThemePreset('{{ $presetKey }}')" class="overflow-hidden rounded-xl border bg-white text-left transition hover:shadow-md {{ !($presentation['inherit_colors'] ?? true) && ($presentation['colors']['background'] ?? '') === $preset['background'] ? 'border-blue-500 ring-2 ring-blue-200' : 'border-slate-200' }}">
                    <div class="p-4" style="background-color: {{ $preset['background'] }}; color: {{ $preset['foreground'] }};">
                        <div class="mb-3 text-sm font-bold" style="color: {{ $preset['heading'] }};">{{ $preset['name'] }}</div>
                        <div class="mb-2 h-2 w-3/4 rounded" style="background-color: {{ $preset['foreground'] }};"></div>
                        <div class="mb-2 h-2 w-1/2 rounded" style="background-color: {{ $preset['muted'] }};"></div>
                        <div class="mt-4 h-1 w-full rounded" style="background-color: {{ $preset['accent'] }};"></div>
                    </div>
                    <div class="px-3 py-2">
                        <div class="text-sm font-semibold text-slate-800">{{ $preset['name'] }}</div>
                        <div class="text-xs text-slate-500">{{ $preset['description'] }}</div>
                        @if(!($presentation['inherit_colors'] ?? true) && ($presentation['colors']['background'] ?? '') === $preset['background'])
                            <span class="text-xs font-semibold text-blue-700">Đang chọn</span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    </section>

        </div>
    </details>
    <section x-show="studioPanel === 'style'" x-cloak class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-semibold text-gray-900">Presentation</h3>
                <p class="mt-1 text-sm text-gray-500">Thay đổi được phản ánh trực tiếp trong Responsive Preview trước khi lưu.</p>
            </div>
            <div class="inline-flex rounded-lg bg-gray-100 p-1">
                <button type="button" wire:click="$set('presentation.mode', 'basic')" class="rounded-md px-3 py-1.5 text-sm font-semibold {{ ($presentation['mode'] ?? 'basic') === 'basic' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500' }}">Basic</button>
                <button type="button" wire:click="$set('presentation.mode', 'advanced')" class="rounded-md px-3 py-1.5 text-sm font-semibold {{ ($presentation['mode'] ?? 'basic') === 'advanced' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500' }}">Advanced</button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">Container</span><select wire:model.live="presentation.container" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="compact">Compact</option><option value="standard">Standard</option><option value="wide">Wide</option><option value="full">Full</option></select></label>
            <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">Khoảng cách dọc</span><select wire:model.live="presentation.spacing" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="compact">Compact</option><option value="normal">Normal</option><option value="comfortable">Comfortable</option></select></label>
            <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">Khoảng cách cột</span><select wire:model.live="presentation.column_gap" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="compact">Compact</option><option value="normal">Normal</option><option value="comfortable">Comfortable</option></select></label>
        </div>

        <div class="mt-5 flex flex-wrap gap-5 text-sm text-gray-700">
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="presentation.inherit_colors" class="rounded border-gray-300"> Kế thừa màu Global Theme</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="presentation.accent" class="rounded border-gray-300"> Top accent</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="presentation.border" class="rounded border-gray-300"> Border</label>
        </div>

        @if(($presentation['mode'] ?? 'basic') === 'advanced')
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach(['container_width' => 'Container width','padding_top' => 'Padding top','padding_bottom' => 'Padding bottom','column_gap' => 'Column gap','section_gap' => 'Section gap','logo_max_height' => 'Logo max height','social_icon_size' => 'Social icon size'] as $field => $label)
                    <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">{{ $label }}</span><input type="number" wire:model.live.debounce.250ms="presentation.custom.{{ $field }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></label>
                @endforeach
            </div>
        @endif

        <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Màu nền Footer</div>
                    <p class="mt-1 text-xs text-slate-500">Bỏ chọn “Kế thừa màu Global Theme” để dùng màu nền tùy chỉnh. Cập nhật profile để áp dụng lên Website.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="color" aria-label="Chọn màu nền Footer" wire:model.live="presentation.colors.background" @disabled($presentation['inherit_colors'] ?? true) class="h-10 w-14 rounded-lg border border-slate-300 bg-white p-1 disabled:cursor-not-allowed disabled:opacity-50">
                    <input type="text" aria-label="Mã màu nền Footer" wire:model.live.debounce.300ms="presentation.colors.background" @disabled($presentation['inherit_colors'] ?? true) maxlength="7" placeholder="#111827" class="w-28 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:opacity-50">
                </div>
            </div>
        </div>

        @if(!($presentation['inherit_colors'] ?? true))
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach(['background' => 'Background','foreground' => 'Foreground','heading' => 'Heading','muted' => 'Muted','accent' => 'Accent','border' => 'Border'] as $field => $label)
                    <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">{{ $label }}</span><input type="color" wire:model.live="presentation.colors.{{ $field }}" class="h-10 w-full rounded-lg border border-gray-300 p-1"></label>
                @endforeach
            </div>
        @endif
        <div class="mt-5 flex justify-end">
            <button type="button" wire:click="updateTheme" wire:loading.attr="disabled" wire:target="updateTheme" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Cập nhật profile</button>
        </div>
    </section>
        </div>
        <aside class="min-w-0 xl:order-1 xl:sticky xl:top-4 xl:max-h-[calc(100vh-2rem)] xl:overflow-y-auto" aria-label="Live Footer Preview">
            <div class="mb-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-slate-700">Bản xem trước nội dung · Thay đổi bản nháp không tự động xuất bản.</div>
            @include('Website::livewire.admin.footer.partials.builder-preview')
        </aside>
    </div>
</div>
