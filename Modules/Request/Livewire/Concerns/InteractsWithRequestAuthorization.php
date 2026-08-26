<?php

namespace Modules\Request\Livewire\Concerns;

use Livewire\Attributes\Locked;
use Modules\Request\Authorization\RequestAuthorizationContext;

trait InteractsWithRequestAuthorization
{
    #[Locked]
    public string $requestGuard = 'admin';

    protected function initializeRequestAuthorization(RequestAuthorizationContext $context): void
    {
        $this->requestGuard = $context->guard() ?? 'admin';
        $this->synchronizeRequestLocale();
    }

    protected function requestActor(RequestAuthorizationContext $context): mixed
    {
        $context->setGuard($this->requestGuard);
        $this->synchronizeRequestLocale();

        $user = auth($this->requestGuard)->user();
        abort_unless($user, 401);

        return $user;
    }

    protected function requestRouteName(string $name): string
    {
        return $this->requestGuard === 'web' ? 'client.request.'.$name : 'request.'.$name;
    }

    private function synchronizeRequestLocale(): void
    {
        if ($this->requestGuard === 'web') {
            app()->setLocale('vi');
        }
    }
}
