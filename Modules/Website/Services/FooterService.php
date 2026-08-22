<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Website\Models\FooterColumn;
use Modules\Website\Models\FooterLink;
use Modules\Website\Models\SocialLink;

class FooterService
{
    public function updateSocialLinks(array $data): void
    {
        Cache::forget('social_links');
    }

    public function createColumn(array $data): FooterColumn
    {
        $col = FooterColumn::create($data);
        $this->clearCache();
        return $col;
    }

    public function duplicateColumn(FooterColumn $source, array $overrides = []): FooterColumn
    {
        $duplicate = DB::transaction(function () use ($source, $overrides) {
            $source->loadMissing('links');
            $maxSort = (int) FooterColumn::max('sort_order');

            $column = FooterColumn::create([
                'title' => $overrides['title'] ?? $source->title,
                'slug' => $overrides['slug'] ?? $source->slug,
                'sort_order' => $maxSort + 1,
                'is_active' => (bool) $source->is_active,
            ]);

            foreach ($source->links->sortBy('sort_order')->values() as $index => $link) {
                FooterLink::create([
                    'footer_column_id' => $column->id,
                    'label' => $link->label,
                    'url' => $link->url,
                    'route_name' => $link->route_name,
                    'new_tab' => (bool) $link->new_tab,
                    'sort_order' => $index + 1,
                    'is_active' => (bool) $link->is_active,
                ]);
            }

            return $column;
        });

        $this->clearCache();
        return $duplicate;
    }

    public function deleteColumn(int $id): bool
    {
        $col = FooterColumn::find($id);
        if ($col) {
            $col->delete();
            $this->clearCache();
            return true;
        }
        return false;
    }

    public function addLinkToColumn(int $columnId, array $data): FooterLink
    {
        $data['footer_column_id'] = $columnId;
        $link = FooterLink::create($data);
        $this->clearCache();
        return $link;
    }

    public function deleteLink(int $linkId): bool
    {
        $link = FooterLink::findOrFail($linkId);
        $link->delete();
        $this->clearCache();
        return true;
    }

    private function clearCache(): void
    {
        Cache::forget('footer_columns_admin');
        Cache::forget('footer_columns_frontend');
    }

    public function updateLink(int $id, array $data): bool
    {
        $link = FooterLink::find($id);
        if ($link) {
            $link->update($data);
            $this->clearCache();
            return true;
        }
        return false;
    }

    public function moveLinkByDrag(int $linkId, int $fromColumnId, int $toColumnId, array $targetOrderedIds): bool
    {
        $link = FooterLink::query()
            ->whereKey($linkId)
            ->where('footer_column_id', $fromColumnId)
            ->first();

        if (! $link || ! FooterColumn::whereKey($toColumnId)->exists()) {
            return false;
        }

        DB::transaction(function () use ($link, $fromColumnId, $toColumnId, $targetOrderedIds): void {
            if ($fromColumnId !== $toColumnId) {
                $link->update(['footer_column_id' => $toColumnId]);
            }

            $targetIds = FooterLink::query()
                ->where('footer_column_id', $toColumnId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $targetLookup = array_flip($targetIds);
            $ordered = [];

            foreach ($targetOrderedIds as $id) {
                $id = (int) $id;
                if (isset($targetLookup[$id]) && ! in_array($id, $ordered, true)) {
                    $ordered[] = $id;
                }
            }

            foreach ($targetIds as $id) {
                if (! in_array($id, $ordered, true)) {
                    $ordered[] = $id;
                }
            }

            foreach ($ordered as $index => $id) {
                FooterLink::query()
                    ->where('footer_column_id', $toColumnId)
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }

            if ($fromColumnId !== $toColumnId) {
                $sourceIds = FooterLink::query()
                    ->where('footer_column_id', $fromColumnId)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->pluck('id');

                foreach ($sourceIds as $index => $id) {
                    FooterLink::query()
                        ->where('footer_column_id', $fromColumnId)
                        ->whereKey($id)
                        ->update(['sort_order' => $index + 1]);
                }
            }
        });

        $this->clearCache();
        return true;
    }

    public function updateLinkOrder(int $columnId, array $orderedIds): void
    {
        $validIds = FooterLink::query()
            ->where('footer_column_id', $columnId)
            ->whereIn('id', $orderedIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $validLookup = array_flip($validIds);
        $safeIds = [];

        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if (isset($validLookup[$id]) && ! in_array($id, $safeIds, true)) {
                $safeIds[] = $id;
            }
        }

        DB::transaction(function () use ($columnId, $safeIds) {
            foreach ($safeIds as $index => $id) {
                FooterLink::query()
                    ->where('footer_column_id', $columnId)
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        $this->clearCache();
    }

    public function updateColumnOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            FooterColumn::where('id', $id)->update(['sort_order' => $index + 1]);
        }
        $this->clearCache();
    }

    public function toggleColumnStatus(int $id): bool
    {
        $col = FooterColumn::find($id);
        if ($col) {
            $col->update(['is_active' => ! $col->is_active]);
            $this->clearCache();
            return true;
        }
        return false;
    }

    public function getColumnsForAdmin()
    {
        return Cache::remember('footer_columns_admin', 3600, function () {
            return FooterColumn::query()
                ->orderBy('sort_order')
                ->with(['links' => function ($q) {
                    $q->orderBy('sort_order');
                }])
                ->get();
        });
    }

    public function getColumnsForFrontend()
    {
        return Cache::remember('footer_columns_frontend', 3600, function () {
            return FooterColumn::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->with(['links' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                }])
                ->get();
        });
    }

    public function updateColumn(int $id, array $data): bool
    {
        $col = FooterColumn::find($id);
        if ($col) {
            $col->update($data);
            $this->clearCache();
            return true;
        }
        return false;
    }

    public function updateSocialLink(int $id, array $data): bool
    {
        $link = SocialLink::find($id);
        if ($link) {
            $link->update($data);
            Cache::forget('social_links');
            return true;
        }
        return false;
    }

    public function updateSocialLinkOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            SocialLink::where('id', $id)->update(['sort_order' => $index + 1]);
        }
        Cache::forget('social_links');
    }

    public function getSocialLinks()
    {
        return Cache::remember('social_links', 86400, function () {
            return SocialLink::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }

    public function createSocialLink(array $data): void
    {
        SocialLink::create($data);
        Cache::forget('social_links');
    }

    public function deleteSocialLink(int $id): void
    {
        SocialLink::destroy($id);
        Cache::forget('social_links');
    }
}
