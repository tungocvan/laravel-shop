<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Contracts\RequestDefinitionPackage;
use Modules\Request\Domain\Approval\ActorResolverConfigRegistry;
use Modules\Request\Domain\Enums\AudienceActorType;
use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Domain\Enums\StageMode;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Models\RequestType;
use Modules\Role\Contracts\RoleDirectory;
use Modules\User\Contracts\UserDirectory;

final readonly class DryRunRequestDefinitionPackage
{
    public function __construct(
        private RequestDefinitionPackage $packages,
        private DefinitionCanonicalizer $canonicalizer,
        private ActorResolverConfigRegistry $resolvers,
        private UserDirectory $users,
        private RoleDirectory $roles,
    ) {}

    public function handle(RequestType $targetType, array $package, array $mappings): array
    {
        $errors = $this->packages->validate($package);
        if ($targetType->active_draft_version_id !== null) {
            $errors['target'][] = 'active_draft_exists';
        }
        if ($targetType->current_published_version_id === null) {
            $errors['target'][] = 'published_source_required';
        }

        $definition = (array) ($package['definition'] ?? []);
        $required = $this->requiredMappings($package);
        $resolved = $this->resolveMappings($required, $mappings, $errors);
        $audiences = $this->resolveAudiences((array) ($definition['audiences'] ?? []), $resolved, $errors);
        $stages = $this->resolveStages((array) ($definition['stages'] ?? []), $resolved, $errors);

        $normalized = [
            'title' => trim((string) ($definition['title'] ?? '')),
            'description' => $definition['description'] ?? null,
            'requester_guidance' => $definition['requester_guidance'] ?? null,
            'form_schema_json' => (array) ($definition['form_schema'] ?? []),
            'policy_json' => (array) ($definition['policy'] ?? []),
            'presentation_json' => (array) ($definition['presentation'] ?? []),
            'audiences' => $audiences,
            'stages' => $stages,
        ];

        $target = $targetType->currentPublishedVersion;
        $current = $target ? [
            'title' => $target->title,
            'description' => $target->description,
            'requester_guidance' => $target->requester_guidance,
            'form_schema_json' => (array) $target->form_schema_json,
            'policy_json' => (array) $target->policy_json,
            'presentation_json' => (array) $target->presentation_json,
            'audiences' => $target->audiences()->orderBy('id')->get()->map(fn ($audience): array => $audience->only(['actor_type', 'actor_id', 'capability']))->all(),
            'stages' => $target->stages()->get()->map(fn ($stage): array => $stage->only(['stage_key', 'name', 'position', 'mode', 'resolver_key', 'resolver_config_json', 'instructions', 'allow_reassignment']))->all(),
        ] : [];

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $this->warnings($package),
            'required_mappings' => array_values($required),
            'resolved_definition' => $normalized,
            'changed_sections' => $this->changedSections($current, $normalized),
        ];
    }

    private function requiredMappings(array $package): array
    {
        $required = [];
        foreach ((array) ($package['required_mappings'] ?? []) as $mapping) {
            if (is_array($mapping) === false || isset($mapping['ref'], $mapping['kind']) === false) {
                continue;
            }
            $required[(string) $mapping['ref']] = [
                'ref' => (string) $mapping['ref'],
                'kind' => (string) $mapping['kind'],
                'source_id' => (int) ($mapping['source_id'] ?? 0),
            ];
        }

        return $required;
    }

    private function resolveMappings(array $required, array $mappings, array &$errors): array
    {
        $resolved = [];
        foreach ($required as $ref => $requiredMapping) {
            $targetId = isset($mappings[$ref]) ? (int) $mappings[$ref] : 0;
            if ($targetId < 1) {
                $errors['mappings.'.$ref][] = 'mapping_required';

                continue;
            }

            $kind = $requiredMapping['kind'];
            $available = $kind === 'user'
                ? $this->users->findActive($targetId) !== null
                : ($kind === 'role' && $this->roles->findAdminRole($targetId) !== null);

            if ($available === false) {
                $errors['mappings.'.$ref][] = 'mapping_target_unavailable';

                continue;
            }
            $resolved[$ref] = $targetId;
        }

        return $resolved;
    }

    private function resolveAudiences(array $audiences, array $resolved, array &$errors): array
    {
        $normalized = [];
        foreach (array_values($audiences) as $index => $audience) {
            if (is_array($audience) === false) {
                $errors['definition.audiences.'.$index][] = 'invalid_audience';

                continue;
            }
            $actorType = (string) ($audience['actor_type'] ?? '');
            $capability = (string) ($audience['capability'] ?? '');
            $ref = (string) ($audience['actor_ref'] ?? '');
            if (AudienceActorType::tryFrom($actorType) === null || AudienceCapability::tryFrom($capability) === null || isset($resolved[$ref]) === false) {
                $errors['definition.audiences.'.$index][] = 'invalid_or_unmapped_audience';

                continue;
            }
            $normalized[] = ['actor_type' => $actorType, 'actor_id' => $resolved[$ref], 'capability' => $capability];
        }

        return $normalized;
    }

    private function resolveStages(array $stages, array $resolved, array &$errors): array
    {
        $normalized = [];
        foreach (array_values($stages) as $index => $stage) {
            if (is_array($stage) === false) {
                $errors['definition.stages.'.$index][] = 'invalid_stage';

                continue;
            }
            $mode = (string) ($stage['mode'] ?? '');
            $resolver = (string) ($stage['resolver_key'] ?? '');
            if (StageMode::tryFrom($mode) === null || $this->resolvers->supports($resolver) === false) {
                $errors['definition.stages.'.$index][] = 'unsupported_stage_configuration';

                continue;
            }

            $config = (array) ($stage['resolver_config'] ?? []);
            if ($resolver === 'fixed_users') {
                $userIds = [];
                foreach ((array) ($config['user_refs'] ?? []) as $ref) {
                    if (isset($resolved[(string) $ref]) === false) {
                        $errors['definition.stages.'.$index][] = 'user_mapping_required';

                        continue;
                    }
                    $userIds[] = $resolved[(string) $ref];
                }
                unset($config['user_refs']);
                $config['user_ids'] = array_values(array_unique($userIds));
            }
            if ($resolver === 'role_members') {
                $ref = (string) ($config['role_ref'] ?? '');
                if (isset($resolved[$ref]) === false) {
                    $errors['definition.stages.'.$index][] = 'role_mapping_required';
                } else {
                    unset($config['role_ref']);
                    $config['role_id'] = $resolved[$ref];
                }
            }

            $normalized[] = [
                'stage_key' => (string) ($stage['stage_key'] ?? ''),
                'name' => (string) ($stage['name'] ?? ''),
                'position' => $index + 1,
                'mode' => $mode,
                'resolver_key' => $resolver,
                'resolver_config_json' => $config,
                'instructions' => $stage['instructions'] ?? null,
                'allow_reassignment' => (bool) ($stage['allow_reassignment'] ?? false),
            ];
        }

        return $normalized;
    }

    private function changedSections(array $current, array $incoming): array
    {
        $changed = [];
        foreach (array_keys($incoming) as $key) {
            if ($this->canonicalizer->canonicalize([$current[$key] ?? null]) !== $this->canonicalizer->canonicalize([$incoming[$key] ?? null])) {
                $changed[] = $key;
            }
        }

        return $changed;
    }

    private function warnings(array $package): array
    {
        $warnings = [];
        if (($package['signature'] ?? null) === null) {
            $warnings[] = 'signature_not_present';
        }

        return $warnings;
    }
}
