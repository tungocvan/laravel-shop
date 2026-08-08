<?php

namespace Modules\Administrative\Livewire\Submissions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Administrative\Enums\SubmissionStatus;
use Modules\Administrative\Models\AdministrativeSubmission;
use Modules\Administrative\Services\ReceiptService;
use Modules\Administrative\Services\SubmissionService;

class SubmissionDetail extends Component
{
    #[Locked]
    public int $submissionId;

    #[Locked]
    public int $version;

    public string $response = '';

    public string $reason_code = '';

    public string $rejection_reason = '';

    public bool $showApprove = false;

    public bool $showReject = false;

    public bool $showSupplement = false;

    public string $supplement_reason = '';

    public function mount(int $id, SubmissionService $service): void
    {
        $this->authorizePermission('administrative.submission.view');
        $submission = $service->findForAdmin($id);
        $this->submissionId = $submission->id;
        $this->version = $submission->version;
        $this->response = $submission->response ?? '';
    }

    public function approve(SubmissionService $service, ReceiptService $receipts): void
    {
        $this->authorizePermission('administrative.submission.edit');
        $validated = $this->validate(['response' => ['nullable', 'string', 'max:5000']]);
        $submission = $service->approve($this->submissionId, $this->version, (int) Auth::guard('admin')->id(), $validated['response'] ?: null);
        $this->queueStatusEmailWhenAvailable($submission, $receipts);
        $this->version = $submission->version;
        $this->showApprove = false;
        $this->dispatch('notify', content: 'Đã phê duyệt hồ sơ.', type: 'success');
    }

    public function reject(SubmissionService $service, ReceiptService $receipts): void
    {
        $this->authorizePermission('administrative.submission.edit');
        $validated = $this->validate([
            'reason_code' => ['required', 'in:wrong_form,missing_signature,missing_documents,unreadable_file,mismatched_information,ineligible,other'],
            'rejection_reason' => ['required', 'string', 'min:10', 'max:5000'],
            'response' => ['nullable', 'string', 'max:5000'],
        ]);
        $submission = $service->reject($this->submissionId, $this->version, (int) Auth::guard('admin')->id(), $validated['reason_code'], $validated['rejection_reason'], $validated['response'] ?: null);
        $this->queueStatusEmailWhenAvailable($submission, $receipts);
        $this->version = $submission->version;
        $this->showReject = false;
        $this->dispatch('notify', content: 'Đã từ chối hồ sơ.', type: 'success');
    }

    public function requestSupplement(SubmissionService $service, ReceiptService $receipts): void
    {
        $this->authorizePermission('administrative.submission.edit');
        $validated = $this->validate([
            'supplement_reason' => ['required', 'string', 'min:10', 'max:5000'],
            'response' => ['nullable', 'string', 'max:5000'],
        ]);
        $submission = $service->requestSupplement($this->submissionId, $this->version, (int) Auth::guard('admin')->id(), $validated['supplement_reason'], $validated['response'] ?: null);
        $this->queueStatusEmailWhenAvailable($submission, $receipts);
        $this->version = $submission->version;
        $this->showSupplement = false;
        $this->dispatch('notify', content: 'Đã yêu cầu phụ huynh bổ sung hồ sơ.', type: 'success');
    }

    public function render(SubmissionService $service)
    {
        $this->authorizePermission('administrative.submission.view');

        return view('Administrative::livewire.submissions.submission-detail', [
            'submission' => $service->findForAdmin($this->submissionId),
            'pendingStatus' => SubmissionStatus::Pending,
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }

    private function queueStatusEmailWhenAvailable(AdministrativeSubmission $submission, ReceiptService $receipts): void
    {
        if ($submission->email) {
            $receipts->queueStatusEmail($submission, (int) Auth::guard('admin')->id());
        }
    }
}
