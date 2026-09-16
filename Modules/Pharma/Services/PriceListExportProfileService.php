<?php

namespace Modules\Pharma\Services;

use InvalidArgumentException;
use Modules\Pharma\Models\PriceListExportProfile;

class PriceListExportProfileService
{
    public const EXPORT_SCHEMA = 'pharma.price-list-export-profile.v1';

    public const COLUMNS = [
        'stt' => ['label' => 'STT', 'group' => 'general', 'align' => 'center', 'width' => 60, 'type' => 'number'],
        'medicine_code' => ['label' => 'Mã thuốc', 'group' => 'identity', 'align' => 'left', 'width' => 105, 'type' => 'string'],
        'medicine_name' => ['label' => 'Tên thuốc', 'group' => 'identity', 'align' => 'left', 'width' => 190, 'type' => 'auto'],
        'registration_number' => ['label' => 'SĐK / GPLH', 'group' => 'identity', 'align' => 'left', 'width' => 135, 'type' => 'string'],
        'registration_number_raw' => ['label' => 'SĐK nguyên bản', 'group' => 'identity', 'align' => 'left', 'width' => 145, 'type' => 'string'],
        'registration_number_primary' => ['label' => 'SĐK chính', 'group' => 'identity', 'align' => 'left', 'width' => 135, 'type' => 'string'],
        'sku' => ['label' => 'SKU', 'group' => 'identity', 'align' => 'left', 'width' => 130, 'type' => 'string'],
        'package_code' => ['label' => 'Mã quy cách', 'group' => 'identity', 'align' => 'left', 'width' => 125, 'type' => 'string'],
        'gtin' => ['label' => 'GTIN', 'group' => 'identity', 'align' => 'left', 'width' => 130, 'type' => 'string'],
        'barcode' => ['label' => 'Barcode', 'group' => 'identity', 'align' => 'left', 'width' => 130, 'type' => 'string'],
        'active_ingredients' => ['label' => 'Hoạt chất', 'group' => 'clinical', 'align' => 'left', 'width' => 210, 'type' => 'auto'],
        'strength' => ['label' => 'Nồng độ / Hàm lượng', 'group' => 'clinical', 'align' => 'left', 'width' => 145, 'type' => 'auto'],
        'dosage_form' => ['label' => 'Dạng bào chế', 'group' => 'clinical', 'align' => 'left', 'width' => 145, 'type' => 'auto'],
        'route_of_administration' => ['label' => 'Đường dùng', 'group' => 'clinical', 'align' => 'left', 'width' => 135, 'type' => 'auto'],
        'unit' => ['label' => 'Đơn vị tính', 'group' => 'clinical', 'align' => 'center', 'width' => 95, 'type' => 'auto'],
        'therapeutic_group' => ['label' => 'Nhóm thuốc / điều trị', 'group' => 'clinical', 'align' => 'left', 'width' => 180, 'type' => 'auto'],
        'circular_group' => ['label' => 'Nhóm thông tư', 'group' => 'clinical', 'align' => 'left', 'width' => 135, 'type' => 'auto'],
        'circular_order_number' => ['label' => 'STT thông tư', 'group' => 'clinical', 'align' => 'center', 'width' => 100, 'type' => 'string'],
        'shelf_life' => ['label' => 'Tuổi thọ / HSD', 'group' => 'clinical', 'align' => 'left', 'width' => 120, 'type' => 'auto'],
        'shelf_life_months' => ['label' => 'HSD (tháng)', 'group' => 'clinical', 'align' => 'right', 'width' => 95, 'type' => 'number'],
        'is_special_control' => ['label' => 'Kiểm soát đặc biệt', 'group' => 'clinical', 'align' => 'center', 'width' => 125, 'type' => 'auto'],
        'registered_company' => ['label' => 'Công ty đăng ký', 'group' => 'legal', 'align' => 'left', 'width' => 210, 'type' => 'auto'],
        'manufacturing_company' => ['label' => 'Nhà sản xuất', 'group' => 'legal', 'align' => 'left', 'width' => 210, 'type' => 'auto'],
        'manufacturing_country' => ['label' => 'Nước sản xuất', 'group' => 'legal', 'align' => 'left', 'width' => 125, 'type' => 'auto'],
        'visa_validity_date' => ['label' => 'Hạn hiệu lực Visa', 'group' => 'legal', 'align' => 'center', 'width' => 115, 'type' => 'date'],
        'gmp_certification_date' => ['label' => 'Ngày chứng nhận GMP', 'group' => 'legal', 'align' => 'center', 'width' => 125, 'type' => 'date'],
        'presentation_text' => ['label' => 'Dạng trình bày', 'group' => 'package', 'align' => 'left', 'width' => 155, 'type' => 'auto'],
        'base_unit' => ['label' => 'Đơn vị cơ sở', 'group' => 'package', 'align' => 'center', 'width' => 105, 'type' => 'auto'],
        'content_value' => ['label' => 'Hàm lượng quy đổi', 'group' => 'package', 'align' => 'right', 'width' => 115, 'type' => 'number'],
        'content_uom' => ['label' => 'ĐVT hàm lượng', 'group' => 'package', 'align' => 'center', 'width' => 105, 'type' => 'auto'],
        'package' => ['label' => 'Quy cách đóng gói', 'group' => 'package', 'align' => 'left', 'width' => 190, 'type' => 'auto'],
        'outer_package_type' => ['label' => 'Bao bì ngoài', 'group' => 'package', 'align' => 'left', 'width' => 120, 'type' => 'auto'],
        'inner_package_type' => ['label' => 'Bao bì trong', 'group' => 'package', 'align' => 'left', 'width' => 120, 'type' => 'auto'],
        'outer_quantity' => ['label' => 'SL bao bì ngoài', 'group' => 'package', 'align' => 'right', 'width' => 105, 'type' => 'number'],
        'inner_quantity' => ['label' => 'SL bao bì trong', 'group' => 'package', 'align' => 'right', 'width' => 105, 'type' => 'number'],
        'base_quantity' => ['label' => 'SL đơn vị cơ sở', 'group' => 'package', 'align' => 'right', 'width' => 110, 'type' => 'number'],
        'container_volume' => ['label' => 'Thể tích bao bì', 'group' => 'package', 'align' => 'right', 'width' => 110, 'type' => 'number'],
        'container_volume_uom' => ['label' => 'ĐVT thể tích', 'group' => 'package', 'align' => 'center', 'width' => 95, 'type' => 'auto'],
        'is_orderable' => ['label' => 'Được đặt hàng', 'group' => 'package', 'align' => 'center', 'width' => 105, 'type' => 'auto'],
        'is_inventory_unit' => ['label' => 'Đơn vị tồn kho', 'group' => 'package', 'align' => 'center', 'width' => 105, 'type' => 'auto'],
        'declared_price' => ['label' => 'Giá kê khai', 'group' => 'pricing', 'align' => 'right', 'width' => 110, 'type' => 'number'],
        'bid_price' => ['label' => 'Đơn giá trúng thầu', 'group' => 'bid', 'align' => 'right', 'width' => 125, 'type' => 'number'],
        'bid_quantity' => ['label' => 'Số lượng trúng thầu', 'group' => 'bid', 'align' => 'right', 'width' => 120, 'type' => 'number'],
        'bid_decision' => ['label' => 'Số quyết định', 'group' => 'bid', 'align' => 'left', 'width' => 140, 'type' => 'string'],
        'bid_date' => ['label' => 'Ngày trúng thầu', 'group' => 'bid', 'align' => 'center', 'width' => 115, 'type' => 'date'],
        'bid_contractor' => ['label' => 'Nhà thầu trúng thầu', 'group' => 'bid', 'align' => 'left', 'width' => 220, 'type' => 'auto'],
        'bid_investor' => ['label' => 'Chủ đầu tư / Bên mời thầu', 'group' => 'bid', 'align' => 'left', 'width' => 220, 'type' => 'auto'],
        'bid_unit' => ['label' => 'ĐVT trúng thầu', 'group' => 'bid', 'align' => 'center', 'width' => 105, 'type' => 'auto'],
        'bid_source' => ['label' => 'Nguồn KQ trúng thầu', 'group' => 'bid', 'align' => 'left', 'width' => 130, 'type' => 'auto'],
        'company_sale_price' => ['label' => 'Giá bán công ty', 'group' => 'pricing', 'align' => 'right', 'width' => 120, 'type' => 'number'],
        'discount_percent' => ['label' => '% CK thu', 'group' => 'pricing', 'align' => 'right', 'width' => 85, 'type' => 'number'],
        'actual_receivable_price' => ['label' => 'Giá thu thực tế', 'group' => 'pricing', 'align' => 'right', 'width' => 120, 'type' => 'number'],
        'invoice_price' => ['label' => 'Giá xuất hóa đơn', 'group' => 'pricing', 'align' => 'right', 'width' => 120, 'type' => 'number'],
        'partner' => ['label' => 'Khách hàng / loại', 'group' => 'commercial', 'align' => 'left', 'width' => 200, 'type' => 'auto'],
        'effective' => ['label' => 'Hiệu lực', 'group' => 'commercial', 'align' => 'center', 'width' => 170, 'type' => 'auto'],
        'status' => ['label' => 'Trạng thái', 'group' => 'commercial', 'align' => 'center', 'width' => 100, 'type' => 'auto'],
        'note' => ['label' => 'Ghi chú', 'group' => 'commercial', 'align' => 'left', 'width' => 200, 'type' => 'auto'],
    ];

