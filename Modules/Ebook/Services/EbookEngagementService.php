<?php

namespace Modules\Ebook\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookDocumentRecent;

class EbookEngagementService
{
    public function toggleFavorite(int $documentId): EbookDocument
    {
        $document = EbookDocument::query()->findOrFail($documentId);
        $document->update(['is_favorite' => ! $document->is_favorite]);

        return $document->fresh();
    }

    public function favorites(int $limit = 10): Collection
    {
        return EbookDocument::query()
            ->with('folder:id,name')
            ->where('is_active', true)
            ->where('is_favorite', true)
            ->orderBy('title')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }

    public function recordRecent(int $userId, int $documentId): void
    {
        DB::transaction(function () use ($userId, $documentId): void {
            EbookDocumentRecent::query()->updateOrCreate(
                ['user_id' => $userId, 'ebook_document_id' => $documentId],
                ['viewed_at' => now()]
            );

            $limit = max(1, (int) config('ebook.ebook.recent_limit', 20));
            $staleIds = EbookDocumentRecent::query()
                ->where('user_id', $userId)
                ->orderByDesc('viewed_at')
                ->orderByDesc('id')
                ->skip($limit)
                ->pluck('id');

            if ($staleIds->isNotEmpty()) {
                EbookDocumentRecent::query()->whereIn('id', $staleIds)->delete();
            }
        });
    }

    public function recents(int $userId, int $limit = 10): Collection
    {
        return EbookDocumentRecent::query()
            ->with(['document.folder:id,name'])
            ->where('user_id', $userId)
            ->whereHas('document', fn ($query) => $query->where('is_active', true))
            ->orderByDesc('viewed_at')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }
}
