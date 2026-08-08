<div class="flex gap-2 overflow-x-auto pb-2 md:pb-0 no-scrollbar mask-gradient">
    <button wire:click="setCategory('')"
            class="px-5 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-colors duration-200
            {{ is_null($activeSlug)
                ? 'bg-gray-900 text-white shadow-md'
                : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-900 hover:text-gray-900'
            }}">
        Tất cả
    </button>

    @foreach($categories as $cat)
        <button wire:click="setCategory('{{ $cat->slug }}')"
                class="px-5 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-colors duration-200
                {{ $activeSlug === $cat->slug
                    ? 'bg-blue-600 text-white shadow-md'
                    : 'bg-white text-gray-600 border border-gray-200 hover:border-blue-600 hover:text-blue-600'
                }}">
            {{ $cat->name }}
        </button>
    @endforeach
</div>
