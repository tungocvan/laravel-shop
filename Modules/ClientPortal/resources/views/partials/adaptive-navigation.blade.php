@php
    $allNavigation = $primaryNavigation->concat($moreNavigation)->values();
@endphp

@php($hideMobileNavigation = $hideMobileNavigation ?? false)
@php($mobilePrimaryNavigation = $mobilePrimaryNavigation ?? $primaryNavigation)
@php($mobileMoreNavigation = $mobileMoreNavigation ?? $moreNavigation)
@php($bottomNav = array_replace(['presentation_style' => 'default', 'background_color' => '#ffffff', 'background_opacity' => 95, 'icon_color' => '#64748b', 'text_color' => '#64748b', 'active_icon_color' => '#020617', 'active_text_color' => '#020617', 'active_background_color' => '#f1f5f9', 'text_font_size' => 11, 'icon_size' => 20, 'min_height' => 72], $bottomNavigationAppearance ?? []))
@php($bottomNeumorphism = ($bottomNav['presentation_style'] ?? 'default') === 'neumorphism')
@php($bottomContainerShadow = $bottomNeumorphism ? 'border-top-color: transparent; background-color: transparent;' : '')
@php($bottomDockStyle = $bottomNeumorphism ? 'background-color: #f1f4f8; border-radius: 22px; padding: 5px; box-shadow: 7px 7px 16px rgba(100,116,139,.20), -7px -7px 16px rgba(255,255,255,.98);' : '')
@php($bottomItemShadow = $bottomNeumorphism ? 'background-color: transparent;' : '')
@php($bottomActiveShadow = $bottomNeumorphism ? 'box-shadow: inset 4px 4px 8px rgba(100,116,139,.22), inset -4px -4px 8px rgba(255,255,255,.92);' : '')
@php($bottomTouchStyle = 'touch-action: manipulation; -webkit-tap-highlight-color: transparent;')


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
                        <span class="hidden truncate lg:block"{{--NAVNAME--}}>{{ $item['name'] }}</span>
                        <span class="sr-only lg:hidden"{{--NAVNAME--}}>{{ $item['name'] }}</span>
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
                                <span class="hidden truncate lg:block"{{--NAVNAME--}}>{{ $item['name'] }}</span>
                                <span class="sr-only lg:hidden"{{--NAVNAME--}}>{{ $item['name'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            @endif
        </div>
    </aside>

    @unless($hideMobileNavigation)
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 px-2 pb-3 pt-2 backdrop-blur sm:hidden" style="background-color: {{ sprintf('%s%02X', $bottomNav['background_color'], (int) round(max(0, min(100, (int) $bottomNav['background_opacity'])) * 2.55)) }}; min-height: {{ (int) $bottomNav['min_height'] }}px; {{ $bottomContainerShadow }}" aria-label="Điều hướng ứng dụng">
        <div class="mx-auto flex max-w-md items-end justify-around text-center font-semibold {{ $bottomNeumorphism ? 'gap-1' : 'gap-1' }}" style="font-size: {{ (int) $bottomNav['text_font_size'] }}px; color: {{ $bottomNav['text_color'] }}; {{ $bottomDockStyle }}">
            @foreach($mobilePrimaryNavigation as $item)
                @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                <a href="{{ route($item['route']) }}"
                   class="group/nav-item min-w-0 flex-1 select-none rounded-2xl px-1 py-1.5 transition-[transform,background-color,box-shadow] duration-100 ease-out active:scale-[0.97] motion-reduce:transform-none motion-reduce:transition-none" style="{{ $bottomTouchStyle }} {{ $active ? 'background-color: '.$bottomNav['active_background_color'].'; '.$bottomActiveShadow : $bottomItemShadow }}" onpointerdown="if({{ $bottomNeumorphism ? 'true' : 'false' }}){this.style.boxShadow='inset 4px 4px 8px rgba(100,116,139,.22), inset -4px -4px 8px rgba(255,255,255,.92)'}" onpointerup="this.style.boxShadow=''" onpointercancel="this.style.boxShadow=''" onpointerleave="this.style.boxShadow=''"
                   @if($active) aria-current="page" @endif>
                    <span class="mx-auto mb-1 block transition-transform duration-100 group-active/nav-item:translate-y-px motion-reduce:transform-none" style="width: {{ (int) $bottomNav['icon_size'] }}px; height: {{ (int) $bottomNav['icon_size'] }}px; color: {{ $active ? $bottomNav['active_icon_color'] : $bottomNav['icon_color'] }};">@include('ClientPortal::partials.navigation-icon', ['name' => $item['bottom_icon'] ?? $item['icon'], 'class' => 'h-full w-full'])</span>
                    <span class="block truncate leading-[1.125em]" title="{{ $item['name'] }}" style="color: {{ $active ? $bottomNav['active_text_color'] : $bottomNav['text_color'] }};"{{--NAVNAME--}}>{{ $item['name'] }}</span>
                </a>
            @endforeach

            @if($mobileMoreNavigation->isNotEmpty())
                @php($moreActive = $mobileMoreNavigation->contains(fn (array $item): bool => request()->routeIs($item['route'], $item['route'].'.*')))
                <details class="group relative min-w-0 flex-1">
                    <summary class="group/nav-item cursor-pointer select-none list-none rounded-2xl px-1 py-1.5 transition-[transform,background-color,box-shadow] duration-100 ease-out active:scale-[0.97] motion-reduce:transform-none motion-reduce:transition-none [&::-webkit-details-marker]:hidden" style="{{ $bottomTouchStyle }} {{ $moreActive ? 'background-color: '.$bottomNav['active_background_color'].'; '.$bottomActiveShadow : $bottomItemShadow }}" onpointerdown="if({{ $bottomNeumorphism ? 'true' : 'false' }}){this.style.boxShadow='inset 4px 4px 8px rgba(100,116,139,.22), inset -4px -4px 8px rgba(255,255,255,.92)'}" onpointerup="this.style.boxShadow=''" onpointercancel="this.style.boxShadow=''" onpointerleave="this.style.boxShadow=''" @if($moreActive) aria-current="page" @endif>
                        <span class="mx-auto mb-1 block" style="width: {{ (int) $bottomNav['icon_size'] }}px; height: {{ (int) $bottomNav['icon_size'] }}px; color: {{ $moreActive ? $bottomNav['active_icon_color'] : $bottomNav['icon_color'] }};">@include('ClientPortal::partials.navigation-icon', ['name' => 'ellipsis-horizontal', 'class' => 'h-full w-full'])</span>
                        <span class="block truncate" style="color: {{ $moreActive ? $bottomNav['active_text_color'] : $bottomNav['text_color'] }};">Thêm</span>
                    </summary>
                    <div class="absolute bottom-full right-0 mb-3 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 text-left shadow-xl">
                        @foreach($mobileMoreNavigation as $item)
                            @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                            <a href="{{ route($item['route']) }}"
                               class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm {{ $active ? 'bg-slate-100 font-bold text-slate-950' : 'font-semibold text-slate-600' }}"
                               @if($active) aria-current="page" @endif>
                                @include('ClientPortal::partials.navigation-icon', ['name' => $item['icon'], 'class' => 'h-5 w-5 shrink-0'])
                                <span class="truncate"{{--NAVNAME--}}>{{ $item['name'] }}</span>
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
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 px-2 pb-3 pt-2 backdrop-blur sm:hidden" style="background-color: {{ sprintf('%s%02X', $bottomNav['background_color'], (int) round(max(0, min(100, (int) $bottomNav['background_opacity'])) * 2.55)) }}; min-height: {{ (int) $bottomNav['min_height'] }}px; {{ $bottomContainerShadow }}" aria-label="Điều hướng ứng dụng">
        <div class="mx-auto max-w-md text-center font-semibold" style="font-size: {{ (int) $bottomNav['text_font_size'] }}px; color: {{ $bottomNav['text_color'] }};">
            <a href="{{ route('client.apps.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl px-4 py-2">
                @include('ClientPortal::partials.navigation-icon', ['name' => 'squares-2x2', 'class' => 'h-5 w-5'])
                <span>Ứng dụng</span>
            </a>
        </div>
    </nav>
    @endunless
@endif

