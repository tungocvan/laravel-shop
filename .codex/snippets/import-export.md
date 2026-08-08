# Import Export Snippet

Use this pattern for module import/export services that build on the shared import/export foundation.

```php
<?php

namespace Modules\Example\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Example\Models\Item;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class ItemImportExportService extends BaseImportExportService
{
    public function importRows(iterable $rows): array
    {
        $summary = ['created' => 0, 'failed' => 0, 'errors' => []];

        DB::transaction(function () use ($rows, &$summary): void {
            foreach ($rows as $index => $row) {
                $data = $this->normalizeRow($row);
                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'status' => ['required', 'in:draft,active,archived'],
                ]);

                if ($validator->fails()) {
                    $summary['failed']++;
                    $summary['errors'][] = ['row' => $index + 1, 'errors' => $validator->errors()->toArray()];
                    continue;
                }

                Item::query()->updateOrCreate(
                    ['name' => $data['name']],
                    ['status' => $data['status']]
                );

                $summary['created']++;
            }
        });

        return $summary;
    }
}
```
