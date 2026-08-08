<div
    x-data="{
        focus: false
    }"
    class="relative w-full max-w-md"
>

    {{-- Icon --}}
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
                  d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                  clip-rule="evenodd" />
        </svg>
    </div>

    {{-- Input --}}
    <input
        type="text"
        wire:model.debounce.400ms="query"
        wire:keydown.enter="submit"

        @focus="focus = true"
        @blur="focus = false"

        class="block w-full rounded-xl border border-gray-300
               px-4 py-3 pl-11
               text-gray-900 text-sm
               placeholder:text-gray-400
               bg-gray-50 hover:bg-white
               focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100
               transition-all duration-200"

        placeholder="Tìm kiếm nhanh (Ctrl + K)..."
        aria-label="Tim kiem nhanh"
    >

    <div
        class="pointer-events-none absolute inset-y-0 right-3 hidden items-center text-xs text-gray-400 sm:flex"
        x-show="!focus"
        aria-hidden="true"
    >
        Ctrl K
    </div>
</div>
