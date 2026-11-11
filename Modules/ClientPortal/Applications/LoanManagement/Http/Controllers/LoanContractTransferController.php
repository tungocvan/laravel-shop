<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\LoanManagement\Services\LoanContractService;
use Modules\LoanManagement\Services\LoanContractWorkbookService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LoanContractTransferController extends Controller
{
    public function export(Request $request, LoanContractWorkbookService $service): StreamedResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.view'), 403);
        $data = $request->validate([
            'contracts' => ['sometimes', 'array', 'max:5000'],
            'contracts.*' => ['required', 'integer', 'distinct', 'min:1', \Illuminate\Validation\Rule::exists('loan_contracts', 'id')],
        ]);
        $book = $service->export(array_map('intval', $data['contracts'] ?? []));
        $filename = 'loan-contracts-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($book): void {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function template(Request $request, LoanContractWorkbookService $service): StreamedResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.create'), 403);
        $book = $service->template();

        return response()->streamDownload(function () use ($book): void {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'loan-contracts-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function preview(Request $request, LoanContractWorkbookService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.create'), 403);
        $request->validate(['file' => ['required','file','mimes:xlsx','max:2048']]);
        $rows = $service->preview($request->file('file'));
        if ($rows === []) {
            throw ValidationException::withMessages(['file'=>'File không có hợp đồng để nhập.']);
        }
        $token = Str::random(48);
        $request->session()->put('loan_contract_import', [
            'token'=>$token,
            'rows'=>$rows,
            'user_id'=>$request->user()->getAuthIdentifier(),
            'expires_at'=>now()->addMinutes(15)->timestamp,
        ]);

        return redirect()->to(route('client.loans.contracts.index').'#loan-import')
            ->with('success', 'Đã kiểm tra file. Hãy xem trước kết quả và xác nhận trước khi tạo hợp đồng.');
    }

    public function confirm(Request $request, LoanContractWorkbookService $service, LoanContractService $contracts): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.create'), 403);
        $request->validate(['token'=>['required','string','size:48']]);
        $preview = $request->session()->get('loan_contract_import');
        abort_unless(is_array($preview)
            && hash_equals((string)($preview['token'] ?? ''), (string)$request->input('token'))
            && ($preview['user_id'] ?? null) === $request->user()->getAuthIdentifier()
            && ($preview['expires_at'] ?? 0) >= now()->timestamp, 419);

        // Invalid rows cannot be silently ignored during a confirmed import.
        if (collect($preview['rows'])->contains(fn ($row) => $row['action'] === 'error')) {
            throw ValidationException::withMessages(['file'=>'File có dòng lỗi. Hãy sửa file và xem trước lại trước khi nhập.']);
        }
        $result = $service->importNewDrafts($preview['rows'], $contracts);
        $request->session()->forget('loan_contract_import');

        return redirect()->route('client.loans.contracts.index')->with('success',
            'Đã tạo '.$result['created'].' hợp đồng nháp; bỏ qua '.$result['skipped'].' hợp đồng đã tồn tại. Không ghi nhận giải ngân, thanh toán hay bảo hiểm.');
    }
}
