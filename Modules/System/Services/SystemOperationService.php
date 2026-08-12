<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class SystemOperationService
{
    private const OPERATIONS = [
        'artisan.list' => [
            'label' => 'Danh sách Artisan',
            'description' => 'Hiển thị các câu lệnh Artisan đang được đăng ký trong ứng dụng.',
            'command' => 'list',
            'arguments' => [],
            'confirmation' => false,
        ],
        'cache.optimize-clear' => [
            'label' => 'Xóa cache framework',
            'description' => 'Xóa config, route, view và các cache tối ưu của Laravel.',
            'command' => 'optimize:clear',
            'arguments' => [],
            'confirmation' => true,
        ],
    ];

    public function operations(): array
    {
        return collect(self::OPERATIONS)
            ->map(fn (array $operation, string $id): array => [
                'id' => $id,
                'label' => $operation['label'],
                'description' => $operation['description'],
                'confirmation' => $operation['confirmation'],
            ])
            ->values()
            ->all();
    }

    public function execute(string $operationId, ?int $actorId = null): array
    {
        $operation = self::OPERATIONS[$operationId] ?? null;

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
}
