<?php

declare(strict_types=1);

namespace Modules\System\Livewire\Database;

use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Database\ModuleSchemaDoctorService;
use Modules\System\Services\Database\ModuleSchemaRepairPlanner;
use Modules\System\Services\Database\ModuleSnapshotService;

class ModuleSchemaDoctorPanel extends Component
{
    use AuthorizesSystemActions;

    public string $module = '';

    public ?string $reference = null;

    public ?array $report = null;

    public ?array $repairPlan = null;

    public bool $open = false;

    public function diagnose(
        string $reference,
        ModuleSchemaDoctorService $doctor,
        ModuleSchemaRepairPlanner $planner,
    ): void {
        $this->authorizePermission('database.restore');
        $this->reference = $reference;

        try {
            $this->report = $doctor->diagnose($reference, $this->module);
            $this->repairPlan = $planner->plan($this->report);
            $this->open = true;
        } catch (\Throwable $e) {
            report($e);
            $this->report = [
                'verdict' => 'BLOCKED',
                'summary' => 'Không thể hoàn tất chẩn đoán snapshot. Kiểm tra log hệ thống.',
                'issues' => [],
                'auto_repair_available' => false,
                'restore_unlocked' => false,
            ];
            $this->repairPlan = ['steps' => [], 'all_safe' => false, 'execution_available' => false];
            $this->open = true;
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reference = null;
        $this->report = null;
        $this->repairPlan = null;
    }

    public function render(ModuleSnapshotService $snapshots)
    {
        $blocked = [];
        if ($this->module !== '' && $this->module !== 'Unknown') {
            $blocked = array_values(array_filter(
                $snapshots->listLocal($this->module, 30),
                static fn (array $snapshot): bool => ($snapshot['compatibility'] ?? 'BLOCKED') === 'BLOCKED',
            ));
        }

        return view('System::livewire.database.module-schema-doctor-panel', [
            'blockedSnapshots' => $blocked,
        ]);
    }
}
