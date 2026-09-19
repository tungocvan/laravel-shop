<?php

namespace App\Dossiers\Services;

use App\Dossiers\Jobs\UploadDossierAttachmentToGoogleDrive;
use App\Dossiers\Models\Dossier;
use App\Dossiers\Models\DossierAttachment;
use App\Dossiers\Models\DossierItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use RuntimeException;
use Throwable;

class DossierStorageService
{
    public const TARGET_LOCAL = 'local';

    public const TARGET_GOOGLE_DRIVE = 'google_drive';

    public function __construct(private readonly GoogleDriveConnectionService $googleDrive) {}

    /** @return array{upload_max_bytes:int,post_max_bytes:int,safe_post_bytes:int,upload_max_label:string,post_max_label:string,safe_post_label:string} */
    public function uploadLimits(): array
    {
        $upload = $this->iniBytes((string) ini_get('upload_max_filesize'));
        $post = $this->iniBytes((string) ini_get('post_max_size'));
        $safePost = $post > 0 ? (int) floor($post * 0.9) : 0;

        return [
            'upload_max_bytes' => $upload,
            'post_max_bytes' => $post,
            'safe_post_bytes' => $safePost,
            'upload_max_label' => $this->bytesLabel($upload),
            'post_max_label' => $this->bytesLabel($post),
            'safe_post_label' => $this->bytesLabel($safePost),
        ];
    }

    public function googleDriveConnected(): bool
    {
        try {
            return (bool) ($this->googleDrive->status()['connected'] ?? false);
        } catch (Throwable) {
            return false;
        }
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function bytesLabel(int $bytes): string
    {
        if ($bytes <= 0) {
            return 'Không giới hạn';
        }

        return rtrim(rtrim(number_format($bytes / 1024 / 1024, 1, '.', ''), '0'), '.').' MB';
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
        string $queue = 'default',
    ): DossierAttachment {
        $targets = array_values(array_unique(array_intersect($targets, [self::TARGET_LOCAL, self::TARGET_GOOGLE_DRIVE])));
        if ($targets === []) {
            throw new RuntimeException('Phải chọn ít nhất một nơi lưu hồ sơ.');
        }

        $keepLocal = in_array(self::TARGET_LOCAL, $targets, true);
        $useGoogleDrive = in_array(self::TARGET_GOOGLE_DRIVE, $targets, true);
        if ($useGoogleDrive && ! $this->googleDriveConnected()) {
            throw new RuntimeException('Google Drive chưa được kết nối. Hãy chọn Local hoặc kết nối Google Drive.');
        }

        // Always stage first: the Pharma queue worker must be able to read the file after the HTTP request ends.
        $directory = trim($root, '/');
        $originalName = $this->safeFileName($file->getClientOriginalName());
        $storedName = Str::uuid().'-'.$originalName;
        $path = $file->storeAs($directory, $storedName, 'local');
        if (!is_string($path) || $path === '') {
            throw new RuntimeException('Không thể lưu file hồ sơ vào vùng tạm local.');
        }

        $attachment = $dossier->attachments()->create([
            'item_id' => $item?->id,
            'kind' => $kind,
            'disk' => $keepLocal ? 'local' : 'google_drive',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', Storage::disk('local')->path($path)),
            'sync_status' => $useGoogleDrive ? 'pending' : 'local_only',
            'remote_path' => null,
            'remote_id' => null,
            'uploaded_by' => auth('admin')->id(),
        ]);

        if ($useGoogleDrive) {
            UploadDossierAttachmentToGoogleDrive::dispatch($attachment->id, $directory, $originalName, $keepLocal, $queue)
                ->onQueue($queue);
        }

        return $attachment;
    }

    public function deleteAttachmentStorage(DossierAttachment $attachment): void
    {
        if ($attachment->remote_id || $attachment->remote_path) {
            $this->googleDrive->deleteApplicationFile($attachment->remote_id, $attachment->remote_path);
        }

        if ($attachment->disk === 'local' || $attachment->sync_status === 'synced' || ! $attachment->remote_path) {
            Storage::disk('local')->delete($attachment->path);
        }
    }

    private function safeFileName(string $name): string
    {
        $name = trim(str_replace(["/", "\\", "\\0"], '-', $name));

        return $name !== '' ? $name : 'document';
    }
}
