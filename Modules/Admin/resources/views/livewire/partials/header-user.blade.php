<div class="relative" x-data="{ open: false }">
    <button
        @click="open = !open"
        @keydown.escape.window="open = false"
        type="button"
        class="flex items-center rounded-lg p-1.5 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        id="user-menu-button"
        :aria-expanded="open.toString()"
        aria-haspopup="menu"
    >
        <span class="sr-only">Open user menu</span>

        <div
            class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs ring-2 ring-white shadow-sm overflow-hidden">
            @if (isset($user) && $user->avatar)
                <img src="{{ asset($user->avatar) }}" alt="" class="h-full w-full object-cover">
            @else
                {{ substr($user->name ?? 'A', 0, 1) }}
            @endif
        </div>

        <span class="hidden lg:flex lg:items-center">
            <span class="ml-4 text-sm font-semibold leading-6 text-gray-900"
                aria-hidden="true">{{ $user->name ?? 'Admin' }}</span>
            <svg class="ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                    clip-rule="evenodd" />
            </svg>
        </span>
    </button>
    {{-- Phần code hiển thị Menu cũ của bạn sẽ được thay thế --}}
    <div
        x-cloak
        x-show="open"
        @click.away="open = false"
        x-transition
        role="menu"
        aria-labelledby="user-menu-button"
        class="absolute right-0 z-10 mt-2.5 w-48 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
    >

        {{-- Thông tin User cho Mobile --}}
        <div class="px-3 py-2 border-b border-gray-100 lg:hidden">
            <p class="text-sm font-medium text-gray-900">{{ $user->name ?? 'Admin' }}</p>
            <p class="text-xs text-gray-500 truncate">{{ $user->email ?? '' }}</p>
        </div>

        @foreach ($adminMenuItems as $item)
            <a href="{{ $item->url ?? ($item['url'] ?? '#') }}"
                class="block px-3 py-1 text-sm leading-6 text-gray-900 hover:bg-gray-50" role="menuitem">
                {{ $item->title ?? $item['title'] }}
            </a>
        @endforeach

        <hr class="my-1 border-gray-100">

        {{-- Nút Đăng xuất giữ nguyên logic form --}}
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit"
                class="block w-full px-3 py-1 text-left text-sm font-medium leading-6 text-red-600 hover:bg-red-50"
                role="menuitem">
                Đăng xuất
            </button>
        </form>
    </div>
</div>
