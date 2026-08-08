<?php

namespace Modules\Administrative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Account\Models\User;
use Modules\Administrative\Enums\AdministrativeFileType;

class AdministrativeFile extends Model
{
    protected $table = 'administrative_files';

    protected $fillable = [
        'submission_id',
        'file_type',
        'document_type',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'size',
        'checksum',
        'uploaded_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'file_type' => AdministrativeFileType::class,
            'size' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AdministrativeSubmission::class, 'submission_id');
    }

    public function uploadedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_admin_id');
    }
}
