<?php

namespace Modules\Pharma\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Modules\Pharma\Models\InventoryIssueCommission;

final class CommissionExcelExportService
{
    public function download(Collection $rows, CarbonInterface $from, CarbonInterface $to, array $profileInput=[]): BinaryFileResponse
    {
        $profile=CommissionExportProfileService::normalize($profileInput);
        $selected=array_values(array_filter($profile['column_order'],fn($key)=>in_array($key,$profile['selected_columns'],true)&&isset(CommissionExportProfileService::COLUMNS[$key])));
        $headers=array_map(fn($key)=>$profile['headers'][$key]??CommissionExportProfileService::COLUMNS[$key]['label'],$selected);

        $spreadsheet=new Spreadsheet();
        $sheet=$spreadsheet->getActiveSheet();
        $sheet->setTitle('Hoa hong');
        $lastColumn=Coordinate::stringFromColumnIndex(count($headers));
        $sheet->mergeCells("A1:{$lastColumn}1")->setCellValue('A1','TRUNG TÂM HOA HỒNG · CHI TIẾT PHÁT SINH');
        $sheet->mergeCells("A2:{$lastColumn}2")->setCellValue('A2','Kỳ dữ liệu: '.$from->format('d/m/Y').' - '.$to->format('d/m/Y'));
        $sheet->fromArray([$headers],null,'A4');
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("A4:{$lastColumn}4")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}".max(4,4+$rows->count()))->getAlignment()->setVertical('center')->setWrapText(true);

        foreach($rows as $index=>$row){
            $excelRow=5+$index;
            $values=$this->values($row,$index+1);
            $sheet->fromArray([array_map(fn($key)=>$values[$key],$selected)],null,"A{$excelRow}");
        }

        $lastRow=4+$rows->count();
        $sheet->getStyle("A4:{$lastColumn}4")->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
        $sheet->getStyle("A4:{$lastColumn}4")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR)->getColor()->setARGB('FFD9E2EC');

        foreach($selected as $offset=>$key){
            $letter=Coordinate::stringFromColumnIndex($offset+1);
            $definition=CommissionExportProfileService::COLUMNS[$key];
            $type=$profile['data_types'][$key]??$definition['type'];
            $decimals=(int)($profile['decimals'][$key]??0);
            if($type==='number')$sheet->getStyle("{$letter}5:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0'.($decimals>0?'.'.str_repeat('0',$decimals):''));
            if($type==='date')$sheet->getStyle("{$letter}5:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $sheet->getStyle("{$letter}5:{$letter}{$lastRow}")->getAlignment()->setHorizontal($profile['alignments'][$key]??$definition['align'])->setVertical('center')->setWrapText((bool)($profile['wrap_texts'][$key]??true));
            if($profile['auto_widths'][$key]??true){
                $maxChars=mb_strlen((string)($profile['headers'][$key]??$definition['label']));
                foreach($rows as $index=>$exportRow)$maxChars=max($maxChars,mb_strlen((string)($this->values($exportRow,$index+1)[$key]??'')));
                $sheet->getColumnDimension($letter)->setAutoSize(false);
                $sheet->getColumnDimension($letter)->setWidth(max(7,min(28,$maxChars+2)));
            }else{
                $sheet->getColumnDimension($letter)->setAutoSize(false);
                $sheet->getColumnDimension($letter)->setWidth(max(6,((int)($profile['widths'][$key]??$definition['width']))/7));
            }
        }

        $footerRow=$lastRow+2;
        foreach(['revenue'=>'Tổng giá trị','commission'=>'Tổng hoa hồng'] as $key=>$label){
            $offset=array_search($key,$selected,true);
            if($offset===false)continue;
            $column=Coordinate::stringFromColumnIndex($offset+1);
            $labelColumn=Coordinate::stringFromColumnIndex(max(1,Coordinate::columnIndexFromString($column)-1));
            $sheet->setCellValue("{$labelColumn}{$footerRow}",$label);
            $sheet->setCellValue("{$column}{$footerRow}",(float)$rows->sum($key==='revenue'?'revenue_amount':'commission_amount'));
            $sheet->getStyle("{$labelColumn}{$footerRow}:{$column}{$footerRow}")->getFont()->setBold(true);
            $sheet->getStyle("{$labelColumn}{$footerRow}:{$column}{$footerRow}")->getAlignment()->setVertical('center')->setWrapText(true);
            $sheet->getStyle("{$labelColumn}{$footerRow}")->getAlignment()->setHorizontal('right');
            $sheet->getStyle("{$column}{$footerRow}")->getAlignment()->setHorizontal('right');
            $sheet->getStyle("{$column}{$footerRow}")->getNumberFormat()->setFormatCode('#,##0');
        }

        if($profile['page_setup']['auto_height']??true)foreach(range(1,$footerRow) as $rowIndex)$sheet->getRowDimension($rowIndex)->setRowHeight(-1);
        $sheet->freezePane('A5')->setAutoFilter("A4:{$lastColumn}{$lastRow}");
        $orientation=($profile['page_setup']['orientation']??'landscape')==='portrait'?PageSetup::ORIENTATION_PORTRAIT:PageSetup::ORIENTATION_LANDSCAPE;
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation($orientation)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setLeft(0.2)->setRight(0.2)->setTop(0.4)->setBottom(0.4);
        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getHeaderFooter()->setOddFooter('&LTrung tâm hoa hồng&RTrang &P / &N');

        $path=storage_path('app/private/exports/commissions/pharma-hoa-hong-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.xlsx');
        if(!is_dir(dirname($path)))mkdir(dirname($path),0775,true);
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    private function values(InventoryIssueCommission $row, int $index): array
    {
        return [
            'stt'=>$index,'date'=>$row->calculated_at?->format('d/m/Y'),'issue'=>$row->issue?->number,
            'source'=>$row->source_type===InventoryIssueCommission::SOURCE_BID?'Hàng thầu':'Bảng giá',
            'customer'=>$row->partner?->name?:$row->issue?->recipient_name,'manager'=>$row->user?->name?:'Chưa phân công',
            'medicine_code'=>$row->medicine?->medicine_code,'medicine'=>$row->medicine?->name,'quantity'=>(float)$row->quantity,
            'unit_price'=>(float)$row->unit_price,'receivable'=>$row->receivable_price_snapshot!==null?(float)$row->receivable_price_snapshot:null,
            'revenue'=>(float)$row->revenue_amount,'percentage'=>$row->commission_percentage!==null?(float)$row->commission_percentage:null,
            'commission'=>(float)$row->commission_amount,'status'=>$row->status===InventoryIssueCommission::STATUS_UNRESOLVED?'Chưa đủ dữ liệu':'Đã tính',
        ];
    }
}
