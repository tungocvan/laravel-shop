<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineSource;

/**
 * Import public tender observations without modifying medicine master pricing.
 * Identity is name + verified registration + unit. Missing registration is review-only.
 */
final class PublicAwardMedicineSync
{
    public function __construct(private readonly MedicineCatalogNormalizer $normalizer) {}

    public function inspect(array $item): array
    {
        $name = $this->normalizer->text($this->string($item['tenThuoc'] ?? null));
        $registration = $this->registration($item);
        $unit = $this->normalizer->text($this->string($item['donViTinh'] ?? null));
        if ($name === null || $registration === null || $unit === null) {
            return ['status' => 'review', 'medicine_id' => null, 'reason' => 'Thiếu tên thuốc, số đăng ký hợp lệ hoặc ĐVT; cần xác minh thủ công.'];
        }

        // Exact normalization is performed in PHP; broad DB candidate selection is
        // intentional to avoid depending on database collation for identity.
        $rawName = $this->string($item['tenThuoc'] ?? null);
        $rawRegistration = $this->registrationRaw($item);
        $candidates = Medicine::query()
            ->where(function ($query) use ($rawName, $rawRegistration): void {
                $query->where('name', 'like', '%'.addcslashes($rawName, '%_\\').'%');
                if ($rawRegistration !== '') {
                    $pattern = '%'.addcslashes($rawRegistration, '%_\\').'%';
                    $query->orWhere('registration_number', 'like', $pattern)
                        ->orWhere('registration_number_primary', 'like', $pattern)
                        ->orWhere('registration_number_raw', 'like', $pattern);
                }
            })->get(['id', 'name', 'unit', 'registration_number', 'registration_number_primary', 'registration_number_raw']);

        $matches = $candidates->filter(fn (Medicine $medicine): bool =>
            $this->normalizer->text($medicine->name) === $name
            && $this->normalizer->text($medicine->unit) === $unit
            && collect([$medicine->registration_number, $medicine->registration_number_primary, $medicine->registration_number_raw])
                ->contains(fn ($value): bool => $this->normalizer->registration($value) === $registration));

        if ($matches->count() > 1) {
            return ['status' => 'review', 'medicine_id' => null, 'reason' => 'Nhiều thuốc trùng bộ ba định danh; cần đối chiếu thủ công.'];
        }
        if ($matches->count() === 1) {
            return ['status' => 'existing', 'medicine_id' => (int) $matches->first()->id, 'reason' => null];
        }

        // Partial matches require manual review to avoid accidental duplicates.
        if ($candidates->contains(fn (Medicine $medicine): bool =>
            $this->normalizer->text($medicine->name) === $name
            && $this->normalizer->text($medicine->unit) === $unit)) {
            return ['status' => 'review', 'medicine_id' => null, 'reason' => 'Tên thuốc và ĐVT đã tồn tại nhưng số đăng ký khác; cần đối chiếu.'];
        }

        return ['status' => 'new', 'medicine_id' => null, 'reason' => null];
    }

    public function sync(array $item, bool $allowCreate): array
    {
        $sourceId = $this->string($item['id'] ?? null);
        if ($sourceId === '' || mb_strlen($sourceId) > 160) {
            throw ValidationException::withMessages(['selected' => 'Bản ghi nguồn thiếu mã định danh.']);
        }

        $key = 'pharma:award-sync:'.hash('sha256', implode('|', [
            $this->normalizer->text($this->string($item['tenThuoc'] ?? null)),
            $this->registration($item),
            $this->normalizer->text($this->string($item['donViTinh'] ?? null)),
        ]));
        return Cache::lock($key, 30)->block(5, function () use ($item, $allowCreate, $sourceId): array {
            return DB::transaction(function () use ($item, $allowCreate, $sourceId): array {
                $existingSource = MedicineSource::query()
                    ->where('source_system', 'public_tender')
                    ->where('source_record_type', 'award_price')
                    ->where('source_record_key', $sourceId)
                    ->lockForUpdate()->first();

                $match = $this->inspect($item);
                if ($match['status'] === 'review') {
                    throw ValidationException::withMessages(['selected' => $match['reason']]);
                }

                if ($existingSource !== null) {
                    $medicine = $existingSource->medicine;
                    if (! $medicine || $match['status'] !== 'existing' || (int) $match['medicine_id'] !== (int) $medicine->id) {
                        throw ValidationException::withMessages(['selected' => 'Bản ghi nguồn đã liên kết với thuốc khác; cần đối chiếu thủ công.']);
                    }
                } elseif ($match['status'] === 'existing') {
                    $medicine = Medicine::query()->findOrFail($match['medicine_id']);
                } else {
                    if (! $allowCreate) {
                        throw ValidationException::withMessages(['selected' => 'Cần xác nhận tạo mới thuốc trước khi đồng bộ.']);
                    }
                    $medicine = Medicine::query()->create([
                        'name' => mb_substr($this->string($item['tenThuoc'] ?? null), 0, 255),
                        'registration_number' => $this->registrationRaw($item),
                        'unit' => mb_substr($this->string($item['donViTinh'] ?? null), 0, 255),
                        'product_type' => Medicine::PRODUCT_TYPE_MODERN,
                        'active_ingredients' => mb_substr($this->string($item['tenHoatChat'] ?? null), 0, 255),
                        'concentration' => mb_substr($this->string($item['nongDo'] ?? null), 0, 255),
                        'dosage_form' => mb_substr($this->string($item['dangBaoChe'] ?? null), 0, 255),
                        'packaging_specification' => mb_substr($this->string($item['quyCachDongGoi'] ?? null), 0, 255),
                        'manufacturing_company' => mb_substr($this->string($item['tenCoSoSanXuat'] ?? null), 0, 255),
                        'manufacturing_country' => mb_substr($this->string($item['nuocSanXuat'] ?? null), 0, 255),
                        'profile_status' => Medicine::PROFILE_NEEDS_REVIEW,
                    ]);
                }

                $payload = [
                    'medicine_id' => $medicine->id,
                    'source_reference' => mb_substr($this->string($item['maTbmt'] ?? null), 0, 255),
                    'payload_hash' => hash('sha256', json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
                    'observed_at' => now(),
                    'synced_at' => now(),
                    'is_active' => true,
                    'match_method' => 'name_registration_unit',
                    'match_confidence' => 100,
                    'metadata' => ['award' => $item],
                ];
                MedicineSource::query()->updateOrCreate([
                    'source_system' => 'public_tender',
                    'source_record_type' => 'award_price',
                    'source_record_key' => $sourceId,
                ], $payload);

                return ['medicine_id' => (int) $medicine->id, 'created' => $match['status'] === 'new'];
            });
        });
    }

    private function registration(array $item): ?string
    {
        $raw = $this->registrationRaw($item);
        if ($raw === '' || mb_strlen($raw) > 60 || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9.\/-]{3,59}$/D', $raw)
            || ! preg_match('/[A-Za-z]/', $raw) || ! preg_match('/[0-9]/', $raw)
            || preg_match('/GPNK|GIAYPHEP|^GP[0-9]/i', $raw)) {
            return null;
        }

        return $this->normalizer->registration($raw);
    }

    private function registrationRaw(array $item): string
    {
        return $this->string($item['gdklh_GPNK'] ?? null);
    }

    private function string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
