@props([
    'as' => 'button',
    'href' => null,
    'disabled' => false,
    'pressedScale' => 'active:scale-[0.985]',
])

@php
    $tag = $href ? 'a' : $as;
    $isDisabled = (bool) $disabled;
    $nativeTouchClasses = 'select-none touch-manipulation transition-transform duration-100 ease-out motion-reduce:transform-none motion-reduce:transition-none [-webkit-tap-highlight-color:transparent]';
    if (! $isDisabled) {
        $nativeTouchClasses .= ' '.$pressedScale;
    }
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    @if($tag === 'button') type="{{ $attributes->get('type', 'button') }}" @endif
    @if($isDisabled && $tag === 'button') disabled @endif
    @if($isDisabled && $tag !== 'button') aria-disabled="true" tabindex="-1" @endif
    {{ $attributes->except(['type'])->class([$nativeTouchClasses, 'pointer-events-none opacity-60' => $isDisabled]) }}
>
    {{ $slot }}
</{{ $tag }}>
