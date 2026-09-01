<?php

namespace Modules\Admission\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Admission\Models\AdmissionApplication;

class ApplicationsExport implements FromQuery, WithHeadings, WithMapping
{
    protected array $exceptFields = [
        'noi_sinh_chi_tiet',
        'updated_at',
        'deleted_at',
        'pdf_path',
        'word_path',
        'created_at',
    ];

    protected array $columns = [];

    public function __construct(
        protected $search = null,
        protected $status = null,
        protected $class = null,
    ) {
        $this->columns = $this->getTableColumns();
    }

    public function query(): Builder
    {
        return AdmissionApplication::query()
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $nested) {
                    $nested->where('ho_va_ten_hoc_sinh', 'like', '%'.$this->search.'%')
                        ->orWhere('ma_dinh_danh', 'like', '%'.$this->search.'%')
                        ->orWhere('sdt_enetviet', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->class, fn (Builder $query) => $query->where('loai_lop_dang_ky', $this->class))
            ->select($this->columns)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');
    }

    public function map($application): array
    {
        return collect($this->columns)
            ->map(function (string $column) use ($application) {
                $value = $application->{$column};

                if ($this->isDateField($column) && $value) {
                    try {
                        $value = Carbon::parse($value)->format('d/m/Y');
                    } catch (\Throwable) {
                        // Preserve the original value when an imported date is malformed.
                    }
                }

                if (is_array($value)) {
                    return implode(', ', $value);
                }

                return $value;
            })
            ->all();
    }

    public function headings(): array
    {
        return $this->columns;
    }

    protected function getTableColumns(): array
    {
        $model = new AdmissionApplication;

        return collect(Schema::getColumnListing($model->getTable()))
            ->reject(fn ($column) => in_array($column, $this->exceptFields, true))
            ->values()
            ->all();
    }

    protected function isDateField(string $column): bool
    {
        return str_contains($column, 'date')
            || str_contains($column, 'ngay')
            || in_array($column, ['approved_at', 'rejected_at'], true);
    }
}
