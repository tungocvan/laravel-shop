<?php

namespace Modules\Request\Domain\Approval;

use Illuminate\Validation\ValidationException;
use Modules\Request\Contracts\ActorResolver;
use Modules\Request\Domain\Enums\CandidateSource;
use Modules\User\Contracts\UserDirectory;

final class FormUserFieldResolver implements ActorResolver
{
    public function __construct(private readonly UserDirectory $users) {}

    public function key(): string
    {
        return 'form_user_field';
    }

    public function resolve(ActorResolutionContext $context): ResolvedActors
    {
        $field = (string) data_get($context->stage->resolver_config_json, 'field_key', '');
        $user = $this->users->findActive((int) ($context->payload[$field] ?? 0));
        if ($field === '' || $user === null) {
            throw ValidationException::withMessages(['stage' => ['user_unavailable']]);
        }

        return new ResolvedActors([$user], CandidateSource::FormUserField, $field);
    }
}
