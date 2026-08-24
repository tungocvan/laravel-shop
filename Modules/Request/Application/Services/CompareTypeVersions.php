<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Models\RequestTypeVersion;

final class CompareTypeVersions
{
    public function __construct(private readonly DefinitionCanonicalizer $canonicalizer) {}

    public function handle(RequestTypeVersion $left, RequestTypeVersion $right): array
    {
        $definition = fn (RequestTypeVersion $version): array => [
            'schema' => $version->form_schema_json,
            'policy' => $version->policy_json,
            'presentation' => $version->presentation_json,
            'audiences' => $version->audiences()->orderBy('id')->get()
                ->map->only(['actor_type', 'actor_id', 'capability'])->all(),
            'stages' => $version->stages()->orderBy('position')->get()
                ->map->only(['stage_key', 'name', 'position', 'mode', 'resolver_key', 'resolver_config_json', 'instructions', 'allow_reassignment'])->all(),
        ];
        $leftDefinition = $definition($left);
        $rightDefinition = $definition($right);

        return ['changed' => $this->canonicalizer->checksum($leftDefinition) !== $this->canonicalizer->checksum($rightDefinition), 'left' => $leftDefinition, 'right' => $rightDefinition];
    }
}
