<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class QueueFailedJobService
{
    public function list(string $queue, int $limit = 25): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        $identifier = $this->identifierColumn();

        return DB::table('failed_jobs')
            ->where('queue', $queue)
            ->orderByDesc('failed_at')
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(function (object $row) use ($identifier): array {
                $payload = json_decode((string) ($row->payload ?? ''), true);
                $job = is_array($payload)
                    ? (string) ($payload['displayName'] ?? data_get($payload, 'data.commandName', 'Unknown job'))
                    : 'Unknown job';

                return [
                    'id' => (string) ($row->{$identifier} ?? ''),
                    'job' => $job !== '' ? $job : 'Unknown job',
                    'connection' => (string) ($row->connection ?? ''),
                    'queue' => (string) ($row->queue ?? ''),
                    'failed_at' => (string) ($row->failed_at ?? ''),
                    'exception' => $this->exceptionSummary((string) ($row->exception ?? '')),
                ];
            })
            ->filter(fn (array $row): bool => $row['id'] !== '')
            ->values()
            ->all();
    }

    public function retry(string $queue, array $ids): int
    {
        $ids = $this->validatedIds($queue, $ids);
        $retried = 0;

        foreach ($ids as $id) {
            $exitCode = Artisan::call('queue:retry', ['id' => [$id]]);

            if ($exitCode === 0) {
                $retried++;
            }
        }

        return $retried;
    }

    public function forget(string $queue, array $ids): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        $ids = $this->validatedIds($queue, $ids);

        if ($ids === []) {
            return 0;
        }

        return DB::table('failed_jobs')
            ->where('queue', $queue)
            ->whereIn($this->identifierColumn(), $ids)
            ->delete();
    }

    public function clear(string $queue): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return DB::table('failed_jobs')->where('queue', $queue)->delete();
    }

    private function validatedIds(string $queue, array $ids): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        $ids = collect($ids)
            ->filter(fn (mixed $id): bool => is_string($id) || is_int($id))
            ->map(fn (mixed $id): string => trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $identifier = $this->identifierColumn();

        return DB::table('failed_jobs')
            ->where('queue', $queue)
            ->whereIn($identifier, $ids)
            ->pluck($identifier)
            ->map(fn (mixed $id): string => (string) $id)
            ->values()
            ->all();
    }

    private function identifierColumn(): string
    {
        if (Schema::hasColumn('failed_jobs', 'uuid')) {
            return 'uuid';
        }

        if (Schema::hasColumn('failed_jobs', 'id')) {
            return 'id';
        }

        throw new RuntimeException('Failed jobs table has no supported identifier column.');
    }

    private function exceptionSummary(string $exception): string
    {
        $line = trim((string) strtok($exception, "\r\n"));

        if ($line === '') {
            return 'Không có thông tin lỗi.';
        }

        return mb_strimwidth($line, 0, 240, '…', 'UTF-8');
    }
}
