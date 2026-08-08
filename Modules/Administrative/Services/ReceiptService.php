<?php

namespace Modules\Administrative\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Administrative\Enums\HistoryActorType;
use Modules\Administrative\Enums\SubmissionAction;
use Modules\Administrative\Mail\SubmissionReceiptMail;
use Modules\Administrative\Mail\SubmissionStatusMail;
use Modules\Administrative\Models\AdministrativeSubmission;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ReceiptService
{
    public function queueEmail(AdministrativeSubmission $submission, string $lookupToken): void
    {
        if (! $submission->wants_email_receipt || ! $submission->email) {
            return;
        }

        try {
            Mail::to($submission->email)->queue(new SubmissionReceiptMail($submission, $lookupToken));
        } catch (Throwable $exception) {
            Log::warning('Không thể đưa email biên nhận hồ sơ hành chính vào queue.', [
                'submission_id' => $submission->id,
                'exception' => $exception::class,
            ]);
        }
    }

    public function downloadFromSession(string $receipt): Response
    {
        $submissionId = session("administrative.receipts.{$receipt}");
        $lookupToken = session("administrative.lookup_tokens.{$receipt}");
        abort_unless(is_numeric($submissionId) && is_string($lookupToken), 404);

        $submission = AdministrativeSubmission::query()->with('procedure:id,name')->findOrFail((int) $submissionId);

        return Pdf::loadView('Administrative::pdf.submission-receipt', compact('submission', 'lookupToken'))
            ->setPaper('a4')
            ->download("bien-nhan-{$submission->submission_code}.pdf");
    }

    public function queueStatusEmail(AdministrativeSubmission $submission, int $adminId): void
    {
        if (! $submission->email) {
            throw ValidationException::withMessages(['email' => 'Hồ sơ không có email người nhận.']);
        }

        try {
            Mail::to($submission->email)->queue(new SubmissionStatusMail($submission->loadMissing('procedure:id,name')));
            $submission->statusHistories()->create([
                'from_status' => $submission->status,
                'to_status' => $submission->status,
                'action' => SubmissionAction::EmailSent,
                'actor_type' => HistoryActorType::Admin,
                'actor_id' => $adminId,
                'note' => 'Đã xếp hàng gửi email thông báo trạng thái.',
                'metadata' => ['channel' => 'email'],
            ]);
        } catch (Throwable $exception) {
            Log::warning('Không thể đưa email trạng thái hồ sơ vào queue.', ['submission_id' => $submission->id, 'exception' => $exception::class]);
            throw ValidationException::withMessages(['email' => 'Không thể xếp hàng gửi email. Vui lòng thử lại.']);
        }
    }
}
