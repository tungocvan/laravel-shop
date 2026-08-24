<?php

namespace Modules\Request\Application\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Modules\Request\Data\RequestExportPlan;
use Modules\Request\Domain\Enums\RequestStatus;

final readonly class PlanRequestExport
{
    private const SAFE_FIELDS = [
        'request_number',
        'type_code',
        'type_name',
        'status',
        'title',
        'requester_id',
        'submitted_at',
        'created_at',
        'updated_at',
    ];

    public function __construct(private RequestExportQuery $query) {}

    public function plan(mixed $user, array $filters = [], array $fields = []): RequestExportPlan
    {
        $this->authorize($user);

        $normalizedFilters = $this->normalizeFilters($filters);
        $normalizedFields = $this->normalizeFields($fields);
        $maxRows = max(1, (int) config('request.exports.max_rows', 100000));
        $syncLimit = min($maxRows, max(1, (int) config('request.exports.sync_row_limit', 500)));
        $rowCount = $this->query->countBounded($user, $normalizedFilters, $maxRows);

        if ($rowCount > $maxRows) {
            throw ValidationException::withMessages([
                'export' => __('Request::request.exports.too_many_rows', ['max' => number_format($maxRows)]),
            ]);
        }

        return new RequestExportPlan(
            filters: $normalizedFilters,
            fields: $normalizedFields,
            authorizationScope: $this->query->authorizationScopeFor($user),
            authorizedRowCount: $rowCount,
            mode: $rowCount <= $syncLimit ? 'sync' : 'queued',
        );
    }

    private function authorize(mixed $user): void
    {
        if ($this->hasPermission($user, 'request.export') === false) {
            throw new AuthorizationException;
        }
    }

    private function normalizeFilters(array $filters): array
    {
        $normalized = [];

        if (isset($filters['status']) && in_array($filters['status'], array_column(RequestStatus::cases(), 'value'), true)) {
            $normalized['status'] = $filters['status'];
        }

        foreach (['type_public_id', 'created_from', 'created_to'] as $key) {
            if (isset($filters[$key]) && is_string($filters[$key]) && trim($filters[$key]) !== '') {
                $normalized[$key] = trim($filters[$key]);
            }
        }

        return $normalized;
    }

    private function normalizeFields(array $fields): array
    {
        if ($fields === []) {
            return self::SAFE_FIELDS;
        }

        $fields = array_values(array_unique(array_filter($fields, fn (mixed $field): bool => is_string($field) && in_array($field, self::SAFE_FIELDS, true))));

        if ($fields === []) {
            throw ValidationException::withMessages([
                'fields' => __('Request::request.exports.invalid_fields'),
            ]);
        }

        return $fields;
    }

    private function hasPermission(mixed $user, string $permission): bool
    {
        if (method_exists($user, 'checkPermissionTo')) {
            return $user->checkPermissionTo($permission, 'admin');
        }

        return method_exists($user, 'can') && $user->can($permission);
    }
}
