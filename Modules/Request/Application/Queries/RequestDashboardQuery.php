<?php

namespace Modules\Request\Application\Queries;

use Illuminate\Support\Facades\Schema;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestTask;

final class RequestDashboardQuery
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(mixed $user): array
    {
        $userId = (int) $user->getAuthIdentifier();

        $canViewOwn = $this->hasPermission($user, 'request.instance.view-own');
        $canCreate = $this->hasPermission($user, 'request.instance.create');
        $canApprove = $this->hasPermission($user, 'request.task.view');

        $ownCounts = [
            'active' => 0,
            'returned' => 0,
            'draft' => 0,
        ];
        $recentRequests = collect();

        if ($canViewOwn && Schema::hasTable('request_instances')) {
            $ownBase = InternalRequest::query()->where('requester_id', $userId);

            $ownCounts['active'] = (clone $ownBase)->where('status', 'pending')->count();
            $ownCounts['returned'] = (clone $ownBase)->where('status', 'returned')->count();
            $ownCounts['draft'] = (clone $ownBase)->where('status', 'draft')->count();

            $recentRequests = (clone $ownBase)
                ->select(['id', 'public_id', 'request_number', 'status', 'title_snapshot', 'updated_at'])
                ->latest('updated_at')
                ->latest('id')
                ->limit(5)
                ->get();
        }

        $approvalCounts = [
            'pending' => 0,
            'warning' => 0,
            'overdue' => 0,
        ];
        $pendingTasks = collect();

        if ($canApprove && Schema::hasTable('request_tasks')) {
            $activeTasks = RequestTask::query()
                ->where('assignee_user_id', $userId)
                ->where('status', 'active');

            $approvalCounts['pending'] = (clone $activeTasks)->count();
            $approvalCounts['warning'] = (clone $activeTasks)
                ->whereNotNull('warning_at')
                ->where('warning_at', '<=', now('UTC'))
                ->where(function ($query): void {
                    $query->whereNull('due_at')->orWhere('due_at', '>', now('UTC'));
                })
                ->count();
            $approvalCounts['overdue'] = (clone $activeTasks)
                ->whereNotNull('due_at')
                ->where('due_at', '<=', now('UTC'))
                ->count();

            $pendingTasks = (clone $activeTasks)
                ->with(['run.requestInstance:id,public_id,request_number,title_snapshot,status,submitted_at'])
                ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_at')
                ->orderBy('id')
                ->limit(5)
                ->get();
        }

        return [
            'capabilities' => [
                'create' => $canCreate,
                'view_own' => $canViewOwn,
                'approve' => $canApprove,
                'manage_groups' => $this->hasPermission($user, 'request.group.view'),
                'manage_types' => $this->hasPermission($user, 'request.type.view'),
                'reports' => $this->hasPermission($user, 'request.report.view'),
                'operations' => $this->hasPermission($user, 'request.operation.view'),
            ],
            'own_counts' => $ownCounts,
            'approval_counts' => $approvalCounts,
            'recent_requests' => $recentRequests,
            'pending_tasks' => $pendingTasks,
        ];
    }

    private function hasPermission(mixed $user, string $permission): bool
    {
        return method_exists($user, 'checkPermissionTo')
            && $user->checkPermissionTo($permission, 'admin');
    }
}
