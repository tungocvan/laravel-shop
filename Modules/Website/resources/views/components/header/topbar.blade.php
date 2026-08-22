<div class="text-xs hidden md:block" style="background: {{ ($headerPresentation['inherit_colors'] ?? true) ? 'var(--website-color-text)' : ($headerPresentation['colors']['topbar_background'] ?? '#111827') }}; color: {{ ($headerPresentation['inherit_colors'] ?? true) ? 'var(--website-color-surface)' : ($headerPresentation['colors']['topbar_foreground'] ?? '#ffffff') }};">
    <div class="mx-auto px-4 flex justify-between items-center" style="min-height: var(--header-topbar-height); {{ !empty($headerPresentation['container_width']) ? 'max-width: '.(int)$headerPresentation['container_width'].'px;' : '' }}">
        <div class="flex items-center gap-4">
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $headerSettings['hotline'] ?? '') }}" class="hover:opacity-80">{{ $headerSettings['hotline'] ?? '1900 xxxx' }}</a>
            <span class="opacity-40">|</span>
            <a href="mailto:{{ $headerSettings['email'] ?? '' }}" class="hover:opacity-80">{{ $headerSettings['email'] ?? 'contact@domain.com' }}</a>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ $headerSettings['help_url'] ?? '#' }}" class="hover:opacity-80">Trợ giúp</a>
            <a href="{{ $headerSettings['order_tracking_url'] ?? '#' }}" class="hover:opacity-80">Theo dõi đơn hàng</a>
        </div>
    </div>
</div>
