<?php

namespace Modules\Admission\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Models\AdmissionImportRun;
use Modules\Admission\Services\AdmissionImportService;
use Modules\Admission\Services\AdmissionService;
use Symfony\Component\Process\Process;
use Throwable;

class AdmissionController extends Controller
{
    protected $admissionService;

    public function __construct(AdmissionService $admissionService)
    {
        $this->admissionService = $admissionService;
    }

    public function index()
    {
        return view('Admission::pages.public.register');
    }

    public function adminIndex()
    {
        return view('Admission::pages.admin.index');
    }

    public function adminCreate()
    {
        return view('Admission::pages.admin.create');
    }

    public function dashboard()
    {
        return view('Admission::pages.dashboard');
    }

    public function listClass()
    {
        return view('Admission::pages.public.list-class');
    }

    public function dvhc()
    {
        return view('Admission::pages.admin.dvhc');
    }

    public function search($ma_dinh_danh = '', $password = '')
    {
        return view('Admission::pages.public.search', [
            'ma_dinh_danh' => $ma_dinh_danh ?? '',
            'password' => $password ?? '',
        ]);
    }

    public function adminEdit($id)
    {
        return view('Admission::pages.admin.create', compact('id'));
    }

    public function downloadPdf($id, AdmissionService $service)
    {
        try {
            $data = $service->getDataForTemplate($id);
            $fileNameBase = 'Don_Dang_Ky_' . str_replace(' ', '_', $data['HoVaTenHocSinh']);

            $tempDir = storage_path('app/admission/');
            if (! file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $wordPath = $tempDir . $fileNameBase . '.docx';
            $pdfPath = $tempDir . $fileNameBase . '.pdf';

            if (file_exists($pdfPath)) {
                return response()->download($pdfPath);
            }

            if (! file_exists($wordPath)) {
                $templatePath = storage_path('app/templates/application.docx');
                if (! file_exists($templatePath)) {
                    throw new \Exception('Không tìm thấy file mẫu tại: ' . $templatePath);
                }

                $templateProcessorClass = 'PhpOffice\\PhpWord\\TemplateProcessor';
                $templateProcessor = new $templateProcessorClass($templatePath);

                foreach ($data as $key => $value) {
                    $templateProcessor->setValue($key, $value);
                }

                $templateProcessor->saveAs($wordPath);
            }

            $process = new Process([
                'libreoffice',
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $tempDir,
                $wordPath,
            ]);

            $process->setEnv(['HOME' => $tempDir]);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \Exception('Lỗi chuyển đổi PDF: ' . $process->getErrorOutput());
            }

            if (! file_exists($pdfPath)) {
                throw new \Exception('LibreOffice không tạo được file PDF.');
            }

            return response()->download($pdfPath);
        } catch (\Exception $e) {
            \Log::error('Lỗi xuất PDF: ' . $e->getMessage());

            return back()->with('error', 'Lỗi khi xuất PDF: ' . $e->getMessage());
        }
    }

    public function downloadDocx($id, AdmissionService $service)
    {
        $data = $service->getDataForTemplate($id);
        $templateProcessorClass = 'PhpOffice\\PhpWord\\TemplateProcessor';
        $templateProcessor = new $templateProcessorClass(storage_path('app/templates/application.docx'));

        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        $fileName = 'Don_Dang_Ky_' . $data['HoVaTenHocSinh'] . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'word');
        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function import(Request $request, AdmissionImportService $service)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'restore_status' => ['nullable', 'boolean'],
        ]);

        $restoreStatus = $request->boolean('restore_status');
        $admin = $request->user('admin');

        if ($restoreStatus) {
            abort_unless($admin && $admin->can('approve_admission'), 403);
        }

        try {
            $run = $service->import($validated['file'], $admin?->id, $restoreStatus);

            $summary = [
                'run_id' => $run->id,
                'total' => $run->total_rows,
                'success' => $run->success_rows,
                'failed' => $run->failed_rows,
                'created' => $run->created_rows,
                'updated' => $run->updated_rows,
                'restore_status' => $restoreStatus,
            ];

            return back()
                ->with('success', sprintf(
                    'Import hoàn tất: %d dòng — %d thành công, %d lỗi.%s',
                    $run->total_rows,
                    $run->success_rows,
                    $run->failed_rows,
                    $restoreStatus ? ' Đã bật chế độ khôi phục trạng thái.' : '',
                ))
                ->with('import_summary', $summary);
        } catch (Throwable $e) {
            return back()->with('error', 'Không thể đọc hoặc xử lý file Import. Vui lòng kiểm tra file và thử lại.');
        }
    }

    public function importHistory(AdmissionImportService $service)
    {
        return view('Admission::pages.admin.imports.index', [
            'runs' => $service->runs(),
        ]);
    }

    public function importErrors(AdmissionImportRun $run, AdmissionImportService $service)
    {
        return view('Admission::pages.admin.imports.errors', [
            'run' => $run,
            'errors' => $service->errorsForRun($run),
        ]);
    }

    public function download($id, $type)
    {
        $item = AdmissionApplication::findOrFail($id);

        $path = match ($type) {
            'pdf' => $item->pdf_path,
            'word' => $item->word_path,
            default => null,
        };

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'File không tồn tại');
        }

        return response()->download(storage_path('app/' . $path));
    }

    public function receipt($id, AdmissionService $service)
    {
        $app = AdmissionApplication::findOrFail($id);

        if ($app->status !== 'approved') {
            abort(403, 'Hồ sơ chưa được duyệt');
        }

        return $service->generateBienNhan($app);
    }
}
