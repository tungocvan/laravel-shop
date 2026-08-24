<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Request\Models\RequestGroup;

final class CreateRequestGroup
{
    public function __construct(private readonly RequestAuditAppender $audit) {}

    public function handle(array $data, int $actorId): RequestGroup
    {
        return DB::transaction(function () use ($data, $actorId): RequestGroup {
            $group = RequestGroup::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $this->audit->append('request_group', $group->public_id, 'request.group.created.v1', $actorId, (string) Str::uuid());

            return $group;
        });
    }
}
