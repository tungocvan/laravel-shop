@php
    $globalColors = (array) config('website.design.colors', []);
    $inheritColors = (bool) ($previewPresentation['inherit_colors'] ?? true);
    $previewColors = $previewPresentation['colors'] ?? [];

    $surface = $inheritColors ? ($globalColors['surface'] ?? '#ffffff') : ($previewColors['background'] ?? '#ffffff');
    $foreground = $inheritColors ? ($globalColors['text'] ?? '#111827') : ($previewColors['foreground'] ?? '#111827');
    $accent = $inheritColors ? ($globalColors['primary'] ?? '#2563eb') : ($previewColors['accent'] ?? '#2563eb');
    $border = $inheritColors ? ($globalColors['border'] ?? '#e5e7eb') : ($previewColors['border'] ?? '#e5e7eb');
    $topbarBackground = $inheritColors ? '#111827' : ($previewColors['topbar_background'] ?? '#111827');
    $topbarForeground = $inheritColors ? '#ffffff' : ($previewColors['topbar_foreground'] ?? '#ffffff');

    $enabledItems = static fn (array $items): array => array_values(array_filter(
        $items,
        static fn ($item): bool => is_array($item) && (($item['enabled'] ?? true) !== false)
    ));

    $slotItems = [];
    foreach ($builderSlotNames as $slotKey => $slotLabel) {
        $slotItems[$slotKey] = $enabledItems((array) ($builderSlots[$slotKey] ?? []));
    }

    $desktopHeight = (int) data_get($previewPresentation, 'heights.desktop', 80);
    $tabletHeight = (int) data_get($previewPresentation, 'heights.tablet', 72);
    $mobileHeight = (int) data_get($previewPresentation, 'heights.mobile', 64);
    $topbarHeight = (int) data_get($previewPresentation, 'custom.topbar_height', 32);
    $logoHeight = (int) data_get($previewPresentation, 'custom.logo_max_height', 48);
    $searchWidth = (int) data_get($previewPresentation, 'custom.search_max_width', 560);
@endphp

<section class="rounded-xl border border-gray-200 bg-gray-50 p-4" aria-label="Responsive Header Preview">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h4 class="font-bold text-gray-900">Responsive Preview</h4>
            <p class="text-sm text-gray-500">Preview dùng state hiện tại của Builder, kể cả thay đổi chưa lưu.</p>
        </div>
        <div class="inline-flex rounded-lg bg-white p-1 shadow-sm ring-1 ring-gray-200">
            <button type="button" @click="previewDevice = 'desktop'" :class="previewDevice === 'desktop' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="rounded-md px-3 py-1.5 text-xs font-semibold">Desktop</button>
            <button type="button" @click="previewDevice = 'tablet'" :class="previewDevice === 'tablet' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="rounded-md px-3 py-1.5 text-xs font-semibold">Tablet</button>
            <button type="button" @click="previewDevice = 'mobile'" :class="previewDevice === 'mobile' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="rounded-md px-3 py-1.5 text-xs font-semibold">Mobile</button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-200/60 p-4">
        <div class="mx-auto transition-all duration-200" :class="{
            'w-full min-w-[900px]': previewDevice === 'desktop',
            'w-[768px]': previewDevice === 'tablet',
            'w-[390px]': previewDevice === 'mobile'
        }">
            <div class="overflow-hidden rounded-lg shadow-sm" style="background: {{ $surface }}; color: {{ $foreground }}; border: 1px solid {{ $border }};">
                <div x-show="previewDevice !== 'mobile'" class="flex items-center justify-between px-4 text-xs" style="height: {{ $topbarHeight }}px; background: {{ $topbarBackground }}; color: {{ $topbarForeground }};">
                    <div class="flex items-center gap-2">
                        @foreach($slotItems['desktop.topbar'] ?? [] as $item)
                            <span class="rounded px-2 py-1 font-semibold" style="background: rgba(255,255,255,.12)">{{ $headerComponents[$item['type']]['label'] ?? $item['type'] }}</span>
                        @endforeach
                    </div>
                    <span class="opacity-70">Topbar</span>
                </div>

                <div x-show="previewDevice === 'desktop'" class="grid grid-cols-3 items-center gap-3 px-4" style="height: {{ $desktopHeight }}px;">
                    @foreach(['desktop.main.left', 'desktop.main.center', 'desktop.main.right'] as $slotKey)
                        <div class="flex items-center {{ $slotKey === 'desktop.main.center' ? 'justify-center' : ($slotKey === 'desktop.main.right' ? 'justify-end' : 'justify-start') }} gap-2 rounded-md border border-dashed p-2" style="border-color: {{ $border }}; min-height: 48px;">
                            @forelse($slotItems[$slotKey] ?? [] as $item)
                                @php($type = $item['type'] ?? '')
                                <span class="inline-flex items-center justify-center rounded-md px-2 py-1 text-xs font-semibold" style="border: 1px solid {{ $border }}; color: {{ $foreground }}; max-width: {{ $type === 'search' ? $searchWidth.'px' : 'none' }};">
                                    @if($type === 'brand')
                                        <span class="mr-1 inline-block rounded bg-gray-900" style="width: {{ min(28, $logoHeight) }}px; height: {{ min(28, $logoHeight) }}px;"></span>
                                    @endif
                                    {{ $headerComponents[$type]['label'] ?? $type }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400">Slot trống</span>
                            @endforelse
                        </div>
                    @endforeach
                </div>

                <div x-show="previewDevice === 'tablet'" class="grid grid-cols-[auto_1fr_auto] items-center gap-2 px-3" style="height: {{ $tabletHeight }}px;">
                    @foreach(['desktop.main.left', 'desktop.main.center', 'desktop.main.right'] as $slotKey)
                        <div class="flex items-center {{ $slotKey === 'desktop.main.center' ? 'justify-center' : ($slotKey === 'desktop.main.right' ? 'justify-end' : 'justify-start') }} gap-1 overflow-hidden">
                            @foreach($slotItems[$slotKey] ?? [] as $item)
                                <span class="truncate rounded border px-2 py-1 text-[11px] font-semibold" style="border-color: {{ $border }};">{{ $headerComponents[$item['type']]['label'] ?? $item['type'] }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div x-show="previewDevice === 'mobile'">
                    <div class="flex items-center justify-between gap-2 px-3" style="height: {{ $mobileHeight }}px;">
                        <span class="rounded border px-2 py-1 text-xs font-semibold" style="border-color: {{ $border }};">☰</span>
                        <div class="flex min-w-0 flex-1 items-center gap-2">
                            <span class="inline-block rounded bg-gray-900" style="width: {{ min(28, $logoHeight) }}px; height: {{ min(28, $logoHeight) }}px;"></span>
                            <span class="truncate text-xs font-bold">Brand</span>
                        </div>
                        <span class="rounded px-2 py-1 text-xs font-semibold" style="background: {{ $accent }}; color: #fff;">Actions</span>
                    </div>
                    <div class="space-y-2 border-t px-3 py-3" style="border-color: {{ $border }};">
                        @foreach($slotItems['mobile.search'] ?? [] as $item)
                            <div class="rounded-lg border px-3 py-2 text-xs" style="border-color: {{ $border }};">{{ $headerComponents[$item['type']]['label'] ?? $item['type'] }}</div>
                        @endforeach
                        @foreach($slotItems['mobile.drawer'] ?? [] as $item)
                            <div class="rounded-lg border border-dashed px-3 py-2 text-xs" style="border-color: {{ $border }};">{{ $headerComponents[$item['type']]['label'] ?? $item['type'] }} · drawer</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
