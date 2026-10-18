<div class="space-y-6" x-data="{ builderDevice: 'desktop', previewDevice: 'desktop', drag: null, studioPanel: 'layout' }">
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <label for="footer-editing-template" class="mb-2 block text-sm font-semibold text-gray-900">Template đang quản trị</label>
        <select id="footer-editing-template" wire:model.live="editingTemplate" class="w-full max-w-md rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
            <option value="default">Default — Website bán lẻ</option>
            <option value="pharma">Pharma — Website dược phẩm</option>
        </select>
        <p class="mt-2 text-xs text-slate-500">Đang xuất bản: <strong>{{ $publishedTemplate === 'pharma' ? 'Pharma' : 'Default' }}</strong>. Chọn workspace chỉ thay đổi phần đang quản trị, không thay đổi template đang xuất bản.</p>
    </section>

    <div class="mb-4 rounded-xl border border-blue-100 bg-blue-50 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Footer Studio</h2>
                <p class="mt-1 text-sm text-slate-600">Chỉnh sửa bản nháp bên trái, theo dõi Preview cố định bên phải. Lưu profile rồi mới kích hoạt để xuất bản.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="studioPanel = 'profile'" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold">Quản lý profile</button>
                <button type="button" wire:click="updateTheme" @disabled(!$selectedTheme) class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Cập nhật profile</button>
                <button type="button" wire:click="publishTheme" wire:confirm="Kích hoạt profile đã lưu và xuất bản lên website?" @disabled(!$selectedTheme) class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Xuất bản website</button>
            </div>
        </div>
    </div>
    <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,55fr)_minmax(0,45fr)] xl:items-start">
        <div class="min-w-0 space-y-5">
            <nav class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-2 shadow-sm" aria-label="Các nhóm thiết lập Footer">
                @foreach(['layout' => 'Bố cục', 'content' => 'Nội dung', 'style' => 'Giao diện', 'profile' => 'Profile'] as $panel => $label)
                    <button type="button" @click="studioPanel = '{{ $panel }}'" :class="studioPanel === '{{ $panel }}' ? 'bg-blue-600 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'" class="rounded-lg px-4 py-2 text-sm font-semibold">{{ $label }}</button>
                @endforeach
            </nav>
            <div x-show="studioPanel === 'profile'" x-cloak>
                @include('Website::livewire.admin.footer.partials.theme-manager')
            </div>
    <section x-show="studioPanel === 'layout'" x-cloak class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold text-slate-900">Thiết kế cột Footer</h3>
                <p class="mt-1 text-sm text-slate-500">Giai đoạn 1: tạo bản nháp số cột, thứ tự, độ rộng và ẩn/hiện. Chưa áp dụng ra Footer công khai.</p>
            </div>
            <button type="button" wire:click="addFooterColumn" @disabled(count($footerColumns) >= 6) class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 disabled:opacity-40">+ Thêm cột</button>
        </div>
        <div class="mt-4 space-y-3">
            @foreach($footerColumns as $index => $column)
                <div wire:key="footer-column-editor-{{ $index }}" class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 md:grid-cols-[minmax(0,1fr)_150px_auto] md:items-end">
                    <label class="text-sm font-medium text-slate-700">Tên cột
                        <input type="text" maxlength="80" wire:model="footerColumns.{{ $index }}.title" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </label>
                    <label class="text-sm font-medium text-slate-700">Độ rộng / 12
                        <select wire:model="footerColumns.{{ $index }}.width" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            @foreach([2,3,4,6,12] as $width)
                                <option value="{{ $width }}">{{ $width }}/12</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex items-center gap-1 text-sm"><input type="checkbox" wire:model="footerColumns.{{ $index }}.enabled"> Hiện</label>
                        <button type="button" wire:click="moveFooterColumn({{ $index }}, -1)" @disabled($index === 0) class="rounded border px-2 py-1 disabled:opacity-30" aria-label="Đưa cột lên">↑</button>
                        <button type="button" wire:click="moveFooterColumn({{ $index }}, 1)" @disabled($index === count($footerColumns) - 1) class="rounded border px-2 py-1 disabled:opacity-30" aria-label="Đưa cột xuống">↓</button>
                        <button type="button" wire:click="removeFooterColumn({{ $index }})" @disabled(count($footerColumns) <= 1) class="rounded border border-red-200 px-2 py-1 text-red-700 disabled:opacity-30">Xóa</button>
                    </div>
                </div>
                <div class="mt-3 rounded-lg border border-slate-200 bg-white p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold text-slate-800">Nội dung cột</h4>
                        <div class="flex gap-2">
                            <button type="button" wire:click="addFooterColumnItem({{ $index }}, 'text')" class="rounded border px-2 py-1 text-xs">+ Văn bản</button>
                            <button type="button" wire:click="addFooterColumnItem({{ $index }}, 'link')" class="rounded border px-2 py-1 text-xs">+ Liên kết</button>
                        </div>
                    </div>
                    <div class="mt-3 space-y-2">
                        @foreach(($column['items'] ?? []) as $itemIndex => $item)
                            <div wire:key="footer-column-item-{{ $index }}-{{ $itemIndex }}" class="grid gap-2 rounded-lg bg-slate-50 p-2 sm:grid-cols-[1fr_1fr_auto] sm:items-center">
                                <label class="text-xs text-slate-600">{{ $item['type'] === 'link' ? 'Tên liên kết' : 'Văn bản' }}
                                    <input type="text" maxlength="250" wire:model="footerColumns.{{ $index }}.items.{{ $itemIndex }}.label" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                                </label>
                                @if($item['type'] === 'link')
                                    <label class="text-xs text-slate-600">URL (https:// hoặc /)
                                        <input type="text" maxlength="2048" wire:model="footerColumns.{{ $index }}.items.{{ $itemIndex }}.url" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5">
                                    </label>
                                @else
                                    <span class="text-xs text-slate-400">Đoạn văn bản</span>
                                @endif
                                <div class="flex gap-1">
                                    <button type="button" wire:click="moveFooterColumnItem({{ $index }}, {{ $itemIndex }}, -1)" @disabled($itemIndex === 0) class="rounded border px-2 py-1 disabled:opacity-30" aria-label="Đưa nội dung lên">↑</button>
                                    <button type="button" wire:click="moveFooterColumnItem({{ $index }}, {{ $itemIndex }}, 1)" @disabled($itemIndex === count($column['items']) - 1) class="rounded border px-2 py-1 disabled:opacity-30" aria-label="Đưa nội dung xuống">↓</button>
                                    <button type="button" wire:click="removeFooterColumnItem({{ $index }}, {{ $itemIndex }})" class="rounded border border-red-200 px-2 py-1 text-red-700">Xóa</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex justify-end">
            <button type="button" wire:click="saveFooterColumns" wire:loading.attr="disabled" wire:target="saveFooterColumns" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Cập nhật profile</button>
        </div>
    </section>

    <div x-show="studioPanel === 'layout'" x-cloak class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Bố cục Footer</h2>
            <p class="mt-1 text-sm text-gray-500">Bật/tắt, kéo thả, sắp xếp và theo dõi Footer riêng cho Desktop / Mobile.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" wire:click="resetBuilder" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Khôi phục mặc định</button>
            <button type="button" wire:click="updateTheme" wire:loading.attr="disabled" wire:target="updateTheme" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Cập nhật profile</button>
        </div>
    </div>

    @error('builder')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div x-show="studioPanel === 'layout'" x-cloak class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="font-semibold text-gray-900">Bộ lọc thiết bị</div>
            <p class="text-sm text-gray-500">Chỉ hiển thị các vùng của thiết bị đang chỉnh. Overlay dùng chung luôn hiển thị.</p>
        </div>
        <div class="inline-flex self-start rounded-lg bg-gray-100 p-1">
            <button type="button" @click="builderDevice = 'desktop'" :class="builderDevice === 'desktop' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500'" class="rounded-md px-4 py-2 text-sm font-semibold">Desktop</button>
            <button type="button" @click="builderDevice = 'mobile'" :class="builderDevice === 'mobile' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500'" class="rounded-md px-4 py-2 text-sm font-semibold">Mobile</button>
        </div>
    </div>

    <div x-show="studioPanel === 'layout'" x-cloak class="grid gap-6 xl:grid-cols-2">
        @foreach($builderSlotNames as $slot => $slotLabel)
            @php
                $isDesktopSlot = str_starts_with($slot, 'desktop.');
                $isMobileSlot = str_starts_with($slot, 'mobile.');
                $isSharedSlot = $slot === 'overlay';
            @endphp
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition"
                x-show="{{ $isSharedSlot ? 'true' : ($isDesktopSlot ? "builderDevice === 'desktop'" : "builderDevice === 'mobile'") }}"
                x-cloak
                :class="drag ? 'border-blue-200' : ''"
                wire:key="footer-builder-slot-{{ str_replace('.', '-', $slot) }}">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $slotLabel }}</h3>
                        <span class="text-[11px] text-gray-400">Kéo component để đổi thứ tự hoặc chuyển vùng hợp lệ</span>
                    </div>
                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-500">{{ count($builderSlots[$slot] ?? []) }}</span>
                </div>

                <div class="space-y-3">
                    @foreach($builderSlots[$slot] ?? [] as $index => $item)
                        @php
                            $type = $item['type'] ?? '';
                            $definition = $footerComponents[$type] ?? [];
                            $allowedSlots = $definition['allowed_slots'] ?? [];
                            $enabled = (bool) ($item['enabled'] ?? true);
                        @endphp
                        <div draggable="true"
                            @dragstart="drag = { slot: '{{ $slot }}', index: {{ $index }} }; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.setData('text/plain', 'footer-component')"
                            @dragend="drag = null"
                            @dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                            @drop.prevent="if (drag) { $wire.moveComponentByDrag(drag.slot, drag.index, '{{ $slot }}', {{ $index }}); drag = null }"
                            class="cursor-grab rounded-lg border bg-gray-50 p-3 transition active:cursor-grabbing {{ $enabled ? 'border-gray-200' : 'border-dashed border-gray-300 opacity-60' }}"
                            wire:key="footer-builder-{{ str_replace('.', '-', $slot) }}-{{ $index }}-{{ $type }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="select-none text-gray-300" title="Kéo để di chuyển">⋮⋮</span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-gray-900">{{ $definition['label'] ?? $type }}</div>
                                    <div class="text-xs text-gray-500">{{ $type }}</div>
                                </div>
                                <button type="button" wire:click="toggleComponent('{{ $slot }}', {{ $index }})" class="rounded-full px-3 py-1 text-xs font-semibold {{ $enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">{{ $enabled ? 'Đang bật' : 'Đang tắt' }}</button>
                                <button type="button" wire:click="moveUp('{{ $slot }}', {{ $index }})" @disabled($index === 0) class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 disabled:opacity-30">↑</button>
                                <button type="button" wire:click="moveDown('{{ $slot }}', {{ $index }})" @disabled($index === count($builderSlots[$slot] ?? []) - 1) class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 disabled:opacity-30">↓</button>
                            </div>

                            @if(count($allowedSlots) > 1)
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-500">Chuyển vùng:</span>
                                    <select wire:change="moveComponent('{{ $slot }}', {{ $index }}, $event.target.value)" class="min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        <option value="{{ $slot }}">Chọn vùng...</option>
                                        @foreach($allowedSlots as $targetSlot)
                                            @if($targetSlot !== $slot)
                                                <option value="{{ $targetSlot }}">{{ $builderSlotNames[$targetSlot] ?? $targetSlot }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div @dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                        @drop.prevent="if (drag) { $wire.moveComponentByDrag(drag.slot, drag.index, '{{ $slot }}', {{ count($builderSlots[$slot] ?? []) }}); drag = null }"
                        class="rounded-lg border border-dashed border-gray-300 px-4 py-4 text-center text-xs text-gray-400 transition"
                        :class="drag ? 'border-blue-400 bg-blue-50 text-blue-600' : ''">
                        {{ empty($builderSlots[$slot] ?? []) ? 'Vùng trống · thả component vào đây' : 'Thả vào cuối vùng' }}
                    </div>
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
        <aside class="min-w-0 xl:sticky xl:top-4 xl:max-h-[calc(100vh-2rem)] xl:overflow-y-auto" aria-label="Live Footer Preview">
            <div class="mb-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-slate-700">Live Preview luôn hiển thị · Thay đổi bản nháp không tự động xuất bản.</div>
            @include('Website::livewire.admin.footer.partials.builder-preview')
        </aside>
    </div>
</div>
