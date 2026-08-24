<?php

namespace Modules\Request\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Request\Contracts\PrivateRequestFileStore;

final class LaravelPrivateRequestFileStore implements PrivateRequestFileStore
{
    public function put(UploadedFile $file, string $path, string $filename): string
    {
        $disk = (string) config('request.files.disk', 'local');
        if ($disk === 'public') {
            throw ValidationException::withMessages(['attachment' => ['private_disk_required']]);
        }
        $stored = Storage::disk($disk)->putFileAs($path, $file, $filename);
        if (! is_string($stored) || $stored === '') {
            throw ValidationException::withMessages(['attachment' => ['attachment_store_failed']]);
        }

        return $stored;
    }

    public function exists(string $disk, string $path): bool
    {
        return $disk !== 'public' && Storage::disk($disk)->exists($path);
    }

    public function readStream(string $disk, string $path): mixed
    {
        return $disk === 'public' ? null : Storage::disk($disk)->readStream($path);
    }

    public function checksum(string $disk, string $path): ?string
    {
        $stream = $this->readStream($disk, $path);
        if (! is_resource($stream)) {
            return null;
        }
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);

        return hash_final($hash);
    }

    public function delete(string $disk, string $path): void
    {
        if ($disk !== 'public' && str_starts_with($path, 'request/attachments/') && ! str_contains($path, '..') && ! str_contains($path, '\\')) {
            Storage::disk($disk)->delete($path);
        }
    }
}
