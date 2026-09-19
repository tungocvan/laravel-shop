<?php

namespace App\Dossiers\Services;

use App\Dossiers\Models\Dossier;
use App\Dossiers\Models\DossierTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DossierManager
{
    public function createFromTemplate(DossierTemplate $template, Model $owner, array $attributes, array $itemMetadata): Dossier
    {
        return DB::transaction(function () use ($template, $owner, $attributes, $itemMetadata): Dossier {
            if ($attributes['is_current'] ?? true) {
                Dossier::query()
                    ->where('owner_type', $owner::class)
                    ->where('owner_id', $owner->getKey())
                    ->update(['is_current' => false]);
            }

            $dossier = Dossier::query()->create([
                'template_id' => $template->id,
                'owner_type' => $owner::class,
                'owner_id' => $owner->getKey(),
                'version' => $attributes['version'] ?? '1',
                'status' => $attributes['status'] ?? 'draft',
                'is_current' => $attributes['is_current'] ?? true,
                'metadata' => $attributes['metadata'] ?? [],
                'created_by' => auth('admin')->id(),
                'updated_by' => auth('admin')->id(),
            ]);

            foreach ($template->items as $templateItem) {
                $dossier->items()->create([
                    'template_item_id' => $templateItem->id,
                    'code' => $templateItem->code,
                    'title' => $templateItem->name,
                    'sort_order' => $templateItem->sort_order,
                    'metadata' => $itemMetadata[$templateItem->code] ?? [],
                ]);
            }

            return $dossier->load(['items', 'attachments']);
        });
    }
}
