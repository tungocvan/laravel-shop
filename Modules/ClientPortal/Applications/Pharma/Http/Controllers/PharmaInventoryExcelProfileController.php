<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Pharma\Models\MedicineExcelProfile;

final class PharmaInventoryExcelProfileController extends Controller
{
    public const COLUMNS = [
        'medicine_code' => 'Mã thuốc', 'name' => 'Tên thuốc', 'batch_number' => 'Số lô',
        'expiry_date' => 'Hạn dùng', 'quantity_on_hand' => 'Tồn hiện tại',
        'average_cost_price' => 'Giá vốn', 'inventory_value' => 'Giá trị tồn',
        'period_opening' => 'Tồn đầu kỳ', 'period_in' => 'Nhập trong kỳ',
        'period_out' => 'Xuất trong kỳ', 'period_closing' => 'Tồn cuối kỳ',
    ];

    public static function allowed(string $report, bool $costs): array
    {
        $keys = $report === 'stock'
            ? ['medicine_code','name','batch_number','expiry_date','quantity_on_hand','average_cost_price','inventory_value']
            : ['medicine_code','name','batch_number','expiry_date','period_opening','period_in','period_out','period_closing'];
        if (! $costs) {
            $keys = array_diff($keys, ['average_cost_price', 'inventory_value']);
        }
        return array_intersect_key(self::COLUMNS, array_flip($keys));
    }

    public static function guard(string $report): string
    {
        abort_unless(in_array($report, ['stock','movements'], true), 404);
        return 'web-inventory-'.$report;
    }

    private function user(Request $request, ApplicationRegistry $registry): int
    {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.inventory.view'), 403);
        return (int) $user->id;
    }

    public static function profile(int $userId, string $report, ?int $id = null): ?MedicineExcelProfile
    {
        $query = MedicineExcelProfile::query()->where('user_id', $userId)->where('owner_guard', self::guard($report));
        return $id ? $query->findOrFail($id) : $query->orderByDesc('is_default')->orderBy('id')->first();
    }

    public function index(Request $request, ApplicationRegistry $registry, string $report): View
    {
        $id = $this->user($request, $registry);
        $selected = $request->validate(['profile_id' => 'nullable|integer']);
        $profile = self::profile($id, $report, $selected['profile_id'] ?? null);
        return view('ClientPortal::applications.pharma.inventory-excel-config', [
            'report' => $report,
            'profile' => $profile,
            'profiles' => MedicineExcelProfile::query()->where('user_id', $id)->where('owner_guard', self::guard($report))->orderByDesc('is_default')->get(),
            'columns' => self::allowed($report, $registry->userCan($request->user('web'), 'client.pharma.inventory.costs')),
        ]);
    }

    public function save(Request $request, ApplicationRegistry $registry, string $report): RedirectResponse
    {
        $id = $this->user($request, $registry);
        $allowed = self::allowed($report, $registry->userCan($request->user('web'), 'client.pharma.inventory.costs'));
        $data = $request->validate([
            'profile_id' => 'nullable|integer', 'name' => 'required|string|max:120',
            'columns' => 'required|array|min:1', 'columns.*' => 'required|string|distinct',
            'headers' => 'nullable|array', 'headers.*' => 'nullable|string|max:120',
            'widths' => 'nullable|array', 'widths.*' => 'nullable|integer|between:40,400',
            'alignments' => 'nullable|array', 'alignments.*' => 'nullable|in:left,center,right',
            'settings' => 'nullable|array', 'settings.title' => 'nullable|string|max:200',
            'settings.company_name' => 'nullable|string|max:200',
            'settings.paper_size' => 'nullable|in:A4,A3,LETTER',
            'settings.orientation' => 'nullable|in:portrait,landscape',
            'settings.font_family' => 'nullable|in:Times New Roman,Arial,Calibri',
        ]);
        if (array_diff($data['columns'], array_keys($allowed))) {
            throw ValidationException::withMessages(['columns' => 'Cột xuất không hợp lệ hoặc không có quyền truy cập.']);
        }
        foreach (['headers','widths','alignments'] as $key) {
            if (array_diff(array_keys($data[$key] ?? []), array_keys($allowed))) {
                throw ValidationException::withMessages([$key => 'Cấu hình chứa cột không được phép.']);
            }
        }
        $guard = self::guard($report);
        $query = MedicineExcelProfile::query()->where('user_id', $id)->where('owner_guard', $guard);
        $profile = isset($data['profile_id']) ? (clone $query)->findOrFail($data['profile_id']) : new MedicineExcelProfile();
        if ((clone $query)->where('name', $data['name'])->when($profile->exists, fn ($q) => $q->whereKeyNot($profile->id))->exists()) {
            throw ValidationException::withMessages(['name' => 'Tên mẫu đã tồn tại.']);
        }
        $makeDefault = $request->boolean('is_default') || ! (clone $query)->exists();
        if ($makeDefault) (clone $query)->update(['is_default' => false]);
        $settings = array_replace([
            'title' => $report === 'stock' ? 'BÁO CÁO TỒN KHO' : 'BÁO CÁO XUẤT NHẬP TỒN',
            'company_name' => '', 'paper_size' => 'A4', 'orientation' => 'landscape',
            'font_family' => 'Times New Roman', 'header_font_size' => 12, 'body_font_size' => 11,
            'header_fill' => 'EFF4FA', 'body_border' => true,
        ], $data['settings'] ?? []);
        $profile->forceFill([
            'user_id' => $id, 'owner_guard' => $guard, 'name' => $data['name'],
            'is_default' => $makeDefault || (bool) $profile->is_default,
            'columns' => $data['columns'],
            'headers' => array_replace($allowed, array_intersect_key($data['headers'] ?? [], $allowed)),
            'widths' => array_replace(array_fill_keys(array_keys($allowed), 130), $data['widths'] ?? []),
            'alignments' => array_replace(array_fill_keys(array_keys($allowed), 'left'), $data['alignments'] ?? []),
            'settings' => $settings,
        ])->save();
        return redirect()->route('client.pharma.inventory.excel-config', ['report' => $report, 'profile_id' => $profile->id])->with('status', 'Đã lưu mẫu Excel.');
    }

    public function delete(Request $request, ApplicationRegistry $registry, string $report, int $profile): RedirectResponse
    {
        $id = $this->user($request, $registry);
        $record = self::profile($id, $report, $profile);
        $wasDefault = $record->is_default;
        $record->delete();
        if ($wasDefault) {
            MedicineExcelProfile::query()->where('user_id', $id)->where('owner_guard', self::guard($report))->orderBy('id')->first()?->update(['is_default' => true]);
        }
        return redirect()->route('client.pharma.inventory.excel-config', ['report' => $report])->with('status', 'Đã xóa mẫu Excel.');
    }
}
