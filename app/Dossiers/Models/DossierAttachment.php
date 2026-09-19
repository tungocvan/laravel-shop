<?php

namespace App\Dossiers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierAttachment extends Model
{
    protected $fillable = ['dossier_id', 'item_id', 'kind', 'disk', 'path', 'original_name', 'mime_type', 'size', 'checksum', 'sync_status', 'remote_path', 'uploaded_by'];

    public function dossier(): BelongsTo { return $this->belongsTo(Dossier::class); }
    public function item(): BelongsTo { return $this->belongsTo(DossierItem::class, 'item_id'); }
}
