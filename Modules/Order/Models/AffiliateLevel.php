<?php
namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AffiliateLevel extends Model {
    protected $fillable = ['name', 'slug', 'min_revenue_required', 'is_default'];

    public function users() {
        return $this->hasMany(User::class, 'affiliate_level_id');
    }

    public function schemes() {
        return $this->hasMany(AffiliateScheme::class, 'level_id');
    }
}
