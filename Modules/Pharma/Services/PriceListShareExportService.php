<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListExportShare;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class PriceListShareExportService
{
    public function __construct(
        private readonly PriceListExportProfileService $profiles,
        private readonly PriceListExcelTypography $typography,
        private readonly PriceListExcelDocumentLayout $layout,
    ) {}

    public function profilesForUser(int $userId): array
    {
        return $this->profiles->profilesForUser($userId);
    }

    public function export(PriceList $priceList, int $userId, ?int $profileId = null, array $itemIds = []): array
    {
        abort_unless($priceList->status === PriceList::STATUS_ACTIVE, 422, 'Chỉ bảng giá đang kích hoạt mới được xuất Excel.');
        $priceList->loadMissing(['partner','officialFacility','manager']);
        $profile = $this->profiles->forUser($userId, $profileId);
        $columns = array_values(array_filter($profile['column_order'], fn ($key) => in_array($key, $profile['selected_columns'], true) && isset(PriceListExportProfileService::COLUMNS[$key])));
        abort_if($columns === [], 422, 'Cấu hình xuất phải có ít nhất một cột.');

        $selected = collect($itemIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $query = $priceList->items()->with(['medicine','variant','package','bidEvidence']);
        if ($selected->isNotEmpty()) $query->whereKey($selected);
        $items = $query->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bang gia');
        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
        $customerLabel = $priceList->type === 'global' ? 'Bảng giá chung' : ($priceList->customer_source === 'official_facility' ? ($priceList->officialFacility?->facility_name ?? 'Chưa chỉ định') : ($priceList->partner?->name ?? 'Chưa chỉ định'));
        $row = $this->layout->header($sheet, $profile, count($columns), 1, $customerLabel);
        $headerRow = $row;
        $sheet->fromArray([array_map(fn ($key) => $profile['headers'][$key] ?? PriceListExportProfileService::COLUMNS[$key]['label'], $columns)], null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->getFont()->setBold(true);

        foreach ($items as $index => $item) {
            $medicine=$item->medicine; $variant=$item->variant; $package=$item->package; $evidence=$item->bidEvidence;
            $discount=($item->company_sale_price && $item->actual_receivable_price!==null) ? round((1-((float)$item->actual_receivable_price/(float)$item->company_sale_price))*100,2) : null;
            $values=[
                'stt'=>$index+1,'medicine_code'=>$medicine?->medicine_code,'medicine_name'=>$medicine?->name,'registration_number'=>$medicine?->registration_number,'sku'=>$variant?->sku,
                'active_ingredients'=>$medicine?->active_ingredients,'strength'=>$variant?->strength_text?:$medicine?->concentration,'dosage_form'=>$medicine?->dosage_form,'route_of_administration'=>$medicine?->route_of_administration,'unit'=>$medicine?->unit,'therapeutic_group'=>$medicine?->therapeutic_group,'circular_group'=>$medicine?->circular_group,
                'registered_company'=>$medicine?->registered_company,'manufacturing_company'=>$medicine?->manufacturing_company,'manufacturing_country'=>$medicine?->manufacturing_country,'presentation_text'=>$variant?->presentation_text,'base_unit'=>$variant?->base_unit,'package'=>$package?->packaging_text?:$medicine?->packaging_specification,
                'declared_price'=>$item->declared_price_snapshot,'bid_price'=>$evidence?->bid_price,'bid_quantity'=>$evidence?->quantity,'bid_decision'=>$evidence?->decision_number,'bid_date'=>$evidence?->award_date,'bid_contractor'=>$evidence?->contractor_name,'bid_investor'=>$evidence?->investor_name,'bid_unit'=>$evidence?->unit,'bid_source'=>$evidence?->source_system,
                'company_sale_price'=>$item->company_sale_price,'discount_percent'=>$discount,'actual_receivable_price'=>$item->actual_receivable_price,'invoice_price'=>$item->invoice_price,'partner'=>$customerLabel,'manager'=>$priceList->manager?->name,'effective'=>($item->effective_from?->toDateString()??$priceList->effective_from?->toDateString()??'∞').' → '.($item->effective_to?->toDateString()??$priceList->effective_to?->toDateString()??'∞'),'status'=>$item->status,'note'=>$item->note,
            ];
            $dataRow=$headerRow+$index+1;
            foreach($columns as $columnIndex=>$key) $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex+1).$dataRow,$values[$key]??null);
        }
        foreach($columns as $index=>$key){$letter=Coordinate::stringFromColumnIndex($index+1);$sheet->getColumnDimension($letter)->setWidth(max(6,((int)($profile['widths'][$key]??100))/7));$sheet->getStyle($letter.$headerRow.':'.$letter.($headerRow+$items->count()))->getAlignment()->setHorizontal($profile['alignments'][$key]??'left')->setVertical('center')->setWrapText(true);}
        $page=$profile['page_setup'];$this->typography->apply($sheet,$headerRow,$headerRow+$items->count(),$page);$this->layout->footer($sheet,$profile,count($columns),$headerRow+$items->count());
        $setup=$sheet->getPageSetup();$setup->setPaperSize(match($page['paper_size']??'A4'){'A3'=>PageSetup::PAPERSIZE_A3,'LETTER'=>PageSetup::PAPERSIZE_LETTER,'LEGAL'=>PageSetup::PAPERSIZE_LEGAL,default=>PageSetup::PAPERSIZE_A4});$setup->setOrientation(($page['orientation']??'landscape')==='portrait'?PageSetup::ORIENTATION_PORTRAIT:PageSetup::ORIENTATION_LANDSCAPE);if(($page['scaling']??'fit_width')!=='none'){$setup->setFitToWidth((int)($page['fit_width']??1));$setup->setFitToHeight(($page['scaling']??'fit_width')==='fit_sheet'?1:0);}$sheet->freezePane('A'.($headerRow+1));

        $token = Str::random(64);
        $downloadName = Str::slug($priceList->code ?: 'bang-gia').'-'.now()->format('Ymd-His').'.xlsx';
        $storagePath = 'Pharma/price-lists/exports/'.now()->format('Y/m').'/'.Str::uuid().'.xlsx';
        $absolutePath = Storage::disk('local')->path($storagePath);
        if (! is_dir(dirname($absolutePath))) mkdir(dirname($absolutePath), 0775, true);
        (new Xlsx($spreadsheet))->save($absolutePath);

        $share = PriceListExportShare::query()->create([
            'price_list_id'=>$priceList->id,'created_by'=>$userId,'export_profile_id'=>$profile['profile_id'] ?: null,
            'storage_path'=>$storagePath,'download_name'=>$downloadName,'token_hash'=>hash('sha256',$token),
            'expires_at'=>now()->addDays(30),
        ]);

        return ['share'=>$share,'token'=>$token];
    }

    public function resolve(string $token): PriceListExportShare
    {
        $share=PriceListExportShare::query()->where('token_hash',hash('sha256',$token))->firstOrFail();
        abort_unless($share->isAvailable() && Storage::disk('local')->exists($share->storage_path), 404);
        return $share;
    }

    public function revoke(int $shareId, int $userId): void
    {
        $share=PriceListExportShare::query()->whereKey($shareId)->where('created_by',$userId)->firstOrFail();
        $share->update(['revoked_at'=>now()]);
    }
}
