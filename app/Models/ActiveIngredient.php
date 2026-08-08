<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ActiveIngredient extends Model
{
    use SoftDeletes;

    public const HOSPITAL_LEVEL_LABELS = [
        1 => 'Tuyen dac biet',
        2 => 'Tuyen I',
        3 => 'Tuyen II',
        4 => 'Tuyen III',
    ];

    protected $fillable = [
        'stt',
        'name',
        'dosage_form',
        'hospital_level',
        'note',
        'drug_group',
        'row_hash',
        'source_file',
    ];

    protected $casts = [
        'stt' => 'integer',
        'hospital_level' => 'integer',
        'deleted_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $ingredient): void {
            $ingredient->fill(
                static::sanitizePayload($ingredient->attributesToArray())
            );
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->where('name', 'like', "%{$term}%")
                ->orWhere('dosage_form', 'like', "%{$term}%")
                ->orWhere('drug_group', 'like', "%{$term}%")
                ->orWhere('note', 'like', "%{$term}%");

            if (is_numeric($term)) {
                $builder
                    ->orWhere('stt', (int) $term)
                    ->orWhere('hospital_level', (int) $term);
            }
        });
    }

    public static function sanitizePayload(array $payload): array
    {
        $name = static::nullableTrim($payload['name'] ?? null);
        $dosageForm = static::nullableTrim($payload['dosage_form'] ?? null);
        $note = static::nullableTrim($payload['note'] ?? null);
        $drugGroup = static::nullableTrim($payload['drug_group'] ?? null);
        $sourceFile = static::nullableTrim($payload['source_file'] ?? null);
        $stt = static::nullableInteger($payload['stt'] ?? null);
        $hospitalLevel = static::nullableInteger($payload['hospital_level'] ?? null);

        return [
            'stt' => $stt,
            'name' => $name,
            'dosage_form' => $dosageForm,
            'hospital_level' => $hospitalLevel,
            'note' => $note,
            'drug_group' => $drugGroup,
            'row_hash' => static::makeRowHash([
                'stt' => $stt,
                'name' => $name,
                'dosage_form' => $dosageForm,
                'hospital_level' => $hospitalLevel,
                'note' => $note,
                'drug_group' => $drugGroup,
            ]),
            'source_file' => $sourceFile,
        ];
    }

    public static function makeRowHash(array $payload): string
    {
        $parts = [
            $payload['stt'] ?? null,
            Str::lower(trim((string) ($payload['name'] ?? ''))),
            Str::lower(trim((string) ($payload['dosage_form'] ?? ''))),
            $payload['hospital_level'] ?? null,
            Str::lower(trim((string) ($payload['note'] ?? ''))),
            Str::lower(trim((string) ($payload['drug_group'] ?? ''))),
        ];

        return hash('sha256', implode('|', array_map(
            static fn ($value) => (string) ($value ?? ''),
            $parts,
        )));
    }

    protected static function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected static function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
