<?php

namespace Modules\Admission\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Admission\Models\AdmissionApplication;
use Modules\Admission\Models\AdmissionImportRun;
use Modules\Admission\Models\AdmissionLocation;
use Modules\Admission\Services\AdmissionApplicationAdminService;
use Modules\Admission\Services\AdmissionDocumentService;
use Modules\Admission\Services\AdmissionImportService;
use Modules\Admission\Services\AdmissionService;
use Throwable;

class AdmissionController extends Controller
{
    public function __construct(
        protected AdmissionService $admissionService,
    ) {}

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

    public function dvhc(Request $request)
    {
        $province = trim($request->string('province')->toString());
        $search = trim($request->string('search')->toString());

        $query = AdmissionLocation::query()
            ->when($province !== '', fn ($query) => $query->where('province_name', $province))
            ->when($search !== '', fn ($query) => $query->where('ward_name', 'like', "%{$search}%"));

        return view('Admission::pages.admin.dvhc', [
            'provinces' => AdmissionLocation::query()
                ->select('province_name')
                ->distinct()
                ->orderBy('province_name')
                ->pluck('province_name'),
            'rows' => (clone $query)->orderBy('ward_name')->limit(200)->get(),
            'totalRows' => (clone $query)->count(),
            'province' => $province,
            'search' => $search,
        ]);
    }

    public function updateDvhcProvince(Request $request)
    {
        $validated = $request->validate([
            'current_province_name' => ['required', 'string', 'max:255'],
            'province_name' => ['required', 'string', 'max:255'],
        ]);

        $currentProvinceName = trim($validated['current_province_name']);
        $provinceName = trim($validated['province_name']);

        $updated = AdmissionLocation::query()
            ->where('province_name', $currentProvinceName)
            ->update(['province_name' => $provinceName]);

        return redirect()
            ->route('admin.admission.dvhc', ['province' => $provinceName])
            ->with('success', "Đã cập nhật {$updated} đơn vị thuộc tỉnh/thành phố.");
    }

    public function updateDvhc(Request $request, AdmissionLocation $location)
    {
        $validated = $request->validate([
            'province_name' => ['required', 'string', 'max:255'],
            'ward_name' => ['required', 'string', 'max:255'],
        ]);

        $provinceName = trim($validated['province_name']);
        $wardName = trim($validated['ward_name']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($location, $provinceName, $wardName): void {
            AdmissionLocation::query()
                ->where('province_code', $location->province_code)
                ->update(['province_name' => $provinceName]);

            $location->update(['ward_name' => $wardName]);
        });

        return redirect()
            ->route('admin.admission.dvhc')
            ->with('success', 'Đã cập nhật tên tỉnh và phường/xã.');
    }

    public function deleteDvhcSelected(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:admission_locations,id'],
        ]);

        $deleted = AdmissionLocation::query()
            ->whereKey($validated['ids'])
            ->delete();

        return redirect()
            ->route('admin.admission.dvhc')
            ->with('success', "Đã xóa {$deleted} đơn vị hành chính.");
    }

    public function search()
    {
        return view('Admission::pages.public.search');
    }

    public function legacySearch()
    {
        return redirect()
            ->route('admission.search')
            ->with('warning', 'Vì lý do bảo mật, vui lòng nhập lại Mã định danh và ngày sinh để tra cứu.');
    }

    public function adminEdit($id)
    {
        return view('Admission::pages.admin.create', compact('id'));
    }

    public function export(Request $request, AdmissionApplicationAdminService $service)
    {
        return $service->downloadExport([
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'class' => $request->string('class')->toString(),
        ]);
    }

    public function downloadPdf($id, AdmissionDocumentService $documents)
    {
        try {
            $result = $documents->generate((int) $id, false, true);

            return response()->download(
                $result['pdf_path'],
                $result['download_name'].'.pdf',
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Lỗi khi xuất PDF: '.$e->getMessage());
        }
    }

    public function downloadDocx($id, AdmissionDocumentService $documents)
    {
        try {
            $result = $documents->generate((int) $id, true, false);

            return response()->download(
                $result['word_path'],
                $result['download_name'].'.docx',
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Lỗi khi xuất Word: '.$e->getMessage());
        }
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
            report($e);

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

    public function clearImportLogs(AdmissionImportService $service)
    {
        $deleted = $service->clearLogs();

        return redirect()
            ->route('admin.admission.imports.index')
            ->with('success', "Đã xóa {$deleted} lịch sử Import và toàn bộ lỗi liên quan.");
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

        return response()->download(storage_path('app/'.$path));
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
