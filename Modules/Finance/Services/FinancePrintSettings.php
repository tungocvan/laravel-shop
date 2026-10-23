<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class FinancePrintSettings
{
    public function get(): array
    {
        $defaults = require dirname(__DIR__).'/config/print.php';
        $settings = $defaults['defaults'];
        if (! Schema::hasTable('finance_print_settings')) {
            return $settings;
        }
        $record = DB::table('finance_print_settings')->where('key', 'voucher')->value('settings');
        $stored = $record ? json_decode((string) $record, true) : [];
        return array_replace($settings, is_array($stored) ? array_intersect_key($stored, $settings) : []);
    }

    public function save(array $input): void
    {
        $input = array_replace((require dirname(__DIR__).'/config/print.php')['defaults'], $input);
        $data = validator($input, [
            'organization' => 'required|string|max:150',
            'subtitle' => 'required|string|max:180',
            'paper' => 'required|in:A5,A4',
            'orientation' => 'required|in:portrait,landscape',
            'margin_mm' => 'required|integer|between:5,20',
            'font_size' => 'required|integer|between:9,14',
            'show_reference' => 'required|boolean',
            'show_bank' => 'required|boolean',
            'show_signatures' => 'required|boolean',
            'signature_creator' => 'required|string|max:80',
            'signature_approver' => 'required|string|max:80',
            'creator_full_name' => 'nullable|string|max:120',
            'approver_full_name' => 'nullable|string|max:120',
            'show_creator_name' => 'required|boolean',
            'show_approver_name' => 'required|boolean',
            'show_status' => 'required|boolean',
            'show_voucher_number' => 'required|boolean',
            'show_category' => 'required|boolean',
            'show_amount_words' => 'required|boolean',
            'title_font_size' => 'required|integer|between:12,18',
            'line_height' => 'required|numeric|between:1.1,1.6',
        ])->validate();
        if (! Schema::hasTable('finance_print_settings')) {
            throw ValidationException::withMessages(['settings' => 'Cần chạy migration Finance trước khi lưu cấu hình mẫu in.']);
        }
        DB::table('finance_print_settings')->updateOrInsert(
            ['key' => 'voucher'],
            ['settings' => json_encode($data, JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
