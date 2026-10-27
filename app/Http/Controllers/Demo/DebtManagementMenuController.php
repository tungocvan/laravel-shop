<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DebtManagementMenuController extends Controller
{
    private const PATH = 'demo-settings/debt-management/sidebar-menu.json';

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
        $secret = config('warehouse_demo.backup_code');
        abort_unless(is_string($secret) && strlen($secret) >= 16, 503);
        abort_unless(hash_equals($secret, (string) $request->header('X-Demo-Backup-Code', '')), 403);
        abort_if(strlen($request->getContent()) > 65536, 413);

        $payload = Validator::make($request->all(), [
            'config' => ['required', 'array'],
        ])->validate()['config'];

        if (! $this->valid($payload)) {
            return response()->json(['message' => 'Cấu hình menu không hợp lệ.'], 422);
        }
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (! Storage::disk('local')->put(self::PATH, $json)) {
            return response()->json(['message' => 'Không thể lưu menu trên server.'], 500);
        }

        return response()->json(['saved' => true])->header('Cache-Control', 'no-store');
    }

    private function valid(array $config): bool
    {
        if (($config['version'] ?? null) !== 1 || ! isset($config['groups']) || ! is_array($config['groups'])
            || ! array_is_list($config['groups']) || count($config['groups']) > 20) {
            return false;
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
                if (! preg_match('/^[a-z0-9-]{1,64}$/', $item['id']) || isset($ids[$item['id']])
                    || mb_strlen($item['label']) < 1 || mb_strlen($item['label']) > 80
                    || ! preg_match('/^[a-z0-9-]{1,48}$/', $item['icon'])
                    || mb_strlen($item['href']) > 250
                    || ! (preg_match('/^#[a-z0-9-]{1,64}$/', $item['href'])
                        || preg_match('~^https://[a-z0-9][a-z0-9.-]+(?:/[^\\s]*)?$~i', $item['href']))) {
                    return false;
                }
                $ids[$item['id']] = true;
            }
        }

        return true;
    }
}
