<?php

namespace App\Dossiers\Services;

use App\Dossiers\Models\Dossier;
use App\Dossiers\Models\DossierAttachment;
use App\Dossiers\Models\DossierItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Throwable;

class DossierStorageService
{
    public function __construct(private readonly GoogleDriveConnectionService $googleDrive) {}

    public function store(Dossier $dossier, ?DossierItem $item, UploadedFile $file, string $root, string $kind = 'item'): DossierAttachment
    {
        $segment = $item ? sprintf('%02d-%s', max(1, $item->sort_order), $item->code) : 'master';
        $directory = trim($root, '/').'/'.$segment;
        $path = $file->store($directory, 'local');
        $remotePath = 'Laravel-Backup/'.trim($directory, '/').'/'.basename($path);
        $syncStatus = 'local_only';

        try {
            if ($this->googleDrive->status()['connected'] ?? false) {
                $absolutePath = Storage::disk('local')->path($path);
                $folders = array_values(array_filter(explode('/', trim($directory, '/'))));
                $this->googleDrive->uploadApplicationFile(
                    $absolutePath,
                    $folders,
                    basename($path),
                    $file->getClientMimeType() ?: 'application/octet-stream',
                );
                $syncStatus = 'synced';
            }
        } catch (Throwable) {
            $syncStatus = 'sync_failed';
        }

        return $dossier->attachments()->create([
            'item_id' => $item?->id,
            'kind' => $kind,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'sync_status' => $syncStatus,
            'remote_path' => $remotePath,
            'uploaded_by' => auth('admin')->id(),
        ]);
    }
}
