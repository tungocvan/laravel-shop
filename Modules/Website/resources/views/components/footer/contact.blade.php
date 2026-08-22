<div class="space-y-3">
    <div class="flex items-start gap-3">
        <svg class="w-5 h-5 mt-0.5" style="color: var(--footer-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <span class="text-sm">{{ $footerSettings['address'] ?? 'Địa chỉ chưa cập nhật' }}</span>
    </div>
    <div class="flex items-center gap-3">
        <svg class="w-5 h-5" style="color: var(--footer-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        <span class="text-sm">{{ $footerSettings['email'] ?? 'email@domain.com' }}</span>
    </div>
    <div class="flex items-center gap-3">
        <svg class="w-5 h-5" style="color: var(--footer-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
        <span class="text-sm font-bold" style="color: var(--footer-heading);">{{ $footerSettings['phone'] ?? '1900 xxxx' }}</span>
    </div>
</div>
