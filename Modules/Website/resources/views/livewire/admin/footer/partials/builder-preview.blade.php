@php
    $globalColors = (array) config('website.design.colors', []);
    $inheritColors = (bool) ($previewPresentation['inherit_colors'] ?? true);
    $colors = (array) ($previewPresentation['colors'] ?? []);

    $background = $inheritColors ? '#111827' : ($colors['background'] ?? '#111827');
    $foreground = $inheritColors ? '#9ca3af' : ($colors['foreground'] ?? '#9ca3af');
    $heading = $inheritColors ? '#ffffff' : ($colors['heading'] ?? '#ffffff');
    $muted = $inheritColors ? '#9ca3af' : ((($colors['muted'] ?? '#6b7280') === '#6b7280') ? ($colors['foreground'] ?? '#9ca3af') : $colors['muted']);
    $accent = $inheritColors ? ($globalColors['primary'] ?? '#2563eb') : ($colors['accent'] ?? '#2563eb');
    $border = $inheritColors ? '#1f2937' : ($colors['border'] ?? '#1f2937');

    $enabled = static fn (array $items): array => array_values(array_filter(
        $items,
        static fn ($item): bool => is_array($item) && (($item['enabled'] ?? true) !== false)
    ));

    $slotItems = [];
    foreach ($builderSlotNames as $slotKey => $slotLabel) {
        $slotItems[$slotKey] = $enabled((array) ($builderSlots[$slotKey] ?? []));
    }

    $componentLabel = static fn (array $item): string => $footerComponents[$item['type'] ?? '']['label'] ?? ($item['type'] ?? 'Unknown');
@endphp

