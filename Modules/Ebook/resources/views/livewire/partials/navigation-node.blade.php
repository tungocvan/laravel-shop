@foreach ($nodes as $node)
    <div class="mb-2">
        <div class="fw-semibold small text-uppercase text-muted">{{ $node['name'] }}</div>

        @foreach ($node['documents'] as $item)
            <a href="{{ route('admin.ebook.document.show', $item['id']) }}"
               class="d-block py-1 text-decoration-none {{ $item['id'] === $documentId ? 'fw-bold' : '' }}">
                {{ $item['title'] }}
            </a>
        @endforeach

        @if ($node['children'] !== [])
            <div class="ms-3 mt-1">
                @include('Ebook::livewire.partials.navigation-node', [
                    'nodes' => $node['children'],
                    'documentId' => $documentId,
                ])
            </div>
        @endif
    </div>
@endforeach
