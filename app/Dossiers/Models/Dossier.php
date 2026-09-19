<?php

namespace App\Dossiers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Dossier extends Model
{
    protected $fillable = ['template_id', 'owner_type', 'owner_id', 'version', 'status', 'is_current', 'metadata', 'created_by', 'updated_by'];

    protected $casts = ['is_current' => 'boolean', 'metadata' => 'array'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DossierTemplate::class, 'template_id');
    }
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
    public function items(): HasMany
    {
        return $this->hasMany(DossierItem::class)->orderBy('sort_order');
    }
    public function attachments(): HasMany
    {
        return $this->hasMany(DossierAttachment::class);
    }
}
