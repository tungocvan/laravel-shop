<?php

namespace Modules\Ebook\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ebook_document_users', 'ebook_document_id', 'user_id')
            ->withTimestamps();
    }
}
