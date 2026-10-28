<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class AttendancePayrollDatasetController extends Controller
{
    private const DIR = 'demo-datasets/attendance-payroll';
    private const LIMIT = 10485760;
    private const MAX_FILES = 100;

    public function index(): JsonResponse
    {
        $disk = Storage::disk('local');
        $items = [];
        foreach ($disk->files(self::DIR) as $file) {
            $id = pathinfo($file, PATHINFO_FILENAME);
            if (!preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $id) || !str_ends_with($file, '.json')) continue;
            $payload = $this->read($file);
            if ($payload === null) continue;
            $items[] = ['id' => $id, 'name' => $payload['name'], 'employees' => count($payload['data']['employees'])];
        }
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

        $v = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'data' => ['required', 'array'],
            'data.settings' => ['required', 'array'],
            'data.employees' => ['present', 'array', 'max:10000'],
            'data.cham' => ['present', 'array'],
            'data.extra' => ['present', 'array'],
        ])->validate();
        abort_unless($this->validData($v['data']), 422);
        $disk = Storage::disk('local');
        abort_if(count($disk->files(self::DIR)) >= self::MAX_FILES, 409);
        $payload = ['name' => trim($v['name']), 'savedAt' => now()->toIso8601String(), 'data' => $v['data']];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        abort_if(strlen($json) > self::LIMIT, 413);
        $id = substr(Str::slug($payload['name']) ?: 'bang-luong', 0, 40).'-'.Str::lower(Str::random(12));
        abort_unless($disk->put(self::DIR.'/'.$id.'.json', $json), 500);
        return response()->json(['id' => $id, 'name' => $payload['name']], 201)->header('Cache-Control', 'no-store');
    }

    private function read(string $file): ?array
    {
        $disk = Storage::disk('local');
        if (!$disk->exists($file) || $disk->size($file) > self::LIMIT) return null;
        try { $data = json_decode($disk->get($file), true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { return null; }
        return is_array($data) && is_string($data['name'] ?? null) && $this->validData($data['data'] ?? null) ? $data : null;
    }

    private function validData(mixed $data): bool
    {
        if (!is_array($data) || !is_array($data['settings'] ?? null) || !is_array($data['employees'] ?? null)
            || !is_array($data['cham'] ?? null) || !is_array($data['extra'] ?? null)
            || !array_is_list($data['employees']) || count($data['employees']) > 10000) return false;
        $seen = [];
        foreach ($data['employees'] as $employee) {
            if (!is_array($employee) || !is_string($employee['id'] ?? null) || !is_string($employee['ma'] ?? null)
                || !is_string($employee['ten'] ?? null) || $employee['id'] === ''
                || mb_strlen($employee['id']) > 100 || mb_strlen($employee['ten']) > 255
                || isset($seen[$employee['id']])) return false;
            $seen[$employee['id']] = true;
        }
        foreach (['cham', 'extra'] as $key) {
            if (count($data[$key]) > 240) return false;
            foreach ($data[$key] as $month => $rows) {
                if (!preg_match('/^\\d{4}-(0[1-9]|1[0-2])$/', (string) $month) || !is_array($rows) || count($rows) > 10000) return false;
            }
        }
        return true;
    }
}
