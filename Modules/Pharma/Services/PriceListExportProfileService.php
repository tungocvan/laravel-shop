<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\PriceListExportProfile;

class PriceListExportProfileService
{
    public const COLUMNS = [
        'stt'=>['label'=>'STT','align'=>'center','width'=>60,'type'=>'number'], 'medicine_code'=>['label'=>'Mã thuốc','align'=>'left','width'=>105,'type'=>'string'],
        'medicine_name'=>['label'=>'Tên thuốc','align'=>'left','width'=>190,'type'=>'auto'], 'active_ingredients'=>['label'=>'Hoạt chất','align'=>'left','width'=>210,'type'=>'auto'],
        'strength'=>['label'=>'Nồng độ / Hàm lượng','align'=>'left','width'=>145,'type'=>'auto'], 'registration_number'=>['label'=>'SĐK / GPLH','align'=>'left','width'=>135,'type'=>'string'],
        'sku'=>['label'=>'SKU','align'=>'left','width'=>130,'type'=>'string'], 'package'=>['label'=>'Quy cách đóng gói','align'=>'left','width'=>190,'type'=>'auto'],
        'declared_price'=>['label'=>'Giá kê khai','align'=>'right','width'=>110,'type'=>'number'], 'bid_price'=>['label'=>'Đơn giá trúng thầu','align'=>'right','width'=>125,'type'=>'number'],
        'bid_quantity'=>['label'=>'Số lượng trúng thầu','align'=>'right','width'=>120,'type'=>'number'], 'bid_decision'=>['label'=>'Số quyết định','align'=>'left','width'=>140,'type'=>'string'],
        'bid_date'=>['label'=>'Ngày trúng thầu','align'=>'center','width'=>115,'type'=>'date'], 'bid_contractor'=>['label'=>'Nhà thầu trúng thầu','align'=>'left','width'=>220,'type'=>'auto'],
        'bid_source'=>['label'=>'Nguồn KQ trúng thầu','align'=>'left','width'=>130,'type'=>'auto'], 'company_sale_price'=>['label'=>'Giá bán công ty','align'=>'right','width'=>120,'type'=>'number'],
        'discount_percent'=>['label'=>'% CK thu','align'=>'right','width'=>85,'type'=>'number'], 'actual_receivable_price'=>['label'=>'Giá thu thực tế','align'=>'right','width'=>120,'type'=>'number'],
        'invoice_price'=>['label'=>'Giá xuất hóa đơn','align'=>'right','width'=>120,'type'=>'number'], 'partner'=>['label'=>'Khách hàng / loại','align'=>'left','width'=>200,'type'=>'auto'],
        'effective'=>['label'=>'Hiệu lực','align'=>'center','width'=>170,'type'=>'auto'], 'status'=>['label'=>'Trạng thái','align'=>'center','width'=>100,'type'=>'auto'], 'note'=>['label'=>'Ghi chú','align'=>'left','width'=>200,'type'=>'auto'],
    ];

    public const DEFAULT_HEADER_FOOTER = [
        'enabled'=>true,'company_name'=>'CÔNG TY TNHH INAFO VIỆT NAM','address'=>'','tax_code'=>'','phone'=>'','email'=>'','title'=>'BẢNG BÁO GIÁ',
        'recipient'=>'QUÝ KHÁCH HÀNG','intro'=>'Công ty INAFO Việt Nam xin trân trọng gửi đến Quý Khách hàng báo giá một số sản phẩm chúng tôi đang phân phối trên thị trường hiện nay như sau:',
        'logo_width_cm'=>2.48,'logo_height_cm'=>3.83,'signature_width_cm'=>4.00,'signature_height_cm'=>2.00,'footer_location'=>'Tp.HCM','signatory_title'=>'GIÁM ĐỐC CÔNG TY','signatory_name'=>'','footer_year'=>'',
    ];

    public const DEFAULT_PAGE_SETUP = ['paper_size'=>'A4','orientation'=>'landscape','margin_left_cm'=>0.3,'margin_right_cm'=>0.3,'margin_top_cm'=>0.8,'margin_bottom_cm'=>0.8,'center_horizontal'=>true,'center_vertical'=>false,'scaling'=>'fit_width','fit_width'=>1,'fit_height'=>0];

    public function profilesForUser(int $userId): array
    {
        return PriceListExportProfile::query()->where('user_id',$userId)->orderByDesc('is_default')->orderBy('name')->get(['id','name','is_default'])->toArray();
    }

    public function forUser(int $userId, ?int $profileId = null): array
    {
        $query = PriceListExportProfile::query()->where('user_id',$userId);
        $profile = $profileId ? $query->whereKey($profileId)->first() : $query->orderByDesc('is_default')->orderBy('id')->first();
        return $profile ? $this->payload($profile) : $this->defaults();
    }

