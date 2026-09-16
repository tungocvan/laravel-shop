<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Services\PriceListExcelTypography;
use Modules\Pharma\Services\PriceListExportProfileService;
use Modules\Pharma\Services\PriceListManager;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PriceListController extends Controller
{
    public function index(): View { return view('Pharma::pages.price-list.index'); }
    public function create(): View { return view('Pharma::pages.price-list.create'); }
    public function store(Request $request, PriceListManager $manager): RedirectResponse { $data=$manager->validateHeader($request->validate($this->headerRules()));$data['status']=PriceList::STATUS_DRAFT;$data['created_by']=auth('admin')->id();$priceList=PriceList::query()->create($data);return redirect()->route('admin.pharma.price-lists.edit',$priceList)->with('success','Đã tạo bảng giá nháp.'); }
    public function show(PriceList $priceList): View { $priceList->load(['partner','manager','purpose','items.medicine','items.variant','items.package','items.bidEvidence']);return view('Pharma::pages.price-list.show',compact('priceList')); }
    public function edit(PriceList $priceList): View { abort_unless($priceList->isDraft(),422,'Chỉ bảng giá Draft mới được chỉnh trực tiếp. Hãy clone để tạo phiên bản mới.');return view('Pharma::pages.price-list.edit',compact('priceList')); }
    public function update(Request $request,PriceList $priceList,PriceListManager $manager):RedirectResponse { abort_unless($priceList->isDraft(),422,'Chỉ bảng giá Draft mới được chỉnh trực tiếp.');$priceList->update($manager->validateHeader($request->validate($this->headerRules()),$priceList));return back()->with('success','Đã cập nhật bảng giá.'); }
    public function activate(PriceList $priceList,PriceListManager $manager):RedirectResponse{$manager->activate($priceList,auth('admin')->id());return back()->with('success','Bảng giá đã được kích hoạt.');}
    public function deactivate(PriceList $priceList,PriceListManager $manager):RedirectResponse{$manager->deactivate($priceList);return back()->with('success','Bảng giá đã ngưng hiệu lực.');}
    public function clone(PriceList $priceList,PriceListManager $manager):RedirectResponse{$copy=$manager->clone($priceList,['name'=>$priceList->name.' - Bản sao']);return redirect()->route('admin.pharma.price-lists.edit',$copy)->with('success','Đã clone thành bảng giá Draft mới.');}

    public function export(Request $request,PriceList $priceList,PriceListExportProfileService $profiles,PriceListExcelTypography $typography):BinaryFileResponse
    {
        $validated=$request->validate(['items'=>['nullable','array'],'items.*'=>['integer'],'export_profile_id'=>['nullable','integer']]);
        $profile=$profiles->forUser((int)auth('admin')->id(),isset($validated['export_profile_id'])?(int)$validated['export_profile_id']:null);
        $columns=array_values(array_filter($profile['column_order'],fn($key)=>in_array($key,$profile['selected_columns'],true)&&isset(PriceListExportProfileService::COLUMNS[$key])));abort_if($columns===[],422,'Cấu hình xuất phải có ít nhất một cột.');
        $selected=collect($validated['items']??[])->map(fn($id)=>(int)$id)->filter()->unique()->values();$query=$priceList->items()->with(['medicine','variant','package','bidEvidence']);if($selected->isNotEmpty())$query->whereKey($selected);$items=$query->orderBy('id')->get();
        $spreadsheet=new Spreadsheet();$sheet=$spreadsheet->getActiveSheet();$sheet->setTitle('Bang gia');$hf=$profile['header_footer'];$row=1;$lastColumn=Coordinate::stringFromColumnIndex(count($columns));
        if((bool)($hf['enabled']??true)){$sheet->mergeCells("A{$row}:{$lastColumn}{$row}");$sheet->setCellValue("A{$row}",$hf['company_name']??'');$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);$this->addDrawing($sheet,$profile['logo_path']??null,'Logo','A'.$row,(float)($hf['logo_width_cm']??2.48),(float)($hf['logo_height_cm']??3.83));if(!empty($profile['logo_path']))$sheet->getRowDimension($row)->setRowHeight(max(22,(float)($hf['logo_height_cm']??3.83)*28.35));$row++;$contact=implode(' · ',array_filter([$hf['address']??null,($hf['tax_code']??'')!==''?'MST: '.$hf['tax_code']:null,$hf['phone']??null,$hf['email']??null]));if($contact!==''){$sheet->mergeCells("A{$row}:{$lastColumn}{$row}");$sheet->setCellValue("A{$row}",$contact);$row++;}$sheet->mergeCells("A{$row}:{$lastColumn}{$row}");$sheet->setCellValue("A{$row}",$hf['title']??'BẢNG BÁO GIÁ');$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16);$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal('center');$row++;if(($hf['recipient']??'')!==''){$sheet->mergeCells("A{$row}:{$lastColumn}{$row}");$sheet->setCellValue("A{$row}",'Kính gửi: '.$hf['recipient']);$row++;}if(($hf['intro']??'')!==''){$sheet->mergeCells("A{$row}:{$lastColumn}{$row}");$sheet->setCellValue("A{$row}",$hf['intro']);$sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);$row+=2;}}
        $headerRow=$row;$sheet->fromArray([array_map(fn($key)=>$profile['headers'][$key]??PriceListExportProfileService::COLUMNS[$key]['label'],$columns)],null,"A{$headerRow}");$sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true);
        foreach($items as $index=>$item){$medicine=$item->medicine;$variant=$item->variant;$package=$item->package;$evidence=$item->bidEvidence;$discount=($item->company_sale_price&&$item->actual_receivable_price!==null)?round((1-((float)$item->actual_receivable_price/(float)$item->company_sale_price))*100,2):null;$values=[
            'stt'=>$index+1,'medicine_code'=>$medicine?->medicine_code,'medicine_name'=>$medicine?->name,'registration_number'=>$medicine?->registration_number,'registration_number_raw'=>$medicine?->registration_number_raw,'registration_number_primary'=>$medicine?->registration_number_primary,'sku'=>$variant?->sku,'package_code'=>$package?->package_code,'gtin'=>$package?->gtin,'barcode'=>$package?->barcode,
            'active_ingredients'=>$medicine?->active_ingredients,'strength'=>$variant?->strength_text?:$medicine?->concentration,'dosage_form'=>$medicine?->dosage_form,'route_of_administration'=>$medicine?->route_of_administration,'unit'=>$medicine?->unit,'therapeutic_group'=>$medicine?->therapeutic_group,'circular_group'=>$medicine?->circular_group,'circular_order_number'=>$medicine?->circular_order_number,'shelf_life'=>$medicine?->shelf_life,'shelf_life_months'=>$medicine?->shelf_life_months,'is_special_control'=>$this->yesNo($medicine?->is_special_control),
            'registered_company'=>$medicine?->registered_company,'manufacturing_company'=>$medicine?->manufacturing_company,'manufacturing_country'=>$medicine?->manufacturing_country,'visa_validity_date'=>$medicine?->visa_validity_date,'gmp_certification_date'=>$medicine?->gmp_certification_date,
            'presentation_text'=>$variant?->presentation_text,'base_unit'=>$variant?->base_unit,'content_value'=>$variant?->content_value,'content_uom'=>$variant?->content_uom,'package'=>$package?->packaging_text?:$medicine?->packaging_specification,'outer_package_type'=>$package?->outer_package_type,'inner_package_type'=>$package?->inner_package_type,'outer_quantity'=>$package?->outer_quantity,'inner_quantity'=>$package?->inner_quantity,'base_quantity'=>$package?->base_quantity,'container_volume'=>$package?->container_volume,'container_volume_uom'=>$package?->container_volume_uom,'is_orderable'=>$this->yesNo($package?->is_orderable),'is_inventory_unit'=>$this->yesNo($package?->is_inventory_unit),
            'declared_price'=>$item->declared_price_snapshot,'bid_price'=>$evidence?->bid_price,'bid_quantity'=>$evidence?->quantity,'bid_decision'=>$evidence?->decision_number,'bid_date'=>$evidence?->award_date,'bid_contractor'=>$evidence?->contractor_name,'bid_investor'=>$evidence?->investor_name,'bid_unit'=>$evidence?->unit,'bid_source'=>$evidence?->source_system,
            'company_sale_price'=>$item->company_sale_price,'discount_percent'=>$discount,'actual_receivable_price'=>$item->actual_receivable_price,'invoice_price'=>$item->invoice_price,'partner'=>$priceList->partner?->name?:'Bảng giá chung','effective'=>($item->effective_from?->toDateString()??$priceList->effective_from?->toDateString()??'∞').' → '.($item->effective_to?->toDateString()??$priceList->effective_to?->toDateString()??'∞'),'status'=>$item->status,'note'=>$item->note,
        ];$dataRow=$headerRow+$index+1;foreach($columns as $columnIndex=>$key){$coordinate=Coordinate::stringFromColumnIndex($columnIndex+1).$dataRow;$this->writeConfiguredCell($sheet,$coordinate,$values[$key]??null,$profile['data_types'][$key]??'auto',(int)($profile['decimals'][$key]??0));}}
        foreach($columns as $index=>$key){$letter=Coordinate::stringFromColumnIndex($index+1);$sheet->getColumnDimension($letter)->setWidth(max(6,((int)($profile['widths'][$key]??100))/7));$sheet->getStyle($letter.$headerRow.':'.$letter.$sheet->getHighestRow())->getAlignment()->setHorizontal($profile['alignments'][$key]??'left')->setVertical('center')->setWrapText(true);}
        $page=$profile['page_setup'];$typography->apply($sheet,$headerRow,$headerRow+$items->count(),$page);
        if((bool)($hf['enabled']??true)){$footer=$sheet->getHighestRow()+2;$sheet->mergeCells("A{$footer}:{$lastColumn}{$footer}");$location=trim(($hf['footer_location']??'').' '.(($hf['footer_year']??'')!==''?'năm '.$hf['footer_year']:''));$sheet->setCellValue("A{$footer}",$location);$sheet->getStyle("A{$footer}")->getAlignment()->setHorizontal('right');$footer++;$sheet->mergeCells("A{$footer}:{$lastColumn}{$footer}");$sheet->setCellValue("A{$footer}",$hf['signatory_title']??'');$sheet->getStyle("A{$footer}")->getFont()->setBold(true);$sheet->getStyle("A{$footer}")->getAlignment()->setHorizontal('right');$signatureRow=$footer+1;$this->addDrawing($sheet,$profile['signature_path']??null,'Signature',$lastColumn.$signatureRow,(float)($hf['signature_width_cm']??4),(float)($hf['signature_height_cm']??2),true);if(!empty($profile['signature_path']))$sheet->getRowDimension($signatureRow)->setRowHeight(max(22,(float)($hf['signature_height_cm']??2)*28.35));if(($hf['signatory_name']??'')!==''){$footer+=4;$sheet->mergeCells("A{$footer}:{$lastColumn}{$footer}");$sheet->setCellValue("A{$footer}",$hf['signatory_name']);$sheet->getStyle("A{$footer}")->getAlignment()->setHorizontal('right');}}
        $setup=$sheet->getPageSetup();$setup->setPaperSize(match($page['paper_size']??'A4'){'A3'=>PageSetup::PAPERSIZE_A3,'LETTER'=>PageSetup::PAPERSIZE_LETTER,'LEGAL'=>PageSetup::PAPERSIZE_LEGAL,default=>PageSetup::PAPERSIZE_A4});$setup->setOrientation(($page['orientation']??'landscape')==='portrait'?PageSetup::ORIENTATION_PORTRAIT:PageSetup::ORIENTATION_LANDSCAPE);if(($page['scaling']??'fit_width')!=='none'){$setup->setFitToWidth((int)($page['fit_width']??1));$setup->setFitToHeight(($page['scaling']??'fit_width')==='fit_sheet'?1:0);}$margins=$sheet->getPageMargins();$margins->setLeft(((float)($page['margin_left_cm']??0.3))/2.54)->setRight(((float)($page['margin_right_cm']??0.3))/2.54)->setTop(((float)($page['margin_top_cm']??0.8))/2.54)->setBottom(((float)($page['margin_bottom_cm']??0.8))/2.54);$setup->setHorizontalCentered((bool)($page['center_horizontal']??true))->setVerticalCentered((bool)($page['center_vertical']??false));$sheet->freezePane('A'.($headerRow+1));
        $path=storage_path('app/private/exports/price-lists/'.$priceList->code.'-'.now()->format('Ymd-His').'.xlsx');if(!is_dir(dirname($path)))mkdir(dirname($path),0775,true);(new Xlsx($spreadsheet))->save($path);return response()->download($path)->deleteFileAfterSend(true);
    }

    private function yesNo(?bool $value):?string{return $value===null?null:($value?'Có':'Không');}
    private function writeConfiguredCell($sheet,string $coordinate,mixed $value,string $type,int $decimals):void
    {
        if($value===null||$value===''){$sheet->setCellValue($coordinate,null);return;}
        if($type==='string'){$sheet->setCellValueExplicit($coordinate,(string)$value,DataType::TYPE_STRING);return;}
        if($type==='number'){$numeric=is_numeric($value)?(float)$value:null;if($numeric===null){$sheet->setCellValueExplicit($coordinate,(string)$value,DataType::TYPE_STRING);return;}$sheet->setCellValue($coordinate,$numeric);$sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode('#,##0'.($decimals>0?'.'.str_repeat('0',min(6,$decimals)):''));return;}
        if($type==='date'){
            try{$date=$value instanceof DateTimeInterface?$value:new DateTimeImmutable((string)$value);}
            catch(Throwable){$sheet->setCellValueExplicit($coordinate,(string)$value,DataType::TYPE_STRING);return;}
            $sheet->setCellValue($coordinate,Date::PHPToExcel($date));$sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode('dd/mm/yyyy');return;
        }
        $sheet->setCellValue($coordinate,$value);
    }
    private function addDrawing($sheet,?string $path,string $name,string $coordinate,float $widthCm,float $heightCm,bool $right=false):void{if(!$path||!Storage::disk('public')->exists($path))return;$drawing=new Drawing();$drawing->setName($name);$drawing->setPath(Storage::disk('public')->path($path));$drawing->setCoordinates($coordinate);$drawing->setWidth(max(20,(int)round($widthCm*37.795)));$drawing->setHeight(max(20,(int)round($heightCm*37.795)));if($right)$drawing->setOffsetX(-max(0,(int)round($widthCm*20)));$drawing->setWorksheet($sheet);}
    private function headerRules():array{return['code'=>['required','string','max:80'],'name'=>['required','string','max:255'],'type'=>['required','in:global,customer'],'partner_id'=>['nullable','integer'],'effective_from'=>['nullable','date'],'effective_to'=>['nullable','date'],'currency'=>['required','string','size:3'],'priority'=>['required','integer'],'notes'=>['nullable','string']];}
}
