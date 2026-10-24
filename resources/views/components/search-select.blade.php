<div class="relative" x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
    <button type="button" class="flex w-full items-center justify-between rounded-2xl border border-slate-300 bg-white px-4 py-3 text-left" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-haspopup="listbox" @disabled($attributes->has('disabled'))>
        <span>{{ collect($options)->firstWhere('id', (string) $selected)['label'] ?? $placeholder }}</span>
        <span aria-hidden="true">⌄</span>
    </button>
    <div x-show="open" x-cloak class="absolute left-0 right-0 z-50 mt-2 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
        <input type="search" @if($searchModel) wire:model.live.debounce.350ms="{{ $searchModel }}" @endif placeholder="{{ $searchPlaceholder }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" aria-label="{{ $searchPlaceholder }}">
        <div role="listbox" class="mt-2 max-h-64 overflow-y-auto">
            @forelse($options as $option)
                <button type="button" role="option" wire:key="search-option-{{ $option['id'] }}" @if($selectAction) wire:click="{{ $selectAction }}(@js($option['id']))" @endif x-on:click="open = false" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-indigo-50">{{ $option['label'] }}</button>
            @empty
                <p class="p-3 text-slate-500">Không có kết quả phù hợp.</p>
            @endforelse
        </div>
    </div>
</div>
