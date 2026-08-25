<?php

namespace Modules\Request\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Request\Application\Services\RequestAudienceService;
use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;

final class RequestCatalogQuery
{
    public function __construct(private readonly RequestAudienceService $audience) {}

    public function paginate(int $userId, string $search, ?int $groupId, int $perPage): LengthAwarePaginator
    {
        $versionIds = $this->audience->eligibleVersionIds($userId, AudienceCapability::Discover);
        $now = now();

        return RequestType::query()
            ->select(['id', 'public_id', 'request_group_id', 'name', 'summary', 'current_published_version_id', 'sort_order'])
            ->with('group:id,name')
            ->where('status', RequestTypeStatus::Published->value)
            ->whereIn('current_published_version_id', $versionIds)
            ->whereHas('group', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))
            ->when($groupId, fn ($query) => $query->where('request_group_id', $groupId))
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')))
            ->where(fn ($query) => $query->whereNull('available_from')->orWhere('available_from', '<=', $now))
            ->where(fn ($query) => $query->whereNull('available_until')->orWhere('available_until', '>', $now))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function findEligible(string $publicId, int $userId, AudienceCapability $capability): RequestType
    {
        $versionIds = $this->audience->eligibleVersionIds($userId, $capability);
        $now = now();

        return RequestType::query()
            ->with(['group:id,name', 'currentPublishedVersion:id,request_type_id,title,description,requester_guidance,form_schema_json,schema_version'])
            ->where('public_id', $publicId)
            ->where('status', RequestTypeStatus::Published->value)
            ->whereIn('current_published_version_id', $versionIds)
            ->whereHas('group', fn ($query) => $query->where('is_active', true)->whereNull('archived_at'))
            ->where(fn ($query) => $query->whereNull('available_from')->orWhere('available_from', '<=', $now))
            ->where(fn ($query) => $query->whereNull('available_until')->orWhere('available_until', '>', $now))
            ->firstOrFail();
    }

    public function groupOptions(int $userId): array
    {
        $versionIds = $this->audience->eligibleVersionIds($userId, AudienceCapability::Discover);

        return RequestGroup::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->whereHas('types', fn ($query) => $query->whereIn('current_published_version_id', $versionIds)->where('status', RequestTypeStatus::Published->value))
            ->orderBy('sort_order')
            ->limit(100)
            ->get()
            ->all();
    }
}
