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

        $documents = EbookDocument::query()
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
            ->values()
            ->all();
    }
}
