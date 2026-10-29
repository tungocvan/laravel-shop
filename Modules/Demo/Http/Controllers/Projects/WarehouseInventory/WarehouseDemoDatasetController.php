<?php

namespace Modules\Demo\Http\Controllers\Projects\WarehouseInventory;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WarehouseDemoDatasetController extends Controller
{
    private const DIRECTORY = 'demo-datasets/warehouse-inventory';

    public function index(): JsonResponse
    {
        $disk = Storage::disk('local');
        $datasets = [];

        foreach ($disk->files(self::DIRECTORY) as $path) {
            if (! Str::endsWith($path, '.json')) {
                continue;
            }

            $id = pathinfo($path, PATHINFO_FILENAME);
            if (! preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $id)) {
                continue;
            }

            $data = $this->readDataset($path);
            if ($data === null) {
                continue;
            }

            $datasets[] = [
                'id' => $id,
                'name' => isset($data['datasetName']) && is_string($data['datasetName']) ? $data['datasetName'] : $this->displayName($id),
                'products' => count($data['products']),
                'txns' => count($data['txns']),
            ];
        }

        usort($datasets, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        return response()->json(['datasets' => $datasets])
            ->header('Cache-Control', 'no-store');
    }

    public function show(string $dataset): JsonResponse
    {
        abort_unless(preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $dataset), 404);

        $path = self::DIRECTORY.'/'.$dataset.'.json';
        $data = $this->readDataset($path);
        abort_if($data === null, 404);

        return response()->json($data)->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        $secret = config('warehouse_demo.backup_code');
        // Fail closed unless the operator configured a nontrivial server-side secret.
        abort_unless(is_string($secret) && strlen($secret) >= 16, 503);

        $provided = $request->header('X-Demo-Backup-Code', '');
        abort_unless(is_string($provided) && hash_equals($secret, $provided), 403);

        $maxBytes = (int) config('warehouse_demo.max_bytes', 10485760);
        abort_if(strlen($request->getContent()) > $maxBytes, 413);

        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'data' => ['required', 'array'],
            'data.products' => ['required', 'array'],
            'data.txns' => ['required', 'array'],
        ])->validate();

        $data = $validated['data'];
        if (! $this->isValidDataset($data)) {
            return response()->json(['message' => 'Cấu trúc dữ liệu kho không hợp lệ.'], 422);
        }

        $disk = Storage::disk('local');
        if (count($disk->files(self::DIRECTORY)) >= (int) config('warehouse_demo.max_datasets', 100)) {
            return response()->json(['message' => 'Thư viện đã đạt giới hạn bản sao lưu.'], 409);
        }

        $name = trim($validated['name']);
        $data['datasetName'] = $name;
        $data['updated'] = time() * 1000;
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        abort_if(strlen($json) > $maxBytes, 413);

        $slug = Str::slug($name) ?: 'demo';
        $slug = substr($slug, 0, 40);
        $id = $slug.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6));
        $path = self::DIRECTORY.'/'.$id.'.json';

        if (! $disk->put($path, $json)) {
            return response()->json(['message' => 'Không thể lưu file JSON trên server.'], 500);
        }

        return response()->json([
            'id' => $id,
            'name' => $name,
            'products' => count($data['products']),
            'txns' => count($data['txns']),
        ], 201)->header('Cache-Control', 'no-store');
    }

    private function isValidDataset(array $data): bool
    {
        if (! isset($data['products'], $data['txns'])
            || ! is_array($data['products']) || ! array_is_list($data['products'])
            || ! is_array($data['txns']) || ! array_is_list($data['txns'])) {
            return false;
        }

        $ids = [];
        foreach ($data['products'] as $product) {
            if (! is_array($product) || ! isset($product['id'], $product['code'], $product['name'])
                || ! is_string($product['id']) || $product['id'] === ''
                || ! is_string($product['code']) || ! is_string($product['name'])
                || isset($ids[$product['id']])) {
                return false;
            }
            $ids[$product['id']] = true;
        }

        foreach ($data['txns'] as $txn) {
            if (! is_array($txn) || ! isset($txn['pid'], $txn['type'], $txn['qty'])
                || ! is_string($txn['pid']) || ! isset($ids[$txn['pid']])
                || ! in_array($txn['type'], ['in', 'out'], true)
                || ! is_numeric($txn['qty']) || ! is_finite((float) $txn['qty'])
                || (float) $txn['qty'] <= 0) {
                return false;
            }
        }

        return true;
    }

    private function readDataset(string $path): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($path) || $disk->size($path) > 10 * 1024 * 1024) {
            return null;
        }

        try {
            $data = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return null;
        }

        if (! is_array($data) || ! $this->isValidDataset($data)) {
            return null;
        }

        return $data;
    }

    private function displayName(string $id): string
    {
        return match ($id) {
            'dien-thoai-2026' => 'Ngành điện thoại (2026)',
            default => Str::headline(str_replace('-', ' ', $id)),
        };
    }
}
