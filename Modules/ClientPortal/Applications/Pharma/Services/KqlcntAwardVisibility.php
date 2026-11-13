<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Muasamcong\Models\KqlcntAwardItem;

/** Server-side read boundary for canonical KQLCNT groups. */
final class KqlcntAwardVisibility
{
    public const VIEW_ALL = 'client.pharma.kqlcnt-awards.view-all';

    public function __construct(
        private readonly ApplicationRegistry $registry,
        private readonly KqlcntAssignedUserOverview $assignments,
    ) {}

    public function canViewAll(Request $request): bool
    {
        $user = $request->user('web');

        return $user !== null && $this->registry->userCan($user, self::VIEW_ALL);
    }

    /** @return array<int, list<array{user_id:int,name:string}>> */
    public function visibleAssignments(Request $request): array
    {
        $user = $request->user('web');
        abort_unless($user, 403);
        $groups = $this->assignments->assignedGroupUsers();
        if ($this->canViewAll($request)) {
            return $groups;
        }

        $id = (int) $user->id;

        return array_filter($groups, fn (array $users) => collect($users)->contains(
            fn (array $entry) => $entry['user_id'] === $id
        ));
    }

    /** @return list<int> */
    public function visibleGroupIds(Request $request): array
    {
        return array_map('intval', array_keys($this->visibleAssignments($request)));
    }

    public function authorizeItem(Request $request, int $awardItem): void
    {
        abort_unless($request->user('web'), 403);
        if ($this->canViewAll($request)) {
            return;
        }

        $item = KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')->findOrFail($awardItem);
        $visible = $this->visibleGroupIds($request);
        $allowed = KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')
            ->where('notify_no', $item->notify_no)
            ->when($item->contractor_code === null, fn ($q) => $q->whereNull('contractor_code'), fn ($q) => $q->where('contractor_code', $item->contractor_code))
            ->when($item->contractor_name === null, fn ($q) => $q->whereNull('contractor_name'), fn ($q) => $q->where('contractor_name', $item->contractor_name))
            ->whereIn('id', $visible)->exists();

        abort_unless($allowed, 403);
    }

    public function authorizeExport(Request $request, string $notice, string $contractor): void
    {
        if ($this->canViewAll($request)) {
            return;
        }

        // Export selects all rows for a notice + contractor code. Require
        // every distinct canonical group in that export to be authorized;
        // otherwise two legal names sharing a code could leak rows.
        $exportGroups = KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')
            ->where('notify_no', $notice)->where('contractor_code', $contractor)
            ->selectRaw('MIN(id) AS id')
            ->groupBy('notify_no', 'contractor_code', 'contractor_name')
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        $visible = $this->visibleGroupIds($request);

        abort_unless($exportGroups !== [] && array_diff($exportGroups, $visible) === [], 403);
    }
}
