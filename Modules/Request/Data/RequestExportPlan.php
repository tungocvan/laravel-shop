<?php

namespace Modules\Request\Data;

final readonly class RequestExportPlan
{
    public function __construct(
        public array $filters,
        public array $fields,
        public array $authorizationScope,
        public int $authorizedRowCount,
        public string $mode,
    ) {
    }

    public function shouldQueue(): bool
    {
        return $this->mode === 'queued';
    }
}
