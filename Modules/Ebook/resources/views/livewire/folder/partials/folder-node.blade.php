<div class="mb-1" style="padding-left: {{ $level * 1.25 }}rem;">
    <button type="button" wire:click="edit({{ $folder->id }})"
        class="group flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:hover:bg-indigo-950/30">
        <span class="flex min-w-0 items-center gap-2">
            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-300" aria-hidden="true">📁</span>
            <span class="truncate text-sm font-medium text-gray-700 group-hover:text-indigo-700 dark:text-gray-200 dark:group-hover:text-indigo-300">{{ $folder->name }}</span>
        </span>

        @if (! $folder->is_active)
            <span class="ml-2 shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Tắt</span>
        @endif
    </button>
</div>

@foreach ($folder->childrenRecursive as $child)
    @include('Ebook::livewire.folder.partials.folder-node', ['folder' => $child, 'level' => $level + 1])
@endforeach
