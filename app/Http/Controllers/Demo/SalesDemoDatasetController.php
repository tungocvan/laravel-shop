<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SalesDemoDatasetController extends Controller
{
    private const DIRECTORY = 'demo-datasets/sales-employees';

    public function index(): JsonResponse
    {
        $datasets = [];
        foreach (Storage::disk('local')->files(self::DIRECTORY) as $path) {
            $id = pathinfo($path, PATHINFO_FILENAME);
            if (! Str::endsWith($path, '.json') || ! preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $id)) {
                continue;
            }
            $data = $this->read($path);
            if ($data === null) {
                continue;
            }
            $datasets[] = [
                'id' => $id,
                'name' => is_string($data['datasetName'] ?? null) ? $data['datasetName'] : Str::headline($id),
                'nv' => count($data['nv']),
                'sp' => count($data['sp']),
                'ban' => count($data['ban']),
            ];
        }
        usort($datasets, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return response()->json(['datasets' => $datasets])->header('Cache-Control', 'no-store');
    }

    public function show(string $dataset): JsonResponse
    {
        abort_unless(preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $dataset), 404);
        $data = $this->read(self::DIRECTORY.'/'.$dataset.'.json');
        abort_if($data === null, 404);
        return response()->json($data)->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        // Reuse the existing warehouse demo operator code; never expose it to public clients.
        $secret = config('warehouse_demo.backup_code');
        abort_unless(is_string($secret) && strlen($secret) >= 16, 503);
        abort_unless(hash_equals($secret, (string) $request->header('X-Demo-Backup-Code', '')), 403);
        $maxBytes = (int) config('warehouse_demo.max_bytes', 10485760);
        abort_if(strlen($request->getContent()) > $maxBytes, 413);

        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'data' => ['required', 'array'],
            'data.nv' => ['required', 'array'],
            'data.sp' => ['required', 'array'],
            'data.ban' => ['required', 'array'],
        ])->validate();
        $data = $validated['data'];
        if (! $this->valid($data)) {
            return response()->json(['message' => 'Dữ liệu doanh số không hợp lệ.'], 422);
        }
        $disk = Storage::disk('local');
        if (count($disk->files(self::DIRECTORY)) >= (int) config('warehouse_demo.max_datasets', 100)) {
            return response()->json(['message' => 'Thư viện đã đạt giới hạn lưu trữ.'], 409);
        }
        $name = trim($validated['name']);
        $data['datasetName'] = $name;
        $data['updated'] = time() * 1000;
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        abort_if(strlen($json) > $maxBytes, 413);
        $slug = substr(Str::slug($name) ?: 'demo', 0, 40);
        $id = $slug.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6));
        if (! $disk->put(self::DIRECTORY.'/'.$id.'.json', $json)) {
            return response()->json(['message' => 'Không thể lưu JSON lên server.'], 500);
        }
        return response()->json(['id' => $id, 'name' => $name], 201)->header('Cache-Control', 'no-store');
    }

    private function valid(array $data): bool
    {
        foreach (['nv', 'sp', 'ban'] as $table) {
            if (! isset($data[$table]) || ! is_array($data[$table]) || ! array_is_list($data[$table])) {
                return false;
            }
            $ids = [];
            foreach ($data[$table] as $row) {
                if (! is_array($row) || ! isset($row['id']) || ! is_string($row['id'])
                    || $row['id'] === '' || isset($ids[$row['id']])) {
                    return false;
                }
                $ids[$row['id']] = true;
            }
        }
        return true;
    }

    private function read(string $path): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($path) || $disk->size($path) > (int) config('warehouse_demo.max_bytes', 10485760)) {
            return null;
        }
        try {
            $data = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }
        return is_array($data) && $this->valid($data) ? $data : null;
    }
}
