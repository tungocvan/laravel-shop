<div class="lg:col-span-4 space-y-6">
    <a href="/" class="flex items-center gap-2 group">
        @if(!empty($footerSettings['logo']))
            <img src="{{ str_starts_with($footerSettings['logo'], 'http') ? $footerSettings['logo'] : asset('storage/'.$footerSettings['logo']) }}" alt="{{ $footerSettings['brand_name'] }}" class="h-10 w-auto max-w-40 object-contain">
        @else
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-gray-900 font-black text-2xl group-hover:bg-blue-500 group-hover:text-white transition-colors">{{ mb_substr($footerSettings['brand_name'] ?? 'F', 0, 1) }}</div>
        @endif
        <span class="text-2xl font-bold text-white tracking-tight">
            {{ $footerSettings['brand_name'] ?? 'FlexBiz' }}<span class="text-blue-500">.</span>
        </span>
    </a>

    <p class="text-sm leading-relaxed text-gray-500">
        {{ $footerSettings['description'] ?? 'Mô tả thương hiệu chưa được cập nhật.' }}
    </p>

    <div class="space-y-3">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span class="text-sm">{{ $footerSettings['address'] ?? 'Địa chỉ chưa cập nhật' }}</span>
        </div>
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 002 2v10a2 2 0 002 2z"></path></svg>
            <span class="text-sm">{{ $footerSettings['email'] ?? 'email@domain.com' }}</span>
        </div>
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
            <span class="text-sm font-bold text-white">{{ $footerSettings['phone'] ?? '1900 xxxx' }}</span>
        </div>
    </div>
</div>
