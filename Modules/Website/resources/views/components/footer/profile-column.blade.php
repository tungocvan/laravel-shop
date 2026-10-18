{{-- Profile columns are authoritative only when the published snapshot opts into profile source. --}}
@switch($column->module ?? 'menu-links')
    @case('brand')
        @include('Website::components.footer.brand')
        @break
    @case('contact')
        @include('Website::components.footer.contact')
        @break
    @case('social-links')
        @include('Website::components.footer.social-links')
        @break
    @case('app-install')
        @include('Website::components.footer.app-install')
        @break
    @default
        @if(filled($column->title ?? ''))
            <h3 class="mb-4 text-lg font-bold" style="color: var(--footer-heading);">{{ $column->title }}</h3>
        @endif
        @if(($column->links ?? collect())->isNotEmpty())
            <ul class="space-y-3 text-sm">
                @foreach($column->links as $link)
                    <li><a href="{{ $link->url }}" target="{{ $link->new_tab ? '_blank' : '_self' }}" @if($link->new_tab) rel="noopener noreferrer" @endif class="hover:underline" style="color: var(--footer-foreground);">{{ $link->label }}</a></li>
                @endforeach
            </ul>
        @endif
@endswitch
