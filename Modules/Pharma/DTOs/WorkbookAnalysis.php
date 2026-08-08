<?php

namespace Modules\Pharma\DTOs;

final readonly class WorkbookAnalysis
{
    public function __construct(
        public string $sheetName,
        public int $headerRow,
        public string $lastHeaderColumn,
        public array $columns,
        public array $products,
        public array $duplicateHeaders,
    ) {}

    public function toArray(): array
    {
        return [
            'sheet_name' => $this->sheetName,
            'header_row' => $this->headerRow,
            'last_header_column' => $this->lastHeaderColumn,
            'columns' => $this->columns,
            'products' => $this->products,
            'duplicate_headers' => $this->duplicateHeaders,
        ];
    }
}
