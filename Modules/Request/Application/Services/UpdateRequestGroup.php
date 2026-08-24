<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Request\Models\RequestGroup;

final class UpdateRequestGroup
{
    public function __construct(private readonly RequestAuditAppender $audit) {}

    public function handle(RequestGroup $group, array $data, int $actorId): RequestGroup
    {
        return DB::transaction(function () use ($group, $data, $actorId): RequestGroup {
            $changes = array_intersect_key($data, array_flip([
                'code',
                'name',
                'description',
                'sort_order',
                'is_active',
            ]));
            $group->update($changes + ['updated_by' => $actorId]);
            $this->audit->append('request_group', $group->public_id, 'request.group.updated.v1', $actorId, (string) Str::uuid(), ['changed_keys' => array_keys($changes)]);

            return $group->refresh();
        });
    }
}
