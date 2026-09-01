<?php

namespace Tests\Feature\Attendance;

use Modules\Attendance\Exports\AttendanceRecordsExport;
use Modules\Attendance\Services\AttendanceRecordQueryService;
use Tests\TestCase;

class AttendanceExportContractTest extends TestCase
{
    public function test_export_classes_autoload_without_new_infrastructure(): void
    {
        $this->assertTrue(class_exists(AttendanceRecordQueryService::class));
        $this->assertTrue(class_exists(AttendanceRecordsExport::class));

        $composer = file_get_contents(base_path('composer.json'));
        $this->assertStringContainsString('"maatwebsite/excel": "^3.1.64"', $composer);
    }

    public function test_record_filters_are_normalized_consistently_for_ui_and_export(): void
    {
        $filters = (new AttendanceRecordQueryService)->normalize([
            'search' => '  NV-001  ',
            'status' => 'not-a-status',
            'shift' => '12',
            'location' => '-1',
            'fromDate' => '2026-09-01',
            'toDate' => '2026-02-31',
        ]);

        $this->assertSame('NV-001', $filters['search']);
        $this->assertSame('all', $filters['status']);
        $this->assertSame('12', $filters['shift']);
        $this->assertSame('all', $filters['location']);
        $this->assertSame('2026-09-01', $filters['fromDate']);
        $this->assertSame('', $filters['toDate']);
    }

    public function test_selected_record_ids_are_positive_unique_and_stable(): void
    {
        $ids = (new AttendanceRecordQueryService)->normalizeSelectedIds(['9', 3, '9', 0, -2, 'invalid']);

        $this->assertSame([3, 9], $ids);
    }

    public function test_export_uses_filtered_query_and_server_side_permission_guard(): void
    {
        $component = file_get_contents(base_path('Modules/Attendance/Livewire/AdminRecordsTable.php'));

        $this->assertStringContainsString("authorizePermission('attendance.export')", $component);
        $this->assertStringContainsString('exportFiltered()', $component);
        $this->assertStringContainsString('exportSelected()', $component);
        $this->assertStringContainsString('$this->recordsQuery->query($this->filters())', $component);
        $this->assertStringContainsString('$this->recordsQuery->query($this->filters(), $selectedIds)', $component);
        $this->assertStringContainsString('AttendanceRecordsExport', $component);
        $this->assertStringContainsString(".xlsx'", $component);
    }

    public function test_export_ui_is_permission_aware_and_supports_selected_and_filtered_scopes(): void
    {
        $view = file_get_contents(base_path('Modules/Attendance/resources/views/livewire/admin-records-table.blade.php'));

        $this->assertStringContainsString("can('attendance.export')", $view);
        $this->assertStringContainsString('wire:model.live="selectedRecordIds"', $view);
        $this->assertStringContainsString('wire:click="exportSelected"', $view);
        $this->assertStringContainsString('wire:click="exportFiltered"', $view);
        $this->assertStringContainsString('không chứa tọa độ GPS chính xác', $view);
    }

    public function test_export_contract_excludes_precise_geolocation_fields(): void
    {
        $export = file_get_contents(base_path('Modules/Attendance/Exports/AttendanceRecordsExport.php'));

        $this->assertStringContainsString("'Mã nhân viên'", $export);
        $this->assertStringContainsString("'Ngày công'", $export);
        $this->assertStringContainsString("'Vị trí vào'", $export);
        $this->assertStringContainsString("'Vị trí ra'", $export);
        $this->assertStringNotContainsString('check_in_latitude', $export);
        $this->assertStringNotContainsString('check_in_longitude', $export);
        $this->assertStringNotContainsString('check_out_latitude', $export);
        $this->assertStringNotContainsString('check_out_longitude', $export);
        $this->assertStringNotContainsString('accuracy_meters', $export);
        $this->assertStringNotContainsString('distance_meters', $export);
    }
}
