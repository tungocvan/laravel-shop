<?php

namespace Tests\Feature\Request\Authorization;

use Modules\Request\Authorization\RequestAuthorizationContext;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Policies\InternalRequestPolicy;
use Tests\TestCase;

class RequestMultiChannelAuthorizationTest extends TestCase
{
    protected function tearDown(): void
    {
        app(RequestAuthorizationContext::class)->restore(null);

        parent::tearDown();
    }

    public function test_admin_channel_uses_only_admin_request_permissions(): void
    {
        $request = $this->draftRequest(7);
        $user = $this->user(7, [
            'admin' => ['request.instance.submit'],
            'web' => [],
        ]);

        app(RequestAuthorizationContext::class)->setGuard('admin');

        $this->assertTrue((new InternalRequestPolicy)->submit($user, $request));
    }

    public function test_web_channel_uses_only_web_request_permissions(): void
    {
        $request = $this->draftRequest(7);
        $user = $this->user(7, [
            'admin' => [],
            'web' => ['request.instance.submit'],
        ]);

        app(RequestAuthorizationContext::class)->setGuard('web');

        $this->assertTrue((new InternalRequestPolicy)->submit($user, $request));
    }

    public function test_admin_permission_does_not_leak_into_web_channel(): void
    {
        $request = $this->draftRequest(7);
        $user = $this->user(7, [
            'admin' => ['request.instance.submit'],
            'web' => [],
        ]);

        app(RequestAuthorizationContext::class)->setGuard('web');

        $this->assertFalse((new InternalRequestPolicy)->submit($user, $request));
    }

    public function test_web_permission_does_not_leak_into_admin_channel(): void
    {
        $request = $this->draftRequest(7);
        $user = $this->user(7, [
            'admin' => [],
            'web' => ['request.instance.submit'],
        ]);

        app(RequestAuthorizationContext::class)->setGuard('admin');

        $this->assertFalse((new InternalRequestPolicy)->submit($user, $request));
    }

    public function test_policy_fails_closed_without_an_authorization_channel(): void
    {
        $request = $this->draftRequest(7);
        $user = $this->user(7, [
            'admin' => ['request.instance.submit'],
            'web' => ['request.instance.submit'],
        ]);

        app(RequestAuthorizationContext::class)->restore(null);

        $this->assertFalse((new InternalRequestPolicy)->submit($user, $request));
    }

    public function test_requester_ownership_remains_required_on_both_channels(): void
    {
        $request = $this->draftRequest(99);
        $user = $this->user(7, [
            'admin' => ['request.instance.submit'],
            'web' => ['request.instance.submit'],
        ]);

        app(RequestAuthorizationContext::class)->setGuard('admin');
        $this->assertFalse((new InternalRequestPolicy)->submit($user, $request));

        app(RequestAuthorizationContext::class)->setGuard('web');
        $this->assertFalse((new InternalRequestPolicy)->submit($user, $request));
    }

    private function draftRequest(int $requesterId): InternalRequest
    {
        return new InternalRequest([
            'requester_id' => $requesterId,
            'status' => RequestStatus::Draft,
            'lock_version' => 1,
        ]);
    }

    private function user(int $id, array $permissionsByGuard): object
    {
        return new class($id, $permissionsByGuard)
        {
            public function __construct(private readonly int $id, private readonly array $permissionsByGuard) {}

            public function getAuthIdentifier(): int
            {
                return $this->id;
            }

            public function checkPermissionTo(string $permission, string $guard): bool
            {
                return in_array($permission, $this->permissionsByGuard[$guard] ?? [], true);
            }
        };
    }
}
