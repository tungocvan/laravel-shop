<div class="mb-1" style="padding-left: {{ $level * 1.25 }}rem;">
    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" wire:click="edit({{ $folder->id }})">
        <i class="fa-regular fa-folder me-1"></i>{{ $folder->name }}
    </button>
    @if (! $folder->is_active)
        <span class="badge text-bg-secondary ms-1">Tắt</span>
    @endif
</div>

@foreach ($folder->childrenRecursive as $child)
    @include('Ebook::livewire.folder.partials.folder-node', ['folder' => $child, 'level' => $level + 1])
@endforeach
