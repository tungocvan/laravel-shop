<?php

namespace Modules\Pharma\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pharma\Services\MedicineCategoryCsvService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicineCategoryCsvController extends Controller
{
    public function index(Request $request)
    {
        return view('Pharma::medicine.category-csv', [
            'preview' => $request->session()->get('pharma_category_csv_preview', []),
        ]);
    }

    public function export(MedicineCategoryCsvService $service): StreamedResponse
    {
        return response()->streamDownload(function () use ($service): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['medicine_code', 'medicine_name', 'category_slugs']);
            foreach ($service->exportRows() as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'pharma-medicine-categories.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request, MedicineCategoryCsvService $service)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $request->session()->forget('pharma_category_csv_preview');
        $rows = $service->preview($request->file('file'));
        $request->session()->put('pharma_category_csv_preview', $rows);

        return redirect()->route('admin.pharma.medicines.categories-csv.index');
    }

    public function commit(Request $request, MedicineCategoryCsvService $service)
    {
        $rows = $request->session()->get('pharma_category_csv_preview', []);
        if (! is_array($rows) || $rows === []) {
            return redirect()->route('admin.pharma.medicines.categories-csv.index')
                ->withErrors(['file' => 'Chưa có dữ liệu xem trước để xác nhận.']);
        }
        $count = $service->commit($rows);
        $request->session()->forget('pharma_category_csv_preview');

        return redirect()->route('admin.pharma.medicines.categories-csv.index')
            ->with('success', "Đã cập nhật danh mục cho {$count} thuốc.");
    }
}
