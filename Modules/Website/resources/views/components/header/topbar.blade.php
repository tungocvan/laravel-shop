<div class="bg-gray-900 text-white text-xs py-2 hidden md:block">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $headerSettings['hotline'] ?? '') }}" class="hover:text-yellow-400">{{ $headerSettings['hotline'] ?? '1900 xxxx' }}</a>
            <span class="text-gray-600">|</span>
            <a href="mailto:{{ $headerSettings['email'] ?? '' }}" class="hover:text-yellow-400">{{ $headerSettings['email'] ?? 'contact@domain.com' }}</a>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ $headerSettings['help_url'] ?? '#' }}" class="hover:text-yellow-400">Trợ giúp</a>
            <a href="{{ $headerSettings['order_tracking_url'] ?? '#' }}" class="hover:text-yellow-400">Theo dõi đơn hàng</a>
        </div>
    </div>
</div>
