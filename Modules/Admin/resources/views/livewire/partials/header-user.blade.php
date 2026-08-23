@php
    $showAvatar = (bool) data_get($userMenuContext, 'show_avatar', true);
    $showName = (bool) data_get($userMenuContext, 'show_name', true);
    $showEmail = (bool) data_get($userMenuContext, 'show_email', true);
    $showRole = (bool) data_get($userMenuContext, 'show_role', false);
    $roleLabel = data_get($userMenuContext, 'role');
    $menuItems = (array) data_get($userMenuContext, 'items', []);
@endphp

<div class="relative" x-data="{ open: false }">
    <button
        @click="open = !open"
        @keydown.escape.window="open = false"
        type="button"
        class="group flex h-10 items-center gap-2 rounded-xl px-1.5 transition duration-200 hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
        id="user-menu-button"
        :aria-expanded="open.toString()"
        aria-haspopup="menu"
    >
        <span class="sr-only">Mở menu tài khoản</span>

        @if ($showAvatar)
            <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-indigo-50 text-xs font-bold text-indigo-600 ring-1 ring-indigo-100">
                @if (isset($user) && $user->avatar)
                    <img src="{{ asset($user->avatar) }}" alt="" class="h-full w-full object-cover">
                @else
                    {{ substr($user->name ?? 'A', 0, 1) }}
                @endif
            </span>
        @endif

        @if ($showName)
            <span class="hidden min-w-0 lg:block">
                <span class="block max-w-36 truncate text-left text-sm font-semibold leading-5 text-slate-700">{{ $user->name ?? 'Admin' }}</span>
            </span>
        @endif

        <svg class="hidden h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200 lg:block" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-cloak
        x-show="open"
        @click.away="open = false"
        @keydown.escape.window="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
        role="menu"
        aria-labelledby="user-menu-button"
        class="absolute right-0 z-30 mt-2 w-64 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 focus:outline-none"
    >
        @if ($showName || $showEmail || ($showRole && $roleLabel))
            <div class="border-b border-slate-100 px-4 py-3">
                @if ($showName)
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name ?? 'Admin' }}</p>
                @endif
                @if ($showEmail)
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $user->email ?? '' }}</p>
                @endif
                @if ($showRole && $roleLabel)
                    <p class="mt-1 truncate text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $roleLabel }}</p>
                @endif
            </div>
        @endif

        @if (count($menuItems))
            <div class="p-1.5">
                @foreach ($menuItems as $item)
                    <a
                        href="{{ $item['url'] }}"
                        target="{{ $item['target'] ?? '_self' }}"
                        @if (($item['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif
                        class="flex min-h-9 items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 hover:text-slate-950 focus:bg-slate-50 focus:outline-none"
                        role="menuitem"
                    >
                        @if (! empty($item['icon']))
                            <i class="{{ $item['icon'] }} w-4 text-center text-xs text-slate-400" aria-hidden="true"></i>
                        @endif
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="border-t border-slate-100 p-1.5">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"
                    class="flex min-h-9 w-full items-center rounded-lg px-2.5 py-2 text-left text-sm font-semibold text-rose-600 transition-colors hover:bg-rose-50 focus:bg-rose-50 focus:outline-none"
                    role="menuitem">
                    Đăng xuất
                </button>
            </form>
        </div>
    </div>
</div>
