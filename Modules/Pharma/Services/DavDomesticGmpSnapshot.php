<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

final class DavDomesticGmpSnapshot
{
    private const ROOT = 'pharma/dav-domestic-gmp/current';
    private const GROUPS = ['manufacturers', 'packaging', 'business_certificates'];

    public function current(): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists(self::ROOT.'/manifest.json')) {
            return null;
        }
        $manifest = json_decode($disk->get(self::ROOT.'/manifest.json'), true);
        if (! is_array($manifest) || ! isset($manifest['version'], $manifest['publication'], $manifest['files'])) {
            throw new RuntimeException('GMP local manifest is invalid.');
        }
        foreach (self::GROUPS as $group) {
            $file = $manifest['files'][$group] ?? null;
            if (! is_array($file) || ! isset($file['sha256'], $file['name'])
                || ! preg_match('/^[a-f0-9]{64}$/', (string) $file['sha256'])
                || ! $disk->exists($this->filePath($manifest['version'], $group))) {
                throw new RuntimeException('GMP local snapshot is incomplete.');
            }
        }

        return $manifest;
    }

    public function filePath(string $version, string $group): string
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $version) || ! in_array($group, self::GROUPS, true)) {
            throw new RuntimeException('Invalid GMP snapshot file reference.');
        }

        return self::ROOT.'/versions/'.$version.'/'.$group.'.xlsx';
    }

    public function refresh(DavDomesticGmpCrawler $crawler): array
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory(self::ROOT);
        $lockPath = $disk->path(self::ROOT.'/update.lock');
        $lock = fopen($lockPath, 'c');
        if ($lock === false) {
            throw new RuntimeException('Cannot acquire GMP update lock.');
        }
        try {
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('A GMP update is already running.');
            }
            $publication = $crawler->latest();
            $files = [];
            $payloads = [];
            foreach (self::GROUPS as $group) {
                $remote = $publication['files'][$group] ?? null;
                if (! is_array($remote) || empty($remote['url'])) {
                    throw new RuntimeException('DAV publication lacks required GMP Excel group: '.$group);
                }
                $bytes = $crawler->download($remote['url']);
                $this->assertXlsx($bytes);
                $payloads[$group] = $bytes;
                $files[$group] = [
                    'name' => (string) $remote['name'],
                    'url' => (string) $remote['url'],
                    'sha256' => hash('sha256', $bytes),
                    'bytes' => strlen($bytes),
                ];
            }
            // Version changes even when DAV silently replaces a file at the same URL.
            $version = hash('sha256', json_encode(array_map(
                fn (string $group): string => $files[$group]['sha256'], self::GROUPS
            ), JSON_THROW_ON_ERROR));
            $previous = $this->current();
            if ($previous !== null && $previous['version'] === $version
                && ($previous['publication']['url'] ?? null) === $publication['url']) {
                return ['status' => 'unchanged', 'snapshot' => $previous];
            }
            $versionDir = self::ROOT.'/versions/'.$version;
            $disk->makeDirectory($versionDir);
            foreach (self::GROUPS as $group) {
                if (! $disk->put($this->filePath($version, $group), $payloads[$group])) {
                    throw new RuntimeException('Cannot stage GMP Excel '.$group);
                }
            }
            $manifest = [
                'version' => $version,
                'checked_at' => now()->toIso8601String(),
                'publication' => [
                    'url' => $publication['url'],
                    'title' => $publication['title'],
                    'published' => $publication['published'],
                ],
                'files' => $files,
            ];
            $manifestPath = $disk->path(self::ROOT.'/manifest.json');
            $temporary = $disk->path(self::ROOT.'/manifest.'.bin2hex(random_bytes(8)).'.tmp');
            if (file_put_contents($temporary, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) === false) {
                throw new RuntimeException('Cannot stage GMP manifest.');
            }
            if (! rename($temporary, $manifestPath)) {
                @unlink($temporary);
                throw new RuntimeException('Cannot publish GMP manifest.');
            }
            // Only delete a previous managed version after the new manifest is
            // atomically published. Never remove arbitrary files or old data on failure.
            if ($previous !== null && $previous['version'] !== $version) {
                $disk->deleteDirectory(self::ROOT.'/versions/'.$previous['version']);
            }

            return ['status' => 'updated', 'snapshot' => $manifest];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function assertXlsx(string $bytes): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'dav-gmp-');
        if ($temp === false) {
            throw new RuntimeException('Cannot validate GMP Excel.');
        }
        try {
            file_put_contents($temp, $bytes);
            $zip = new ZipArchive();
            if ($zip->open($temp) !== true) {
                throw new RuntimeException('DAV GMP Excel ZIP is corrupted.');
            }
            try {
                foreach (['[Content_Types].xml', 'xl/workbook.xml'] as $required) {
                    if ($zip->locateName($required) === false) {
                        throw new RuntimeException('DAV attachment is not an XLSX workbook.');
                    }
                }
            } finally {
                $zip->close();
            }
        } finally {
            @unlink($temp);
        }
    }
}
