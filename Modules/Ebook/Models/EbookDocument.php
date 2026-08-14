<?php

namespace Modules\Ebook\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EbookDocument extends Model
{
    protected $table = 'ebook_documents';

    protected $fillable = [
        'folder_id',
        'title',
        'slug',
        'file_name',
        'file_path',
        'source_type',
        'description',
        'sort_order',
        'is_active',
        'is_favorite',
        'content_hash',
        'file_mtime',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_favorite' => 'boolean',
        'file_mtime' => 'integer',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(EbookFolder::class, 'folder_id');
    }
}
