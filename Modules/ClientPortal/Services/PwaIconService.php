<?php

namespace Modules\ClientPortal\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PwaIconService
{
    public function icons(?string $version): array
    {
        if ($this->available($version)) {
            return [
                ['src' => $this->url($version, '192'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $this->url($version, '512'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $this->url($version, 'maskable'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ];
        }
        return [
            ['src' => '/pwa/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any'],
            ['src' => '/pwa/icon-maskable.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'maskable'],
        ];
    }

    public function appleTouchIcon(?string $version): string
    {
        return $this->available($version) ? $this->url($version, '180') : '/pwa/icon.svg';
    }

    public function store(UploadedFile $file): string
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException('Máy chủ chưa cài PHP GD để xử lý icon.');
        }
        $info = @getimagesize($file->getRealPath());
        if (!$info || $info[2] !== IMAGETYPE_PNG || $info[0] !== $info[1] || $info[0] < 512 || $info[0] > 2048) {
            throw new RuntimeException('Icon phải là ảnh PNG vuông từ 512 đến 2048 pixel.');
        }
        $source = @imagecreatefrompng($file->getRealPath());
        if (!$source) {
            throw new RuntimeException('Không đọc được ảnh PNG.');
        }
        $version = md5_file($file->getRealPath());
        $written = [];
        try {
            foreach (['180' => 180, '192' => 192, '512' => 512, 'maskable' => 512] as $key => $size) {
                $canvas = imagecreatetruecolor($size, $size);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $clear = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $clear);
                $pad = $key === 'maskable' ? 64 : 0;
                imagecopyresampled($canvas, $source, $pad, $pad, 0, 0, $size - $pad * 2, $size - $pad * 2, $info[0], $info[1]);
                ob_start();
                $ok = imagepng($canvas);
                $bytes = ob_get_clean();
                imagedestroy($canvas);
                if (!$ok || !is_string($bytes) || !Storage::disk('public')->put($this->path($version, $key), $bytes)) {
                    throw new RuntimeException('Không thể lưu icon PWA.');
                }
                $written[] = $this->path($version, $key);
            }
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($written);
            throw $e;
        } finally {
            imagedestroy($source);
        }
        return $version;
    }

    public function delete(?string $version): void
    {
        if ($this->valid($version)) {
            Storage::disk('public')->delete(array_map(fn (string $size) => $this->path($version, $size), ['180', '192', '512', 'maskable']));
        }
    }

    private function available(?string $version): bool
    {
        return $this->valid($version) && collect(['180', '192', '512', 'maskable'])->every(
            fn (string $size) => Storage::disk('public')->exists($this->path($version, $size))
        );
    }

    private function valid(?string $version): bool
    {
        return is_string($version) && (bool) preg_match('/^[a-f0-9]{32}$/', $version);
    }

    private function path(string $version, string $size): string
    {
        return "clientportal/pwa-icons/{$version}-{$size}.png";
    }

    private function url(string $version, string $size): string
    {
        return Storage::disk('public')->url($this->path($version, $size));
    }
}
