<?php

namespace Modules\LoanManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class SendLoanReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $deliveryId)
    {
    }

    public function handle(): void
    {
        // Explicit infrastructure gate: opt-in settings alone never activate outbound email.
        if (!config('loan_reminders.dispatch_enabled', false)) return;

        $delivery = DB::table('loan_reminder_deliveries as d')
            ->join('loan_repayment_schedules as s', 's.id', '=', 'd.loan_repayment_schedule_id')
            ->join('loan_contracts as c', 'c.id', '=', 's.loan_contract_id')
            ->join('loan_reminder_settings as r', 'r.loan_contract_id', '=', 'c.id')
            ->where('d.id', $this->deliveryId)
            ->select('d.*', 's.installment', 's.due_at', 's.principal_due', 's.interest_due', 's.fees_due',
                'c.contract_number', 'c.status as contract_status', 'r.enabled as reminder_enabled', 'r.recipient_email as current_email')
            ->first();
        if (!$delivery || $delivery->status === 'sent') return;
        if (!$delivery->reminder_enabled || $delivery->contract_status !== 'active' ||
            $delivery->recipient_email !== $delivery->current_email ||
            $delivery->due_at_snapshot !== $delivery->due_at) return;

        $paid = DB::table('loan_payment_allocations')->where('loan_repayment_schedule_id', $delivery->loan_repayment_schedule_id)
            ->selectRaw('COALESCE(SUM(principal_allocated),0) as principal, COALESCE(SUM(interest_allocated),0) as interest, COALESCE(SUM(fees_allocated),0) as fees')->first();
        $total = bcadd(
            bcadd(bcsub((string) $delivery->principal_due, (string) $paid->principal, 2),
                bcsub((string) $delivery->interest_due, (string) $paid->interest, 2), 2),
            bcsub((string) $delivery->fees_due, (string) $paid->fees, 2), 2
        );
        if (bccomp($total, '0', 2) <= 0) return;

        try {
            Mail::raw(
                "Nhắc lịch trả nợ hợp đồng {$delivery->contract_number}\n".
                "Kỳ {$delivery->installment} - hạn thanh toán {$delivery->due_at}\n".
                "Số tiền còn phải trả theo kỳ: ".number_format((float) $total, 0, ',', '.')." VND\n".
                "Đây là thông báo nhắc hẹn, không phải xác nhận đã thanh toán.",
                static function ($message) use ($delivery): void {
                    $message->to($delivery->recipient_email)->subject('Nhắc thanh toán khoản vay '.$delivery->contract_number);
                }
            );
            DB::table('loan_reminder_deliveries')->where('id', $this->deliveryId)
                ->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            DB::table('loan_reminder_deliveries')->where('id', $this->deliveryId)
                ->update(['status' => 'failed', 'last_error' => mb_substr($e->getMessage(), 0, 2000), 'updated_at' => now()]);
            throw $e;
        }
    }
}
