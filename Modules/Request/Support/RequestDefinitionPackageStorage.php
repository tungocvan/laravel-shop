<?php

namespace Modules\Request\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class RequestDefinitionPackageStorage
{
    private const MAX_BYTES = 262144;

    public function __construct(private RequestPrivateExportStorage $privateStorage) {}

    public function store(UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'json' || $file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['package' => __('Request::definition_package.invalid_file')]);
        }

        $mime = strtolower((string) $file->getMimeType());
        if (! in_array($mime, ['application/json', 'text/json', 'text/plain', 'application/octet-stream'], true)) {
            throw ValidationException::withMessages(['package' => __('Request::definition_package.invalid_file')]);
        }

        $disk = $this->privateStorage->disk();
        $path = 'request/packages/tmp/'.Str::lower((string) Str::ulid()).'.json';
        $stream = fopen($file->getRealPath(), 'rb');
        if (! is_resource($stream) || Storage::disk($disk)->put($path, $stream) !== true) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw ValidationException::withMessages(['package' => __('Request::definition_package.store_failed')]);
        }
        fclose($stream);

        return ['disk' => $disk, 'path' => $path];
    }

    public function read(array $stored): string
    {
        $path = (string) ($stored['path'] ?? '');
        $disk = (string) ($stored['disk'] ?? '');
        if (! str_starts_with($path, 'request/packages/tmp/') || str_contains($path, '..') || $disk === 'public') {
            throw ValidationException::withMessages(['package' => __('Request::definition_package.invalid_file')]);
        }

        $contents = Storage::disk($disk)->get($path);
        if (strlen($contents) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['package' => __('Request::definition_package.invalid_file')]);
        }

        return $contents;
    }

    public function delete(array $stored): void
    {
        $path = (string) ($stored['path'] ?? '');
        $disk = (string) ($stored['disk'] ?? '');
        if ($disk !== 'public' && str_starts_with($path, 'request/packages/tmp/') && ! str_contains($path, '..')) {
            Storage::disk($disk)->delete($path);
        }
    }
}
