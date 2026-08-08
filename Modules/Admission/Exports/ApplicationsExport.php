<?php

namespace Modules\Admission\Exports;

use Modules\Admission\Models\AdmissionApplication;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Schema;


class ApplicationsExport implements FromCollection, WithHeadings
{
    protected $search;
    protected $status;
    protected $class;

    // Các cột muốn bỏ qua
    protected array $exceptFields = [
        'noi_sinh_chi_tiet',
        'updated_at',
        'deleted_at',        
        'pdf_path',
        'word_path',
        'created_at'
        // thêm field bạn muốn bỏ ở đây
    ];

    protected array $columns = [];

    public function __construct($search = null, $status = null, $class = null)
    {
        $this->search = $search;
        $this->status = $status;
        $this->class = $class;

        // Lấy toàn bộ column từ DB
        $this->columns = $this->getTableColumns();
    }

    protected function getTableColumns(): array
    {
        $model = new AdmissionApplication();

        return collect(Schema::getColumnListing($model->getTable()))
            ->reject(fn($col) => in_array($col, $this->exceptFields))
            ->values()
            ->toArray();
    }

    public function collection()
    {
        return AdmissionApplication::query()
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('ho_va_ten_hoc_sinh', 'like', '%' . $this->search . '%')
                        ->orWhere('ma_dinh_danh', 'like', '%' . $this->search . '%')
                        ->orWhere('sdt_enetviet', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->class, fn($q) => $q->where('loai_lop_dang_ky', $this->class))
            ->get($this->columns) // chỉ select các cột cần
            ->map(function ($item) {

                $row = [];

                foreach ($this->columns as $col) {
                    $value = $item->$col;

                    // ✅ format ngày (Carbon hoặc datetime string)
                    if ($this->isDateField($col) && $value) {
                        try {
                            $value = \Carbon\Carbon::parse($value)->format('d/m/Y');
                        } catch (\Exception $e) {
                            // fallback nếu lỗi parse
                        }
                    }

                    // array -> string
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }

                    $row[$col] = $value;
                }

                return $row;
            });
    }
    protected function isDateField($column): bool
    {
        return str_contains($column, 'date') ||
            str_contains($column, 'ngay') ||
            in_array($column, ['created_at', 'updated_at']);
    }
    public function headings(): array
    {
        return $this->columns;
    }
}
