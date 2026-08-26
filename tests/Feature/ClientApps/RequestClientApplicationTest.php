<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ApplicationPermissionService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Request\Http\Middleware\UseRequestAuthorizationGuard;
use Tests\TestCase;

class RequestClientApplicationTest extends TestCase
{
    public function test_registry_discovers_request_adapter_manifest_when_request_module_is_enabled(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        $application = app(ApplicationRegistry::class)->find('request');

        $this->assertNotNull($application);
        $this->assertSame('Request', $application['module']);
        $this->assertSame('Đề nghị & Phê duyệt', $application['name']);
        $this->assertSame('client.request.dashboard', $application['route']);
        $this->assertSame('client.request.access', $application['permission']);
        $this->assertSame(
            ['overview', 'create', 'mine', 'inbox', 'processed'],
            collect($application['features'])->pluck('key')->all(),
        );
        $this->assertSame('client.request.inbox', $application['features'][3]['route']);
        $this->assertSame('client.request.processed', $application['features'][4]['route']);
    }

    public function test_request_application_disappears_when_source_module_is_disabled(): void
    {
        config()->set('modules.registry.Request.enabled', false);

        $this->assertNull(app(ApplicationRegistry::class)->find('request'));
    }

    public function test_guest_is_redirected_from_request_dashboard(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        $this->get('/apps/request')->assertRedirect(route('client.apps.login'));
    }

    public function test_request_dashboard_route_uses_web_client_and_request_authorization_boundaries(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        $route = Route::getRoutes()->getByName('client.request.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('apps/request', $route->uri());
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.application:request', $route->gatherMiddleware());
        $this->assertContains('client.feature:request,overview', $route->gatherMiddleware());
        $this->assertContains(UseRequestAuthorizationGuard::class.':web', $route->gatherMiddleware());
    }

    public function test_request_approver_routes_use_web_guard_and_feature_boundaries(): void
    {
        config()->set('modules.registry.Request.enabled', true);

        foreach ([
            'client.request.inbox' => ['apps/request/inbox', 'client.feature:request,inbox'],
            'client.request.approval.show' => ['apps/request/inbox/{requestPublicId}', 'client.feature:request,inbox'],
            'client.request.processed' => ['apps/request/processed', 'client.feature:request,processed'],
        ] as $name => [$uri, $featureMiddleware]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, $name);
            $this->assertSame($uri, $route->uri(), $name);
            $this->assertContains('auth:web', $route->gatherMiddleware(), $name);
            $this->assertContains('client.application:request', $route->gatherMiddleware(), $name);
            $this->assertContains($featureMiddleware, $route->gatherMiddleware(), $name);
            $this->assertContains(UseRequestAuthorizationGuard::class.':web', $route->gatherMiddleware(), $name);
        }
    }

    public function test_approver_inbox_component_is_channel_aware(): void
    {
        $component = file_get_contents(base_path('Modules/Request/Livewire/Approver/Inbox.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/livewire/approver/inbox.blade.php'));
        $dashboard = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/request/dashboard.blade.php'));

        $this->assertStringContainsString('InteractsWithRequestAuthorization', $component);
        $this->assertStringContainsString('$this->requestActor($context)', $component);
        $this->assertStringContainsString('Gate::forUser($user)->authorize', $component);
        $this->assertStringNotContainsString("auth('admin')->id()", $component);
        $this->assertStringContainsString("client.request.approval.show", $component);
        $this->assertStringContainsString("\$requestGuard === 'admin'", $view);
        $this->assertStringContainsString("route('client.request.inbox')", $dashboard);
        $this->assertStringContainsString("route('client.request.processed')", $dashboard);
    }

