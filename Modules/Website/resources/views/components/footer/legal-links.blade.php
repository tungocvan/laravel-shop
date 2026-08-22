@php($legalLinks = collect($footerSettings['legal_links'] ?? [])->filter(fn ($item) => is_array($item) && ($item['enabled'] ?? true) && !empty($item['label'])))

@if($legalLinks->isNotEmpty())
    <div class="flex flex-wrap gap-x-4 gap-y-2 mt-2 justify-center md:justify-start">
        @foreach($legalLinks as $item)
            <a href="{{ $item['url'] ?? '#' }}"
               target="{{ ($item['new_tab'] ?? false) ? '_blank' : '_self' }}"
               @if($item['new_tab'] ?? false) rel="noopener noreferrer" @endif
               class="transition-colors hover:text-white">{{ $item['label'] }}</a>
        @endforeach
    </div>
@endif
