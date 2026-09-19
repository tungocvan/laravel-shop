<?php

namespace App\Dossiers\Services;

use App\Dossiers\Models\Dossier;
use App\Dossiers\Models\DossierAttachment;
use App\Dossiers\Models\DossierItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use RuntimeException;
use Throwable;

class DossierStorageService
{
    public const TARGET_LOCAL = 'local';

    public const TARGET_GOOGLE_DRIVE = 'google_drive';

    public function __construct(private readonly GoogleDriveConnectionService $googleDrive) {}

    public function googleDriveConnected(): bool
    {
        try {
            return (bool) ($this->googleDrive->status()['connected'] ?? false);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $targets
     */
    public function store(
        Dossier $dossier,
        ?DossierItem $item,
        UploadedFile $file,
        string $root,
        string $kind = 'item',
        array $targets = [self::TARGET_LOCAL],
    ): DossierAttachment {
        $targets = array_values(array_unique(array_intersect($targets, [
            self::TARGET_LOCAL,
            self::TARGET_GOOGLE_DRIVE,
        ])));

        if ($targets === []) {
            throw new RuntimeException('Phải chọn ít nhất một nơi lưu hồ sơ.');
        }

        $segment = $item ? sprintf('%02d-%s', max(1, $item->sort_order), $item->code) : 'master';
        $directory = trim($root, '/').'/'.$segment;
        $path = $file->store($directory, 'local');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Không thể lưu file hồ sơ vào vùng tạm local.');
        }

        $keepLocal = in_array(self::TARGET_LOCAL, $targets, true);
        $useGoogleDrive = in_array(self::TARGET_GOOGLE_DRIVE, $targets, true);
        $remotePath = null;
        $syncStatus = $keepLocal ? 'local_only' : 'pending';
        $googleUploaded = false;

        if ($useGoogleDrive) {
            if (! $this->googleDriveConnected()) {
                if (! $keepLocal) {
                    Storage::disk('local')->delete($path);
                    throw new RuntimeException('Google Drive chưa được kết nối. Hãy chọn Local hoặc kết nối Google Drive.');
                }

                $syncStatus = 'sync_failed';
            } else {
                try {
                    $absolutePath = Storage::disk('local')->path($path);
                    $folders = array_values(array_filter(explode('/', trim($directory, '/'))));
                    $this->googleDrive->uploadApplicationFile(
                        $absolutePath,
                        $folders,
                        basename($path),
                        $file->getClientMimeType() ?: 'application/octet-stream',
                    );
                    $remotePath = 'Laravel-Backup/'.trim($directory, '/').'/'.basename($path);
                    $googleUploaded = true;
                    $syncStatus = $keepLocal ? 'synced' : 'google_only';
                } catch (Throwable $exception) {
                    if (! $keepLocal) {
                        Storage::disk('local')->delete($path);
                        throw new RuntimeException('Không thể lưu hồ sơ lên Google Drive.', previous: $exception);
                    }

                    $syncStatus = 'sync_failed';
                }
            }
        }

        if (! $keepLocal && $googleUploaded) {
            Storage::disk('local')->delete($path);
        }

        return $dossier->attachments()->create([
            'item_id' => $item?->id,
            'kind' => $kind,
            'disk' => $keepLocal ? 'local' : 'google_drive',
            'path' => $keepLocal ? $path : (string) $remotePath,
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
