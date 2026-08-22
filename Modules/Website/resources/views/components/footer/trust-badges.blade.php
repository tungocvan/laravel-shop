@php($trustBadges = collect($footerSettings['trust_badges'] ?? [])->filter(fn ($item) => is_array($item) && ($item['enabled'] ?? true) && !empty($item['image_url'])))

@if($trustBadges->isNotEmpty())
    <div class="flex flex-wrap items-center gap-4 grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all duration-300">
        @foreach($trustBadges as $badge)
            @php($image = $badge['image_url'] ?? '')
            @php($isExternal = str_starts_with($image, 'http://') || str_starts_with($image, 'https://'))
            @php($src = $isExternal ? $image : asset('storage/'.$image))

            @if(!empty($badge['url']))
                <a href="{{ $badge['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $badge['label'] ?? '' }}">
                    <img src="{{ $src }}" alt="{{ $badge['label'] ?? 'Trust badge' }}" loading="lazy" class="h-8 max-w-28 object-contain bg-white/90 rounded p-1">
                </a>
            @else
                <img src="{{ $src }}" alt="{{ $badge['label'] ?? 'Trust badge' }}" title="{{ $badge['label'] ?? '' }}" loading="lazy" class="h-8 max-w-28 object-contain bg-white/90 rounded p-1">
            @endif
        @endforeach
    </div>
@endif
