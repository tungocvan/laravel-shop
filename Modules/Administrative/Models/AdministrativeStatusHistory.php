<?php

namespace Modules\Administrative\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Account\Models\User;
use Modules\Administrative\Enums\HistoryActorType;
use Modules\Administrative\Enums\SubmissionAction;
use Modules\Administrative\Enums\SubmissionStatus;

class AdministrativeStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'administrative_status_histories';

    protected $fillable = [
        'submission_id',
        'from_status',
        'to_status',
        'action',
        'actor_type',
        'actor_id',
        'note',
        'reason_code',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => SubmissionStatus::class,
            'to_status' => SubmissionStatus::class,
            'action' => SubmissionAction::class,
            'actor_type' => HistoryActorType::class,
            'actor_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AdministrativeSubmission::class, 'submission_id');
    }

    public function actorAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
