<?php

namespace Modules\Request\Contracts;

use Illuminate\Http\UploadedFile;

interface PrivateRequestFileStore
{
    public function put(UploadedFile $file, string $path, string $filename): string;

    public function exists(string $disk, string $path): bool;

    /** @return resource|null */
    public function readStream(string $disk, string $path): mixed;

    public function checksum(string $disk, string $path): ?string;

    public function delete(string $disk, string $path): void;
}