    public function save(int $userId, array $data, ?int $profileId = null): array
    {
        $profile = $profileId ? PriceListExportProfile::query()->where('user_id',$userId)->findOrFail($profileId) : new PriceListExportProfile(['user_id'=>$userId]);
        $order = $this->normalizeOrder((array)($data['column_order'] ?? []));
        $selected = array_values(array_intersect($order, (array)($data['selected_columns'] ?? [])));
        if ($selected === []) $selected = $order;
        $makeDefault = (bool)($data['is_default'] ?? false);
        if (! $profile->exists && ! PriceListExportProfile::query()->where('user_id',$userId)->exists()) $makeDefault = true;
        if ($makeDefault) PriceListExportProfile::query()->where('user_id',$userId)->update(['is_default'=>false]);
        $profile->forceFill([
            'user_id'=>$userId,'name'=>mb_substr(trim((string)($data['name'] ?? 'Cấu hình Bảng Giá')) ?: 'Cấu hình Bảng Giá',0,120),'is_default'=>$makeDefault || (bool)$profile->is_default,
            'column_order'=>$order,'selected_columns'=>$selected,'headers'=>$this->normalizeMap((array)($data['headers'] ?? []),'label'),
            'alignments'=>$this->normalizeMap((array)($data['alignments'] ?? []),'align'),'widths'=>$this->normalizeWidths((array)($data['widths'] ?? [])),
            'data_types'=>$this->normalizeMap((array)($data['data_types'] ?? []),'type'),'decimals'=>$this->normalizeDecimals((array)($data['decimals'] ?? [])),
            'header_footer'=>array_replace(self::DEFAULT_HEADER_FOOTER,(array)($data['header_footer'] ?? [])),'page_setup'=>array_replace(self::DEFAULT_PAGE_SETUP,(array)($data['page_setup'] ?? [])),
            'logo_path'=>$data['logo_path'] ?? $profile->logo_path,'signature_path'=>$data['signature_path'] ?? $profile->signature_path,
        ])->save();
        return $this->payload($profile->fresh());
    }

    public function duplicate(int $userId, int $profileId): array
    {
        $source=PriceListExportProfile::query()->where('user_id',$userId)->findOrFail($profileId); $copy=$source->replicate(); $copy->name=mb_substr($source->name.' - Bản sao',0,120); $copy->is_default=false; $copy->save(); return $this->payload($copy);
    }

    public function delete(int $userId,int $profileId): void
    {
        $profile=PriceListExportProfile::query()->where('user_id',$userId)->findOrFail($profileId); $wasDefault=$profile->is_default; $profile->delete(); if($wasDefault) PriceListExportProfile::query()->where('user_id',$userId)->orderBy('id')->first()?->update(['is_default'=>true]);
    }

    public function defaults(): array
    {
        $keys=array_keys(self::COLUMNS); return ['profile_id'=>null,'profile_name'=>'Mặc định','is_default'=>false,'column_order'=>$keys,'selected_columns'=>$keys,'headers'=>$this->normalizeMap([],'label'),'alignments'=>$this->normalizeMap([],'align'),'widths'=>$this->normalizeWidths([]),'data_types'=>$this->normalizeMap([],'type'),'decimals'=>$this->normalizeDecimals([]),'header_footer'=>self::DEFAULT_HEADER_FOOTER,'page_setup'=>self::DEFAULT_PAGE_SETUP,'logo_path'=>null,'signature_path'=>null];
    }

    private function payload(PriceListExportProfile $p): array
    {
        return ['profile_id'=>(int)$p->id,'profile_name'=>$p->name,'is_default'=>(bool)$p->is_default,'column_order'=>$this->normalizeOrder((array)$p->column_order),'selected_columns'=>(array)$p->selected_columns,'headers'=>$this->normalizeMap((array)$p->headers,'label'),'alignments'=>$this->normalizeMap((array)$p->alignments,'align'),'widths'=>$this->normalizeWidths((array)$p->widths),'data_types'=>$this->normalizeMap((array)$p->data_types,'type'),'decimals'=>$this->normalizeDecimals((array)$p->decimals),'header_footer'=>array_replace(self::DEFAULT_HEADER_FOOTER,(array)$p->header_footer),'page_setup'=>array_replace(self::DEFAULT_PAGE_SETUP,(array)$p->page_setup),'logo_path'=>$p->logo_path,'signature_path'=>$p->signature_path];
    }

    private function normalizeOrder(array $order): array { $valid=array_values(array_unique(array_filter($order,fn($k)=>is_string($k)&&isset(self::COLUMNS[$k])))); foreach(array_keys(self::COLUMNS) as $key) if(!in_array($key,$valid,true))$valid[]=$key; return $valid; }
    private function normalizeMap(array $values,string $default): array { $out=[]; foreach(self::COLUMNS as $key=>$column)$out[$key]=trim((string)($values[$key]??$column[$default])) ?: $column[$default]; return $out; }
    private function normalizeWidths(array $values): array { $out=[]; foreach(self::COLUMNS as $key=>$column)$out[$key]=max(40,min(600,(int)($values[$key]??$column['width']))); return $out; }
    private function normalizeDecimals(array $values): array { $out=[]; foreach(self::COLUMNS as $key=>$column)$out[$key]=max(0,min(6,(int)($values[$key]??($column['type']==='number'?0:0)))); return $out; }
}
