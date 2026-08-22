@if(data_get($websiteFeatures ?? [], 'back_to_top', true))
    @php($backToTopPosition = data_get($websiteFeatures ?? [], 'back_to_top_position', 'bottom-right'))
    <button x-data="{ show: false }"
            x-on:scroll.window="show = window.pageYOffset > 300"
            x-show="show"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
            @click="window.scrollTo({top: 0, behavior: 'smooth'})"
            aria-label="Về đầu trang"
            class="fixed z-40 p-3 rounded-full bg-blue-600 text-white shadow-xl hover:bg-blue-700 transition-all duration-300
                {{ $backToTopPosition === 'bottom-left' ? 'bottom-8 left-8 hover:-translate-y-1' : '' }}
                {{ $backToTopPosition === 'bottom-right' ? 'bottom-8 right-8 hover:-translate-y-1' : '' }}
                {{ $backToTopPosition === 'right-middle' ? 'right-8 top-1/2 -translate-y-1/2 hover:-translate-y-[55%]' : '' }}"
            style="display: none;">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
    </button>
@endif
