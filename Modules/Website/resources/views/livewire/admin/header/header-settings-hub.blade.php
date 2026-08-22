<div class="max-w-6xl mx-auto py-6" x-data="{ tab: @entangle('activeTab') }">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Cấu hình Header System</h2>
        <p class="text-sm text-gray-500">Quản lý nội dung, menu và bố cục Header theo schema an toàn.</p>
    </div>

    <div class="flex flex-wrap border-b border-gray-200 mb-6">
        <button @click="tab = 'general'"
            :class="tab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="py-2 px-6 border-b-2 font-medium text-sm transition-colors">
            Thông tin chung
        </button>
        <button @click="tab = 'menu'"
            :class="tab === 'menu' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="py-2 px-6 border-b-2 font-medium text-sm transition-colors">
            Cấu trúc Menu
        </button>
        <button @click="tab = 'builder'"
            :class="tab === 'builder' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
            class="py-2 px-6 border-b-2 font-medium text-sm transition-colors">
            Bố cục Header
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div x-show="tab === 'general'" x-cloak>
            <form wire:submit.prevent="saveGeneral" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên thương hiệu</label>
                        <input type="text" wire:model="generalData.brand_name" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email liên hệ</label>
                        <input type="email" wire:model="generalData.topbar_email" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition">
                        Lưu cấu hình
                    </button>
                </div>
            </form>
        </div>

        <div x-show="tab === 'menu'" x-cloak>
            @include('Admin::livewire.header.partials.menu-tree-manager')
        </div>

        <div x-show="tab === 'builder'" x-cloak class="space-y-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Header Builder</h3>
                    <p class="text-sm text-gray-500">Bật/tắt, sắp xếp và chuyển component giữa các slot hợp lệ. Drag & drop sẽ được bổ sung ở Phase 9E.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="resetBuilder" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Khôi phục mặc định
                    </button>
                    <button type="button" wire:click="saveBuilder" wire:loading.attr="disabled" wire:target="saveBuilder" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                        Lưu bố cục
                    </button>
                </div>
            </div>

            @error('builder')
                <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                @foreach($builderSlotNames as $slotKey => $slotLabel)
                    <section class="rounded-xl border border-gray-200 bg-gray-50 p-4" wire:key="builder-slot-{{ str_replace('.', '-', $slotKey) }}">
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="font-bold text-gray-800">{{ $slotLabel }}</h4>
                            <span class="rounded-full bg-white px-2 py-1 text-xs font-medium text-gray-500">{{ count($builderSlots[$slotKey] ?? []) }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($builderSlots[$slotKey] ?? [] as $index => $item)
                                @php
                                    $definition = $headerComponents[$item['type']] ?? [];
                                    $allowedSlots = $definition['allowed_slots'] ?? [];
                                    $enabled = $item['enabled'] ?? true;
                                @endphp
                                <div class="rounded-lg border bg-white p-3 shadow-sm {{ $enabled ? 'border-gray-200' : 'border-dashed border-gray-300 opacity-60' }}" wire:key="builder-item-{{ str_replace('.', '-', $slotKey) }}-{{ $index }}-{{ $item['type'] ?? 'unknown' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">{{ $definition['label'] ?? ($item['type'] ?? 'Unknown') }}</div>
                                            <div class="mt-1 text-xs text-gray-500">{{ $item['type'] ?? '' }}</div>
                                        </div>
                                        <button type="button" wire:click="toggleComponent('{{ $slotKey }}', {{ $index }})" class="rounded-md px-2 py-1 text-xs font-semibold {{ $enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $enabled ? 'Đang bật' : 'Đang tắt' }}
                                        </button>
                                    </div>

                                    <div class="mt-3 flex items-center gap-2">
                                        <button type="button" wire:click="moveUp('{{ $slotKey }}', {{ $index }})" @disabled($index === 0) class="rounded border border-gray-200 px-2 py-1 text-xs disabled:opacity-30">↑</button>
                                        <button type="button" wire:click="moveDown('{{ $slotKey }}', {{ $index }})" @disabled($index === count($builderSlots[$slotKey] ?? []) - 1) class="rounded border border-gray-200 px-2 py-1 text-xs disabled:opacity-30">↓</button>

                                        @if(count($allowedSlots) > 1)
                                            <select wire:change="moveComponent('{{ $slotKey }}', {{ $index }}, $event.target.value)" class="ml-auto rounded-md border-gray-300 py-1 pl-2 pr-7 text-xs">
                                                <option value="{{ $slotKey }}">Chuyển vị trí…</option>
                                                @foreach($allowedSlots as $targetSlot)
                                                    @if($targetSlot !== $slotKey)
                                                        <option value="{{ $targetSlot }}">{{ $builderSlotNames[$targetSlot] ?? $targetSlot }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-gray-300 px-3 py-6 text-center text-xs text-gray-400">Slot trống</div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="border-t border-gray-100 pt-6">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h4 class="font-bold text-gray-900">Thiết lập giao diện Header</h4>
                        <p class="text-sm text-gray-500">Basic dùng preset tối ưu. Advanced cho phép nhập giá trị custom trong giới hạn an toàn.</p>
                    </div>
                    <div class="inline-flex rounded-lg bg-gray-100 p-1">
                        <button type="button" wire:click="$set('presentation.mode', 'basic')" class="rounded-md px-3 py-1.5 text-sm font-semibold {{ ($presentation['mode'] ?? 'basic') === 'basic' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500' }}">Basic</button>
                        <button type="button" wire:click="$set('presentation.mode', 'advanced')" class="rounded-md px-3 py-1.5 text-sm font-semibold {{ ($presentation['mode'] ?? 'basic') === 'advanced' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500' }}">Advanced</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <label class="text-sm font-medium text-gray-700">Chiều rộng nội dung
                        <select wire:model="presentation.container" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="compact">Compact · 1024px</option>
                            <option value="standard">Standard · 1280px</option>
                            <option value="wide">Wide · 1440px</option>
                            <option value="full">Full width</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">Kích thước Header
                        <select wire:model="presentation.size" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="compact">Compact</option>
                            <option value="normal">Normal</option>
                            <option value="comfortable">Comfortable</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700">
                        <input type="checkbox" wire:model="presentation.sticky" class="rounded border-gray-300 text-blue-600">
                        Sticky Header
                    </label>
                    <label class="text-sm font-medium text-gray-700">Shadow
                        <select wire:model="presentation.shadow" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="none">None</option>
                            <option value="soft">Soft</option>
                            <option value="medium">Medium</option>
                        </select>
                    </label>
                </div>

                <div class="mt-5 rounded-xl border border-gray-200 p-4">
                    <label class="flex items-center gap-3 text-sm font-semibold text-gray-700">
                        <input type="checkbox" wire:model="presentation.inherit_colors" class="rounded border-gray-300 text-blue-600">
                        Kế thừa màu Global Website Theme
                    </label>
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                        @foreach([
                            'background' => 'Background',
                            'foreground' => 'Text',
                            'accent' => 'Accent',
                            'border' => 'Border',
                            'topbar_background' => 'Topbar BG',
                            'topbar_foreground' => 'Topbar Text',
                        ] as $colorKey => $colorLabel)
                            <label class="text-xs font-medium text-gray-600">{{ $colorLabel }}
                                <input type="color" wire:model="presentation.colors.{{ $colorKey }}" class="mt-1 h-10 w-full rounded border border-gray-200 bg-white p-1">
                            </label>
                        @endforeach
                    </div>
                </div>

                @if(($presentation['mode'] ?? 'basic') === 'advanced')
                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 rounded-xl border border-amber-200 bg-amber-50/40 p-4">
                        @foreach([
                            'container_width' => ['Container max width', 960, 1920],
                            'desktop_height' => ['Desktop height', 52, 120],
                            'tablet_height' => ['Tablet height', 52, 120],
                            'mobile_height' => ['Mobile height', 52, 120],
                            'topbar_height' => ['Topbar height', 24, 56],
                            'logo_max_height' => ['Logo max height', 24, 72],
                            'search_max_width' => ['Search max width', 320, 900],
                        ] as $field => [$label, $min, $max])
                            <label class="text-sm font-medium text-gray-700">{{ $label }}
                                <div class="relative mt-1">
                                    <input type="number" min="{{ $min }}" max="{{ $max }}" wire:model="presentation.custom.{{ $field }}" class="w-full rounded-lg border-gray-300 pr-10 text-sm">
                                    <span class="absolute right-3 top-2.5 text-xs text-gray-400">px</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
