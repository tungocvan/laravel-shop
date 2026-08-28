@php
    $portalAccount = $portalAccount
        ?? app(\Modules\ClientPortal\Services\PortalAccountPresenter::class)->for(auth('web')->user());
@endphp

@if($portalAccount !== [])
    <details class="relative" data-client-account-menu>
        <summary
            class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 bg-white p-1.5 pr-2 text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 [&::-webkit-details-marker]:hidden"
            aria-label="Mở menu tài khoản của {{ $portalAccount['name'] }}"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-900 text-xs font-bold text-white">
                @if($portalAccount['avatar_url'])
                    <img src="{{ $portalAccount['avatar_url'] }}" alt="" class="h-full w-full object-cover" loading="lazy" referrerpolicy="no-referrer">
                @else
                    {{ $portalAccount['initials'] }}
                @endif
            </span>
            <span class="hidden max-w-36 truncate text-left text-sm font-bold lg:block">{{ $portalAccount['name'] }}</span>
            <svg class="hidden h-4 w-4 shrink-0 text-slate-400 lg:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </summary>

        <div class="fixed inset-x-4 top-[4.5rem] z-50 max-h-[calc(100dvh-5.5rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-3 sm:w-80">
            <div class="border-b border-slate-100 px-3 py-3">
                <div class="truncate text-sm font-bold text-slate-950">{{ $portalAccount['name'] }}</div>
                <div class="mt-0.5 truncate text-xs text-slate-500">{{ $portalAccount['email'] ?: 'Chưa có email' }}</div>
            </div>

            <nav class="py-1" aria-label="Tùy chọn tài khoản">
                <a
                    href="{{ route('client.apps.account') }}"
                    class="flex min-h-11 items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('client.apps.account') ? 'bg-slate-100 text-slate-950' : 'text-slate-700 hover:bg-slate-50' }}"
                    @if(request()->routeIs('client.apps.account')) aria-current="page" @endif
                >
                    <span>Thông tin tài khoản</span>
                    <span aria-hidden="true">›</span>
                </a>
                <a
                    href="{{ route('client.apps.settings') }}"
                    class="flex min-h-11 items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('client.apps.settings') ? 'bg-slate-100 text-slate-950' : 'text-slate-700 hover:bg-slate-50' }}"
                    @if(request()->routeIs('client.apps.settings')) aria-current="page" @endif
                >
                    <span>Cài đặt</span>
                    <span aria-hidden="true">›</span>
                </a>
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 pt-1">
                @csrf
                <button type="submit" class="flex min-h-11 w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-bold text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-inset">
                    Đăng xuất
                </button>
            </form>
        </div>
    </details>

    <script>
    (() => {
        document.querySelectorAll('[data-client-account-menu]:not([data-account-menu-ready])').forEach((menu) => {
            menu.dataset.accountMenuReady = '1';
            const summary = menu.querySelector('summary');

            document.addEventListener('click', (event) => {
                if (menu.open && !menu.contains(event.target)) menu.removeAttribute('open');
            });

            menu.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape' || !menu.open) return;
                menu.removeAttribute('open');
                summary?.focus();
            });

            menu.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => menu.removeAttribute('open'));
            });
        });
    })();
    </script>
@endif
