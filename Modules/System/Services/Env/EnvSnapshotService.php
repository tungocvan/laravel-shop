<?php

namespace Modules\System\Services\Env;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class EnvSnapshotService
{
    private const OPERATIONS = [
        'production' => 'Production',
        'local' => 'Local',
    ];

    private const RETENTION_PER_TYPE = 5;

    public function create(string $operation, ?int $actorId = null): array
    {
        $label = self::OPERATIONS[$operation] ?? null;

        if ($label === null) {
            throw new InvalidArgumentException('Unsupported environment snapshot operation.');
        }

        $source = base_path('.env');
        if (!File::isFile($source)) {
            throw new RuntimeException('Environment source file is unavailable.');
        }

        $lock = Cache::lock('system:env-snapshot:create', 15);

        if (!$lock->get()) {
            throw new RuntimeException('Environment snapshot operation is already in progress.');
        }

        try {
            return $this->createLocked($source, $operation, $label, $actorId);
        } finally {
            $lock->release();
        }
    }

    private function createLocked(string $source, string $operation, string $label, ?int $actorId): array
    {
        $directory = storage_path('app/private/backups/env-snapshots');

        if (!File::isDirectory($directory) && !File::makeDirectory($directory, 0700, true)) {
            throw new RuntimeException('Environment snapshot directory is unavailable.');
        }

        @chmod($directory, 0700);

        $content = @file_get_contents($source);
        if ($content === false) {
            throw new RuntimeException('Environment source file could not be read.');
        }

        $createdAt = now();
        $filename = sprintf('env-%s-%s-%s.env', $operation, $createdAt->format('Ymd_His_u'), bin2hex(random_bytes(3)));
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        if (File::exists($path)) {
            throw new RuntimeException('Environment snapshot filename collision.');
        }

        $written = @file_put_contents($path, $content, LOCK_EX);
        if ($written === false || $written !== strlen($content)) {
            @unlink($path);
            throw new RuntimeException('Environment snapshot could not be written.');
        }

        @chmod($path, 0600);

        $deleted = 0;
        try {
            $deleted = $this->applyRetention($directory, $operation);
        } catch (Throwable $e) {
            Log::warning('Environment snapshot retention cleanup failed.', [
                'actor_id' => $actorId,
                'snapshot_type' => $operation,
                'exception' => $e::class,
            ]);
        }

        Log::info('Environment snapshot created.', [
            'actor_id' => $actorId,
            'operation' => 'env.snapshot.create',
            'snapshot_type' => $operation,
            'source_bytes' => strlen($content),
            'retention_deleted' => $deleted,
            'created_at' => $createdAt->toIso8601String(),
        ]);

        return [
            'operation' => $operation,
            'label' => $label,
            'created_at' => $createdAt->toIso8601String(),
        ];
    }

    private function applyRetention(string $directory, string $operation): int
    {
        $pattern = $directory . DIRECTORY_SEPARATOR . 'env-' . $operation . '-*.env';
        $files = glob($pattern) ?: [];

        usort($files, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        $deleted = 0;
        foreach (array_slice($files, self::RETENTION_PER_TYPE) as $file) {
            if (is_file($file) && @unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
