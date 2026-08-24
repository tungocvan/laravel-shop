<?php

namespace Modules\Request\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Request\Contracts\PrivateRequestFileStore;
use Modules\Request\Domain\Approval\ActorResolverConfigRegistry;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Domain\Forms\FormFieldRegistry;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAttachment;
use Modules\Request\Models\RequestAuditEvent;
use Modules\Request\Models\RequestComment;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestTask;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;
use Modules\Request\Policies\InternalRequestPolicy;
use Modules\Request\Policies\RequestAttachmentPolicy;
use Modules\Request\Policies\RequestAuditEventPolicy;
use Modules\Request\Policies\RequestCommentPolicy;
use Modules\Request\Policies\RequestGroupPolicy;
use Modules\Request\Policies\RequestTaskPolicy;
use Modules\Request\Policies\RequestTypePolicy;
use Modules\Request\Policies\RequestTypeVersionPolicy;
use Modules\Request\Support\LaravelPrivateRequestFileStore;

class RequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FormFieldRegistry::class);
        $this->app->singleton(ActorResolverConfigRegistry::class);
        $this->app->singleton(DefinitionCanonicalizer::class);
        $this->app->bind(PrivateRequestFileStore::class, LaravelPrivateRequestFileStore::class);
    }

    public function boot(): void
    {
        Gate::policy(RequestGroup::class, RequestGroupPolicy::class);
        Gate::policy(InternalRequest::class, InternalRequestPolicy::class);
        Gate::policy(RequestType::class, RequestTypePolicy::class);
        Gate::policy(RequestTypeVersion::class, RequestTypeVersionPolicy::class);
        Gate::policy(RequestTask::class, RequestTaskPolicy::class);
        Gate::policy(RequestComment::class, RequestCommentPolicy::class);
        Gate::policy(RequestAttachment::class, RequestAttachmentPolicy::class);
        Gate::policy(RequestAuditEvent::class, RequestAuditEventPolicy::class);
        RateLimiter::for('request-submit', fn (HttpRequest $request) => Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('request-decide', fn (HttpRequest $request) => Limit::perMinute(20)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('request-comment', fn (HttpRequest $request) => Limit::perMinute(20)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('request-upload', fn (HttpRequest $request) => Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('request-download', fn (HttpRequest $request) => Limit::perMinute(60)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
