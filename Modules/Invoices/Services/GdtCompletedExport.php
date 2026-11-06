<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\File;

/**
 * A file alone is not evidence that canonical sync completed.
 * The sidecar is written only after a complete canonical + RAW detail run.
 */
class GdtCompletedExport
{
    public function path(string $from, string $to, bool $vatIn, string $source): string
    {
        if (! in_array($source, ['regular', 'pos'], true)) {
            throw new \InvalidArgumentException('Nguồn hóa đơn không hợp lệ.');
        }
        $start = \Carbon\Carbon::parse($from)->toDateString();
        $end = \Carbon\Carbon::parse($to)->toDateString();
        $base = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $folder = storage_path('app/'.$base.'/'.($vatIn ? 'vat_in' : 'vat_out'));
        $filename = ($vatIn ? 'vat_in_' : 'vat_out_').$source.'_'.$start.'_'.$end.'.xlsx';

        return $folder.'/'.$filename;
    }

    public function completed(string $from, string $to, bool $vatIn, string $source): ?string
    {
        $file = $this->path($from, $to, $vatIn, $source);
        $manifest = $file.'.complete.json';
        if (! is_file($file) || ! is_readable($file) || filesize($file) <= 0 || ! is_file($manifest)) {
            return null;
        }
        $data = json_decode((string) File::get($manifest), true);
        if (! is_array($data) || ($data['version'] ?? null) !== 1
            || ($data['source'] ?? null) !== $source
            || ($data['sha256'] ?? null) !== hash_file('sha256', $file)
            || ($data['bytes'] ?? null) !== filesize($file)
            || ($data['from'] ?? null) !== \Carbon\Carbon::parse($from)->toDateString()
            || ($data['to'] ?? null) !== \Carbon\Carbon::parse($to)->toDateString()
            || ($data['direction'] ?? null) !== ($vatIn ? 'purchase' : 'sold')) {
            return null;
        }

        return $file;
    }

    public function mark(string $from, string $to, bool $vatIn, string $source, string $file): void
    {
        if ($file !== $this->path($from, $to, $vatIn, $source)
            || ! is_file($file) || ! is_readable($file) || filesize($file) <= 0) {
            throw new \RuntimeException('Không thể xác nhận Excel canonical hoàn tất.');
        }
        $manifest = $file.'.complete.json';
        $data = [
            'version' => 1,
            'source' => $source,
            'direction' => $vatIn ? 'purchase' : 'sold',
            'from' => \Carbon\Carbon::parse($from)->toDateString(),
            'to' => \Carbon\Carbon::parse($to)->toDateString(),
            'bytes' => filesize($file),
            'sha256' => hash_file('sha256', $file),
        ];
        $temporary = $manifest.'.'.bin2hex(random_bytes(8)).'.tmp';
        try {
            if (file_put_contents($temporary, json_encode($data, JSON_THROW_ON_ERROR), LOCK_EX) === false
                || ! rename($temporary, $manifest)) {
                throw new \RuntimeException('Không lưu được trạng thái đồng bộ hoàn tất.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
