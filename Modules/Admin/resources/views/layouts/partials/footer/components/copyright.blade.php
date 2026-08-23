<div class="flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-1" data-admin-footer-copyright>
    <span class="shrink-0">© {{ $year }}</span>
    @if ($showAppName)
        <span class="font-semibold text-[var(--admin-text-secondary)]">{{ $appName }}</span>
    @endif
    @if ($owner !== '')
        <span class="hidden text-[var(--admin-text-muted)] sm:inline">·</span>
        <span class="text-[var(--admin-text-muted)]">Bản quyền:</span>
        @if ($url)
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--admin-text-secondary)] transition hover:text-[var(--admin-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--admin-focus-ring)] focus:ring-offset-2">{{ $owner }}</a>
        @else
            <span class="font-medium text-[var(--admin-text-secondary)]">{{ $owner }}</span>
        @endif
    @endif
</div>
