<?php

namespace Modules\Request\Application\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;

final class RequestExportQuery
{
    public function queryFor(mixed $user, array $filters = []): Builder
    {
        return $this->queryForAuthorizationScope($this->authorizationScopeFor($user), $filters);
    }

    public function queryForAuthorizationScope(array $scope, array $filters = []): Builder
    {
        $query = InternalRequest::query()
            ->select([
                'request_instances.id',
                'request_instances.public_id',
                'request_instances.request_number',
                'request_instances.request_type_id',
                'request_instances.requester_id',
                'request_instances.status',
                'request_instances.title_snapshot',
                'request_instances.submitted_at',
                'request_instances.approved_at',
                'request_instances.rejected_at',
                'request_instances.returned_at',
                'request_instances.cancelled_at',
                'request_instances.created_at',
                'request_instances.updated_at',
            ])
            ->with('type:id,public_id,code,name');

        $this->applyAuthorizationSnapshot($query, $scope);
        $this->applyFilters($query, $filters);

        return $query->orderByDesc('request_instances.id');
    }

    public function countBounded(mixed $user, array $filters, int $ceiling): int
    {
        $bounded = $this->queryFor($user, $filters)
            ->reorder()
            ->select('request_instances.id')
            ->limit($ceiling + 1);

        return DB::query()->fromSub($bounded, 'request_export_scope')->count();
    }

    public function authorizationScopeFor(mixed $user): array
    {
        return [
            'user_id' => (int) $user->getAuthIdentifier(),
            'view_all' => $this->hasPermission($user, 'request.instance.view-all'),
            'view_own' => $this->hasPermission($user, 'request.instance.view-own'),
            'view_participant' => $this->hasPermission($user, 'request.instance.view-participant'),
        ];
    }

    private function applyAuthorizationSnapshot(Builder $query, array $scope): void
    {
        if (($scope['view_all'] ?? false) === true) {
            return;
        }

        $userId = (int) ($scope['user_id'] ?? 0);
        $canViewOwn = ($scope['view_own'] ?? false) === true;
        $canViewParticipant = ($scope['view_participant'] ?? false) === true;

        if ($userId <= 0 || (! $canViewOwn && ! $canViewParticipant)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $authorization) use ($userId, $canViewOwn, $canViewParticipant): void {
            if ($canViewOwn) {
                $authorization->where('request_instances.requester_id', $userId);
            }

            if ($canViewParticipant) {
                $method = $canViewOwn ? 'orWhereExists' : 'whereExists';
                $authorization->{$method}(function ($tasks) use ($userId): void {
                    $tasks->selectRaw('1')
                        ->from('request_runs')
                        ->join('request_tasks', 'request_tasks.request_run_id', '=', 'request_runs.id')
                        ->whereColumn('request_runs.request_instance_id', 'request_instances.id')
                        ->where('request_tasks.assignee_user_id', $userId);
                });
            }
        });
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['status']) && in_array($filters['status'], array_column(RequestStatus::cases(), 'value'), true)) {
            $query->where('request_instances.status', $filters['status']);
        }

        if (! empty($filters['type_public_id'])) {
            $query->whereHas('type', fn (Builder $type): Builder => $type->where('public_id', $filters['type_public_id']));
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('request_instances.created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('request_instances.created_at', '<=', $filters['created_to']);
        }
    }

    private function hasPermission(mixed $user, string $permission): bool
    {
        if (method_exists($user, 'checkPermissionTo')) {
            return $user->checkPermissionTo($permission, 'admin');
        }

        return method_exists($user, 'can') && $user->can($permission);
    }
}
