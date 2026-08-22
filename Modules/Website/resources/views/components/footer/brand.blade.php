<a href="/" class="flex items-center gap-2 group">
    @if(!empty($footerSettings['logo']))
        <img src="{{ str_starts_with($footerSettings['logo'], 'http') ? $footerSettings['logo'] : asset('storage/'.$footerSettings['logo']) }}" alt="{{ $footerSettings['brand_name'] }}" class="w-auto max-w-40 object-contain" style="height: var(--footer-logo-max);">
    @else
        <div class="flex items-center justify-center rounded-lg font-black text-2xl transition-colors group-hover:text-white" style="width: var(--footer-logo-max); height: var(--footer-logo-max); background: var(--footer-heading); color: var(--footer-background);">{{ mb_substr($footerSettings['brand_name'] ?? 'F', 0, 1) }}</div>
    @endif
    <span class="text-2xl font-bold tracking-tight" style="color: var(--footer-heading);">
        {{ $footerSettings['brand_name'] ?? 'FlexBiz' }}<span style="color: var(--footer-accent);">.</span>
    </span>
</a>

<p class="text-sm leading-relaxed" style="color: var(--footer-muted);">
    {{ $footerSettings['description'] ?? 'Mô tả thương hiệu chưa được cập nhật.' }}
</p>
