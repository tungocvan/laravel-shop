<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\LookupWishlist;
use Modules\Pharma\Models\Medicine;

/**
 * Registration wishlist -> Medicine Master. The source is always re-verified;
 * the wishlist snapshot is never treated as authoritative for writes.
 */
final class RegistrationWishlistMedicineSync
{
    public function __construct(
        private readonly DavDrugRegistrationLookup $dav,
        private readonly TraditionalDrugRegistrationLookup $traditional,
        private readonly DavMedicineCandidateMatcher $matcher,
        private readonly MedicineService $medicines,
    ) {}

    public function candidate(LookupWishlist $favorite): array
    {
        if (! in_array($favorite->source_type, ['dav_registration', 'traditional_registration'], true)) {
            throw ValidationException::withMessages(['medicine' => 'Nguồn không hỗ trợ đồng bộ đăng ký thuốc.']);
        }
        $context = $favorite->search_context ?? [];
        $lookup = $favorite->source_type === 'dav_registration' ? $this->dav : $this->traditional;
        $field = (string) ($context['field'] ?? 'name');
        $q = (string) ($context['q'] ?? '');
        $page = (int) ($context['page'] ?? 1);
        if (! array_key_exists($field, DavDrugRegistrationLookup::FIELDS) || trim($q) === '' || $page < 1 || $page > 1000) {
            throw ValidationException::withMessages(['medicine' => 'Thiếu thông tin tra cứu gốc. Vui lòng lưu lại thuốc từ trang công bố.']);
        }
        $candidate = $lookup->findOnPage($field, $q, $page, $favorite->source_id);
        if ($candidate === null) {
            throw ValidationException::withMessages(['medicine' => 'Không tìm thấy bản ghi trên nguồn công bố.']);
        }
        $snapshot = $favorite->snapshot ?? [];
        foreach (['name', 'registration', 'source_id'] as $key) {
            if (trim((string) ($candidate[$key] ?? '')) === ''
                || trim((string) ($candidate[$key] ?? '')) !== trim((string) ($snapshot[$key] ?? ''))) {
                throw ValidationException::withMessages(['medicine' => 'Thông tin định danh nguồn đã thay đổi. Cần đối chiếu và lưu lại.']);
            }
        }
        if (($candidate['withdrawn'] ?? false) === true || ($candidate['active'] ?? false) !== true) {
            throw ValidationException::withMessages(['medicine' => 'Giấy đăng ký không còn trạng thái hoạt động; cần đối chiếu thủ công.']);
        }

        return $candidate;
    }

    public function inspect(array $candidate): array
    {
        return $this->matcher->inspect($candidate);
    }

    public function import(LookupWishlist $favorite, string $unit): Medicine
    {
        $unit = trim($unit);
        if ($unit === '' || mb_strlen($unit) > 255 ||
            ! Medicine::query()->where('unit', $unit)->exists()) {
            throw ValidationException::withMessages(['unit' => 'Chọn đơn vị tính đang sử dụng trong Danh mục thuốc.']);
        }
        $candidate = $this->candidate($favorite);
        $lock = 'pharma:registration-wishlist:'.hash('sha256', mb_strtolower($candidate['name']).'|'.$candidate['registration']);
        return Cache::lock($lock, 30)->block(5, function () use ($favorite, $unit): Medicine {
            $candidate = $this->candidate($favorite);
            $match = $this->inspect($candidate);
            if ($match['status'] !== 'new') {
                throw ValidationException::withMessages(['medicine' => 'Thuốc đã tồn tại hoặc cần đối chiếu thủ công. Không tạo thuốc trùng.']);
            }
            return $this->medicines->storeWithDeclaredPriceOnly([
                'name' => mb_substr($candidate['name'], 0, 255),
                'product_type' => $favorite->source_type === 'dav_registration'
                    ? Medicine::PRODUCT_TYPE_MODERN : Medicine::PRODUCT_TYPE_TRADITIONAL,
                'registration_number' => mb_substr($candidate['registration'], 0, 255),
                'active_ingredients' => mb_substr((string) ($candidate['ingredient'] ?? ''), 0, 255),
                'concentration' => mb_substr((string) ($candidate['strength'] ?? ''), 0, 255),
                'dosage_form' => mb_substr((string) ($candidate['dosage_form'] ?? ''), 0, 255),
                'unit' => $unit,
                'packaging_specification' => mb_substr((string) ($candidate['packaging'] ?? ''), 0, 255),
                'manufacturing_company' => mb_substr((string) ($candidate['manufacturer'] ?? ''), 0, 255),
                'manufacturing_country' => mb_substr((string) ($candidate['manufacturer_country'] ?? ''), 0, 255),
                'registered_company' => mb_substr((string) ($candidate['registrant'] ?? ''), 0, 255),
                // A registration record is not a price declaration.
                'declared_price' => null,
            ]);
        });
    }
}
