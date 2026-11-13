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
            ->where('contractor_code', $item->contractor_code)
            ->where('contractor_name', $item->contractor_name)
            ->whereIn('id', $visible)->exists();

        abort_unless($allowed, 403);
    }

    public function authorizeExport(Request $request, string $notice, string $contractor): void
    {
        if ($this->canViewAll($request)) {
            return;
        }

        $allowed = KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')
            ->where('notify_no', $notice)->where('contractor_code', $contractor)
            ->whereIn('id', $this->visibleGroupIds($request))->exists();

        abort_unless($allowed, 403);
    }
}
