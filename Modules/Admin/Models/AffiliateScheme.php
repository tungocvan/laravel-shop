<?php
namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Website\Models\WpProduct;
use App\Models\User;

class AffiliateScheme extends Model {
    protected $table = 'wp_affiliate_schemes';
    
    protected $fillable = [
        'product_id', 'level_id', 'user_id', 
        'commission_type', 'percent_value', 'fixed_value', 'is_active'
    ];

    protected $casts = [
        'percent_value' => 'decimal:2',
        'fixed_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product() {
        return $this->belongsTo(WpProduct::class, 'product_id');
    }

    public function level() {
        return $this->belongsTo(AffiliateLevel::class, 'level_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}