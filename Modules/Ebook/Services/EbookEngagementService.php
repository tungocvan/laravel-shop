<?php

namespace Modules\Ebook\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookDocumentRecent;

class EbookEngagementService
{
    public function toggleFavorite(int $documentId): EbookDocument
    {
        $document = EbookDocument::query()->findOrFail($documentId);
        app(EbookAccessService::class)->authorizeView(auth('admin')->user(), $document);
        $document->update(['is_favorite' => ! $document->is_favorite]);

        return $document->fresh();
    }

    public function favorites(int $limit = 10): Collection
    {
        return app(EbookAccessService::class)
            ->visibleDocuments(auth('admin')->user())
            ->with('folder:id,name')
            ->where('is_active', true)
            ->where('is_favorite', true)
            ->orderBy('title')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }

    public function recordRecent(int $userId, int $documentId): void
    {
        $user = User::query()->findOrFail($userId);
        $document = EbookDocument::query()->findOrFail($documentId);
        app(EbookAccessService::class)->authorizeView($user, $document);

        DB::transaction(function () use ($userId, $documentId): void {
            EbookDocumentRecent::query()->updateOrCreate(
                ['user_id' => $userId, 'ebook_document_id' => $documentId],
                ['viewed_at' => now()]
            );

            $limit = max(1, (int) config('ebook.ebook.recent_limit', 20));
            $keepIds = EbookDocumentRecent::query()
                ->where('user_id', $userId)
                ->orderByDesc('viewed_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id');

            EbookDocumentRecent::query()
                ->where('user_id', $userId)
                ->when($keepIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $keepIds))
                ->delete();
        });
    }

    public function recents(int $userId, int $limit = 10): Collection
    {
        $user = User::query()->findOrFail($userId);
        $visibleIds = app(EbookAccessService::class)
            ->visibleDocuments($user)
            ->where('is_active', true)
            ->select('ebook_documents.id');

        return EbookDocumentRecent::query()
            ->with(['document.folder:id,name'])
            ->where('user_id', $userId)
            ->whereIn('ebook_document_id', $visibleIds)
            ->orderByDesc('viewed_at')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }
}
