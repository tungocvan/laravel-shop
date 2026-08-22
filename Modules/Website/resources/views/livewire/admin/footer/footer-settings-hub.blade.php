<div class="space-y-6" x-data="{ builderDevice: 'desktop', previewDevice: 'desktop', drag: null }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Bố cục Footer</h2>
            <p class="mt-1 text-sm text-gray-500">Bật/tắt, kéo thả, sắp xếp và theo dõi Footer riêng cho Desktop / Mobile.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" wire:click="resetBuilder" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Khôi phục mặc định</button>
            <button type="button" wire:click="saveBuilder" wire:loading.attr="disabled" wire:target="saveBuilder" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Lưu bố cục</button>
        </div>
    </div>

    @error('builder')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="font-semibold text-gray-900">Bộ lọc thiết bị</div>
            <p class="text-sm text-gray-500">Chỉ hiển thị các vùng của thiết bị đang chỉnh. Overlay dùng chung luôn hiển thị.</p>
        </div>
        <div class="inline-flex self-start rounded-lg bg-gray-100 p-1">
            <button type="button" @click="builderDevice = 'desktop'" :class="builderDevice === 'desktop' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500'" class="rounded-md px-4 py-2 text-sm font-semibold">Desktop</button>
            <button type="button" @click="builderDevice = 'mobile'" :class="builderDevice === 'mobile' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500'" class="rounded-md px-4 py-2 text-sm font-semibold">Mobile</button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
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
                                    <select wire:change="moveComponent('{{ $slot }}', {{ $index }}, $event.target.value)" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm">
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

    @include('Website::livewire.admin.footer.partials.builder-preview')

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
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
            <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">Container</span><select wire:model.live="presentation.container" class="w-full rounded-lg border-gray-300"><option value="compact">Compact</option><option value="standard">Standard</option><option value="wide">Wide</option><option value="full">Full</option></select></label>
            <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">Khoảng cách dọc</span><select wire:model.live="presentation.spacing" class="w-full rounded-lg border-gray-300"><option value="compact">Compact</option><option value="normal">Normal</option><option value="comfortable">Comfortable</option></select></label>
            <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">Khoảng cách cột</span><select wire:model.live="presentation.column_gap" class="w-full rounded-lg border-gray-300"><option value="compact">Compact</option><option value="normal">Normal</option><option value="comfortable">Comfortable</option></select></label>
        </div>

        <div class="mt-5 flex flex-wrap gap-5 text-sm text-gray-700">
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="presentation.inherit_colors" class="rounded border-gray-300"> Kế thừa màu Global Theme</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="presentation.accent" class="rounded border-gray-300"> Top accent</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="presentation.border" class="rounded border-gray-300"> Border</label>
        </div>

        @if(($presentation['mode'] ?? 'basic') === 'advanced')
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach(['container_width' => 'Container width','padding_top' => 'Padding top','padding_bottom' => 'Padding bottom','column_gap' => 'Column gap','section_gap' => 'Section gap','logo_max_height' => 'Logo max height','social_icon_size' => 'Social icon size'] as $field => $label)
                    <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">{{ $label }}</span><input type="number" wire:model.live.debounce.250ms="presentation.custom.{{ $field }}" class="w-full rounded-lg border-gray-300"></label>
                @endforeach
            </div>
        @endif

        @if(!($presentation['inherit_colors'] ?? true))
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach(['background' => 'Background','foreground' => 'Foreground','heading' => 'Heading','muted' => 'Muted','accent' => 'Accent','border' => 'Border'] as $field => $label)
                    <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">{{ $label }}</span><input type="color" wire:model.live="presentation.colors.{{ $field }}" class="h-10 w-full rounded-lg border border-gray-300 p-1"></label>
                @endforeach
            </div>
        @endif
    </section>
</div>
