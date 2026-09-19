<?php

namespace App\Dossiers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DossierItem extends Model
{
    protected $fillable = ['dossier_id', 'template_item_id', 'code', 'title', 'sort_order', 'metadata'];

    protected $casts = ['sort_order' => 'integer', 'metadata' => 'array'];

    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }
    public function attachments(): HasMany { return $this->hasMany(DossierAttachment::class, 'item_id'); }
}
