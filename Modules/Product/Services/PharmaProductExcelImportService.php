<?php

namespace Modules\Product\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\PharmaProductLink;
use Modules\Product\Exports\PharmaProductsExport;
use Modules\Product\Models\Product;

class PharmaProductExcelImportService
{
    private const MAX_ROWS = 5000;

    public function preview(array $sheets): array
    {
        $rows = $sheets[0] ?? [];
        $headings = (new PharmaProductsExport(true))->headings();
        if (($rows[0] ?? null) !== $headings) {
            throw ValidationException::withMessages(['pharmaImportFile' => 'Sai cấu trúc Excel Pharma. Hãy dùng file Export Pharma hoặc Template Pharma mới nhất.']);
        }
        if (count($rows) - 1 > self::MAX_ROWS) {
            throw ValidationException::withMessages(['pharmaImportFile' => 'Tối đa 5.000 dòng dữ liệu.']);
        }

        $results = [];
        $seen = [];
        foreach (array_slice($rows, 1) as $offset => $row) {
            $line = $offset + 2;
            if (count(array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== '')) === 0) {
                continue;
            }
            $errors = [];
            $id = filter_var($row[0] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id || isset($seen[$id])) {
                $errors[] = 'Product ID thiếu, không hợp lệ hoặc trùng trong file.';
            }
            if ($id) {
                $seen[$id] = true;
            }
            $product = $id ? Product::query()->with('pharmaLink')->find($id) : null;
            if (!$product || !$product->pharmaLink) {
                $errors[] = 'Product không tồn tại hoặc không thuộc nguồn Pharma.';
            } elseif ((string) ($row[1] ?? '') !== (string) $product->pharmaLink->medicine_id
                || (string) ($row[2] ?? '') !== (string) $product->title
                || (string) ($row[3] ?? '') !== (string) $product->slug) {
                $errors[] = 'Các cột nhận diện Pharma đã bị thay đổi.';
            }
            $changes = [];
            foreach ([4 => 'regular_price', 5 => 'sale_price', 13 => 'affiliate_commission_rate'] as $column => $field) {
                $value = $row[$column] ?? null;
                if ($value === null || $value === '') {
                    $changes[$field] = null;
                } elseif (!is_numeric($value) || (float) $value < 0 || ($column === 13 && (float) $value > 100)) {
                    $errors[] = "Giá trị cột ".($column + 1)." không hợp lệ.";
                } else {
                    $changes[$field] = $value;
                }
            }
            foreach ([6 => 'short_description', 7 => 'description', 8 => 'image'] as $column => $field) {
                $changes[$field] = ($row[$column] ?? null) === null ? null : (string) $row[$column];
            }
            foreach ([9 => 'gallery', 10 => 'tags'] as $column => $field) {
                $value = $row[$column] ?? null;
                $decoded = ($value === null || $value === '') ? [] : json_decode((string) $value, true);
                if (!is_array($decoded) || !array_is_list($decoded)) {
                    $errors[] = "Cột ".($column + 1)." phải là JSON array.";
                } else {
                    $changes[$field] = $decoded;
                }
            }
            $status = (string) ($row[12] ?? '');
            if (!in_array($status, ['0', '1'], true)) {
                $errors[] = 'Trạng thái chỉ nhận 0 hoặc 1.';
            } else {
                $changes['is_active'] = $status === '1';
            }
            $categoryIds = trim((string) ($row[11] ?? ''));
            $ids = $categoryIds === '' ? [] : array_map('trim', explode(',', $categoryIds));
            if (count(array_filter($ids, fn ($v) => !ctype_digit($v) || (int) $v < 1)) > 0) {
                $errors[] = 'Danh mục phải là danh sách ID nguyên dương, cách nhau bởi dấu phẩy.';
            } elseif ($ids !== [] && DB::table('categories')->whereIn('id', $ids)->count() !== count(array_unique($ids))) {
                $errors[] = 'Danh mục không tồn tại hoặc trùng ID.';
            }
            $results[] = ['line' => $line, 'id' => $id ?: null, 'title' => $product?->title, 'errors' => $errors, 'changes' => $changes, 'category_ids' => array_map('intval', $ids)];
        }

        return $results;
    }

    public function apply(array $sheets): int
    {
        if (!Schema::hasTable('pharma_product_links')) {
            throw ValidationException::withMessages(['pharmaImportFile' => 'Liên kết Pharma chưa sẵn sàng.']);
        }

        return DB::transaction(function () use ($sheets): int {
            $results = $this->preview($sheets);
            if (collect($results)->contains(fn ($row) => $row['errors'] !== [])) {
                throw ValidationException::withMessages(['pharmaImportFile' => 'File có lỗi. Vui lòng xem trước và sửa tất cả lỗi trước khi import.']);
            }
            foreach ($results as $row) {
                $product = Product::query()->lockForUpdate()->findOrFail($row['id']);
                if (!PharmaProductLink::query()->where('product_id', $product->id)->exists()) {
                    throw ValidationException::withMessages(['pharmaImportFile' => 'Liên kết Pharma đã thay đổi. Vui lòng xem trước lại.']);
                }
                $product->fill($row['changes'])->save();
                $product->categories()->sync($row['category_ids']);
            }

            return count($results);
        });
    }
}
