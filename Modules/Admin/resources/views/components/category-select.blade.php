@props(['categories' => [], 'label' => 'Danh mục', 'selected' => []])

<div class="mb-5">
    <label class="block text-sm font-medium leading-6 text-gray-900 mb-2">{{ $label }}</label>

    <div class="max-h-80 overflow-y-auto border border-gray-200 rounded-lg bg-white custom-scrollbar shadow-inner">
        @forelse($categories as $parent)

            <div class="border-b border-gray-50 last:border-0">
                <label class="flex items-center space-x-3 px-3 py-2.5 hover:bg-gray-50 cursor-pointer transition select-none group">
                    <input type="checkbox"
                           value="{{ $parent->id }}"
                           {{ $attributes->wire('model') }}
                           class="h-4.5 w-4.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 transition cursor-pointer">
                    <span class="text-sm font-bold text-gray-900 group-hover:text-indigo-700">
                        {{ $parent->name }}
                    </span>
                </label>

                @if($parent->children->isNotEmpty())
                    <div class="bg-gray-50/50 pb-1">
                        @foreach($parent->children as $child)
                            <label class="flex items-center space-x-3 px-3 py-2 pl-10 hover:bg-indigo-50/50 cursor-pointer transition select-none relative group">
                                <div class="absolute left-6 top-1/2 w-3 h-px bg-gray-300"></div>
                                <div class="absolute left-6 bottom-1/2 w-px h-[150%] bg-gray-300"></div>

                                <input type="checkbox"
                                       value="{{ $child->id }}"
                                       {{ $attributes->wire('model') }}
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 transition cursor-pointer">
                                <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-600">
                                    {{ $child->name }}
                                </span>
                            </label>

                            @if($child->children->isNotEmpty())
                                <div>
                                    @foreach($child->children as $grandChild)
                                        <label class="flex items-center space-x-3 px-3 py-1.5 pl-16 hover:bg-indigo-50 cursor-pointer transition select-none relative group">
                                            <div class="absolute left-12 top-1/2 w-3 h-px bg-gray-200"></div>
                                            <div class="absolute left-12 bottom-1/2 w-px h-[150%] bg-gray-200"></div>

                                            <input type="checkbox"
                                                   value="{{ $grandChild->id }}"
                                                   {{ $attributes->wire('model') }}
                                                   class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 transition cursor-pointer">
                                            <span class="text-xs text-gray-600 group-hover:text-indigo-600">
                                                {{ $grandChild->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

        @empty
            <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                <svg class="w-8 h-8 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <p class="text-xs italic">Chưa có danh mục nào.</p>
            </div>
        @endforelse
    </div>

    @error($attributes->wire('model')->value())
        <p class="mt-2 text-sm text-red-600 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ $message }}
        </p>
    @enderror
</div>
