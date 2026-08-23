@php
    $customActions = (array) data_get($actions, 'items', []);
    $notification = (array) data_get($actions, 'notification', []);
    $primaryActions = array_values(array_filter($customActions, fn ($action) => ($action['priority'] ?? 'secondary') === 'primary'));
    $secondaryActions = array_values(array_filter($customActions, fn ($action) => ($action['priority'] ?? 'secondary') !== 'primary'));
    $mobileOverflow = (bool) data_get($actions, 'mobile_overflow', true) && (bool) data_get($actions, 'overflow_secondary_actions', true) && count($secondaryActions) > 0;
    $actionButtonClass = 'relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-100 text-indigo-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-indigo-300 hover:bg-indigo-200 hover:text-indigo-800 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-indigo-500/15 motion-reduce:transform-none';
@endphp

<div class="flex shrink-0 items-center gap-1.5 sm:gap-2" data-admin-header-actions>
    @if ((bool) data_get($actions, 'notifications', true))
        <div data-admin-system-action="notifications">
            @if (data_get($notification, 'behavior', 'dropdown') === 'link' && data_get($notification, 'url'))
                <a href="{{ data_get($notification, 'url') }}" target="{{ data_get($notification, 'target', '_self') }}"
                    @if (data_get($notification, 'target', '_self') === '_blank') rel="noopener noreferrer" @endif
                    class="{{ $actionButtonClass }} group" aria-label="Xem thông báo">
                    @include('Admin::livewire.partials.header.components.action-icon', ['icon' => data_get($notification, 'icon', 'bell'), 'class' => 'h-5 w-5 transition-transform duration-200 group-hover:scale-110'])
                    <span class="absolute right-1.5 top-1.5 h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-white" aria-hidden="true"></span>
                </a>
            @else
                @livewire('admin.partials.header-notifications', ['icon' => data_get($notification, 'icon', 'bell')])
            @endif
        </div>
    @endif

    @foreach ($primaryActions as $action)
        <a href="{{ $action['url'] }}" target="{{ $action['target'] ?? '_self' }}"
            @if (($action['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif
            title="{{ $action['title'] }}" aria-label="{{ $action['title'] }}"
            data-admin-header-action-priority="primary" class="{{ $actionButtonClass }} group">
            @include('Admin::livewire.partials.header.components.action-icon', ['icon' => $action['icon'], 'class' => 'h-5 w-5 transition-transform duration-200 group-hover:scale-110'])
            @if (! empty($action['badge']))<span class="absolute -right-1 -top-1 min-w-4 rounded-full bg-rose-500 px-1 text-center text-[9px] font-bold leading-4 text-white ring-2 ring-white">{{ $action['badge'] }}</span>@endif
        </a>
    @endforeach

    @foreach ($secondaryActions as $action)
        <a href="{{ $action['url'] }}" target="{{ $action['target'] ?? '_self' }}"
            @if (($action['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif
            title="{{ $action['title'] }}" aria-label="{{ $action['title'] }}"
            data-admin-header-action-priority="secondary"
            class="{{ $actionButtonClass }} group {{ $mobileOverflow ? 'hidden sm:inline-flex' : 'inline-flex' }}">
            @include('Admin::livewire.partials.header.components.action-icon', ['icon' => $action['icon'], 'class' => 'h-5 w-5 transition-transform duration-200 group-hover:scale-110'])
            @if (! empty($action['badge']))<span class="absolute -right-1 -top-1 min-w-4 rounded-full bg-rose-500 px-1 text-center text-[9px] font-bold leading-4 text-white ring-2 ring-white">{{ $action['badge'] }}</span>@endif
        </a>
    @endforeach

    @if ($mobileOverflow)
        <div class="relative sm:hidden" x-data="{ open: false }" data-admin-header-mobile-overflow>
            <button type="button" @click="open = !open" @keydown.escape.window="open = false" class="{{ $actionButtonClass }}" aria-label="Thêm thao tác" :aria-expanded="open.toString()">
                @include('Admin::livewire.partials.header.components.action-icon', ['icon' => 'ellipsis', 'class' => 'h-5 w-5'])
            </button>
            <div x-cloak x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10">
                @foreach ($secondaryActions as $action)
                    <a href="{{ $action['url'] }}" target="{{ $action['target'] ?? '_self' }}" @if (($action['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif class="flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 ring-1 ring-indigo-200">
                            @include('Admin::livewire.partials.header.components.action-icon', ['icon' => $action['icon'], 'class' => 'h-4 w-4'])
                        </span>
                        <span class="min-w-0 flex-1 truncate">{{ $action['title'] }}</span>
                        @if (! empty($action['badge']))<span class="rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-bold text-rose-600">{{ $action['badge'] }}</span>@endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
