<?php

namespace Modules\Admission\Http\Controllers;

use App\Http\Controllers\Controller;

use Modules\Admission\Services\AdmissionService;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Maatwebsite\Excel\Facades\Excel;
//se Modules\Admission\Exports\ApplicationsExport;
//use Modules\Admission\Imports\ApplicationsImport;
use Illuminate\Support\Facades\Storage;
use App\Services\Data\Import\GenericImport;
use Modules\Admission\Models\AdmissionApplication;


class AdmissionController extends Controller
{
    protected $admissionService;

    public function __construct(AdmissionService $admissionService)
    {
        $this->admissionService = $admissionService;
    }

    /**
     * Giao diện đăng ký dành cho Phụ huynh (Sử dụng Livewire Component)
     */
    public function index()
    {
        return view('Admission::pages.public.register');
    }

    /**
     * Giao diện quản lý danh sách đơn (Admin CRUD)
     */
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

    /**
     * Giao diện chỉnh sửa đơn (Admin CRUD)
     */
    public function adminEdit($id)
    {

        return view('Admission::pages.admin.create', compact('id'));
        // $application = AdmissionApplication::findOrFail($id);
        // return view('Admission::pages.admin.edit', compact('application'));
    }

    /**
     * XỬ LÝ XUẤT PDF - TRẢ VỀ FILE GIỐNG WORD
     */

    public function downloadPdf($id, AdmissionService $service)
    {
        try {
            // 1. Lấy dữ liệu hồ sơ
            $data = $service->getDataForTemplate($id);
            $fileNameBase = 'Don_Dang_Ky_' . str_replace(' ', '_', $data['HoVaTenHocSinh']);

            $tempDir = storage_path('app/admission/');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $wordPath = $tempDir . $fileNameBase . '.docx';
            $pdfPath  = $tempDir . $fileNameBase . '.pdf';

            // 2. KIỂM TRA TỒN TẠI PDF (Ưu tiên số 1)
            if (file_exists($pdfPath)) {
                return response()->download($pdfPath);
            }

            // 3. NẾU CHƯA CÓ PDF, KIỂM TRA WORD
            if (!file_exists($wordPath)) {
                // Chỉ tạo file Word nếu chưa tồn tại
                $templatePath = storage_path('app/templates/application.docx');
                if (!file_exists($templatePath)) {
                    throw new \Exception("Không tìm thấy file mẫu tại: " . $templatePath);
                }

                $templateProcessorClass = 'PhpOffice\\PhpWord\\TemplateProcessor';
                $templateProcessor = new $templateProcessorClass($templatePath);

                foreach ($data as $key => $value) {
                    $templateProcessor->setValue($key, $value);
                }

                $templateProcessor->saveAs($wordPath);
            }

            // 4. CHUYỂN ĐỔI SANG PDF (Sử dụng Symfony Process)
            // Vì libreoffice --convert-to pdf sẽ tự lấy tên file cũ đổi đuôi thành .pdf
            // nên ta cần chạy lệnh convert
            $process = new Process([
                'libreoffice',
                '--headless',
                '--convert-to',
                'pdf',
                '--outdir',
                $tempDir,
                $wordPath
            ]);

            $process->setEnv(['HOME' => $tempDir]);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception('Lỗi chuyển đổi PDF: ' . $process->getErrorOutput());
            }

            // Kiểm tra lại xem file PDF đã thực sự được tạo ra chưa
            if (!file_exists($pdfPath)) {
                throw new \Exception('LibreOffice không tạo được file PDF.');
            }

            // 5. TRẢ VỀ FILE (Không xóa file để lần sau dùng lại làm cache)
            return response()->download($pdfPath);
        } catch (\Exception $e) {
            \Log::error('Lỗi xuất PDF: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi xuất PDF: ' . $e->getMessage());
        }
    }

    public function downloadDocx($id, AdmissionService $service)
    {
        $data = $service->getDataForTemplate($id);

        // Đường dẫn tới tệp Word mẫu bạn đã gửi
        //  $templatePath = storage_path('app/templates/bieumau-2_MergeFields_Full.docx');
        //   $templateProcessor = new TemplateProcessor($templatePath);
        $templateProcessorClass = 'PhpOffice\\PhpWord\\TemplateProcessor';
        $templateProcessor = new $templateProcessorClass(storage_path('app/templates/application.docx'));

        // Điền tất cả dữ liệu vào các biến «...»
        foreach ($data as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // Lưu tệp tạm
        $fileName = 'Don_Dang_Ky_' . $data['HoVaTenHocSinh'] . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'word');
        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {

        Excel::import(
            new GenericImport(
                AdmissionApplication::class,
                ['ma_dinh_danh', 'mhs'] // unique key
            ),
            $request->file('file')
        );
        return back()->with('success', 'Import thành công');
    }
    public function download($id, $type)
    {
        $item = AdmissionApplication::findOrFail($id);

        $path = match ($type) {
            'pdf' => $item->pdf_path,
            'word' => $item->word_path,
            default => null,
        };

        if (!$path || !Storage::disk('local')->exists($path)) {
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
