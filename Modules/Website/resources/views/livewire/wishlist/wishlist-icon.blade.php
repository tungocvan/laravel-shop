<a href="{{ route('account.wishlist') }}" class="hidden sm:block p-2 text-gray-600 hover:text-red-500 transition relative group">
    {{-- Icon --}}
    <svg class="w-6 h-6 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
    </svg>

    {{-- Badge Count --}}
    @if($count > 0)
        <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-500 rounded-full animate-bounce-short shadow-sm border border-white">
            {{ $count }}
        </span>
    @endif
</a>
