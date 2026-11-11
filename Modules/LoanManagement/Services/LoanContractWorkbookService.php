<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class LoanContractWorkbookService
{
    public const FORMAT = 'LOAN-CONTRACTS-V1';
    public const MAX_IMPORT = 100;
    public const MAX_EXPORT = 5000;

    // Export-only dependent ledgers. Import never writes these tables.
    private const SHEETS = [
        'Contracts' => ['loan_contracts', ['contract_number','lender_name','lender_type','lender_tax_identifier','committed_amount','annual_rate_percent','day_count_basis','signed_at','matures_at','status']],
        'Disbursements' => ['loan_disbursements', ['contract_number','disbursed_at','amount','reference']],
        'Schedules' => ['loan_repayment_schedules', ['contract_number','installment','due_at','principal_due','interest_due','fees_due']],
        'Payments' => ['loan_payments', ['contract_number','paid_at','principal_paid','interest_paid','fees_paid','reference']],
        'Insurance' => ['loan_insurance_policies', ['contract_number','type','provider','policy_number','starts_at','ends_at','requirement','insured_amount']],
        'Premiums' => ['loan_insurance_premium_schedules', ['contract_number','policy_number','due_at','amount_due','amount_paid','paid_at']],
    ];

    public function export(): Spreadsheet
    {
        $book = new Spreadsheet();
        $book->removeSheetByIndex(0);
        foreach (self::SHEETS as $name => [$table, $headers]) {
            $sheet = $book->createSheet();
            $sheet->setTitle($name);
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)).'1')->getFont()->setBold(true);
            $sheet->freezePane('A2');
            $query = DB::table($table.' as t');
            if ($name === 'Contracts') {
                $query->join('loan_lenders as l', 'l.id', '=', 't.lender_id')
                    ->select('t.contract_number','l.name as lender_name','l.type as lender_type','l.tax_identifier as lender_tax_identifier','t.committed_amount','t.annual_rate_percent','t.day_count_basis','t.signed_at','t.matures_at','t.status');
            } elseif ($name === 'Premiums') {
                $query->join('loan_insurance_policies as policy','policy.id','=','t.loan_insurance_policy_id')
                    ->join('loan_contracts as c','c.id','=','policy.loan_contract_id')
                    ->select('c.contract_number','policy.policy_number','t.due_at','t.amount_due','t.amount_paid','t.paid_at');
            } else {
                $query->join('loan_contracts as c','c.id','=','t.loan_contract_id')
                    ->select(array_merge(['c.contract_number'],array_map(fn ($column) => 't.'.$column,array_slice($headers,1))));
            }
            $rows = $query->orderBy($name === 'Contracts' ? 't.contract_number' : 't.id')->limit(self::MAX_EXPORT + 1)->get();
            if ($rows->count() > self::MAX_EXPORT) {
                $book->disconnectWorksheets();
                throw ValidationException::withMessages(['export' => 'Dữ liệu vượt giới hạn 5.000 dòng mỗi sheet. Chưa xuất file để tránh thiếu dữ liệu.']);
            }
            $index = 2;
            foreach ($rows as $row) {
                foreach ($headers as $column => $header) {
                    $value = $row->{$header};
                    // All exported values are plain strings; never evaluate spreadsheet formulas from user input.
                    $sheet->setCellValueExplicitByColumnAndRow($column + 1, $index, (string) ($value ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $index++;
            }
            $sheet->setAutoFilter('A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)).max(1,$index-1));
        }
        $meta = $book->createSheet();
        $meta->setTitle('README');
        $meta->setCellValue('A1', self::FORMAT);
        $meta->setCellValue('A2', 'Contracts: dữ liệu hợp đồng và trạng thái hiện tại. Các sheet còn lại chỉ để đối chiếu.');
        $meta->setCellValue('A3', 'Import chỉ tạo hợp đồng chưa tồn tại dưới dạng Nháp. Không import giao dịch, lịch, bảo hiểm hay tự động ghi sổ.');
        $book->setActiveSheetIndex(0);
        return $book;
    }

    public function preview(UploadedFile $file): array
    {
        $book = IOFactory::load($file->getRealPath());
        $sheet = $book->getSheetByName('Contracts');
        if (!$sheet) {
            throw ValidationException::withMessages(['file' => 'Thiếu sheet Contracts trong file Excel.']);
        }
        if ($sheet->getHighestRow() > self::MAX_IMPORT + 1) {
            throw ValidationException::withMessages(['file' => 'Mỗi lần nhập tối đa 100 hợp đồng.']);
        }
        $headers = self::SHEETS['Contracts'][1];
        $actual = $sheet->rangeToArray('A1:J1', null, true, false)[0];
        if ($actual !== $headers) {
            throw ValidationException::withMessages(['file' => 'Cấu trúc cột không khớp mẫu Export hợp đồng.']);
        }
        $seen = [];
        $rows = [];
        for ($line = 2; $line <= $sheet->getHighestRow(); $line++) {
            $values = $sheet->rangeToArray('A'.$line.':J'.$line, null, true, false)[0];
            if (count(array_filter($values, fn ($value) => trim((string)$value) !== '')) === 0) continue;
            $item = array_combine($headers, array_map(fn ($value) => trim((string)$value), $values));
            $number = $item['contract_number'];
            $errors = [];
            if ($number === '' || mb_strlen($number) > 255) $errors[] = 'Số hợp đồng không hợp lệ';
            if (isset($seen[$number])) $errors[] = 'Trùng số hợp đồng trong file';
            $seen[$number] = true;
            if (!in_array($item['lender_type'], ['individual','company','credit_institution'],true)) $errors[] = 'Loại đối tác không hợp lệ';
            if ($item['lender_name'] === '') $errors[] = 'Thiếu tên đối tác';
            foreach (['committed_amount','annual_rate_percent'] as $field) {
                if (!is_numeric($item[$field]) || (float)$item[$field] < 0) $errors[] = 'Giá trị '.$field.' không hợp lệ';
            }
            if (!in_array($item['day_count_basis'], ['360','365'],true)) $errors[] = 'Cơ sở tính ngày không hợp lệ';
            foreach (['signed_at','matures_at'] as $field) {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $item[$field]);
                if (!$date || $date->format('Y-m-d') !== $item[$field]) $errors[] = 'Ngày '.$field.' không hợp lệ';
            }
            if ($item['signed_at'] > $item['matures_at']) $errors[] = 'Đáo hạn trước ngày ký';
            if (!in_array($item['status'], ['draft','active','closed','cancelled'],true)) $errors[] = 'Trạng thái không hợp lệ';
            $lender = DB::table('loan_lenders')->where('name',$item['lender_name'])->where('type',$item['lender_type'])->when($item['lender_tax_identifier'] !== '', fn ($q) => $q->where('tax_identifier',$item['lender_tax_identifier']))->get(['id']);
            if ($lender->count() !== 1) $errors[] = 'Đối tác không tồn tại hoặc không duy nhất';
            $existing = DB::table('loan_contracts')->where('contract_number',$number)->first();
            $action = $errors ? 'error' : ($existing ? 'existing' : 'create');
            $rows[] = ['line'=>$line,'data'=>$item,'lender_id'=>$lender->count()===1 ? (int)$lender[0]->id : null,'action'=>$action,'errors'=>$errors];
        }
        $book->disconnectWorksheets();
        return $rows;
    }

    public function importNewDrafts(array $rows, LoanContractService $contracts): array
    {
        $created = 0;
        $skipped = 0;
        DB::transaction(function () use ($rows, $contracts, &$created, &$skipped): void {
            foreach ($rows as $row) {
                if ($row['action'] !== 'create') { $skipped++; continue; }
                $data = $row['data'];
                // Revalidate against live data. Preview is not authorization or a reservation.
                if (DB::table('loan_contracts')->where('contract_number',$data['contract_number'])->exists()) { $skipped++; continue; }
                if (!DB::table('loan_lenders')->where('id',$row['lender_id'])->where('name',$data['lender_name'])->where('type',$data['lender_type'])->exists()) {
                    throw ValidationException::withMessages(['file'=>'Đối tác đã thay đổi sau bước xem trước. Hãy tải lại file.']);
                }
                $contracts->createContract([
                    'lender_id'=>$row['lender_id'],
                    'contract_number'=>$data['contract_number'],
                    'committed_amount'=>$data['committed_amount'],
                    'annual_rate_percent'=>$data['annual_rate_percent'],
                    'day_count_basis'=>(int)$data['day_count_basis'],
                    'signed_at'=>$data['signed_at'],
                    'matures_at'=>$data['matures_at'],
                ]);
                $created++;
            }
        }, 3);
        return ['created'=>$created,'skipped'=>$skipped];
    }
}
