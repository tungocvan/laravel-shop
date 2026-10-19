@php($trustBadges = collect($footerSettings['trust_badges'] ?? [])->filter(fn ($item) => is_array($item) && ($item['enabled'] ?? true) && !empty($item['image_url'])))

@if($trustBadges->isNotEmpty())
    <div class="flex flex-wrap items-center justify-center md:justify-end gap-2.5">
        @foreach($trustBadges as $badge)
            @php($image = $badge['image_url'] ?? '')
            @php($isExternal = str_starts_with($image, 'http://') || str_starts_with($image, 'https://'))
            @php($src = $isExternal ? $image : asset('storage/'.$image))

            @if(!empty($badge['url']))
                <a href="{{ $badge['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $badge['label'] ?? '' }}">
                    <img src="{{ $src }}" alt="{{ $badge['label'] ?? 'Trust badge' }}" loading="lazy" class="h-8 w-auto max-w-24 object-contain bg-white/90 rounded p-1">
                </a>
            @else
                <img src="{{ $src }}" alt="{{ $badge['label'] ?? 'Trust badge' }}" title="{{ $badge['label'] ?? '' }}" loading="lazy" class="h-8 w-auto max-w-24 object-contain bg-white/90 rounded p-1">
            @endif
        @endforeach
    </div>
@endif
