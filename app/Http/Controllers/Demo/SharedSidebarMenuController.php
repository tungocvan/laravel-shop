<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SharedSidebarMenuController extends Controller
{
    private const DEMOS = ['doanh-so-nhan-vien' => 'sales-employees', 'quan-ly-xuat-nhap-ton' => 'warehouse-inventory', 'quan-ly-nhan-su' => 'human-resources', 'cham-cong-tinh-luong' => 'attendance-payroll', 'bao-cao-quan-tri-doanh-nghiep' => 'enterprise-management-report', 'bao-cao-cong-viec-hang-ngay' => 'daily-work-report'];

    private function path(string $demo): string
    {
        abort_unless(array_key_exists($demo, self::DEMOS), 404);
        return 'demo-settings/'.self::DEMOS[$demo].'/sidebar-menu.json';
    }

    private function presets(string $demo): string
    {
        $this->path($demo);
        return 'demo-settings/'.self::DEMOS[$demo].'/presets';
    }

    public function show(string $demo): JsonResponse
    {
        $path = $this->path($demo);
        $disk = Storage::disk('local');
        return response()->json(['config' => $disk->exists($path) ? json_decode($disk->get($path), true) : null]);
    }

    public function store(Request $request, string $demo): JsonResponse
    {
        $path = $this->path($demo);
        if ($error = $this->authorizeWrite($request)) return $error;
        if (strlen($request->getContent()) > 65536) return response()->json(['message' => 'JSON quá lớn.'], 413);
        $validated = Validator::make($request->all(), ['config' => ['required', 'array']])->validate();
        $validated['config'] = $this->normalizeOptionalHrefs($validated['config']);
        if ($reason = $this->configError($validated['config'])) return response()->json(['message' => 'Menu không hợp lệ.', 'field' => $reason], 422);
        $json = json_encode($validated['config'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (strlen($json) > 65536) return response()->json(['message' => 'JSON quá lớn.'], 413);
        Storage::disk('local')->put($path, $json);
        return response()->json(['ok' => true]);
    }

    public function index(string $demo): JsonResponse
    {
        $disk = Storage::disk('local');
        $dir = $this->presets($demo);
        $items = [];
        foreach ($disk->files($dir) as $file) {
            if (! str_ends_with($file, '.json')) continue;
            $data = json_decode($disk->get($file), true);
            if (! is_array($data) || ! $this->validConfig($data['config'] ?? null)) continue;
            $items[] = ['id' => basename($file, '.json'), 'name' => $data['name'] ?? basename($file, '.json')];
        }
        return response()->json(['presets' => $items]);
    }

    public function preset(string $demo, string $preset): JsonResponse
    {
        $dir = $this->presets($demo);
        abort_unless(preg_match('/^[a-z0-9-]{1,80}$/', $preset), 404);
        $file = $dir.'/'.$preset.'.json';
        abort_unless(Storage::disk('local')->exists($file), 404);
        $data = json_decode(Storage::disk('local')->get($file), true);
        abort_unless(is_array($data) && $this->validConfig($data['config'] ?? null), 404);
        return response()->json($data);
    }

    public function savePreset(Request $request, string $demo): JsonResponse
    {
        $dir = $this->presets($demo);
        if ($error = $this->authorizeWrite($request)) return $error;
        $v = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'config' => ['required', 'array'],
        ])->validate();
        $v['config'] = $this->normalizeOptionalHrefs($v['config']);
        if ($reason = $this->configError($v['config'])) return response()->json(['message' => 'Menu không hợp lệ.', 'field' => $reason], 422);
        $id = substr(Str::slug($v['name']) ?: 'menu', 0, 40).'-'.Str::lower(Str::random(10));
        $json = json_encode(['name' => $v['name'], 'config' => $v['config']], JSON_UNESCAPED_UNICODE);
        if (strlen($json) > 65536) return response()->json(['message' => 'JSON quá lớn.'], 413);
        $disk = Storage::disk('local');
        foreach ($disk->files($dir) as $existingPath) {
            $existing = json_decode($disk->get($existingPath), true);
            if (is_array($existing) && mb_strtolower(trim((string) ($existing['name'] ?? ''))) === mb_strtolower(trim($v['name']))) {
                return response()->json(['message' => 'Tên cấu hình đã tồn tại. Hãy chọn file để ghi đè.', 'id' => pathinfo($existingPath, PATHINFO_FILENAME)], 409);
            }
        }
        if (count($disk->files($dir)) >= 100) return response()->json(['message' => 'Đã đạt giới hạn.'], 422);
        $disk->put($dir.'/'.$id.'.json', $json);
        return response()->json(['id' => $id], 201);
    }

    public function updatePreset(Request $request, string $demo, string $preset): JsonResponse
    {
        $dir = $this->presets($demo);
        if ($error = $this->authorizeWrite($request)) return $error;
        if (strlen($request->getContent()) > 65536) return response()->json(['message' => 'JSON quá lớn.'], 413);
        abort_unless(preg_match('/^[a-z0-9-]{1,80}$/', $preset), 404);
        $file = $dir.'/'.$preset.'.json';
        $disk = Storage::disk('local');
        abort_unless($disk->exists($file), 404);
        $old = json_decode($disk->get($file), true);
        abort_unless(is_array($old) && is_string($old['name'] ?? null), 409);
        $validated = Validator::make($request->all(), ['config' => ['required', 'array']])->validate();
        $config = $this->normalizeOptionalHrefs($validated['config']);
        if ($reason = $this->configError($config)) return response()->json(['message' => 'Menu không hợp lệ.', 'field' => $reason], 422);
        $json = json_encode(['name' => $old['name'], 'config' => $config], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($json) > 65536) return response()->json(['message' => 'JSON quá lớn.'], 413);
        abort_unless($disk->put($file, $json), 500);
        return response()->json(['updated' => true, 'id' => $preset]);
    }

    public function deletePreset(Request $request, string $demo, string $preset): JsonResponse
    {
        $dir = $this->presets($demo);
        if ($error = $this->authorizeWrite($request)) return $error;
        abort_unless(preg_match('/^[a-z0-9-]{1,80}$/', $preset), 404);
        $file = $dir.'/'.$preset.'.json';
        abort_unless(Storage::disk('local')->exists($file), 404);
        Storage::disk('local')->delete($file);
        return response()->json(['ok' => true]);
    }

    private function authorizeWrite(Request $request): ?JsonResponse
    {
        $secret = config('warehouse_demo.backup_code');
        if (! is_string($secret) || strlen($secret) < 16) return response()->json(['message' => 'Chưa cấu hình mã quản trị.'], 503);
        $given = $request->header('X-Demo-Backup-Code', '');
        if (! is_string($given) || ! hash_equals($secret, $given)) return response()->json(['message' => 'Sai mã quản trị.'], 403);
        return null;
    }

    /**
     * Laravel's ConvertEmptyStringsToNull middleware converts optional blank
     * links in JSON requests to null. Restore only these two schema-defined
     * optional fields; do not weaken URL or required item validation.
     */
    private function normalizeOptionalHrefs(array $config): array
    {
        foreach (['header' => ['href', 'subtitle'], 'footer' => ['href', 'credit']] as $section => $fields) {
            if (! isset($config[$section]) || ! is_array($config[$section])) continue;
            foreach ($fields as $field) {
                if (array_key_exists($field, $config[$section]) && $config[$section][$field] === null) {
                    $config[$section][$field] = '';
                }
            }
        }

        return $config;
    }

    private function validConfig(mixed $config): bool
    {
        return $this->configError($config) === null;
    }

    private function configError(mixed $config): ?string
    {
        if (! is_array($config)) return 'config';
        if (($config['version'] ?? null) !== 1) return 'version';
        if (! isset($config['groups']) || ! is_array($config['groups']) || count($config['groups']) > 20) return 'groups';

        foreach (['header' => ['title', 'subtitle', 'icon', 'href'], 'footer' => ['credit', 'href']] as $section => $fields) {
            if (! isset($config[$section]) || ! is_array($config[$section])) return $section;
            foreach ($fields as $field) {
                if (! is_string($config[$section][$field] ?? null) || strlen($config[$section][$field]) > 250) return $section.'.'.$field;
            }
            if (! $this->validHref($config[$section]['href'], true)) return $section.'.href';
        }
        if (! preg_match('/^[a-z0-9_-]{1,48}$/', $config['header']['icon'])) return 'header.icon';
        if (! is_bool($config['footer']['showSave'] ?? null)) return 'footer.showSave';

        $ids = [];
        $count = 0;
        foreach ($config['groups'] as $gi => $group) {
            if (! is_array($group) || ! is_string($group['title'] ?? null) || mb_strlen($group['title']) > 60 || ! is_array($group['items'] ?? null)) return 'groups.'.$gi;
            foreach ($group['items'] as $ii => $item) {
                $field = 'groups.'.$gi.'.items.'.$ii;
                if (++$count > 100 || ! is_array($item)) return $field;
                $id = $item['id'] ?? null;
                if (! is_string($id) || ! preg_match('/^[a-z0-9_-]{1,64}$/', $id) || isset($ids[$id])) return $field.'.id';
                $ids[$id] = true;
                if (! is_string($item['label'] ?? null) || trim($item['label']) === '' || mb_strlen($item['label']) > 80) return $field.'.label';
                if (! is_string($item['icon'] ?? null) || ! preg_match('/^[a-z0-9_-]{1,48}$/', $item['icon'])) return $field.'.icon';
                if (! is_string($item['href'] ?? null) || ! $this->validHref($item['href'])) return $field.'.href';
            }
        }
        return null;
    }

    private function validHref(string $href, bool $empty = false): bool
    {
        return ($empty && $href === '') || (bool) preg_match('/^#[a-z0-9_-]{1,64}$/', $href) ||
            (strlen($href) <= 250 && (bool) preg_match('~^https://[a-z0-9][a-z0-9.-]+(?:/[^\\s]*)?$~i', $href));
    }
}
