<?php

namespace Modules\Ebook\Services;

use Illuminate\Support\Collection;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookFolder;

class EbookNavigationService
{
    public function tree(): array
    {
        $folders = EbookFolder::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name']);

        $documents = $this->visibleDocuments()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'folder_id', 'title']);

        return $this->buildLevel($folders, $documents, null);
    }

    public function breadcrumbs(EbookDocument $document): array
    {
        $items = [];
        $folder = $document->folder;

        while ($folder !== null) {
            array_unshift($items, [
                'id' => (int) $folder->id,
                'name' => $folder->name,
            ]);
            $folder = $folder->parent()->first();
        }

        return $items;
    }

    public function documentPicker(): array
    {
        return $this->visibleDocuments()
            ->with('folder:id,name')
            ->where('is_active', true)
            ->orderByDesc('is_favorite')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'folder_id', 'title', 'is_favorite'])
            ->map(fn (EbookDocument $document): array => [
                'id' => (int) $document->id,
                'title' => $document->title,
                'folder' => $document->folder?->name ?? 'Thư mục gốc',
                'favorite' => (bool) $document->is_favorite,
            ])
            ->all();
    }

    public function adjacent(EbookDocument $document): array
    {
        $documents = $this->visibleDocuments()
            ->where('is_active', true)
            ->orderBy('folder_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id', 'title']);

        $index = $documents->search(fn (EbookDocument $item): bool => (int) $item->id === (int) $document->id);

        if ($index === false) {
            return ['previous' => null, 'next' => null];
        }

        $previous = $index > 0 ? $documents->get($index - 1) : null;
        $next = $index < $documents->count() - 1 ? $documents->get($index + 1) : null;

        return [
            'previous' => $previous ? ['id' => (int) $previous->id, 'title' => $previous->title] : null,
            'next' => $next ? ['id' => (int) $next->id, 'title' => $next->title] : null,
        ];
    }

    private function visibleDocuments()
    {
        return app(EbookAccessService::class)->visibleDocuments(auth('admin')->user());
    }

    private function buildLevel(Collection $folders, Collection $documents, ?int $parentId): array
    {
        return $folders
            ->filter(fn (EbookFolder $folder): bool => ($folder->parent_id === null ? null : (int) $folder->parent_id) === $parentId)
            ->map(function (EbookFolder $folder) use ($folders, $documents): array {
                return [
                    'id' => (int) $folder->id,
                    'name' => $folder->name,
                    'documents' => $documents
                        ->where('folder_id', $folder->id)
                        ->map(fn (EbookDocument $document): array => [
                            'id' => (int) $document->id,
                            'title' => $document->title,
                        ])
                        ->values()
                        ->all(),
                    'children' => $this->buildLevel($folders, $documents, (int) $folder->id),
                ];
            })
            ->filter(fn (array $folder): bool => $folder['documents'] !== [] || $folder['children'] !== [])
            ->values()
            ->all();
    }
}
