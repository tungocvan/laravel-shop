<?php

namespace Modules\Admission\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Models\SchoolSetting;

class AdmissionCodePrefixService
{
    public function replaceExistingPrefix(string $prefix): int
    {
        $prefix = strtoupper(trim($prefix));
        if (! preg_match('/^[A-Z]{3}$/', $prefix)) {
            throw ValidationException::withMessages(['application_code_prefix' => 'Tiền tố phải gồm đúng 3 chữ cái A-Z.']);
        }

        $updated = DB::transaction(function () use ($prefix): int {
            $applications = AdmissionApplication::query()->lockForUpdate()->get(['id', 'mhs']);
            $newCodes = [];
            foreach ($applications as $application) {
                $old = (string) $application->mhs;
                if (! preg_match('/^[A-Z]{3}[0-9]{8,}$/', $old)) {
                    throw ValidationException::withMessages(['application_code_prefix' => 'Có mã hồ sơ không đúng định dạng: '.$old.'. Chưa cập nhật dữ liệu.']);
                }
                $new = $prefix.substr($old, 3);
                if (isset($newCodes[$new])) {
                    throw ValidationException::withMessages(['application_code_prefix' => 'Phát hiện mã hồ sơ trùng sau khi đổi tiền tố: '.$new.'. Chưa cập nhật dữ liệu.']);
                }
                $newCodes[$new] = true;
            }

            $count = 0;
            foreach ($applications as $application) {
                $new = $prefix.substr((string) $application->mhs, 3);
                if ($new !== $application->mhs) {
                    AdmissionApplication::query()->whereKey($application->id)->update(['mhs' => $new]);
                    $count++;
                }
            }

            SchoolSetting::query()->updateOrCreate(
                ['key' => 'application_code_prefix'],
                ['value' => $prefix],
            );

            return $count;
        });

        Cache::forget('admission.school-settings');

        return $updated;
    }
}
