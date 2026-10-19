@php
    $trustBadges = collect($footerSettings['trust_badges'] ?? [])
        ->filter(fn ($item) => is_array($item) && ($item['enabled'] ?? true) && filled($item['image_url'] ?? null));
@endphp

@if($trustBadges->isNotEmpty())
    <div class="flex flex-wrap items-center justify-center gap-2.5 md:justify-end">
        @foreach($trustBadges as $badge)
            @php
                $image = trim((string) ($badge['image_url'] ?? ''));
                $isExternal = preg_match('~^https?://~i', $image) === 1;
                $path = ltrim($image, '/');
                // Accept both stored disk paths and already-public /storage/ URLs.
                $src = $isExternal ? $image : (str_starts_with($path, 'storage/') ? asset($path) : asset('storage/'.$path));
                $label = $badge['label'] ?? 'Trust badge';
            @endphp
            @if(!empty($badge['url']))
                <a href="{{ $badge['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $label }}" class="inline-flex min-h-9 items-center justify-center rounded bg-white/90 px-2 py-1">
                    <img src="{{ $src }}" alt="{{ $label }}" loading="lazy" class="h-7 w-auto max-w-24 object-contain">
                </a>
            @else
                <span class="inline-flex min-h-9 items-center justify-center rounded bg-white/90 px-2 py-1">
                    <img src="{{ $src }}" alt="{{ $label }}" loading="lazy" class="h-7 w-auto max-w-24 object-contain">
                </span>
            @endif
        @endforeach
    </div>
@endif
