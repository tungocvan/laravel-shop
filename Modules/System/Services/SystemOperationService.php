<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class SystemOperationService
{
    public function operations(): array
    {
        return collect($this->registry())
            ->map(fn (array $operation, string $id): array => [
                'id' => $id,
                'group' => $operation['group'],
                'label' => $operation['label'],
                'description' => $operation['description'],
                'confirmation' => $operation['confirmation'],
            ])
            ->values()
            ->all();
    }

    public function execute(string $operationId, ?int $actorId = null): array
    {
        $operation = $this->registry()[$operationId] ?? null;

        if ($operation === null) {
            Log::warning('Rejected unknown System operation.', [
                'actor_id' => $actorId,
                'operation_id' => $operationId,
            ]);

            throw new InvalidArgumentException('Unknown System operation.');
        }

        $context = [
            'actor_id' => $actorId,
            'operation_id' => $operationId,
            'command' => $operation['command'],
        ];

        Log::notice('System operation started.', $context);

        try {
            $exitCode = Artisan::call($operation['command'], $operation['arguments']);
            $output = trim(Artisan::output());

            Log::notice('System operation completed.', $context + [
                'exit_code' => $exitCode,
            ]);

            return [
                'exit_code' => $exitCode,
                'output' => $output,
            ];
        } catch (Throwable $e) {
            Log::error('System operation failed.', $context + [
                'exception' => $e::class,
            ]);

            throw $e;
        }
    }

    private function registry(): array
    {
        $path = base_path('Modules/System/config/artisan_operations.php');
        $operations = File::exists($path) ? File::getRequire($path) : [];

        if (! is_array($operations)) {
            throw new InvalidArgumentException('Invalid System Artisan operation registry.');
        }

        foreach ($operations as $id => $operation) {
            if (! is_string($id)
                || ! is_array($operation)
                || ! isset($operation['group'], $operation['label'], $operation['description'], $operation['command'], $operation['arguments'], $operation['confirmation'])
                || ! is_string($operation['group'])
                || ! is_string($operation['label'])
                || ! is_string($operation['description'])
                || ! is_string($operation['command'])
                || ! is_array($operation['arguments'])
                || ! is_bool($operation['confirmation'])) {
                throw new InvalidArgumentException('Invalid System Artisan operation definition.');
            }
        }

        return $operations;
    }
}
