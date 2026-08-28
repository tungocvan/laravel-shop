@php
    $allNavigation = $primaryNavigation->concat($moreNavigation)->values();
@endphp

@if($allNavigation->isNotEmpty())
    <aside class="hidden sm:flex sm:w-20 sm:shrink-0 sm:flex-col sm:border-r sm:border-slate-200 sm:bg-white lg:w-64" aria-label="Điều hướng ứng dụng">
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

    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-2 pb-[max(.7rem,env(safe-area-inset-bottom))] pt-2 backdrop-blur sm:hidden" aria-label="Điều hướng ứng dụng">
        <div class="mx-auto flex max-w-md items-end justify-around gap-1 text-center text-[11px] font-semibold text-slate-500">
            @foreach($primaryNavigation as $item)
                @php($active = request()->routeIs($item['route'], $item['route'].'.*'))
                <a href="{{ route($item['route']) }}"
                   class="min-w-0 flex-1 rounded-xl px-1 py-2 {{ $active ? 'bg-slate-100 text-slate-950' : 'text-slate-500' }}"
                   @if($active) aria-current="page" @endif>
                    @include('ClientPortal::partials.navigation-icon', ['name' => $item['icon'], 'class' => 'mx-auto mb-1 h-5 w-5'])
                    <span class="block truncate">{{ $item['name'] }}</span>
                </a>
            @endforeach

            @if($moreNavigation->isNotEmpty())
                @php($moreActive = $moreNavigation->contains(fn (array $item): bool => request()->routeIs($item['route'], $item['route'].'.*')))
                <details class="group relative min-w-0 flex-1">
                    <summary class="cursor-pointer list-none rounded-xl px-1 py-2 [&::-webkit-details-marker]:hidden {{ $moreActive ? 'bg-slate-100 text-slate-950' : 'text-slate-500' }}" @if($moreActive) aria-current="page" @endif>
                        @include('ClientPortal::partials.navigation-icon', ['name' => 'ellipsis-horizontal', 'class' => 'mx-auto mb-1 h-5 w-5'])
                        <span class="block truncate">Thêm</span>
                    </summary>
                    <div class="absolute bottom-full right-0 mb-3 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 text-left shadow-xl">
                        @foreach($moreNavigation as $item)
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
@else
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-2 pb-[max(.7rem,env(safe-area-inset-bottom))] pt-2 backdrop-blur sm:hidden" aria-label="Điều hướng ứng dụng">
        <div class="mx-auto max-w-md text-center text-xs font-semibold text-slate-500">
            <a href="{{ route('client.apps.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl px-4 py-2">
                @include('ClientPortal::partials.navigation-icon', ['name' => 'squares-2x2', 'class' => 'h-5 w-5'])
                <span>Ứng dụng</span>
            </a>
        </div>
    </nav>
@endif
