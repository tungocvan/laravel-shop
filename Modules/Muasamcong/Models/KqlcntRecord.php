<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class KqlcntRecord extends Model
{
    protected $table = 'muasamcong_kqlcnt_records';

    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'current_contractor_won' => 'boolean',
        'contract_period' => 'integer',
        'contracts' => 'array',
        'all_winners' => 'array',
        'verified_lots' => 'array',
        'tbmt_raw' => 'array',
        'contracts_raw' => 'array',
        'synced_at' => 'datetime',
        'hsmt_synced_at' => 'datetime',
        'imported_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (KqlcntRecord $record): void {
            $tbmt = is_array($record->tbmt_raw) ? $record->tbmt_raw : [];
            $contracts = is_array($record->contracts_raw) ? $record->contracts_raw : [];

            $period = $record->contract_period
                ?? data_get($tbmt, 'bidoNotifyContractorM.contractPeriod')
                ?? data_get($tbmt, 'bidNoContractorResponse.bidNotification.contractPeriod');
            $unit = trim((string) ($record->contract_period_unit
                ?? data_get($tbmt, 'bidoNotifyContractorM.contractPeriodUnit')
                ?? data_get($tbmt, 'bidNoContractorResponse.bidNotification.contractPeriodUnit')
                ?? ''));

            if ($period !== null && is_numeric($period)) {
                $record->contract_period = (int) $period;
                $record->contract_period_unit = $unit !== '' ? $unit : null;

                if (blank($record->contract_period_text)) {
                    $record->contract_period_text = self::formatContractPeriod((int) $period, $unit);
                }
            }

            if (blank($record->effect_frame_period)) {
                $effectFramePeriod = collect($contracts)
                    ->filter(fn (mixed $contract): bool => is_array($contract))
                    ->map(fn (array $contract): string => trim((string) ($contract['effectFramePeriod'] ?? '')))
                    ->first(fn (string $value): bool => $value !== '');

                if ($effectFramePeriod !== null) {
                    $record->effect_frame_period = $effectFramePeriod;
                }
            }
        });
    }

    private static function formatContractPeriod(int $period, string $unit): string
    {
        return match (mb_strtoupper(trim($unit))) {
            'D' => $period.' ngày',
            'M' => $period.' tháng',
            'Y' => $period.' năm',
            default => trim($period.' '.$unit),
        };
    }
}
