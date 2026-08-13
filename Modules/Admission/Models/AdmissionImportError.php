<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionImportError extends Model
{
    protected $table = 'admission_import_errors';

    protected $fillable = [
        'import_run_id',
        'row_number',
        'error_code',
        'field',
        'error_message',
        'ma_dinh_danh',
        'mhs',
        'student_name',
        'row_snapshot',
    ];

    protected $casts = [
        'row_snapshot' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AdmissionImportRun::class, 'import_run_id');
    }
}
