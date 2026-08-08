<?php

namespace Modules\Shared\Services\ImportExport\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesExportStorage
{
    protected function exportDirectory(): string
    {
        return 'exports';
    }

    protected function makeExportPath(string $prefix, string $extension = 'xlsx'): string
    {
        $filename = Str::slug($prefix)
            . '-'
            . now()->format('Ymd-His')
            . '.'
            . $extension;

        Storage::disk('public')->makeDirectory($this->exportDirectory());

        return $this->exportDirectory() . '/' . $filename;
    }

    protected function publicDownloadUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
