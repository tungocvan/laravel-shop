@php
    $globalColors = (array) config('website.design.colors', []);
    $inheritColors = (bool) ($previewPresentation['inherit_colors'] ?? true);
    $colors = (array) ($previewPresentation['colors'] ?? []);

    $background = $inheritColors ? '#111827' : ($colors['background'] ?? '#111827');
    $foreground = $inheritColors ? '#9ca3af' : ($colors['foreground'] ?? '#9ca3af');
    $heading = $inheritColors ? '#ffffff' : ($colors['heading'] ?? '#ffffff');
    $muted = $inheritColors ? '#6b7280' : ($colors['muted'] ?? '#6b7280');
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
            <h3 class="font-bold text-gray-900">Responsive Preview</h3>
            <p class="text-sm text-gray-500">Preview dùng state hiện tại của Builder, kể cả thay đổi chưa bấm Lưu.</p>
        </div>
        <div class="inline-flex rounded-lg bg-white p-1 shadow-sm ring-1 ring-gray-200">
            <button type="button" @click="previewDevice = 'desktop'"
                :class="previewDevice === 'desktop' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                class="rounded-md px-3 py-1.5 text-xs font-semibold">Desktop</button>
            <button type="button" @click="previewDevice = 'mobile'"
                :class="previewDevice === 'mobile' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'"
                class="rounded-md px-3 py-1.5 text-xs font-semibold">Mobile</button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-200/60 p-4">
        <div class="mx-auto transition-all duration-200"
            :class="previewDevice === 'desktop' ? 'w-full min-w-[900px]' : 'w-[390px]'">
            <div class="overflow-hidden rounded-lg shadow-sm"
                style="background: {{ $background }}; color: {{ $foreground }}; border: 1px solid {{ $border }};">
                @if($previewPresentation['accent'] ?? true)
                    <div class="h-1" style="background: linear-gradient(90deg, {{ $accent }}, #8b5cf6, #ec4899);"></div>
                @endif

                <div x-show="previewDevice === 'desktop'" class="p-5">
                    @if(!empty($slotItems['desktop.top']))
                        <div class="mb-4 rounded-md border border-dashed p-2" style="border-color: {{ $border }};">
                            @foreach($slotItems['desktop.top'] as $item)
                                <span class="mr-2 inline-flex rounded px-2 py-1 text-xs font-semibold" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ $componentLabel($item) }}</span>
                            @endforeach
                        </div>
                    @endif

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
                                        <div class="rounded px-2 py-2 text-xs font-semibold" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ $componentLabel($item) }}</div>
                                    @empty
                                        <div class="text-xs" style="color: {{ $muted }};">Slot trống</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-4 border-t pt-4" style="border-color: {{ $border }};">
                        @foreach(['desktop.bottom.left', 'desktop.bottom.right'] as $slotKey)
                            <div class="rounded-md border border-dashed p-3" style="border-color: {{ $border }};">
                                <div class="mb-2 text-[10px] uppercase tracking-wide" style="color: {{ $muted }};">{{ $builderSlotNames[$slotKey] }}</div>
                                <div class="flex flex-wrap gap-2">
                                    @forelse($slotItems[$slotKey] ?? [] as $item)
                                        <span class="rounded px-2 py-1 text-xs" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ $componentLabel($item) }}</span>
                                    @empty
                                        <span class="text-xs" style="color: {{ $muted }};">Slot trống</span>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div x-show="previewDevice === 'mobile'" class="space-y-3 p-4">
                    <div class="text-[10px] uppercase tracking-wide" style="color: {{ $muted }};">Mobile Main</div>
                    @forelse($slotItems['mobile.main'] ?? [] as $item)
                        <div class="rounded-md px-3 py-3 text-xs font-semibold" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ $componentLabel($item) }}</div>
                    @empty
                        <div class="text-xs" style="color: {{ $muted }};">Mobile Main trống</div>
                    @endforelse

                    <div class="border-t pt-3" style="border-color: {{ $border }};">
                        <div class="mb-2 text-[10px] uppercase tracking-wide" style="color: {{ $muted }};">Mobile Bottom</div>
                        <div class="space-y-2">
                            @forelse($slotItems['mobile.bottom'] ?? [] as $item)
                                <div class="rounded-md px-3 py-2 text-xs" style="color: {{ $heading }}; border: 1px solid {{ $border }};">{{ $componentLabel($item) }}</div>
                            @empty
                                <div class="text-xs" style="color: {{ $muted }};">Mobile Bottom trống</div>
                            @endforelse
                        </div>
                    </div>
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
</section>
