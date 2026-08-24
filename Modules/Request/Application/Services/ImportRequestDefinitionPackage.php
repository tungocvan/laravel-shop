<?php

namespace Modules\Request\Application\Services;

use Illuminate\Validation\ValidationException;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;

final readonly class ImportRequestDefinitionPackage
{
    public function __construct(
        private DryRunRequestDefinitionPackage $dryRun,
        private CreateTypeDraft $createDraft,
        private SaveTypeDraft $saveDraft,
    ) {}

    public function handle(RequestType $targetType, array $package, array $mappings, int $actorId): RequestTypeVersion
    {
        $preview = $this->dryRun->handle($targetType->fresh(), $package, $mappings);
        if ($preview['valid'] !== true) {
            throw ValidationException::withMessages($preview['errors']);
        }

        $draft = $this->createDraft->handle($targetType->fresh(), $actorId);
        $targetType->refresh();

        return $this->saveDraft->handle(
            $targetType,
            $preview['resolved_definition'],
            $actorId,
            (int) $targetType->lock_version,
        );
    }
}