    public const GROUPS = [
        'general' => 'Chung', 'identity' => 'Định danh thuốc', 'clinical' => 'Thông tin chuyên môn',
        'legal' => 'Nhà sản xuất & pháp lý', 'package' => 'Variant & quy cách', 'bid' => 'Kết quả trúng thầu',
        'pricing' => 'Giá thương mại', 'commercial' => 'Bảng giá & khách hàng',
    ];

    public const DEFAULT_SELECTED = [
        'stt', 'medicine_code', 'medicine_name', 'active_ingredients', 'strength', 'registration_number', 'sku', 'package',
        'therapeutic_group', 'manufacturing_company', 'manufacturing_country', 'declared_price', 'bid_price', 'bid_date',
        'company_sale_price', 'partner', 'effective', 'note',
    ];

    public const DEFAULT_HEADER_FOOTER = ['enabled'=>true,'company_name'=>'CÔNG TY TNHH INAFO VIỆT NAM','address'=>'','tax_code'=>'','phone'=>'','email'=>'','title'=>'BẢNG BÁO GIÁ','recipient'=>'QUÝ KHÁCH HÀNG','intro'=>'Công ty INAFO Việt Nam xin trân trọng gửi đến Quý Khách hàng báo giá một số sản phẩm chúng tôi đang phân phối trên thị trường hiện nay như sau:','logo_width_cm'=>2.48,'logo_height_cm'=>3.83,'signature_width_cm'=>4.00,'signature_height_cm'=>2.00,'footer_location'=>'Tp.HCM','signatory_title'=>'GIÁM ĐỐC CÔNG TY','signatory_name'=>'','footer_year'=>''];
    public const DEFAULT_PAGE_SETUP = ['paper_size'=>'A4','orientation'=>'landscape','margin_left_cm'=>0.3,'margin_right_cm'=>0.3,'margin_top_cm'=>0.8,'margin_bottom_cm'=>0.8,'center_horizontal'=>true,'center_vertical'=>false,'scaling'=>'fit_width','fit_width'=>1,'fit_height'=>0];

