<?php

namespace Modules\Request\Domain\Forms;

use Modules\Request\Domain\Approval\ActorResolverConfigRegistry;
use Modules\Request\Domain\Enums\StageMode;
use Modules\Request\Models\RequestTypeVersion;
use Modules\Role\Contracts\RoleDirectory;
use Modules\User\Contracts\UserDirectory;

final class RequestTypeDraftValidator
{
    public function __construct(
        private readonly FormSchemaValidator $schemas,
        private readonly ActorResolverConfigRegistry $resolvers,
        private readonly UserDirectory $users,
        private readonly RoleDirectory $roles,
    ) {}

    public function errors(RequestTypeVersion $draft): array
    {
        $errors = $this->schemas->errors((array) $draft->form_schema_json);
        $stages = $draft->stages()->orderBy('position')->get();
        $userFields = collect((array) data_get($draft->form_schema_json, 'sections', []))
            ->flatMap(fn (array $section): array => (array) ($section['fields'] ?? []))
            ->filter(fn (array $field): bool => ($field['type'] ?? null) === 'user')
            ->pluck('key')
            ->all();

        if ($stages->isEmpty()) {
            $errors['stages'][] = 'at_least_one_stage_required';
        }

        if ($stages->count() > (int) config('request.settings.max_stage_count', 20)) {
            $errors['stages'][] = 'stage_limit_exceeded';
        }

        foreach ($stages as $index => $stage) {
            $path = "stages.{$index}";
            if ($stage->position !== $index + 1) {
                $errors["{$path}.position"][] = 'positions_must_be_contiguous';
            }
            if (! $stage->mode instanceof StageMode) {
                $errors["{$path}.mode"][] = 'invalid_mode';
            }
            if (! $this->resolvers->supports($stage->resolver_key)) {
                $errors["{$path}.resolver_key"][] = 'resolver_not_registered';
            }
            $this->validateResolverConfig($stage->resolver_key, (array) $stage->resolver_config_json, $path, $userFields, $errors);
        }

        foreach ($draft->audiences as $index => $audience) {
            $exists = $audience->actor_type->value === 'user'
                ? $this->users->findActive($audience->actor_id) !== null
                : $this->roles->findAdminRole($audience->actor_id) !== null;
            if (! $exists) {
                $errors["audiences.{$index}.actor_id"][] = 'actor_unavailable';
            }
        }

        return $errors;
    }

    private function validateResolverConfig(string $key, array $config, string $path, array $userFields, array &$errors): void
    {
        if ($key === 'fixed_users') {
            $ids = array_values(array_unique(array_map('intval', (array) ($config['user_ids'] ?? []))));
            if ($ids === [] || count($this->users->findManyActive($ids, min(count($ids), 100))) !== count($ids)) {
                $errors["{$path}.resolver_config"][] = 'fixed_users_invalid';
            }
        } elseif ($key === 'role_members') {
            if ($this->roles->findAdminRole((int) ($config['role_id'] ?? 0)) === null) {
                $errors["{$path}.resolver_config"][] = 'role_unavailable';
            }
        } elseif ($key === 'form_user_field') {
            $field = (string) ($config['field_key'] ?? '');
            if ($field === '' || ! in_array($field, $userFields, true)) {
                $errors["{$path}.resolver_config"][] = 'user_field_invalid';
            }
        }
    }
}
