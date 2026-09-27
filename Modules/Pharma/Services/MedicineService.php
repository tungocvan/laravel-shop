<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Partner\Models\Partner;
use LogicException;
use Modules\Pharma\Models\Medicine;

class MedicineService
{
    public function __construct(
        private readonly MedicineImportExport $importExport,
        private readonly MedicineIdentityResolver $identityResolver,
        private readonly MedicineCatalogNormalizer $normalizer,
    ) {}

    public function getPaginatedMedicines(
        ?string $search = null,
        int $perPage = 10,
        int $page = 1,
        ?string $circularGroup = null,
        ?string $specialControl = null,
        ?string $profileStatus = null,
        ?string $hsspStatus = null,
        ?int $supplierId = null,
        ?string $deletable = null,
        ?string $registration = null,
    ): LengthAwarePaginator {
        return Medicine::query()
            ->with([
                'variants:id,medicine_id,sku,strength_text,presentation_text,declared_price,status,is_default',
                'supplierTrackings' => fn ($query) => $query->select(['id', 'medicine_id', 'partner_id', 'status'])->with('partner:id,name'),
                'currentProfile' => fn ($query) => $query->select([
                    'pharma_medicine_profiles.id',
                    'pharma_medicine_profiles.medicine_id',
                    'pharma_medicine_profiles.profile_version',
                    'pharma_medicine_profiles.profile_status',
                    'pharma_medicine_profiles.profile_link',
                    'pharma_medicine_profiles.verified_at',
                    'pharma_medicine_profiles.is_current',
                ]),
            ])
            ->withCount(['sources', 'drugBidAwards', 'variants', 'profiles', 'supplierTrackings', 'priceListItems'])
            ->when($search, fn ($query, $value) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$value}%")
                ->orWhere('medicine_code', 'like', "%{$value}%")
                ->orWhere('active_ingredients', 'like', "%{$value}%")
                ->orWhere('therapeutic_group', 'like', "%{$value}%")
                ->orWhere('registration_number', 'like', "%{$value}%")
                ->orWhere('registration_number_primary', 'like', "%{$value}%")
                ->orWhere('registration_number_raw', 'like', "%{$value}%")
                ->orWhere('concentration', 'like', "%{$value}%")
                ->orWhere('manufacturing_company', 'like', "%{$value}%")
                ->orWhere('manufacturing_country', 'like', "%{$value}%")
                ->orWhereHas('variants', fn ($variant) => $variant
                    ->where('sku', 'like', "%{$value}%")
                    ->orWhere('strength_text', 'like', "%{$value}%")
                    ->orWhere('presentation_text', 'like', "%{$value}%"))
                ->orWhereHas('aliases', fn ($alias) => $alias
                    ->where('alias_value', 'like', "%{$value}%")
                    ->orWhere('normalized_value', 'like', "%{$value}%"))))
            ->when($circularGroup, fn ($query, $value) => $query->where('circular_group', $value))
            ->when($specialControl, fn ($query, $value) => $query->where('is_special_control', $value === 'yes'))
            ->when($profileStatus, fn ($query, $value) => $query->where('profile_status', $value))
            ->when($hsspStatus === 'with', fn ($query) => $query->whereHas('currentProfile'))
            ->when($hsspStatus === 'without', fn ($query) => $query->whereDoesntHave('currentProfile'))
            ->when($supplierId, fn ($query, $value) => $query->whereHas('supplierTrackings', fn ($tracking) => $tracking->where('partner_id', $value)))
            ->when($registration === 'with', fn ($query) => $query->whereNotNull('registration_number')->where('registration_number', '!=', ''))
            ->when($registration === 'without', fn ($query) => $query->where(fn ($nested) => $nested->whereNull('registration_number')->orWhere('registration_number', '')))
            ->when($deletable === 'yes', fn ($query) => $query
                ->whereDoesntHave('profiles')
                ->whereDoesntHave('supplierTrackings')
                ->whereDoesntHave('priceListItems')
                ->where(fn ($nested) => $nested
                    ->where('profile_status', '!=', Medicine::PROFILE_VERIFIED)
                    ->orWhereDoesntHave('drugBidAwards')))
            ->when($deletable === 'no', fn ($query) => $query->where(fn ($nested) => $nested
                ->whereHas('profiles')
                ->orWhereHas('supplierTrackings')
                ->orWhereHas('priceListItems')
                ->orWhere(fn ($verified) => $verified
                    ->where('profile_status', Medicine::PROFILE_VERIFIED)
                    ->whereHas('drugBidAwards'))))
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getUniqueCircularGroups(): array
    {
        return Medicine::query()
            ->whereNotNull('circular_group')
            ->where('circular_group', '!=', '')
            ->distinct()
            ->pluck('circular_group')
            ->all();
    }

    public function supplierFilterCandidates(string $search = '', ?int $selectedId = null, int $limit = 25): Collection
    {
        $search = trim($search);
        $query = Partner::query()
            ->where('status', 'active')
            ->whereJsonContains('partner_types', 'supplier')
            ->whereHas('supplierTrackings')
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('tax_code', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit(max(1, min(25, $limit)));

        $items = $query->get(['id', 'name', 'tax_code']);

        if ($selectedId && ! $items->contains('id', $selectedId)) {
            $selected = Partner::query()->find($selectedId, ['id', 'name', 'tax_code']);
            if ($selected) {
                $items->prepend($selected);
            }
        }

        return $items->unique('id')->values();
    }

    public function catalogStats(): array
    {
        return [
            'total' => Medicine::query()->count(),
            'special_control' => Medicine::query()->where('is_special_control', true)->count(),
        ];
    }

    public function findOrFail(int $id): Medicine
    {
        return Medicine::query()->findOrFail($id);
    }

    public function store(array $data): Medicine
    {
        return DB::transaction(function () use ($data): Medicine {
            $data = $this->normalizeQualityState($data);
            $medicine = Medicine::query()->create($data);

            if (! $medicine->medicine_code) {
                $medicine->forceFill([
                    'medicine_code' => 'MED-'.str_pad((string) $medicine->id, 6, '0', STR_PAD_LEFT),
                ])->save();
            }

            return $medicine->refresh();
        });
    }

    public function update(int $id, array $data): Medicine
    {
        return DB::transaction(function () use ($id, $data) {
            $medicine = $this->findOrFail($id);
            $data = $this->normalizeQualityState($data, $medicine);
            $this->guardCanonicalIdentityCollision($medicine, $data['canonical_identity_key'] ?? null);
            $medicine->update($data);

            return $medicine->refresh();
        });
    }

    public function verifyMaster(int $id): Medicine
    {
        return DB::transaction(function () use ($id): Medicine {
            $medicine = $this->findOrFail($id);

            $required = [
                'registration_number' => 'Giấy phép lưu hành',
                'active_ingredients' => 'Tên hoạt chất',
                'concentration' => 'Nồng độ / Hàm lượng',
                'dosage_form' => 'Dạng bào chế',
                'route_of_administration' => 'Đường dùng',
                'unit' => 'Đơn vị tính',
                'packaging_specification' => 'Quy cách đóng gói',
                'shelf_life' => 'Hạn dùng',
                'registered_company' => 'Cơ sở đăng ký',
                'manufacturing_company' => 'Cơ sở sản xuất',
                'manufacturing_country' => 'Nước sản xuất',
            ];

            $missing = collect($required)
                ->filter(fn (string $label, string $field) => blank($medicine->getAttribute($field)))
                ->values()
                ->all();

            if ($missing !== []) {
                throw new LogicException(
                    'Chưa thể xác minh Medicine Master. Hãy lưu lại đầy đủ thông tin Nhà sản xuất & thông tin quản lý rồi xác nhận lại.'
                );
            }

            $identityKey = $this->identityResolver->canonicalMedicineIdentity($medicine->toArray());
            $this->guardCanonicalIdentityCollision($medicine, $identityKey);

            $medicine->forceFill([
                'canonical_identity_key' => $identityKey,
                'identity_status' => Medicine::IDENTITY_VERIFIED_REGISTRATION,
                'profile_status' => Medicine::PROFILE_VERIFIED,
                'last_verified_at' => now(),
            ])->save();

            return $medicine->refresh();
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $medicine = $this->findOrFail($id);

            // Variants, packages, aliases and source provenance are owned catalog data
            // and are configured to cascade with the medicine. Only external/business
            // references must protect the canonical record from hard deletion.
            if ($medicine->profiles()->exists() || $medicine->supplierTrackings()->exists() || $medicine->priceListItems()->exists()) {
                throw new LogicException('Không thể xóa thuốc vì đã có HSSP, bảng giá hoặc điều kiện thương mại nhà cung cấp tham chiếu.');
            }

            if ($medicine->profile_status === Medicine::PROFILE_VERIFIED && $medicine->drugBidAwards()->exists()) {
                throw new LogicException('Không thể xóa Medicine Master đã xác minh khi còn dữ liệu kết quả lựa chọn nhà thầu tham chiếu.');
            }

            // Legacy bid links to an unverified master are no longer valid under the
            // verified-only matching rule. Preserve the award, but detach the stale
            // canonical pointer before deleting the unverified duplicate.
            if ($medicine->profile_status !== Medicine::PROFILE_VERIFIED) {
                $medicine->drugBidAwards()->update(['medicine_id' => null]);
            }

            return (bool) $medicine->delete();
        });
    }

    public function importFromCsv(string $filePath): int
    {
        $report = $this->importExport->import($filePath, ['mode' => 'update_or_create']);

        return (int) ($report['success_rows'] ?? 0);
    }

    public function exportToCsv(
        ?string $search = null,
        ?string $circularGroup = null,
        ?string $specialControl = null
    ): string {
        $path = $this->importExport->export([
            'search' => $search,
            'circular_group' => $circularGroup,
            'is_special_control' => $specialControl === null ? null : $specialControl === 'yes',
        ]);

        return storage_path('app/public/'.$path);
    }

    private function normalizeQualityState(array $data, ?Medicine $existing = null): array
    {
        if (array_key_exists('registration_number', $data)) {
            $rawRegistration = is_string($data['registration_number'])
                ? trim($data['registration_number'])
                : null;
            $rawRegistration = $rawRegistration === '' ? null : $rawRegistration;
            $primaryRegistration = $this->normalizer->registrationPrimary($rawRegistration);
            $data['registration_number_raw'] = $rawRegistration;
            $data['registration_number_primary'] = $primaryRegistration;
            $data['registration_number'] = $primaryRegistration;
        }

        $identityKey = $this->identityResolver->canonicalMedicineIdentity($data + ($existing?->toArray() ?? []));
        $data['canonical_identity_key'] = $identityKey;

        if ($existing?->identity_status === Medicine::IDENTITY_VERIFIED_REGISTRATION) {
            $data['identity_status'] = $existing->identity_status;
        } elseif (! empty($data['registration_number'])) {
            $data['identity_status'] = Medicine::IDENTITY_UNVERIFIED;
        } elseif ($identityKey) {
            $data['identity_status'] = Medicine::IDENTITY_PROVISIONAL;
        } else {
            $data['identity_status'] = Medicine::IDENTITY_UNVERIFIED;
        }

        $merged = $data + ($existing?->toArray() ?? []);
        $requiredForComplete = [
            'active_ingredients',
            'concentration',
            'dosage_form',
            'route_of_administration',
            'unit',
            'packaging_specification',
            'registration_number',
            'shelf_life',
            'registered_company',
            'manufacturing_company',
            'manufacturing_country',
        ];
        $complete = collect($requiredForComplete)->every(
            fn ($field) => isset($merged[$field]) && trim((string) $merged[$field]) !== ''
        );

        if ($existing?->profile_status === Medicine::PROFILE_VERIFIED && $complete) {
            $data['profile_status'] = Medicine::PROFILE_VERIFIED;
        } else {
            $data['profile_status'] = $complete ? Medicine::PROFILE_COMPLETE : Medicine::PROFILE_INCOMPLETE;
        }

        return $data;
    }

    private function guardCanonicalIdentityCollision(Medicine $medicine, ?string $identityKey): void
    {
        if ($identityKey === null) {
            return;
        }

        $duplicate = Medicine::query()
            ->where('canonical_identity_key', $identityKey)
            ->whereKeyNot($medicine->getKey())
            ->first(['id', 'medicine_code', 'name', 'registration_number']);

        if ($duplicate) {
            $label = $duplicate->medicine_code ?: Medicine::codeForId((int) $duplicate->id);

            throw new LogicException(
                "Không thể cập nhật vì dữ liệu định danh trùng Medicine Master {$label} – {$duplicate->name}"
                .($duplicate->registration_number ? " (GPLH {$duplicate->registration_number})." : '.')
            );
        }
    }
}
