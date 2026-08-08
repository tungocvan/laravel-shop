<?php

namespace Modules\Administrative\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Account\Models\User;

class AdministrativeProcedure extends Model
{
    use SoftDeletes;

    protected $table = 'administrative_procedures';

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'instructions',
        'required_documents',
        'template_disk',
        'template_path',
        'template_original_name',
        'allowed_extensions',
        'max_file_size_kb',
        'max_files',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'required_documents' => 'array',
            'allowed_extensions' => 'array',
            'max_file_size_kb' => 'integer',
            'max_files' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AdministrativeSubmission::class, 'procedure_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
