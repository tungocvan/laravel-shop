<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class SystemScriptOperationService
{
    private const MAX_OUTPUT_BYTES = 32768;

    private const OPERATIONS = [
        // Intentionally empty until individual server-owned scripts are reviewed
        // and explicitly registered here with a fixed path and fixed arguments.
    ];

    public function operations(): array
    {
        return collect(self::OPERATIONS)
            ->map(fn (array $operation, string $id): array => [
                'id' => $id,
                'label' => $operation['label'],
                'description' => $operation['description'],
                'confirmation' => $operation['confirmation'] ?? true,
            ])
            ->values()
            ->all();
    }

    public function execute(string $operationId, ?int $actorId = null): array
    {
        $operation = self::OPERATIONS[$operationId] ?? null;

        if ($operation === null) {
            Log::warning('Rejected unknown System script operation.', [
                'actor_id' => $actorId,
                'operation_id' => $operationId,
            ]);

            throw new InvalidArgumentException('Unknown System script operation.');
        }

        $scriptPath = $this->resolveRegisteredScriptPath($operation['script']);
        $timeout = (float) ($operation['timeout'] ?? 60);
        $arguments = array_values($operation['arguments'] ?? []);

        $context = [
            'actor_id' => $actorId,
            'operation_id' => $operationId,
            'script' => basename($scriptPath),
        ];

        Log::notice('System script operation started.', $context);

        try {
            $process = new Process(array_merge(['bash', $scriptPath], $arguments), base_path());
            $process->setTimeout($timeout);
            $process->run();

            $output = trim($process->getOutput().$process->getErrorOutput());
            $output = $this->boundOutput($output);

            Log::notice('System script operation completed.', $context + [
                'exit_code' => $process->getExitCode(),
            ]);

            if (! $process->isSuccessful()) {
                throw new RuntimeException('Registered System script operation failed.');
            }

            return [
                'exit_code' => $process->getExitCode() ?? 1,
                'output' => $output,
            ];
        } catch (Throwable $e) {
            Log::error('System script operation failed.', $context + [
                'exception' => $e::class,
            ]);

            throw $e;
        }
    }

    private function resolveRegisteredScriptPath(string $relativePath): string
    {
        if ($relativePath === '' || str_contains($relativePath, '..') || str_starts_with($relativePath, '/') || str_starts_with($relativePath, '\\')) {
            throw new RuntimeException('Invalid registered System script path.');
        }

        $root = realpath(base_path('scripts/system'));
        $candidate = realpath(base_path('scripts/system/'.$relativePath));

        if ($root === false || $candidate === false || ! is_file($candidate)) {
            throw new RuntimeException('Registered System script does not exist.');
        }

        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (! str_starts_with($candidate, $rootPrefix)) {
            throw new RuntimeException('Registered System script escaped the approved root.');
        }

        return $candidate;
    }

    private function boundOutput(string $output): string
    {
        if (strlen($output) <= self::MAX_OUTPUT_BYTES) {
            return $output;
        }

        return substr($output, 0, self::MAX_OUTPUT_BYTES)."\n[output truncated]";
    }
}
