<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Models\RequestExportJob;

final class ExpireRequestArtifacts
{
    public function handle(int $limit = 100): int
    {
        $limit = max(1, min($limit, 500));
        $expired = 0;

        RequestExportJob::query()
            ->where('status', ExportStatus::Ready->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (RequestExportJob $export) use (&$expired): void {
                if (filled($export->storage_disk) && filled($export->storage_path)) {
                    Storage::disk($export->storage_disk)->delete($export->storage_path);
                }

                $export->forceFill([
                    'status' => ExportStatus::Expired,
                    'storage_path' => null,
                ])->save();

                $expired++;
            });

        return $expired;
    }
}
