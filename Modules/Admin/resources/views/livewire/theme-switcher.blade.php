<div class="flex gap-2 p-2"  x-data
    x-on:theme-changed.window="location.reload()">
    @foreach ($themes as $t)
        <button
            wire:click="change('{{ $t }}')"
            class="px-3 py-1 rounded border
                {{ $current === $t ? 'bg-indigo-600 text-white' : '' }}">
            {{ $t }}
        </button>
    @endforeach
</div>