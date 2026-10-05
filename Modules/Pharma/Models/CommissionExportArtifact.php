<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommissionExportArtifact extends Model
{
    protected $table='pharma_commission_export_artifacts';
    protected $fillable=['created_by','disk','storage_path','download_name','filters','issue_ids','row_count','generated_at'];
    protected $casts=['filters'=>'array','issue_ids'=>'array','generated_at'=>'datetime'];
    public function creator(): BelongsTo { return $this->belongsTo(\App\Models\User::class,'created_by'); }
}
