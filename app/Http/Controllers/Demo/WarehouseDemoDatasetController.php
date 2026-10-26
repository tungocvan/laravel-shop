<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
                'name' => $this->displayName($id),
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

        if (! is_array($data) || ! isset($data['products'], $data['txns'])
            || ! is_array($data['products']) || ! array_is_list($data['products'])
            || ! is_array($data['txns']) || ! array_is_list($data['txns'])) {
            return null;
        }

        foreach ($data['products'] as $product) {
            if (! is_array($product) || ! isset($product['id'], $product['code'], $product['name'])
                || ! is_string($product['id']) || ! is_string($product['code']) || ! is_string($product['name'])) {
                return null;
            }
        }

        foreach ($data['txns'] as $txn) {
            if (! is_array($txn) || ! isset($txn['pid'], $txn['type'], $txn['qty'])
                || ! in_array($txn['type'], ['in', 'out'], true)
                || ! is_numeric($txn['qty']) || (float) $txn['qty'] <= 0) {
                return null;
            }
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
