<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Pharma\Services\MedicineExcelProfileService;

final class PharmaMedicineExcelProfileController extends Controller
{
    private function authorizeAccess(Request $request, ApplicationRegistry $registry): int
    {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.products.excel-config'), 403);

        return (int) $user->id;
    }

    public function index(Request $request, ApplicationRegistry $registry, MedicineExcelProfileService $profiles): View
    {
        $id = $this->authorizeAccess($request, $registry);
        $selected = $request->validate(['profile_id' => ['nullable', 'integer']]);
        $profile = $profiles->forUser($id, $selected['profile_id'] ?? null, 'web');
        $columns = MedicineExcelProfileService::COLUMNS;
        if (! $registry->userCan($request->user('web'), 'client.pharma.products.supplier-pricing')) {
            unset($columns['cost_price'], $columns['supplier_name']);
        }

        return view('ClientPortal::applications.pharma.excel-config', [
            'profile' => $profile,
            'profiles' => $profiles->listForUser($id, 'web'),
            'availableColumns' => $columns,
        ]);
    }

    public function save(Request $request, ApplicationRegistry $registry, MedicineExcelProfileService $profiles): RedirectResponse
    {
        $id = $this->authorizeAccess($request, $registry);
        $data = $request->validate([
            'profile_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string', 'max:80'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['nullable', 'string', 'max:120'],
            'widths' => ['nullable', 'array'],
            'widths.*' => ['nullable', 'integer', 'between:40,400'],
            'alignments' => ['nullable', 'array'],
            'alignments.*' => ['nullable', 'in:left,center,right'],
            'settings' => ['nullable', 'array'],
            'settings.title' => ['nullable', 'string', 'max:200'],
            'settings.company_name' => ['nullable', 'string', 'max:200'],
            'settings.paper_size' => ['nullable', 'in:A4,A3,LETTER'],
            'settings.orientation' => ['nullable', 'in:portrait,landscape'],
            'settings.font_family' => ['nullable', 'in:Times New Roman,Arial,Calibri'],
            'settings.header_font_size' => ['nullable', 'integer', 'between:8,20'],
            'settings.body_font_size' => ['nullable', 'integer', 'between:8,20'],
            'settings.header_fill' => ['nullable', 'in:EFF4FA,F1F5F9,FFFFFF,EDE9FE'],
        ]);
        if (! $registry->userCan($request->user('web'), 'client.pharma.products.supplier-pricing')) {
            abort_if(count(array_intersect($data['columns'], ['cost_price', 'supplier_name'])) > 0, 403);
        }
        $data['is_default'] = $request->boolean('is_default');
        $data['settings'] = array_replace($profiles->defaults()['settings'], $data['settings'] ?? []);
        $data['settings']['header_enabled'] = $request->boolean('header_enabled');
        $data['settings']['body_border'] = $request->boolean('body_border');
        $saved = $profiles->save($id, $data, $data['profile_id'] ?? null, 'web');

        return redirect()->route('client.pharma.products.excel-config', ['profile_id' => $saved['id']])
            ->with('status', 'Đã lưu mẫu Excel.');
    }

    public function importJson(Request $request, ApplicationRegistry $registry, MedicineExcelProfileService $profiles): RedirectResponse
    {
        $id = $this->authorizeAccess($request, $registry);
        $request->validate(['file' => ['required', 'file', 'max:512', 'mimetypes:application/json,text/plain,application/octet-stream']]);
        $data = json_decode(file_get_contents($request->file('file')->getRealPath()), true);
        if (! is_array($data) || ($data['schema_version'] ?? null) !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Tệp JSON cấu hình không hợp lệ hoặc sai phiên bản.']);
        }
        $allowed = MedicineExcelProfileService::COLUMNS;
        if (! $registry->userCan($request->user('web'), 'client.pharma.products.supplier-pricing')) {
            unset($allowed['cost_price'], $allowed['supplier_name']);
        }
        $columns = $data['columns'] ?? null;
        if (! is_array($columns) || $columns === [] || count($columns) > count($allowed) ||
            count(array_unique($columns)) !== count($columns) ||
            array_diff($columns, array_keys($allowed))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Danh sách cột không hợp lệ hoặc không có quyền truy cập.']);
        }
        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'name' => ['required', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
            'headers' => ['required', 'array'],
            'headers.*' => ['nullable', 'string', 'max:120'],
            'widths' => ['required', 'array'],
            'widths.*' => ['required', 'integer', 'between:40,400'],
            'alignments' => ['required', 'array'],
            'alignments.*' => ['required', 'in:left,center,right'],
            'settings' => ['required', 'array'],
            'settings.title' => ['required', 'string', 'max:200'],
            'settings.company_name' => ['nullable', 'string', 'max:200'],
            'settings.paper_size' => ['required', 'in:A4,A3,LETTER'],
            'settings.orientation' => ['required', 'in:landscape,portrait'],
            'settings.header_enabled' => ['required', 'boolean'],
            'settings.font_family' => ['required', 'in:Times New Roman,Arial,Calibri'],
            'settings.header_font_size' => ['required', 'integer', 'between:8,20'],
            'settings.body_font_size' => ['required', 'integer', 'between:8,20'],
            'settings.header_fill' => ['required', 'in:EFF4FA,F1F5F9,FFFFFF,EDE9FE'],
            'settings.body_border' => ['required', 'boolean'],
        ]);
        $validator->validate();
        foreach (['headers', 'widths', 'alignments'] as $field) {
            if (array_diff(array_keys($data[$field]), array_keys($allowed))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Cấu hình chứa cột không được phép.']);
            }
        }
        // Import always creates a new, non-default profile; never overwrites existing templates.
        $data['is_default'] = false;
        $data['name'] = mb_substr($data['name'].' - '.now()->format('YmdHis'), 0, 120);
        $saved = $profiles->save($id, $data, null, 'web');
        return redirect()->route('client.pharma.products.excel-config', ['profile_id' => $saved['id']])
            ->with('status', 'Đã nhập mẫu Excel từ JSON. Kiểm tra và lưu lại nếu cần.');
    }

    public function delete(Request $request, ApplicationRegistry $registry, MedicineExcelProfileService $profiles, int $profile): RedirectResponse
    {
        $id = $this->authorizeAccess($request, $registry);
        $profiles->delete($id, $profile, 'web');

        return redirect()->route('client.pharma.products.excel-config')->with('status', 'Đã xóa mẫu Excel.');
    }
}
