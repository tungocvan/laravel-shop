<?php

namespace App\Models; // Hoặc namespace user hiện tại của bạn

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Modules\Website\Models\Order; // Giả định namespace Order của bạn
use Modules\Website\Models\UserAddress;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes,HasRoles;
   // protected $guard_name = 'auth:admin';
    protected $fillable = [
        'name', 'email', 'password',
        'google_id', 'google_token', 'google_refresh_token',
        'phone', 'avatar', 'is_active', 'last_login_at'
    ];

    protected $hidden = [
        'password', 'remember_token', 'google_token', 'google_refresh_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // Quan hệ: Sổ địa chỉ
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    // Quan hệ: Đơn hàng
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Helper: Lấy Avatar (nếu null thì lấy ảnh Placehold chữ cái đầu)
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return str_starts_with($this->avatar, 'http') ? $this->avatar : asset('storage/' . $this->avatar);
        }
        // Tạo avatar chữ cái đầu (VD: Nguyen Van A -> N)
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&color=7F9CF5&background=EBF4FF";
    }
}
