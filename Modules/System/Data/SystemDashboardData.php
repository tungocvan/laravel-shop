<?php

namespace Modules\System\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class SystemDashboardData implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, bool>  $capabilities
     * @param  array<string, bool>  $availability
     * @param  array<string, mixed>  $metrics
     * @param  array<string, array<string, mixed>>  $subsystems
     * @param  array<int, array{code: string, label: string, description: string, category: string}>  $workspaces
     * @param  array<int, array{level: string, code: string, message: string}>  $warnings
     */
    public function __construct(
        public string $generatedAt,
        public array $capabilities,
        public array $availability,
        public array $metrics,
        public array $subsystems,
        public array $workspaces,
        public array $warnings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'generated_at' => $this->generatedAt,
            'capabilities' => $this->capabilities,
            'availability' => $this->availability,
            'metrics' => $this->metrics,
            'subsystems' => $this->subsystems,
            'workspaces' => $this->workspaces,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
