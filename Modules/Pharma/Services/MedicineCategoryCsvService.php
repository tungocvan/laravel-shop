<?php

namespace Modules\Pharma\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Category\Models\Category;
use Modules\Pharma\Models\Medicine;

class MedicineCategoryCsvService
{
    public function exportRows(): array
    {
        return Medicine::query()->with('categories:id,slug')->orderBy('id')->get()
            ->map(fn (Medicine $medicine) => [
                $medicine->medicine_code,
                $medicine->name,
                $medicine->categories->pluck('slug')->implode(','),
            ])->all();
    }

    public function preview(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'Không đọc được file CSV.']);
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw ValidationException::withMessages(['file' => 'File CSV trống.']);
            }
            $headers = array_map(fn ($v) => trim((string) $v, " \t\n\r\0\x0B\xEF\xBB\xBF"), $headers);
            if ($headers !== ['medicine_code', 'medicine_name', 'category_slugs']) {
                throw ValidationException::withMessages(['file' => 'Cột CSV phải là medicine_code,medicine_name,category_slugs theo đúng thứ tự.']);
            }

            $rows = [];
            $seen = [];
            $line = 1;
            $allCategories = Category::query()->where('type', 'medicine')->pluck('id', 'slug');
            while (($columns = fgetcsv($handle)) !== false) {
                $line++;
                if (count($columns) === 1 && trim((string) $columns[0]) === '') {
                    continue;
                }
                if (count($columns) !== 3) {
                    throw ValidationException::withMessages(['file' => "Dòng {$line}: số cột không hợp lệ."]);
                }
                [$code, $name, $raw] = array_map('trim', $columns);
                if ($code === '' || isset($seen[$code])) {
                    throw ValidationException::withMessages(['file' => "Dòng {$line}: mã thuốc trống hoặc bị trùng."]);
                }
                $seen[$code] = true;
                $medicine = Medicine::query()->where('medicine_code', $code)->first();
                if (! $medicine || $medicine->name !== $name) {
                    throw ValidationException::withMessages(['file' => "Dòng {$line}: mã hoặc tên thuốc không khớp Medicine Master."]);
                }
                // Empty category cells are explicitly skipped; never clear existing links.
                if ($raw === '') {
                    $rows[] = ['code' => $code, 'name' => $name, 'slugs' => [], 'skip' => true];
                    continue;
                }
                $slugs = array_map('trim', explode(',', $raw));
                if (in_array('', $slugs, true) || count($slugs) !== count(array_unique($slugs))) {
                    throw ValidationException::withMessages(['file' => "Dòng {$line}: danh mục trống hoặc trùng."]);
                }
                foreach ($slugs as $slug) {
                    if (! $allCategories->has($slug)) {
                        throw ValidationException::withMessages(['file' => "Dòng {$line}: danh mục {$slug} không thuộc loại medicine."]);
                    }
                }
                $rows[] = ['code' => $code, 'name' => $name, 'slugs' => $slugs, 'skip' => false];
                if (count($rows) > 5000) {
                    throw ValidationException::withMessages(['file' => 'Tối đa 5000 dòng mỗi lần Import.']);
                }
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['file' => 'Không có dòng dữ liệu.']);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    public function commit(array $rows): int
    {
        return DB::transaction(function () use ($rows): int {
            $categories = Category::query()->where('type', 'medicine')->pluck('id', 'slug');
            $updated = 0;
            foreach ($rows as $row) {
                if ($row['skip']) {
                    continue;
                }
                $medicine = Medicine::query()->where('medicine_code', $row['code'])->lockForUpdate()->firstOrFail();
                if ($medicine->name !== $row['name']) {
                    throw ValidationException::withMessages(['file' => 'Tên thuốc đã thay đổi, vui lòng xem trước lại.']);
                }
                $ids = [];
                foreach ($row['slugs'] as $slug) {
                    if (! $categories->has($slug)) {
                        throw ValidationException::withMessages(['file' => 'Danh mục đã thay đổi, vui lòng xem trước lại.']);
                    }
                    $ids[] = $categories[$slug];
                }
                $medicine->categories()->sync($ids);
                $updated++;
            }

            return $updated;
        });
    }
}
