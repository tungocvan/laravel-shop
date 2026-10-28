<?php
namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class HumanResourcesDatasetController extends Controller
{
    private const DIR = 'demo-datasets/human-resources';
    private const LIMIT = 10485760;
    private const MAX_FILES = 100;

    public function index(): JsonResponse
    {
        $items = [];
        $disk = Storage::disk('local');
        foreach ($disk->files(self::DIR) as $path) {
            $id = pathinfo($path, PATHINFO_FILENAME);
            if (!preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $id) || !str_ends_with($path, '.json')) continue;
            $payload = $this->read($path);
            if ($payload === null) continue;
            $items[] = ['id' => $id, 'name' => $payload['name'], 'employees' => count($payload['data'])];
        }
        usort($items, fn($a, $b) => strcmp($a['name'], $b['name']));
        return response()->json(['datasets' => $items])->header('Cache-Control', 'no-store');
    }

    public function show(string $dataset): JsonResponse
    {
        abort_unless(preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $dataset), 404);
        $payload = $this->read(self::DIR.'/'.$dataset.'.json');
        abort_if($payload === null, 404);
        return response()->json($payload)->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        $secret = config('warehouse_demo.backup_code');
        abort_unless(is_string($secret) && strlen($secret) >= 16, 503);
        $given = $request->header('X-Demo-Backup-Code', '');
        abort_unless(is_string($given) && hash_equals($secret, $given), 403);
        abort_if(strlen($request->getContent()) > self::LIMIT, 413);

        $validated = Validator::make($request->all(), [
            'name' => ['required','string','min:2','max:100'],
            'data' => ['required','array','max:10000'],
        ])->validate();
        abort_unless($this->validEmployees($validated['data']), 422);
        $disk = Storage::disk('local');
        abort_if(count($disk->files(self::DIR)) >= self::MAX_FILES, 409);
        $payload = ['name' => trim($validated['name']), 'savedAt' => now()->toIso8601String(), 'data' => $validated['data']];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        abort_if(strlen($json) > self::LIMIT, 413);
        $id = substr(Str::slug($payload['name']) ?: 'nhan-su', 0, 40).'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(8));
        abort_unless($disk->put(self::DIR.'/'.$id.'.json', $json), 500);
        return response()->json(['id'=>$id,'name'=>$payload['name'],'employees'=>count($payload['data'])], 201)->header('Cache-Control','no-store');
    }

    private function read(string $path): ?array
    {
        $disk = Storage::disk('local');
        if (!$disk->exists($path) || $disk->size($path) > self::LIMIT) return null;
        try { $payload = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) { return null; }
        if (!is_array($payload) || !is_string($payload['name'] ?? null) || !is_array($payload['data'] ?? null) || !$this->validEmployees($payload['data'])) return null;
        return $payload;
    }

    private function validEmployees(array $rows): bool
    {
        if (!array_is_list($rows) || count($rows) > 10000) return false;
        $seen = [];
        foreach ($rows as $row) {
            if (!is_array($row)) return false;
            foreach (['code','name','gender','dob','dept','role','phone','email','start','status'] as $key) {
                if (!isset($row[$key]) || !is_string($row[$key]) || mb_strlen($row[$key]) > 255) return false;
            }
            if ($row['code'] === '' || isset($seen[$row['code']]) || !is_numeric($row['salary'] ?? null) || !is_finite((float)$row['salary']) || (float)$row['salary'] < 0) return false;
            if (isset($row['photo']) && (!is_string($row['photo']) || strlen($row['photo']) > 2000000)) return false;
            $seen[$row['code']] = true;
        }
        return true;
    }
}
