<div class="flex items-center gap-3 md:gap-8">
    <button type="button" @click="mobileMenuOpen=true" aria-label="Mở menu điều hướng" class="lg:hidden p-2 -ml-2 rounded-full opacity-70 hover:bg-black/5">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <a href="/" class="flex-shrink-0 flex items-center gap-2 group">
        @if(!empty($headerSettings['logo']))
            <img src="{{ str_starts_with($headerSettings['logo'], 'http') ? $headerSettings['logo'] : asset('storage/'.$headerSettings['logo']).'?v='.md5($headerSettings['logo']) }}" alt="{{ $headerSettings['brand_name'] ?? 'Logo website' }}" class="w-auto max-w-40 object-contain" style="max-height: var(--header-logo-max);">
        @else
            <div class="w-8 h-8 md:w-10 md:h-10 bg-black text-white rounded-lg flex items-center justify-center font-black text-lg md:text-xl">{{ substr($headerSettings['brand_name'] ?? 'F',0,1) }}</div>
        @endif
        <span class="text-xl md:text-2xl font-bold" style="color: var(--header-text);">{{ $headerSettings['brand_name'] ?? 'FlexBiz' }}<span style="color: var(--header-accent);">.</span></span>
    </a>
</div>
