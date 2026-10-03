@php
    $allNavigation = $primaryNavigation->concat($moreNavigation)->values();
@endphp

@php($hideMobileNavigation = $hideMobileNavigation ?? false)
@php($mobilePrimaryNavigation = $mobilePrimaryNavigation ?? $primaryNavigation)
@php($mobileMoreNavigation = $mobileMoreNavigation ?? $moreNavigation)
@php
    $bottomNavigationAppearance = $bottomNavigationAppearance ?? [];
    $bottomBackground = $bottomNavigationAppearance['background_color'] ?? '#ffffff';
    $bottomOpacity = max(0, min(100, (int) ($bottomNavigationAppearance['background_opacity'] ?? 95))) / 100;
    $bottomRgb = sscanf(ltrim($bottomBackground, '#'), '%02x%02x%02x') ?: [255, 255, 255];
    $bottomBackgroundRgba = 'rgba('.implode(',', $bottomRgb).','.$bottomOpacity.')';
    $bottomIconColor = $bottomNavigationAppearance['icon_color'] ?? '#64748b';
    $bottomTextColor = $bottomNavigationAppearance['text_color'] ?? '#64748b';
    $bottomTextSize = (int) ($bottomNavigationAppearance['text_font_size'] ?? 11);
    $bottomIconSize = (int) ($bottomNavigationAppearance['icon_size'] ?? 20);
    $bottomMinHeight = (int) ($bottomNavigationAppearance['min_height'] ?? 72);
@endphp

@if($allNavigation->isNotEmpty())
    <aside class="hidden sm:flex sm:w-20 sm:shrink-0 sm:flex-col sm:border-r sm:border-slate-200 sm:bg-white lg:w-56 xl:w-60" aria-label="Điều hướng ứng dụng">
        <div class="sticky top-[65px] flex min-h-[calc(100dvh-65px)] flex-col px-2 py-4 lg:px-3">
            <nav class="space-y-1">
                @foreach($primaryNavigation as $item)
                    @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                    <a href="{{ route($item['route']) }}"
                       class="group flex min-h-12 items-center justify-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition lg:justify-start {{ $active ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}"
                       @if($active) aria-current="page" @endif>
                        @include('ClientPortal::partials.navigation-icon', ['name' => $item['icon'], 'class' => 'h-5 w-5 shrink-0'])
                        <span class="hidden truncate lg:block">{{ $item['name'] }}</span>
                        <span class="sr-only lg:hidden">{{ $item['name'] }}</span>
                    </a>
                @endforeach
            </nav>

            @if($moreNavigation->isNotEmpty())
                <div class="mt-4 border-t border-slate-200 pt-4">
                    <div class="mb-2 hidden px-3 text-xs font-bold uppercase tracking-wide text-slate-400 lg:block">Thêm</div>
                    <nav class="space-y-1" aria-label="Điều hướng bổ sung">
                        @foreach($moreNavigation as $item)
                            @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                            <a href="{{ route($item['route']) }}"
                               class="group flex min-h-12 items-center justify-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition lg:justify-start {{ $active ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}"
                               @if($active) aria-current="page" @endif>
                                @include('ClientPortal::partials.navigation-icon', ['name' => $item['icon'], 'class' => 'h-5 w-5 shrink-0'])
                                <span class="hidden truncate lg:block">{{ $item['name'] }}</span>
                                <span class="sr-only lg:hidden">{{ $item['name'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            @endif
        </div>
    </aside>

    @unless($hideMobileNavigation)
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 px-2 pb-[max(.7rem,env(safe-area-inset-bottom))] pt-2 backdrop-blur sm:hidden" style="background-color: {{ $bottomBackgroundRgba }}; min-height: calc({{ $bottomMinHeight }}px + env(safe-area-inset-bottom, 0px));" aria-label="Điều hướng ứng dụng">
        <div class="mx-auto flex max-w-md items-end justify-around gap-1 text-center font-semibold" style="font-size: {{ $bottomTextSize }}px; color: {{ $bottomTextColor }};">
            @foreach($mobilePrimaryNavigation as $item)
                @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                <a href="{{ route($item['route']) }}"
                   class="min-w-0 flex-1 rounded-xl px-1 py-2 {{ $active ? 'bg-slate-100 text-slate-950' : 'text-slate-500' }}"
                   @if($active) aria-current="page" @endif>
                    <span class="mx-auto mb-1 block" style="width: {{ $bottomIconSize }}px; height: {{ $bottomIconSize }}px; color: {{ $active ? '#020617' : $bottomIconColor }};">@include('ClientPortal::partials.navigation-icon', ['name' => $item['bottom_icon'] ?? $item['icon'], 'class' => 'h-full w-full'])</span>
                    <span class="block truncate" style="color: {{ $active ? '#020617' : $bottomTextColor }};">{{ $item['name'] }}</span>
                </a>
            @endforeach

            @if($mobileMoreNavigation->isNotEmpty())
                @php($moreActive = $mobileMoreNavigation->contains(fn (array $item): bool => request()->routeIs($item['route'], $item['route'].'.*')))
                <details class="group relative min-w-0 flex-1">
                    <summary class="cursor-pointer list-none rounded-xl px-1 py-2 [&::-webkit-details-marker]:hidden {{ $moreActive ? 'bg-slate-100 text-slate-950' : 'text-slate-500' }}" @if($moreActive) aria-current="page" @endif>
                        <span class="mx-auto mb-1 block" style="width: {{ $bottomIconSize }}px; height: {{ $bottomIconSize }}px; color: {{ $moreActive ? '#020617' : $bottomIconColor }};">@include('ClientPortal::partials.navigation-icon', ['name' => 'ellipsis-horizontal', 'class' => 'h-full w-full'])</span>
                        <span class="block truncate" style="color: {{ $moreActive ? '#020617' : $bottomTextColor }};">Thêm</span>
                    </summary>
                    <div class="absolute bottom-full right-0 mb-3 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 text-left shadow-xl">
                        @foreach($mobileMoreNavigation as $item)
                            @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                            <a href="{{ route($item['route']) }}"
                               class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm {{ $active ? 'bg-slate-100 font-bold text-slate-950' : 'font-semibold text-slate-600' }}"
                               @if($active) aria-current="page" @endif>
                                @include('ClientPortal::partials.navigation-icon', ['name' => $item['icon'], 'class' => 'h-5 w-5 shrink-0'])
                                <span class="truncate">{{ $item['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    </nav>
    @endunless
@else
    @unless($hideMobileNavigation)
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-2 pb-[max(.7rem,env(safe-area-inset-bottom))] pt-2 backdrop-blur sm:hidden" aria-label="Điều hướng ứng dụng">
        <div class="mx-auto max-w-md text-center text-xs font-semibold text-slate-500">
            <a href="{{ route('client.apps.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl px-4 py-2">
                @include('ClientPortal::partials.navigation-icon', ['name' => 'squares-2x2', 'class' => 'h-5 w-5'])
                <span>Ứng dụng</span>
            </a>
        </div>
    </nav>
    @endunless
@endif
