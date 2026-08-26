<?php

namespace Modules\Request\Support;

use Carbon\CarbonImmutable;
use Modules\Request\Models\RequestTask;

final class RequestTaskSlaPresenter
{
    /**
     * @return array{state:string,label:string,detail:string,deadline:?string,deadline_iso:?string}|null
     */
    public static function present(RequestTask $task): ?array
    {
        if (! $task->due_at) {
            return null;
        }

        $now = CarbonImmutable::now('UTC');
        $timezone = (string) config('app.timezone', 'Asia/Ho_Chi_Minh');
        $due = CarbonImmutable::instance($task->due_at)->utc();
        $warning = $task->warning_at ? CarbonImmutable::instance($task->warning_at)->utc() : null;
        $grace = $task->grace_expires_at ? CarbonImmutable::instance($task->grace_expires_at)->utc() : null;

        if ($task->suspended_at) {
            $state = 'suspended';
            $label = 'Đã tạm dừng';
            $detail = 'Đã vượt thời gian gia hạn';
        } elseif ($now->gte($due)) {
            $state = 'grace';
            $label = 'Đang gia hạn';
            $detail = $grace && $now->lt($grace)
                ? 'Còn '.$now->diffForHumans($grace, ['parts' => 2, 'short' => true, 'syntax' => CarbonImmutable::DIFF_ABSOLUTE]).' gia hạn'
                : 'Đã quá hạn';
        } elseif ($warning && $now->gte($warning)) {
            $state = 'warning';
            $label = 'Sắp quá hạn';
            $detail = 'Còn '.$now->diffForHumans($due, ['parts' => 2, 'short' => true, 'syntax' => CarbonImmutable::DIFF_ABSOLUTE]);
        } else {
            $state = 'normal';
            $label = 'Còn hạn';
            $detail = 'Còn '.$now->diffForHumans($due, ['parts' => 2, 'short' => true, 'syntax' => CarbonImmutable::DIFF_ABSOLUTE]);
        }

        $displayDue = $due->setTimezone($timezone);

        return [
            'state' => $state,
            'label' => $label,
            'detail' => $detail,
            'deadline' => $displayDue->format('d/m/Y H:i:s'),
            'deadline_iso' => $displayDue->toIso8601String(),
        ];
    }
}
