<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Models\RequestGroup;

final class ArchiveRequestGroup
{
    public function __construct(private readonly RequestAuditAppender $audit) {}

    public function handle(RequestGroup $group, int $actorId): RequestGroup
    {
        return DB::transaction(function () use ($group, $actorId): RequestGroup {
            $locked = RequestGroup::query()->lockForUpdate()->findOrFail($group->id);
            if ($locked->types()->where('status', '!=', 'retired')->exists()) {
                throw ValidationException::withMessages(['group' => 'request_group_has_active_types']);
            }
            $locked->update(['is_active' => false, 'archived_at' => now('UTC'), 'updated_by' => $actorId]);
            $this->audit->append('request_group', $locked->public_id, 'request.group.archived.v1', $actorId, (string) Str::uuid());

            return $locked;
        });
    }
}