    public function profilesForUser(int $userId): array { return PriceListExportProfile::query()->where('user_id',$userId)->orderByDesc('is_default')->orderBy('name')->get(['id','name','is_default'])->toArray(); }
    public function forUser(int $userId, ?int $profileId = null): array { $q=PriceListExportProfile::query()->where('user_id',$userId); $p=$profileId?$q->whereKey($profileId)->first():$q->orderByDesc('is_default')->orderBy('id')->first(); return $p?$this->payload($p):$this->defaults(); }

    public function save(int $userId, array $data, ?int $profileId = null): array
    {
        $profile=$profileId?PriceListExportProfile::query()->where('user_id',$userId)->findOrFail($profileId):new PriceListExportProfile(['user_id'=>$userId]);
        $order=$this->normalizeOrder((array)($data['column_order']??[])); $selected=array_values(array_intersect($order,(array)($data['selected_columns']??[]))); if($selected===[])$selected=$order;
        $makeDefault=(bool)($data['is_default']??false); if(!$profile->exists&&!PriceListExportProfile::query()->where('user_id',$userId)->exists())$makeDefault=true; if($makeDefault)PriceListExportProfile::query()->where('user_id',$userId)->update(['is_default'=>false]);
        $profile->forceFill(['user_id'=>$userId,'name'=>mb_substr(trim((string)($data['name']??'Cấu hình Bảng Giá'))?:'Cấu hình Bảng Giá',0,120),'is_default'=>$makeDefault||(bool)$profile->is_default,'column_order'=>$order,'column_groups'=>$this->normalizeGroups((array)($data['column_groups']??[])),'selected_columns'=>$selected,'headers'=>$this->normalizeMap((array)($data['headers']??[]),'label'),'alignments'=>$this->normalizeMap((array)($data['alignments']??[]),'align'),'widths'=>$this->normalizeWidths((array)($data['widths']??[])),'data_types'=>$this->normalizeMap((array)($data['data_types']??[]),'type'),'decimals'=>$this->normalizeDecimals((array)($data['decimals']??[])),'header_footer'=>array_replace(self::DEFAULT_HEADER_FOOTER,(array)($data['header_footer']??[])),'page_setup'=>array_replace(self::DEFAULT_PAGE_SETUP,(array)($data['page_setup']??[])),'logo_path'=>$data['logo_path']??$profile->logo_path,'signature_path'=>$data['signature_path']??$profile->signature_path])->save(); return $this->payload($profile->fresh());
    }
    public function duplicate(int $userId,int $profileId):array{$s=PriceListExportProfile::query()->where('user_id',$userId)->findOrFail($profileId);$c=$s->replicate();$c->name=$this->uniqueName($userId,mb_substr($s->name.' - Bản sao',0,120));$c->is_default=false;$c->save();return $this->payload($c);}
    public function delete(int $userId,int $profileId):void{$p=PriceListExportProfile::query()->where('user_id',$userId)->findOrFail($profileId);$d=$p->is_default;$p->delete();if($d)PriceListExportProfile::query()->where('user_id',$userId)->orderBy('id')->first()?->update(['is_default'=>true]);}

