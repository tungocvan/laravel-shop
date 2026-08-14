<?php

namespace Modules\Ebook\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Ebook\Models\EbookDocument;

class EbookAccessService
{
    public function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function canView(User $user, EbookDocument $document): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $document->viewers()
            ->whereKey($user->getKey())
            ->exists();
    }

    public function visibleDocuments(User $user): Builder
    {
        $query = EbookDocument::query();

        if ($this->isSuperAdmin($user)) {
            return $query;
        }

        return $query->whereHas('viewers', fn (Builder $viewerQuery): Builder => $viewerQuery->whereKey($user->getKey()));
    }

    public function authorizeView(User $user, EbookDocument $document): void
    {
        abort_unless($this->canView($user, $document), 404);
    }
}
