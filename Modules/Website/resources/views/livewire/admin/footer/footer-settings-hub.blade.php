<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Bố cục Footer</h2>
            <p class="mt-1 text-sm text-gray-500">Bật/tắt, sắp xếp và chuyển các component giữa những vùng được cho phép.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" wire:click="resetBuilder" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Khôi phục mặc định
            </button>
            <button type="button" wire:click="saveBuilder" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Lưu bố cục
            </button>
        </div>
    </div>

    @error('builder')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach($builderSlotNames as $slot => $slotLabel)
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ $slotLabel }}</h3>
                    <span class="text-xs text-gray-400">{{ count($builderSlots[$slot] ?? []) }} component</span>
                </div>

                <div class="space-y-3">
                    @forelse($builderSlots[$slot] ?? [] as $index => $item)
                        @php
                            $type = $item['type'] ?? '';
                            $definition = $footerComponents[$type] ?? [];
                            $allowedSlots = $definition['allowed_slots'] ?? [];
                            $enabled = (bool) ($item['enabled'] ?? true);
                        @endphp

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3" wire:key="footer-builder-{{ str_replace('.', '-', $slot) }}-{{ $index }}-{{ $type }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-gray-900">{{ $definition['label'] ?? $type }}</div>
                                    <div class="text-xs text-gray-500">{{ $type }}</div>
                                </div>

                                <button type="button" wire:click="toggleComponent('{{ $slot }}', {{ $index }})"
                                    class="rounded-full px-3 py-1 text-xs font-semibold {{ $enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $enabled ? 'Đang bật' : 'Đang tắt' }}
                                </button>
                                <button type="button" wire:click="moveUp('{{ $slot }}', {{ $index }})" class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700" title="Di chuyển lên">↑</button>
                                <button type="button" wire:click="moveDown('{{ $slot }}', {{ $index }})" class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700" title="Di chuyển xuống">↓</button>
                            </div>

                            @if(count($allowedSlots) > 1)
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-500">Chuyển vùng:</span>
                                    <select class="min-w-0 flex-1 rounded-md border-gray-300 text-sm"
                                        x-on:change="if ($event.target.value) { $wire.moveComponent('{{ $slot }}', {{ $index }}, $event.target.value); $event.target.value = ''; }">
                                        <option value="">Chọn vùng...</option>
                                        @foreach($allowedSlots as $targetSlot)
                                            @if($targetSlot !== $slot)
                                                <option value="{{ $targetSlot }}">{{ $builderSlotNames[$targetSlot] ?? $targetSlot }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-400">
                            Chưa có component trong vùng này.
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-5">
            <h3 class="font-semibold text-gray-900">Presentation</h3>
            <p class="mt-1 text-sm text-gray-500">Cấu hình hiển thị chung của Footer. Typography tiếp tục kế thừa Global Design Tokens.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">Chế độ</span>
                <select wire:model="presentation.mode" class="w-full rounded-lg border-gray-300">
                    <option value="basic">Basic</option>
                    <option value="advanced">Advanced</option>
                </select>
            </label>

            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">Container</span>
                <select wire:model="presentation.container" class="w-full rounded-lg border-gray-300">
                    <option value="compact">Compact</option>
                    <option value="standard">Standard</option>
                    <option value="wide">Wide</option>
                    <option value="full">Full</option>
                </select>
            </label>

            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">Khoảng cách dọc</span>
                <select wire:model="presentation.spacing" class="w-full rounded-lg border-gray-300">
                    <option value="compact">Compact</option>
                    <option value="normal">Normal</option>
                    <option value="comfortable">Comfortable</option>
                </select>
            </label>

            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">Khoảng cách cột</span>
                <select wire:model="presentation.column_gap" class="w-full rounded-lg border-gray-300">
                    <option value="compact">Compact</option>
                    <option value="normal">Normal</option>
                    <option value="comfortable">Comfortable</option>
                </select>
            </label>
        </div>

        <div class="mt-5 flex flex-wrap gap-5 text-sm text-gray-700">
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model="presentation.inherit_colors" class="rounded border-gray-300"> Kế thừa màu Global Theme</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model="presentation.accent" class="rounded border-gray-300"> Top accent</label>
            <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model="presentation.border" class="rounded border-gray-300"> Border</label>
        </div>

        @if(($presentation['mode'] ?? 'basic') === 'advanced')
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach([
                    'container_width' => 'Container width',
                    'padding_top' => 'Padding top',
                    'padding_bottom' => 'Padding bottom',
                    'column_gap' => 'Column gap',
                    'section_gap' => 'Section gap',
                    'logo_max_height' => 'Logo max height',
                    'social_icon_size' => 'Social icon size',
                ] as $field => $label)
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">{{ $label }}</span>
                        <input type="number" wire:model="presentation.custom.{{ $field }}" class="w-full rounded-lg border-gray-300">
                    </label>
                @endforeach
            </div>
        @endif

        @if(!($presentation['inherit_colors'] ?? true))
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach([
                    'background' => 'Background',
                    'foreground' => 'Foreground',
                    'heading' => 'Heading',
                    'muted' => 'Muted',
                    'accent' => 'Accent',
                    'border' => 'Border',
                ] as $field => $label)
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">{{ $label }}</span>
                        <input type="color" wire:model="presentation.colors.{{ $field }}" class="h-10 w-full rounded-lg border border-gray-300 p-1">
                    </label>
                @endforeach
            </div>
        @endif
    </section>
</div>
