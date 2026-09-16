<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class PriceListExportJsonLibrary
{
    public const DIRECTORY = 'pharma/price-list-export-profiles';

    public function list(int $userId): array
    {
        $disk = Storage::disk('local');
        $directory = $this->directory($userId);

        return collect($disk->files($directory))
            ->filter(fn (string $path): bool => str_ends_with(strtolower($path), '.json'))
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'size' => $disk->size($path),
                'updated_at' => $disk->lastModified($path),
            ])
            ->sortByDesc('updated_at')
            ->values()
            ->all();
    }

    public function save(int $userId, string $fileName, array $payload): string
    {
        $name = $this->normalizeFileName($fileName);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        Storage::disk('local')->put($this->path($userId, $name), $json."\n");

        return $name;
    }

    public function read(int $userId, string $fileName): array
    {
        $name = $this->normalizeFileName($fileName);
        $path = $this->path($userId, $name);
        abort_unless(Storage::disk('local')->exists($path), 404);
        $payload = json_decode((string) Storage::disk('local')->get($path), true);

        if (! is_array($payload)) {
            throw new InvalidArgumentException('File JSON trên server không hợp lệ.');
        }

        return $payload;
    }

    public function delete(int $userId, string $fileName): void
    {
        Storage::disk('local')->delete($this->path($userId, $this->normalizeFileName($fileName)));
    }

    public function suggestedName(string $profileName): string
    {
        return $this->normalizeFileName($profileName !== '' ? $profileName : 'cau-hinh-bang-gia');
    }

    private function directory(int $userId): string
    {
        return self::DIRECTORY.'/'.$userId;
    }

    private function path(int $userId, string $fileName): string
    {
        return $this->directory($userId).'/'.$fileName;
    }

    private function normalizeFileName(string $fileName): string
    {
        $name = trim(basename(str_replace('\\', '/', $fileName)));
        $name = preg_replace('/[^\pL\pN._ -]+/u', '-', $name) ?: 'cau-hinh-bang-gia';
        $name = trim($name, ". -\t\n\r\0\x0B");
        $name = mb_substr($name !== '' ? $name : 'cau-hinh-bang-gia', 0, 120);

        if (! str_ends_with(strtolower($name), '.json')) {
            $name .= '.json';
        }

        return $name;
    }
}
