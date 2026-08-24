<?php

namespace Tests\Feature\Request\Definition;

use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;
use Modules\Request\Policies\RequestGroupPolicy;
use Modules\Request\Policies\RequestTypePolicy;

class RequestDefinitionPolicyTest extends RequestDefinitionTestCase
{
    public function test_definition_policies_require_named_permissions_and_record_state(): void
    {
        $allowed = new PermissionActor(['request.group.update', 'request.group.archive', 'request.type.update', 'request.type.publish']);
        $denied = new PermissionActor([]);
        $group = new RequestGroup(['archived_at' => null]);
        $type = new RequestType(['status' => RequestTypeStatus::Draft]);
        $type->active_draft_version_id = 1;

        $this->assertTrue((new RequestGroupPolicy)->update($allowed, $group));
        $this->assertFalse((new RequestGroupPolicy)->update($denied, $group));
        $this->assertTrue((new RequestTypePolicy)->publish($allowed, $type));
        $this->assertFalse((new RequestTypePolicy)->publish($denied, $type));

        $group->archived_at = now();
        $type->status = RequestTypeStatus::Retired;
        $this->assertFalse((new RequestGroupPolicy)->archive($allowed, $group));
        $this->assertFalse((new RequestTypePolicy)->update($allowed, $type));
    }
}

final class PermissionActor
{
    public function __construct(private readonly array $permissions) {}

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
