<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class SystemScriptOperationService
{
    private const MAX_OUTPUT_BYTES = 32768;

    public function operations(): array
    {
        return collect($this->registry())
            ->map(fn (array $operation, string $id): array => [
                'id' => $id,
                'group' => $operation['group'],
                'label' => $operation['label'],
                'description' => $operation['description'],
                'confirmation' => $operation['confirmation'],
                'timeout' => $operation['timeout'],
            ])
            ->values()
            ->all();
    }

    public function execute(string $operationId, ?int $actorId = null): array
    {
        $operation = $this->registry()[$operationId] ?? null;

        if ($operation === null) {
            Log::warning('Rejected unknown System script operation.', [
                'actor_id' => $actorId,
                'operation_id' => $operationId,
            ]);

            throw new InvalidArgumentException('Unknown System script operation.');
        }

        $scriptPath = $this->resolveRegisteredScriptPath($operation['script']);
        $timeout = (float) $operation['timeout'];
        $arguments = array_values($operation['arguments']);

        $context = [
            'actor_id' => $actorId,
            'operation_id' => $operationId,
            'script' => basename($scriptPath),
        ];

        Log::notice('System script operation started.', $context);

        try {
            $process = new Process(array_merge(['/bin/bash', $scriptPath], $arguments), base_path());
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

    private function registry(): array
    {
        $path = base_path('Modules/System/config/script_operations.php');
        $operations = File::exists($path) ? File::getRequire($path) : [];

        if (! is_array($operations)) {
            throw new InvalidArgumentException('Invalid System script operation registry.');
        }

        foreach ($operations as $id => $operation) {
            if (! is_string($id)
                || ! is_array($operation)
                || ! isset($operation['group'], $operation['label'], $operation['description'], $operation['script'], $operation['arguments'], $operation['timeout'], $operation['confirmation'])
                || ! is_string($operation['group'])
                || ! is_string($operation['label'])
                || ! is_string($operation['description'])
                || ! is_string($operation['script'])
                || ! is_array($operation['arguments'])
                || ! is_numeric($operation['timeout'])
                || (float) $operation['timeout'] <= 0
                || ! is_bool($operation['confirmation'])) {
                throw new InvalidArgumentException('Invalid System script operation definition.');
            }
        }

        return $operations;
    }

    private function resolveRegisteredScriptPath(string $relativePath): string
    {
        if ($relativePath === '' || str_contains($relativePath, '..') || str_starts_with($relativePath, '/') || str_starts_with($relativePath, '\\')) {
            throw new RuntimeException('Invalid registered System script path.');
        }

        $root = realpath(app_path('sh'));
        $candidate = realpath(app_path('sh/'.$relativePath));

        if ($root === false || $candidate === false || ! is_file($candidate) || ! is_readable($candidate)) {
            throw new RuntimeException('Registered System script does not exist or is not readable.');
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