    public function test_approver_detail_and_decision_panel_are_channel_aware(): void
    {
        $detail = file_get_contents(base_path('Modules/Request/Livewire/Approver/RequestDetail.php'));
        $decision = file_get_contents(base_path('Modules/Request/Livewire/Approver/DecisionPanel.php'));
        $detailView = file_get_contents(base_path('Modules/Request/resources/views/livewire/approver/request-detail-client.blade.php'));

        $this->assertStringContainsString('InteractsWithRequestAuthorization', $detail);
        $this->assertStringContainsString('request.instance.view-participant', file_get_contents(base_path('Modules/Request/Policies/InternalRequestPolicy.php')));
        $this->assertStringContainsString('Gate::forUser($user)->authorize', $detail);
        $this->assertStringContainsString("Gate::forUser(\$user)->allows('decide', \$task)", $detail);
        $this->assertStringContainsString('InteractsWithRequestAuthorization', $decision);
        $this->assertStringContainsString('$this->requestActor($context)', $decision);
        $this->assertStringContainsString("Gate::forUser(\$user)->authorize('decide', \$task)", $decision);
        $this->assertStringNotContainsString("auth('admin')->id()", $decision);
        $this->assertStringContainsString("client.request.inbox", $decision);
        $this->assertStringContainsString('request.approver.decision-panel', $detailView);
    }

    public function test_client_approver_visible_statuses_are_vietnamese(): void
    {
        $detailView = file_get_contents(base_path('Modules/Request/resources/views/livewire/approver/request-detail-client.blade.php'));
        $decisionView = file_get_contents(base_path('Modules/Request/resources/views/livewire/approver/decision-panel.blade.php'));
        $authorization = file_get_contents(base_path('Modules/Request/Livewire/Concerns/InteractsWithRequestAuthorization.php'));
        $translations = require base_path('Modules/Request/resources/lang/vi/request.php');

        $this->assertStringContainsString("__('Request::request.task_statuses.'.\$taskStatus)", $detailView);
        $this->assertStringNotContainsString('ucfirst($taskStatus)', $detailView);
        $this->assertStringNotContainsString('<dd class="mt-1 text-sm text-slate-800">{{ $taskStatus }}</dd>', $detailView);
        $this->assertStringContainsString("__('Request::request.approve')", $decisionView);
        $this->assertStringContainsString("__('Request::request.return')", $decisionView);
        $this->assertStringContainsString("__('Request::request.reject')", $decisionView);
        $this->assertStringContainsString("if (\$this->requestGuard === 'web')", $authorization);
        $this->assertStringContainsString("app()->setLocale('vi')", $authorization);
        $this->assertSame('Đang xử lý', $translations['task_statuses']['active']);
        $this->assertSame('Đã duyệt', $translations['task_statuses']['approved']);
        $this->assertSame('Từ chối', $translations['task_statuses']['rejected']);
        $this->assertSame('Trả lại', $translations['task_statuses']['returned']);
    }

    public function test_request_discussion_is_shared_and_channel_aware_for_requester_and_approver(): void
    {
        $detailView = file_get_contents(base_path('Modules/Request/resources/views/livewire/approver/request-detail-client.blade.php'));
        $component = file_get_contents(base_path('Modules/Request/Livewire/Requester/CommentComposer.php'));
        $commentView = file_get_contents(base_path('Modules/Request/resources/views/livewire/requester/comment-composer.blade.php'));
        $policy = file_get_contents(base_path('Modules/Request/Policies/RequestCommentPolicy.php'));
        $profiles = app(ApplicationPermissionService::class)->profiles();
        $translations = require base_path('Modules/Request/resources/lang/vi/request.php');

        $this->assertStringContainsString('request.requester.comment-composer', $detailView);
        $this->assertStringContainsString(':request-version="$request->lock_version"', $detailView);
        $this->assertStringContainsString('InteractsWithRequestAuthorization', $component);
        $this->assertStringContainsString('$this->requestActor($context)', $component);
        $this->assertStringContainsString("Gate::forUser(\$user)->authorize('create', [RequestComment::class, \$request])", $component);
        $this->assertStringContainsString("Gate::forUser(\$user)->authorize('view', \$request)", $component);
        $this->assertStringContainsString("\$this->dispatch('request-version-changed'", $component);
        $this->assertStringContainsString('Trao đổi', $commentView);
        $this->assertStringContainsString("__('Request::request.post_comment')", $commentView);
        $this->assertSame('Gửi bình luận', $translations['post_comment']);
        $this->assertStringContainsString("request.comment.create", $policy);
        $this->assertStringContainsString('InternalRequestPolicy', $policy);
        $this->assertContains('request.comment.create', $profiles['requester']['permissions']);
        $this->assertContains('request.comment.create', $profiles['approver']['permissions']);
    }
}
