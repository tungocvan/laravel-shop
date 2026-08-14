@foreach ($nodes as $node)
    <div class="mb-3">
        <div class="mb-1 flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            <span aria-hidden="true">📁</span>
            <span class="truncate">{{ $node['name'] }}</span>
        </div>

        <div class="space-y-1">
            @foreach ($node['documents'] as $item)
                <a href="{{ route('admin.ebook.document.show', $item['id']) }}"
                   class="block rounded-md px-2.5 py-1.5 text-sm transition {{ $item['id'] === $documentId ? 'bg-indigo-50 font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-700 dark:text-gray-300 dark:hover:bg-gray-700/50 dark:hover:text-indigo-300' }}">
                    {{ $item['title'] }}
                </a>
            @endforeach
        </div>

        @if ($node['children'] !== [])
            <div class="mt-2 border-l border-gray-200 pl-3 dark:border-gray-700">
                @include('Ebook::livewire.partials.navigation-node', [
                    'nodes' => $node['children'],
                    'documentId' => $documentId,
                ])
            </div>
        @endif
    </div>
@endforeach
