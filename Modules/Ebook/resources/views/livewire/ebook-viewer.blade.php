<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $document->title }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.ebook.index') }}">Ebook</a></li>
                    @foreach ($breadcrumbs as $item)
                        <li class="breadcrumb-item">{{ $item['name'] }}</li>
                    @endforeach
                    <li class="breadcrumb-item active" aria-current="page">{{ $document->title }}</li>
                </ol>
            </nav>
        </div>

        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="toggleReadingMode" wire:loading.attr="disabled">
            {{ $readingMode ? 'Thoát chế độ đọc' : 'Chế độ đọc' }}
        </button>
    </div>

    <div class="row g-3">
        @unless ($readingMode)
            <aside class="col-12 col-lg-3">
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Tài liệu</div>
                    <div class="card-body" style="max-height: 75vh; overflow: auto;">
                        @include('Ebook::livewire.partials.navigation-node', [
                            'nodes' => $tree,
                            'documentId' => $documentId,
                        ])
                    </div>
                </div>
            </aside>
        @endunless

        <main class="{{ $readingMode ? 'col-12' : 'col-12 col-lg-7' }}">
            <article class="card shadow-sm">
                <div class="card-body ebook-markdown overflow-auto">
                    {!! $html !!}
                </div>
            </article>
        </main>

        @unless ($readingMode)
            <aside class="col-12 col-lg-2">
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Mục lục</div>
                    <div class="card-body">
                        @forelse ($toc as $item)
                            <a href="#{{ $item['id'] }}"
                               class="d-block py-1 text-decoration-none small"
                               style="padding-left: {{ max(0, $item['level'] - 1) * 0.5 }}rem;">
                                {{ $item['title'] }}
                            </a>
                        @empty
                            <div class="text-muted small">Tài liệu chưa có heading.</div>
                        @endforelse
                    </div>
                </div>
            </aside>
        @endunless
    </div>
</div>
