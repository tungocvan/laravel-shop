<?php
namespace Modules\Muasamcong\Models;
use Illuminate\Database\Eloquent\Model;
class PriceListProfile extends Model
{
    protected $table='muasamcong_price_list_profiles';
    protected $guarded=[];
    protected $casts=['columns'=>'array','is_active'=>'boolean','is_default'=>'boolean'];
    public static function availableColumns(): array
    {
        return ['ten_thuoc'=>'Tên thuốc','ten_hoat_chat'=>'Hoạt chất','nong_do'=>'Nồng độ / hàm lượng','dang_bao_che'=>'Dạng bào chế','duong_dung'=>'Đường dùng','don_vi_tinh'=>'Đơn vị tính','quy_cach_dong_goi'=>'Quy cách đóng gói','don_gia'=>'Giá trúng thầu','winning_name'=>'Đơn vị trúng thầu','ten_co_so_san_xuat'=>'Nhà sản xuất','nuoc_san_xuat'=>'Nước sản xuất','ma_tbmt'=>'Mã TBMT','so_quyet_dinh'=>'Số quyết định','ngay_ban_hanh_quyet_dinh'=>'Ngày quyết định'];
    }
}
