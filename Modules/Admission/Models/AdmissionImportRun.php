<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionImportRun extends Model
{
    protected $table = 'admission_import_runs';

    protected $fillable = [
        'original_filename',
        'status',
        'total_rows',
        'success_rows',
        'failed_rows',
        'created_rows',
        'updated_rows',
        'imported_by',
        'started_at',
        'completed_at',
        'fatal_error',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function errors(): HasMany
    {
        return $this->hasMany(AdmissionImportError::class, 'import_run_id');
    }
}
