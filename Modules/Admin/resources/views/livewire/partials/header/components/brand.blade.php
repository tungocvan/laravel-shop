@php
    $brand = $component['data']['brand'] ?? [];
    $logo = $brand['logo'] ?? null;
    $title = $brand['title'] ?? config('app.name', 'Admin');
    $logoSize = (int) ($brand['logo_size'] ?? 32);
    $mobileBrand = $brand['mobile_brand'] ?? 'logo-only';
    $hideTitleOnMobile = (bool) ($brand['hide_title_on_mobile'] ?? true);
    $showTitle = (bool) ($brand['show_title'] ?? true);
    $showSubtitle = (bool) ($brand['show_subtitle'] ?? false);
    $subtitle = trim((string) ($brand['subtitle'] ?? ''));
@endphp

<a
    href="{{ $brand['url'] ?? '/admin' }}"
    class="flex min-w-0 items-center gap-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
    aria-label="{{ $title }}"
    @if ($mobileBrand === 'hidden') class="hidden sm:flex min-w-0 items-center gap-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" @endif
>
    <span
        class="flex shrink-0 items-center justify-center overflow-hidden rounded-lg bg-indigo-50 text-xs font-bold text-indigo-600 ring-1 ring-indigo-100"
        style="width: {{ $logoSize }}px; height: {{ $logoSize }}px;"
        aria-hidden="true"
    >
        @if ($logo)
            <img src="{{ asset($logo) }}" alt="" class="h-full w-full object-contain">
        @else
            {{ mb_strtoupper(mb_substr($title, 0, 1)) }}
        @endif
    </span>

    @if ($showTitle)
        <span class="min-w-0 {{ ($hideTitleOnMobile || $mobileBrand === 'logo-only') ? 'hidden sm:block' : 'block' }}">
            <span class="block max-w-44 truncate text-sm font-semibold leading-5 text-slate-800">{{ $title }}</span>
            @if ($showSubtitle && $subtitle !== '')
                <span class="block max-w-44 truncate text-[11px] leading-4 text-slate-500">{{ $subtitle }}</span>
            @endif
        </span>
    @endif
</a>
