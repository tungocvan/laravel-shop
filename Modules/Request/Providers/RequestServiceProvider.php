<?php

namespace Modules\Request\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Request\Domain\Approval\ActorResolverConfigRegistry;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Domain\Forms\FormFieldRegistry;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;
use Modules\Request\Policies\InternalRequestPolicy;
use Modules\Request\Policies\RequestGroupPolicy;
use Modules\Request\Policies\RequestTypePolicy;
use Modules\Request\Policies\RequestTypeVersionPolicy;

class RequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FormFieldRegistry::class);
        $this->app->singleton(ActorResolverConfigRegistry::class);
        $this->app->singleton(DefinitionCanonicalizer::class);
    }

    public function boot(): void
    {
        Gate::policy(RequestGroup::class, RequestGroupPolicy::class);
        Gate::policy(InternalRequest::class, InternalRequestPolicy::class);
        Gate::policy(RequestType::class, RequestTypePolicy::class);
        Gate::policy(RequestTypeVersion::class, RequestTypeVersionPolicy::class);
    }
}
