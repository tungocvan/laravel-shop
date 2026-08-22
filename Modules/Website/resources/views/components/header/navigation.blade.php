<nav class="hidden xl:flex items-center gap-6 text-sm font-bold text-gray-700">
    @foreach($mainMenu ?? [] as $item)
        <div class="relative group">
            <a href="{{ $item->url }}" target="{{ $item->target }}" class="hover:text-blue-600 flex items-center gap-1 py-4">
                {{ $item->title }}
                @if($item->children->isNotEmpty())
                    <span>⌄</span>
                @endif
            </a>
            @if($item->children->isNotEmpty())
                <div class="absolute left-0 top-full w-56 opacity-0 invisible group-hover:opacity-100 group-hover:visible z-50 pt-1">
                    <div class="bg-white rounded-xl shadow-xl border py-2">
                        @foreach($item->children as $child)
                            <a href="{{ $child->url }}" target="{{ $child->target }}" class="block px-4 py-2.5 hover:bg-blue-50 hover:text-blue-600 text-sm font-medium text-gray-600">{{ $child->title }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</nav>
