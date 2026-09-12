<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use App\Modules\ModuleRegistry;
use RuntimeException;

class ModuleDependencyService
{
    public function __construct(private readonly ModuleRegistry $registry) {}

    public function dependenciesFor(string $module): array
    {
        if (! preg_match('/\A[A-Za-z][A-Za-z0-9_-]{0,79}\z/', $module)) {
            throw new RuntimeException('Tên Module không hợp lệ.');
        }

        $registered = $this->registry->current()->first(
            static fn (array $candidate): bool => ($candidate['name'] ?? null) === $module,
        );

        if (! is_array($registered)) {
            throw new RuntimeException('Module không tồn tại trong Module Registry.');
        }

        $dependencies = array_values(array_unique(array_filter(
            (array) ($registered['depends'] ?? []),
            static fn (mixed $dependency): bool => is_string($dependency)
                && preg_match('/\A[A-Za-z][A-Za-z0-9_-]{0,79}\z/', $dependency) === 1,
        )));
        sort($dependencies, SORT_STRING);

        return $dependencies;
    }
}
