@if(isset($footerColumns) && $footerColumns->isNotEmpty())
    @foreach($footerColumns as $column)
        <div class="lg:col-span-2">
            <h3 class="font-bold text-lg mb-6" style="color: var(--footer-heading);">{{ $column->title }}</h3>
            <ul class="space-y-4 text-sm">
                @foreach($column->links as $link)
                    <li>
                        <a href="{{ $link->url }}" target="{{ $link->new_tab ? '_blank' : '_self' }}" class="transition-colors flex items-center gap-2 hover:underline" style="color: var(--footer-foreground);">{{ $link->label }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
@endif
