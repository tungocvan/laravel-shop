<?php

namespace Modules\Request\Support;

use Illuminate\Support\Str;
use RuntimeException;

final class RequestPrivateExportStorage
{
    public function disk(): string
    {
        $disk = (string) config('request.files.disk', config('filesystems.default', 'local'));
        $definition = (array) config("filesystems.disks.{$disk}", []);

        if ($disk === 'public' || ($definition['visibility'] ?? null) === 'public') {
            throw new RuntimeException('REQUEST_EXPORT_PUBLIC_DISK_FORBIDDEN');
        }

        $root = isset($definition['root']) ? realpath((string) $definition['root']) ?: (string) $definition['root'] : null;
        $publicRoot = realpath(storage_path('app/public')) ?: storage_path('app/public');

        if ($root !== null && str_starts_with(rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR, rtrim($publicRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('REQUEST_EXPORT_PUBLIC_PATH_FORBIDDEN');
        }

        return $disk;
    }

    public function pathFor(string $exportPublicId, string $extension): string
    {
        $extension = strtolower(trim($extension));

        if (! preg_match('/^[a-z0-9]{2,8}$/', $extension)) {
            throw new RuntimeException('REQUEST_EXPORT_INVALID_EXTENSION');
        }

        return sprintf(
            'request/exports/%s/%s.%s',
            $exportPublicId,
            Str::lower(Str::random(40)),
            $extension,
        );
    }
}
