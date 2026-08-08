<button wire:click.prevent.stop="toggle"
        class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full transition shadow-sm z-20
        {{ $isActive ? 'bg-red-50 text-red-500 opacity-100' : 'bg-white/80 text-gray-400 opacity-0 group-hover:opacity-100 hover:text-red-500 hover:bg-white' }}
        duration-300 transform active:scale-90">

    {{-- Icon Tim --}}
    <svg class="w-5 h-5 {{ $isActive ? 'fill-current' : 'fill-none' }}" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
    </svg>

    {{-- Loading Spin nhỏ xíu để biết đang xử lý --}}
    <div wire:loading wire:target="toggle" class="absolute inset-0 flex items-center justify-center bg-white/80 rounded-full">
         <svg class="animate-spin h-4 w-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
</button>