<section class="rounded-xl border border-gray-200 bg-gray-50 p-4" aria-label="Responsive Footer Preview">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="font-bold text-gray-900">Xem trước Footer</h3>
            <p class="text-sm text-gray-500">Xem nội dung bản nháp theo kích thước màn hình. Thay đổi chưa lưu không ảnh hưởng website.</p>
        </div>
        <div class="inline-flex rounded-lg bg-white p-1 shadow-sm ring-1 ring-gray-200">
            <button type="button" @click="previewDevice = 'desktop'"
                :class="previewDevice === 'desktop' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                class="rounded-md px-3 py-1.5 text-xs font-semibold">Desktop</button>
            <button type="button" @click="previewDevice = 'tablet'"
                :class="previewDevice === 'tablet' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                class="rounded-md px-3 py-1.5 text-xs font-semibold">Tablet</button>
            <button type="button" @click="previewDevice = 'mobile'"
                :class="previewDevice === 'mobile' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                class="rounded-md px-3 py-1.5 text-xs font-semibold">Mobile</button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-200/60 p-3">
        <div class="mx-auto transition-all duration-200"
            :class="previewDevice === 'desktop' ? 'w-full min-w-0' : (previewDevice === 'tablet' ? 'w-full max-w-[768px]' : 'w-full max-w-[390px]')">
            <div class="overflow-hidden rounded-lg shadow-sm"
                style="background: {{ $background }}; color: {{ $foreground }}; border: 1px solid {{ $border }};">
                @if($previewPresentation['accent'] ?? true)
                    <div class="h-1" style="background: linear-gradient(90deg, {{ $accent }}, #8b5cf6, #ec4899);"></div>
                @endif

                <div x-show="previewDevice !== 'mobile'" class="p-5">
                    @if(!empty($slotItems['desktop.top']))
                        <div class="mb-4 rounded-md border border-dashed p-2" style="border-color: {{ $border }};">
                            @foreach($slotItems['desktop.top'] as $item)
                                <span class="mr-2 inline-flex rounded px-2 py-1 text-xs font-semibold" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ $componentLabel($item) }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if($columnsSource === 'profile')
                    <div class="mb-4 grid grid-cols-12 gap-2" aria-label="Các cột Footer Desktop">
                        @foreach($footerColumns as $columnIndex => $column)
                            @if(($column['enabled'] ?? true) && ($column['desktop'] ?? true))
                                <button type="button" @click="selectFooterColumn({{ $columnIndex }})" :aria-pressed="selectedFooterColumn === {{ $columnIndex }} ? 'true' : 'false'" :class="selectedFooterColumn === {{ $columnIndex }} ? 'ring-2 ring-blue-400' : ''" class="min-w-0 rounded border p-2 text-left transition hover:ring-2 hover:ring-blue-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-400" aria-label="Chỉnh sửa cột {{ $column['title'] ?? 'Footer' }}" style="grid-column: span {{ (int) ($column['width'] ?? 3) }} / span {{ (int) ($column['width'] ?? 3) }}; border-color: {{ $border }};">
                                    <div class="text-xs font-semibold" style="color: {{ $heading }};">{{ $column['title'] ?? '' }}</div>
                                    <div class="mt-1 text-[10px]" style="color: {{ $muted }};">{{ $column['module'] ?? 'menu-links' }}</div>
                                    @foreach(array_slice($column['items'] ?? [], 0, 8) as $columnItem)
                                        <div class="mt-1 truncate text-[10px]" style="color: {{ $foreground }};">{{ $columnItem['label'] ?? '' }}</div>
                                    @endforeach
                                </button>
                            @endif
                        @endforeach
                    </div>
                    @else
                    <div class="grid grid-cols-12 gap-4">
                        @foreach([
                            'desktop.main.brand' => 'col-span-4',
                            'desktop.main.columns' => 'col-span-5',
                            'desktop.main.extra' => 'col-span-3',
                        ] as $slotKey => $span)
                            <div class="{{ $span }} min-h-28 rounded-md border border-dashed p-3" style="border-color: {{ $border }};">
                                <div class="mb-2 text-[10px] uppercase tracking-wide" style="color: {{ $muted }};">{{ $builderSlotNames[$slotKey] }}</div>
                                <div class="space-y-2">
                                    @forelse($slotItems[$slotKey] ?? [] as $item)
                                        <button type="button" @click="selectFooterSlot(@js($slotKey))" class="block w-full rounded px-2 py-2 text-left text-xs font-semibold hover:ring-2 hover:ring-blue-300" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ $componentLabel($item) }}</button>
                                    @empty
                                        <div class="text-xs" style="color: {{ $muted }};">Slot trống</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @endif
                    @if($bottomBarEnabled)
                    <div class="mt-5 grid grid-cols-2 gap-4 border-t pt-4" style="border-color: {{ $border }};">
                        @foreach(['desktop.bottom.left', 'desktop.bottom.right'] as $slotKey)
                            <div class="rounded-md border border-dashed p-3" style="border-color: {{ $border }};">
                                <div class="mb-2 text-[10px] uppercase tracking-wide" style="color: {{ $muted }};">{{ $builderSlotNames[$slotKey] }}</div>
                                <div class="flex flex-wrap gap-2">
                                    @forelse($slotItems[$slotKey] ?? [] as $item)
                                        <button type="button" @click="selectFooterSlot(@js($slotKey))" class="rounded px-2 py-1 text-left text-xs hover:ring-2 hover:ring-blue-300" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ ($item['type'] ?? '') === 'copyright' && filled(data_get($profileContent, 'legal.overrides.copyright')) ? data_get($profileContent, 'legal.overrides.copyright') : $componentLabel($item) }}</button>
                                    @empty
                                        <span class="text-xs" style="color: {{ $muted }};">Slot trống</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div x-show="previewDevice === 'mobile'" class="space-y-3 p-4">
                    @if($columnsSource === 'profile')
                    <div class="space-y-2" aria-label="Các cột Footer Mobile">
                        @foreach($footerColumns as $column)
                            @if(($column['enabled'] ?? true) && ($column['mobile'] ?? true))
                                <button type="button" @click="selectFooterColumn({{ $columnIndex }})" :aria-pressed="selectedFooterColumn === {{ $columnIndex }} ? 'true' : 'false'" :class="selectedFooterColumn === {{ $columnIndex }} ? 'ring-2 ring-blue-400' : ''" class="w-full rounded border p-2 text-left transition hover:ring-2 hover:ring-blue-300" aria-label="Chỉnh sửa cột {{ $column['title'] ?? 'Footer' }}" style="border-color: {{ $border }};">
                                    <div class="text-xs font-semibold" style="color: {{ $heading }};">{{ $column['title'] ?? '' }}</div>
                                    @foreach(array_slice($column['items'] ?? [], 0, 8) as $columnItem)
                                    <div class="mt-1 text-xs break-words" style="color: {{ $foreground }};">{{ $columnItem['label'] ?? '' }}</div>
                                @endforeach
                                </button>
                            @endif
                        @endforeach
                    </div>
                    @else
                    <div class="text-[10px] uppercase tracking-wide" style="color: {{ $muted }};">Mobile Main</div>
                    @forelse($slotItems['mobile.main'] ?? [] as $item)
                        <button type="button" @click="selectFooterSlot('mobile.main')" class="w-full rounded-md px-3 py-3 text-left text-xs font-semibold hover:ring-2 hover:ring-blue-300" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ $componentLabel($item) }}</button>
                    @empty
                        <div class="text-xs" style="color: {{ $muted }};">Mobile Main trống</div>
                    @endforelse

                    @endif
                    @if($bottomBarEnabled)
                    <div class="border-t pt-3" style="border-color: {{ $border }};">
                        <div class="mb-2 text-[10px] uppercase tracking-wide" style="color: {{ $muted }};">Mobile Bottom</div>
                        <div class="space-y-2">
                            @forelse($slotItems['mobile.bottom'] ?? [] as $item)
                                <button type="button" @click="selectFooterSlot('mobile.bottom')" class="w-full rounded-md px-3 py-2 text-left text-xs hover:ring-2 hover:ring-blue-300" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ $componentLabel($item) }}</button>
                            @empty
                                <div class="text-xs" style="color: {{ $muted }};">Mobile Bottom trống</div>
                            @endforelse
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(!empty($slotItems['overlay']))
        <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
            <span class="font-semibold">Overlay dùng chung:</span>
            @foreach($slotItems['overlay'] as $item)
                <span class="rounded bg-white px-2 py-1 ring-1 ring-gray-200">{{ $componentLabel($item) }}</span>
            @endforeach
        </div>
    @endif
    <div class="mt-4 space-y-2 border-t border-slate-200 pt-4" aria-label="Thao tác lưu Footer Theme">
        <div class="text-xs font-semibold text-slate-700">
            Theme đang chỉnh sửa: {{ $selectedTheme !== '' ? ($layoutThemes[$selectedTheme]['name'] ?? $selectedTheme) : 'Chưa chọn theme' }}
        </div>
        <p class="text-xs text-slate-500">Cập nhật chỉ lưu bản nháp. Lưu &amp; Kích hoạt sẽ xuất bản toàn bộ thay đổi đang xem trước.</p>
        @error('theme') <p class="text-xs text-red-600" role="alert">{{ $message }}</p> @enderror
        <button type="button" wire:click="updateTheme" wire:loading.attr="disabled" wire:target="updateTheme,saveAndPublishTheme" @disabled(!$selectedTheme)
            class="w-full rounded-lg border border-blue-300 bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-40">
            Cập nhật tham số vào theme hiện tại
        </button>
        <button type="button" wire:click="saveAndPublishTheme" wire:confirm="Lưu toàn bộ thay đổi hiện tại và kích hoạt theme này trên website?" wire:loading.attr="disabled" wire:target="updateTheme,saveAndPublishTheme" @disabled(!$selectedTheme)
            class="w-full rounded-lg bg-emerald-700 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-40">
            Lưu &amp; Kích hoạt theme hiện tại
        </button>
    </div>
</section>
