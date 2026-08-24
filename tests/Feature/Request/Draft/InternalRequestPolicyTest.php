<?php

namespace Tests\Feature\Request\Draft;

use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Policies\InternalRequestPolicy;

class InternalRequestPolicyTest extends RequestDraftTestCase
{
    public function test_owner_permissions_and_record_identity_are_both_required(): void
    {
        $owner = new DraftPermissionActor(10, ['request.instance.view-own', 'request.instance.update-own', 'request.instance.cancel-own']);
        $other = new DraftPermissionActor(11, ['request.instance.view-own', 'request.instance.update-own', 'request.instance.cancel-own']);
        $admin = new DraftPermissionActor(12, ['request.instance.view-all']);
        $request = new InternalRequest(['requester_id' => 10, 'status' => RequestStatus::Draft]);
        $policy = new InternalRequestPolicy;

        $this->assertTrue($policy->view($owner, $request));
        $this->assertTrue($policy->update($owner, $request));
        $this->assertTrue($policy->cancel($owner, $request));
        $this->assertFalse($policy->view($other, $request));
        $this->assertFalse($policy->update($other, $request));
        $this->assertTrue($policy->view($admin, $request));

        $request->status = RequestStatus::Cancelled;
        $this->assertFalse($policy->update($owner, $request));
        $this->assertFalse($policy->cancel($owner, $request));
    }
}

final class DraftPermissionActor
{
    public function __construct(private readonly int $id, private readonly array $permissions) {}

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }
}
