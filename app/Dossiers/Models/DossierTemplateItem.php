<?php

namespace App\Dossiers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierTemplateItem extends Model
{
    protected $fillable = ['template_id', 'parent_id', 'code', 'name', 'description', 'sort_order', 'is_required', 'allows_upload', 'allows_multiple_files', 'metadata_schema'];

    protected $casts = ['sort_order' => 'integer', 'is_required' => 'boolean', 'allows_upload' => 'boolean', 'allows_multiple_files' => 'boolean', 'metadata_schema' => 'array'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DossierTemplate::class, 'template_id');
    }
}
