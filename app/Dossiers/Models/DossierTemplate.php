<?php

namespace App\Dossiers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DossierTemplate extends Model
{
    protected $fillable = ['code', 'name', 'version', 'is_active'];

    protected $casts = ['version' => 'integer', 'is_active' => 'boolean'];

    public function items(): HasMany
    {
        return $this->hasMany(DossierTemplateItem::class, 'template_id')->orderBy('sort_order');
    }
}
