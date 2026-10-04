<?php

namespace Modules\Pharma\Services;

class CommissionExportProfileService
{
    public const STORAGE_KEY = 'pharma.commissions.excel-designer.v2';

    public const COLUMNS = [
        'stt'=>['label'=>'STT','group'=>'issue','align'=>'center','width'=>55,'type'=>'number'],
        'date'=>['label'=>'Ngày ghi sổ','group'=>'issue','align'=>'center','width'=>120,'type'=>'date'],
        'issue'=>['label'=>'Số phiếu','group'=>'issue','align'=>'left','width'=>190,'type'=>'string'],
        'source'=>['label'=>'Nguồn','group'=>'issue','align'=>'center','width'=>105,'type'=>'string'],
        'customer'=>['label'=>'Khách hàng / Bệnh viện','group'=>'commercial','align'=>'left','width'=>240,'type'=>'string'],
        'manager'=>['label'=>'Người phụ trách','group'=>'commercial','align'=>'left','width'=>180,'type'=>'string'],
        'medicine_code'=>['label'=>'Mã sản phẩm','group'=>'product','align'=>'left','width'=>120,'type'=>'string'],
        'medicine'=>['label'=>'Sản phẩm','group'=>'product','align'=>'left','width'=>220,'type'=>'string'],
        'quantity'=>['label'=>'SL thực xuất','group'=>'product','align'=>'right','width'=>105,'type'=>'number'],
        'unit_price'=>['label'=>'Giá bán / Trúng thầu','group'=>'pricing','align'=>'right','width'=>135,'type'=>'number'],
        'receivable'=>['label'=>'Giá thu','group'=>'pricing','align'=>'right','width'=>120,'type'=>'number'],
        'revenue'=>['label'=>'Doanh thu','group'=>'pricing','align'=>'right','width'=>130,'type'=>'number'],
        'percentage'=>['label'=>'CK / Chính sách (%)','group'=>'commission','align'=>'right','width'=>125,'type'=>'number'],
        'commission'=>['label'=>'Hoa hồng','group'=>'commission','align'=>'right','width'=>130,'type'=>'number'],
        'status'=>['label'=>'Trạng thái','group'=>'commission','align'=>'center','width'=>120,'type'=>'string'],
    ];

    public const GROUPS = [
        'issue'=>'Phiếu xuất',
        'commercial'=>'Khách hàng & phụ trách',
        'product'=>'Sản phẩm',
        'pricing'=>'Giá trị',
        'commission'=>'Hoa hồng',
    ];

    public static function defaults(): array
    {
        $order=array_keys(self::COLUMNS);
        return [
            'column_order'=>$order,
            'selected_columns'=>$order,
            'headers'=>array_map(fn($column)=>$column['label'],self::COLUMNS),
            'alignments'=>array_map(fn($column)=>$column['align'],self::COLUMNS),
            'widths'=>array_map(fn($column)=>$column['width'],self::COLUMNS),
            'auto_widths'=>array_fill_keys($order,true),
            'wrap_texts'=>array_fill_keys($order,true),
            'data_types'=>array_map(fn($column)=>$column['type'],self::COLUMNS),
            'decimals'=>array_fill_keys($order,0),
            'page_setup'=>['orientation'=>'landscape','auto_height'=>true],
        ];
    }

    public static function normalize(array $input): array
    {
        $defaults=self::defaults();
        $order=array_values(array_unique(array_filter((array)($input['column_order']??[]),fn($key)=>is_string($key)&&isset(self::COLUMNS[$key]))));
        foreach(array_keys(self::COLUMNS) as $key)if(!in_array($key,$order,true))$order[]=$key;
        $selected=array_values(array_intersect($order,(array)($input['selected_columns']??$order)));
        if($selected===[])$selected=$order;
        $map=function(string $name,string $definitionKey)use($input,$defaults){
            $out=[];
            foreach(self::COLUMNS as $key=>$definition)$out[$key]=$input[$name][$key]??$defaults[$name][$key]??$definition[$definitionKey];
            return $out;
        };
        $widths=[];$decimals=[];$autoWidths=[];$wrapTexts=[];
        foreach(self::COLUMNS as $key=>$definition){
            $widths[$key]=max(40,min(400,(int)($input['widths'][$key]??$definition['width'])));
            $decimals[$key]=max(0,min(6,(int)($input['decimals'][$key]??0)));
            $autoWidths[$key]=(bool)($input['auto_widths'][$key]??true);
            $wrapTexts[$key]=(bool)($input['wrap_texts'][$key]??true);
        }
        return [
            'column_order'=>$order,'selected_columns'=>$selected,
            'headers'=>$map('headers','label'),'alignments'=>$map('alignments','align'),'widths'=>$widths,
            'auto_widths'=>$autoWidths,'wrap_texts'=>$wrapTexts,
            'data_types'=>$map('data_types','type'),'decimals'=>$decimals,
            'page_setup'=>array_replace($defaults['page_setup'],(array)($input['page_setup']??[])),
        ];
    }
}
