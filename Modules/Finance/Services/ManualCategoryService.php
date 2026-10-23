<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ManualCategoryService
{
    public function active(string $direction)
    {
        return DB::table('finance_manual_categories')
            ->where('direction', $direction)->where('is_active', true)
            ->orderBy('name')->get(['id', 'code', 'name', 'direction']);
    }

    public function assertActive(string $direction, string $code): void
    {
        if (! DB::table('finance_manual_categories')
            ->where('direction', $direction)->where('code', $code)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['manualCategory' => 'Nghiệp vụ không tồn tại hoặc đã ngừng sử dụng.']);
        }
    }

    public function create(string $direction, string $name): string
    {
        $name = trim($name);
        if (! in_array($direction, ['receipt', 'payment'], true) || $name === '' || mb_strlen($name) > 120) {
            throw ValidationException::withMessages(['categoryName' => 'Tên nghiệp vụ không hợp lệ (tối đa 120 ký tự).']);
        }
        return DB::transaction(function () use ($direction, $name): string {
            if (DB::table('finance_manual_categories')->where('direction', $direction)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                throw ValidationException::withMessages(['categoryName' => 'Nghiệp vụ này đã tồn tại.']);
            }
            $code = 'custom_'.bin2hex(random_bytes(12));
            DB::table('finance_manual_categories')->insert([
                'direction' => $direction, 'code' => $code, 'name' => $name,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            return $code;
        }, 3);
    }
}
