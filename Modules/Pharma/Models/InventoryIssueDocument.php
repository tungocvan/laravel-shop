<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InventoryIssueDocument extends Model
{
    protected $table='pharma_inventory_issue_documents';
    protected $fillable=['issue_id','disk','storage_path','download_name','source_hash','generated_by','generated_at'];
    protected $casts=['generated_at'=>'datetime'];

    public function issue(): BelongsTo { return $this->belongsTo(InventoryIssue::class,'issue_id'); }
    public function shares(): HasMany { return $this->hasMany(InventoryIssueDocumentShare::class,'document_id'); }
}
