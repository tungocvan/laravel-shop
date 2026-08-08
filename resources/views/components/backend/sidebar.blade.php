@php($menus = config('admin_menu', []))
<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" @click="sidebarOpen = false"></div>

<aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-slate-900 text-slate-100 shadow-xl transition-transform lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    <a href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : url('/') }}"
       class="flex h-16 items-center border-b border-white/10 px-6 text-lg font-bold tracking-wide">
        {{ config('app.name') }}
    </a>

    <nav class="flex-1 space-y-2 overflow-y-auto p-3">
        @foreach ($menus as $index => $menu)
            @php
                $children = $menu['children'] ?? [];
                $allowed = auth()->user()
                    && (auth()->user()->hasRole('admin') || auth()->user()->can($menu['permission'] ?? ''));
                $childActive = collect($children)->contains(fn ($child) => request()->routeIs($child['route'] ?? ''));
                $routeActive = isset($menu['route']) && request()->routeIs($menu['route']);
            @endphp
            @continue(! $allowed)

            @if ($children)
                <div x-data="{ open: @js($childActive) }">
                    <button type="button" @click="open = ! open"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm font-medium hover:bg-white/10">
                        <span>{{ $menu['label'] }}</span>
                        <svg class="size-4 transition" :class="open && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" /></svg>
                    </button>
                    <div x-show="open" class="mt-1 space-y-1 pl-3">
                        @foreach ($children as $child)
                            @if (auth()->user()->hasRole('admin') || auth()->user()->can($child['permission'] ?? ''))
                                <a href="{{ route($child['route']) }}"
                                   class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs($child['route']) ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                    {{ $child['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                <a href="{{ route($menu['route']) }}"
                   class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ $routeActive ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    {{ $menu['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
</aside>
