<?php

namespace App\Services\Export;

use App\Services\Data\DataTransformer;

class GenericExportService
{
    public function __construct(
        protected DataTransformer $transformer
    ) {}

    public function handle(
        iterable $collection,
        $model,
        array $columns = [],
        array $headings = []
    ): array {

        $results = [];

        foreach ($collection as $item) {

            $row = is_array($item)
                ? $item
                : $item->toArray();

            // nếu không truyền columns → lấy hết
            if (!empty($columns)) {
                $row = array_intersect_key($row, array_flip($columns));
            }

            $row = $this->transformer->transformOutput($model, $row);

            $results[] = $row;
        }

        // thêm header nếu có
        if (!empty($headings)) {
            array_unshift($results, $headings);
        }

        return $results;
    }
}