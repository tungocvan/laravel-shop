@if(isset($footerColumns) && $footerColumns->isNotEmpty())
    <div class="lg:col-span-5 grid gap-8" style="grid-template-columns: repeat({{ max(1, $footerColumns->count()) }}, minmax(0, 1fr));">
        @foreach($footerColumns as $column)
            <div class="min-w-0">
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
    </div>
@endif