    public function exportPayload(int $userId, ?int $profileId): array
    {
        $p=$this->forUser($userId,$profileId); unset($p['profile_id'],$p['is_default'],$p['logo_path'],$p['signature_path']);
        return ['schema'=>self::EXPORT_SCHEMA,'exported_at'=>now()->toIso8601String(),'profile'=>$p];
    }
    public function importPayload(int $userId,array $payload):array
    {
        if(($payload['schema']??null)!==self::EXPORT_SCHEMA||!is_array($payload['profile']??null))throw new InvalidArgumentException('File không đúng định dạng cấu hình Pharma Price List v1.');
        $p=$payload['profile'];
        $baseName=mb_substr(trim((string)($p['profile_name']??'Cấu hình import')).' - Import',0,120);
        return $this->save($userId,['name'=>$this->uniqueName($userId,$baseName),'is_default'=>false,'column_order'=>(array)($p['column_order']??[]),'column_groups'=>(array)($p['column_groups']??[]),'selected_columns'=>(array)($p['selected_columns']??[]),'headers'=>(array)($p['headers']??[]),'alignments'=>(array)($p['alignments']??[]),'widths'=>(array)($p['widths']??[]),'data_types'=>(array)($p['data_types']??[]),'decimals'=>(array)($p['decimals']??[]),'header_footer'=>(array)($p['header_footer']??[]),'page_setup'=>(array)($p['page_setup']??[])],null);
    }
    public function defaults():array{$k=array_keys(self::COLUMNS);return['profile_id'=>null,'profile_name'=>'Mặc định','is_default'=>false,'column_order'=>$k,'column_groups'=>$this->normalizeGroups([]),'selected_columns'=>self::DEFAULT_SELECTED,'headers'=>$this->normalizeMap([],'label'),'alignments'=>$this->normalizeMap([],'align'),'widths'=>$this->normalizeWidths([]),'data_types'=>$this->normalizeMap([],'type'),'decimals'=>$this->normalizeDecimals([]),'header_footer'=>self::DEFAULT_HEADER_FOOTER,'page_setup'=>self::DEFAULT_PAGE_SETUP,'logo_path'=>null,'signature_path'=>null];}
    private function payload(PriceListExportProfile $p):array{return['profile_id'=>(int)$p->id,'profile_name'=>$p->name,'is_default'=>(bool)$p->is_default,'column_order'=>$this->normalizeOrder((array)$p->column_order),'column_groups'=>$this->normalizeGroups((array)$p->column_groups),'selected_columns'=>array_values(array_intersect(array_keys(self::COLUMNS),(array)$p->selected_columns)),'headers'=>$this->normalizeMap((array)$p->headers,'label'),'alignments'=>$this->normalizeMap((array)$p->alignments,'align'),'widths'=>$this->normalizeWidths((array)$p->widths),'data_types'=>$this->normalizeMap((array)$p->data_types,'type'),'decimals'=>$this->normalizeDecimals((array)$p->decimals),'header_footer'=>array_replace(self::DEFAULT_HEADER_FOOTER,(array)$p->header_footer),'page_setup'=>array_replace(self::DEFAULT_PAGE_SETUP,(array)$p->page_setup),'logo_path'=>$p->logo_path,'signature_path'=>$p->signature_path];}
    private function uniqueName(int $userId,string $base):string{$base=mb_substr(trim($base)?:'Cấu hình',0,120);$name=$base;$i=2;while(PriceListExportProfile::query()->where('user_id',$userId)->where('name',$name)->exists()){$suffix=' ('.$i++.')';$name=mb_substr($base,0,120-mb_strlen($suffix)).$suffix;}return$name;}
    private function normalizeOrder(array $o):array{$v=array_values(array_unique(array_filter($o,fn($k)=>is_string($k)&&isset(self::COLUMNS[$k]))));foreach(array_keys(self::COLUMNS)as$k)if(!in_array($k,$v,true))$v[]=$k;return$v;}
    private function normalizeGroups(array $v):array{$o=[];foreach(self::COLUMNS as$k=>$c){$group=(string)($v[$k]??$c['group']);$o[$k]=isset(self::GROUPS[$group])?$group:$c['group'];}return$o;}
    private function normalizeMap(array $v,string $d):array{$o=[];foreach(self::COLUMNS as$k=>$c)$o[$k]=trim((string)($v[$k]??$c[$d]))?:$c[$d];return$o;}
    private function normalizeWidths(array $v):array{$o=[];foreach(self::COLUMNS as$k=>$c)$o[$k]=max(40,min(600,(int)($v[$k]??$c['width'])));return$o;}
    private function normalizeDecimals(array $v):array{$o=[];foreach(self::COLUMNS as$k=>$c)$o[$k]=max(0,min(6,(int)($v[$k]??0)));return$o;}
}
