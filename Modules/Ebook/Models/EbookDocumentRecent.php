<?php

namespace Modules\Ebook\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EbookDocumentRecent extends Model
{
    protected $table = 'ebook_document_recents';

    protected $fillable = [
        'user_id',
        'ebook_document_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EbookDocument::class, 'ebook_document_id');
    }
}
