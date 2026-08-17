<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class SessionImportToken extends Model
{
    protected $table = 'muasamcong_session_import_tokens';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
