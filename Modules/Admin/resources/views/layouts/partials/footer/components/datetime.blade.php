<div
    class="flex shrink-0 items-center gap-2 tabular-nums text-[var(--admin-text-muted)]"
    data-admin-footer-datetime
    data-show-date="{{ $showDate ? '1' : '0' }}"
    data-show-time="{{ $showTime ? '1' : '0' }}"
    x-data="{ now: new Date(), timer: null }"
    x-init="timer = setInterval(() => now = new Date(), 1000)"
>
    @if ($showDate)
        <span x-text="String(now.getDate()).padStart(2, '0') + '/' + String(now.getMonth() + 1).padStart(2, '0') + '/' + now.getFullYear()">{{ $date }}</span>
    @endif
    @if ($showDate && $showTime)<span aria-hidden="true" class="text-[var(--admin-border-subtle)]">·</span>@endif
    @if ($showTime)
        <time x-text="String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0')">{{ $time }}</time>
    @endif
</div>
