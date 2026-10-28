<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EnterpriseManagementReportMenuController extends Controller
{
    private const PATH = 'demo-settings/enterprise-management-report/sidebar-menu.json';
    private const PRESETS = 'demo-settings/enterprise-management-report/presets';

    public function show(): JsonResponse
    {
        $disk = Storage::disk('local');
        if (! $disk->exists(self::PATH)) {
            return response()->json(['config' => null])->header('Cache-Control', 'no-store');
        }
        try {
            $data = json_decode($disk->get(self::PATH), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return response()->json(['config' => null])->header('Cache-Control', 'no-store');
        }

        return response()->json(['config' => is_array($data) && $this->valid($data) ? $data : null])
            ->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeWrite($request);

        $payload = Validator::make($request->all(), [
            'config' => ['required', 'array'],
        ])->validate()['config'];

        $payload = $this->normalizeOptionalText($payload);
        if (! $this->valid($payload)) {
            return response()->json(['message' => 'Cấu hình menu không hợp lệ.'], 422);
        }
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($json) > 65536) { return response()->json(['message' => 'JSON quá lớn.'], 413); }
        if (! Storage::disk('local')->put(self::PATH, $json)) {
            return response()->json(['message' => 'Không thể lưu menu trên server.'], 500);
        }

        return response()->json(['saved' => true])->header('Cache-Control', 'no-store');
    }

    public function presets(): JsonResponse
    {
        $disk = Storage::disk('local');
        $items = [];
        foreach ($disk->files(self::PRESETS) as $path) {
            $id = pathinfo($path, PATHINFO_FILENAME);
            if (! preg_match('/^[a-z0-9-]{1,80}$/', $id) || ! Str::endsWith($path, '.json')) {
                continue;
            }
            try {
                $payload = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                continue;
            }
            if (! is_array($payload) || ! $this->valid($payload['config'] ?? [])) {
                continue;
            }
            $items[] = ['id' => $id, 'name' => $payload['name'] ?? $id];
        }

        return response()->json(['presets' => $items])->header('Cache-Control', 'no-store');
    }

    public function preset(string $preset): JsonResponse
    {
        abort_unless(preg_match('/^[a-z0-9-]{1,80}$/', $preset), 404);
        $path = self::PRESETS.'/'.$preset.'.json';
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        try {
            $payload = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            abort(404);
        }
        abort_unless(is_array($payload) && $this->valid($payload['config'] ?? []), 404);

        return response()->json(['config' => $payload['config'], 'name' => $payload['name'] ?? $preset])
            ->header('Cache-Control', 'no-store');
    }

    public function savePreset(Request $request): JsonResponse
    {
        $this->authorizeWrite($request);
        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'config' => ['required', 'array'],
        ])->validate();
        $validated['config'] = $this->normalizeOptionalText($validated['config']);
        abort_unless($this->valid($validated['config']), 422);
        $disk = Storage::disk('local');
        // Never create another preset with an existing display name.
        foreach ($disk->files(self::PRESETS) as $existingPath) {
            try {
                $existing = json_decode($disk->get($existingPath), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                continue;
            }
            if (is_array($existing) && isset($existing['name'])
                && mb_strtolower(trim((string) $existing['name'])) === mb_strtolower(trim($validated['name']))) {
                return response()->json(['message' => 'Tên cấu hình đã tồn tại. Hãy chọn file và xác nhận cập nhật.', 'id' => pathinfo($existingPath, PATHINFO_FILENAME)], 409);
            }
        }
        abort_if(count($disk->files(self::PRESETS)) >= 100, 409);
        $id = substr(Str::slug($validated['name']) ?: 'menu', 0, 40).'-'.Str::lower(Str::random(10));
        $json = json_encode(['name' => $validated['name'], 'config' => $validated['config']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        abort_if(strlen($json) > 65536, 413);
        abort_unless($disk->put(self::PRESETS.'/'.$id.'.json', $json), 500);

        return response()->json(['id' => $id, 'name' => $validated['name']], 201);
    }

    public function updatePreset(Request $request, string $preset): JsonResponse
    {
        $this->authorizeWrite($request);
        abort_unless(preg_match('/^[a-z0-9-]{1,80}$/', $preset), 404);
        $path = self::PRESETS.'/'.$preset.'.json';
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        $validated = Validator::make($request->all(), [
            'config' => ['required', 'array'],
        ])->validate();
        $config = $this->normalizeOptionalText($validated['config']);
        abort_unless($this->valid($config), 422);
        try {
            $existing = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            abort(409, 'File JSON hiện tại không hợp lệ.');
        }
        abort_unless(is_array($existing) && is_string($existing['name'] ?? null), 409);
        $json = json_encode(['name' => $existing['name'], 'config' => $config], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        abort_if(strlen($json) > 65536, 413);
        abort_unless($disk->put($path, $json), 500);

        return response()->json(['updated' => true, 'id' => $preset, 'name' => $existing['name']])
            ->header('Cache-Control', 'no-store');
    }

    public function deletePreset(Request $request, string $preset): JsonResponse
    {
        $this->authorizeWrite($request);
        abort_unless(preg_match('/^[a-z0-9-]{1,80}$/', $preset), 404);
        $path = self::PRESETS.'/'.$preset.'.json';
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        abort_unless($disk->delete($path), 500);

        return response()->json(['deleted' => true, 'id' => $preset])
            ->header('Cache-Control', 'no-store');
    }

    private function authorizeWrite(Request $request): void
    {
        $secret = config('warehouse_demo.backup_code');
        abort_unless(is_string($secret) && strlen($secret) >= 16, 503);
        abort_unless(hash_equals($secret, (string) $request->header('X-Demo-Backup-Code', '')), 403);
        abort_if(strlen($request->getContent()) > 65536, 413);
    }

    /**
     * Laravel's ConvertEmptyStringsToNull middleware converts optional blank
     * form fields to null. Restore only known optional text fields, not IDs,
     * icons or links in menu items.
     */
    private function normalizeOptionalText(array $config): array
    {
        foreach (['header' => ['subtitle', 'href'], 'footer' => ['credit', 'href']] as $section => $fields) {
            if (! isset($config[$section]) || ! is_array($config[$section])) {
                continue;
            }
            foreach ($fields as $field) {
                if (array_key_exists($field, $config[$section]) && $config[$section][$field] === null) {
                    $config[$section][$field] = '';
                }
            }
        }

        return $config;
    }

    private function safeHref(string $href): bool
    {
        return $href === '' || (bool) preg_match('/^#[a-z0-9_-]{1,64}$/', $href)
            || (bool) preg_match('~^https://[a-z0-9][a-z0-9.-]+(?:/[^\\s]*)?$~i', $href);
    }

    private function valid(array $config): bool
    {
        if (($config['version'] ?? null) !== 1 || ! isset($config['groups']) || ! is_array($config['groups'])
            || ! array_is_list($config['groups']) || count($config['groups']) > 20) {
            return false;
        }
        foreach (['header', 'footer'] as $section) {
            if (! isset($config[$section])) {
                continue; // Backward compatible with v1 menu JSON.
            }
            if (! is_array($config[$section])) {
                return false;
            }
            $fields = $section === 'header'
                ? ['title' => 100, 'subtitle' => 160, 'icon' => 48, 'href' => 250]
                : ['credit' => 160, 'href' => 250];
            foreach ($fields as $field => $limit) {
                if (! array_key_exists($field, $config[$section]) || ! is_string($config[$section][$field])
                    || mb_strlen($config[$section][$field]) > $limit) {
                    return false;
                }
            }
            if (! preg_match('/^[a-z0-9_-]{1,48}$/', $config['header']['icon'] ?? 'menu')
                || ! $this->safeHref($config[$section]['href'])) {
                return false;
            }
            
        }
        $ids = [];
        $count = 0;
        foreach ($config['groups'] as $group) {
            if (! is_array($group) || ! isset($group['title'], $group['items'])
                || ! is_string($group['title']) || mb_strlen($group['title']) > 60
                || ! is_array($group['items']) || ! array_is_list($group['items'])) {
                return false;
            }
            foreach ($group['items'] as $item) {
                $count++;
                if ($count > 100 || ! is_array($item)) {
                    return false;
                }
                foreach (['id', 'label', 'icon', 'href'] as $key) {
                    if (! isset($item[$key]) || ! is_string($item[$key])) {
                        return false;
                    }
                }
                if (! preg_match('/^[a-z0-9_-]{1,64}$/', $item['id']) || isset($ids[$item['id']])
                    || mb_strlen($item['label']) < 1 || mb_strlen($item['label']) > 80
                    || ! preg_match('/^[a-z0-9_-]{1,48}$/', $item['icon'])
                    || mb_strlen($item['href']) > 250
                    || ! (preg_match('/^#[a-z0-9_-]{1,64}$/', $item['href'])
                        || preg_match('~^https://[a-z0-9][a-z0-9.-]+(?:/[^\\s]*)?$~i', $item['href']))) {
                    return false;
                }
                $ids[$item['id']] = true;
            }
        }

        return true;
    }
}
