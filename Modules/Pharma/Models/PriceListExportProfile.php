<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PriceListExportProfile extends Model
{
    protected $table = 'pharma_price_list_export_profiles';

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'column_order' => 'array',
        'column_groups' => 'array',
        'selected_columns' => 'array',
        'headers' => 'array',
        'alignments' => 'array',
        'widths' => 'array',
        'data_types' => 'array',
        'decimals' => 'array',
        'header_footer' => 'array',
        'page_setup' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $profile): void {
            $updates = [];

            if (! self::mediaExists($profile->logo_path)) {
                $logoPath = self::query()
                    ->where('user_id', $profile->user_id)
                    ->whereKeyNot($profile->getKey())
                    ->whereNotNull('logo_path')
                    ->latest('id')
                    ->pluck('logo_path')
                    ->first(fn ($path) => self::mediaExists($path));

                if ($logoPath) {
                    $updates['logo_path'] = $logoPath;
                }
            }

            if (! self::mediaExists($profile->signature_path)) {
                $headerFooter = (array) $profile->header_footer;
                $title = self::normalizeSignatory($headerFooter['signatory_title'] ?? null);
                $name = self::normalizeSignatory($headerFooter['signatory_name'] ?? null);

                if ($title !== '' && $name !== '') {
                    $signaturePath = self::query()
                        ->where('user_id', $profile->user_id)
                        ->whereKeyNot($profile->getKey())
                        ->whereNotNull('signature_path')
                        ->get(['header_footer', 'signature_path'])
                        ->first(function (self $candidate) use ($title, $name): bool {
                            $candidateHeader = (array) $candidate->header_footer;

                            return self::normalizeSignatory($candidateHeader['signatory_title'] ?? null) === $title
                                && self::normalizeSignatory($candidateHeader['signatory_name'] ?? null) === $name
                                && self::mediaExists($candidate->signature_path);
                        })?->signature_path;

                    if ($signaturePath) {
                        $updates['signature_path'] = $signaturePath;
                    }
                }
            }

            if ($updates !== []) {
                $profile->forceFill($updates)->saveQuietly();
            }
        });
    }

    private static function mediaExists(?string $path): bool
    {
        return is_string($path) && $path !== '' && Storage::disk('public')->exists($path);
    }

    private static function normalizeSignatory(mixed $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '');
    }
}
