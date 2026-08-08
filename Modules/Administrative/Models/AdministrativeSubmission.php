<?php

namespace Modules\Administrative\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Account\Models\User;
use Modules\Administrative\Enums\SubmissionStatus;

class AdministrativeSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'administrative_submissions';

    protected $fillable = [
        'procedure_id',
        'submission_code',
        'lookup_token_hash',
        'applicant_name',
        'phone',
        'email',
        'wants_email_receipt',
        'student_name',
        'student_code',
        'date_of_birth',
        'current_class',
        'academic_year',
        'relationship',
        'relationship_other',
        'status',
        'response',
        'rejection_reason_code',
        'rejection_reason',
        'supplement_reason',
        'supplement_requested_at',
        'resubmitted_at',
        'submitted_at',
        'processed_by',
        'processed_at',
        'version',
        'revision_count',
    ];

    protected $hidden = [
        'lookup_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'wants_email_receipt' => 'boolean',
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'processed_at' => 'datetime',
            'supplement_requested_at' => 'datetime',
            'resubmitted_at' => 'datetime',
            'version' => 'integer',
            'revision_count' => 'integer',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(AdministrativeProcedure::class, 'procedure_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AdministrativeFile::class, 'submission_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(AdministrativeStatusHistory::class, 'submission_id')
            ->orderBy('created_at');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeOfStatus(Builder $query, SubmissionStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof SubmissionStatus ? $status->value : $status);
    }
}
