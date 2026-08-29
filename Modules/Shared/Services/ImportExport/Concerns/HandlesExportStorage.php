<?php

namespace Modules\Shared\Services\ImportExport\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesExportStorage
{
    protected function exportDisk(): string
    {
        return 'local';
    }

    protected function exportDirectory(): string
    {
        return 'exports';
    }

    protected function makeExportPath(string $prefix, string $extension = 'xlsx'): string
    {
        $filename = Str::slug($prefix)
            .'-'
            .now()->format('Ymd-His-u')
            .'-'
            .Str::lower(Str::random(6))
            .'.'
            .$extension;

        Storage::disk($this->exportDisk())->makeDirectory($this->exportDirectory());

        return $this->exportDirectory().'/'.$filename;
    }

    public function exportDiskName(): string
    {
        return $this->exportDisk();
    }

    public function exportAbsolutePath(string $path): string
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
        $directory = trim($this->exportDirectory(), '/').'/';

        abort_unless(str_starts_with($normalizedPath, $directory), 422, 'Đường dẫn export không hợp lệ.');

        return Storage::disk($this->exportDisk())->path($normalizedPath);
    }
}
