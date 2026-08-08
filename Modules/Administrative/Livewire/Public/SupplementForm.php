<?php

namespace Modules\Administrative\Livewire\Public;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Administrative\Enums\SubmissionStatus;
use Modules\Administrative\Services\LookupService;
use Modules\Administrative\Services\SubmissionService;

class SupplementForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $accessToken;

    #[Locked]
    public int $submissionId;

    #[Locked]
    public int $version;

    public array $files = [];

    public bool $confirmation = false;

    public function mount(string $accessToken, LookupService $lookup): void
    {
        $submission = $lookup->submissionForAccess($accessToken);
        abort_unless($submission->status === SubmissionStatus::NeedSupplement, 404);
        $this->accessToken = $accessToken;
        $this->submissionId = $submission->id;
        $this->version = $submission->version;
    }

    public function resubmit(LookupService $lookup, SubmissionService $service)
    {
        $submission = $lookup->submissionForAccess($this->accessToken);
        abort_unless($submission->id === $this->submissionId && $submission->status === SubmissionStatus::NeedSupplement, 404);
        $procedure = $submission->procedure;
        $extensions = implode(',', $procedure->allowed_extensions ?: config('administrative.administrative.allowed_extensions', []));
        $this->validate([
            'files' => ['required', 'array', 'min:1', 'max:'.$procedure->max_files],
            'files.*' => ['required', 'file', 'mimes:'.$extensions, 'max:'.$procedure->max_file_size_kb],
            'confirmation' => ['accepted'],
        ], ['confirmation.accepted' => 'Bạn cần xác nhận đã bổ sung đúng nội dung được yêu cầu.']);

        $service->resubmitSupplement($this->submissionId, $this->version, $this->files);
        session()->flash('success', 'Đã gửi lại hồ sơ bổ sung. Hồ sơ được chuyển về trạng thái Chờ duyệt.');

        return redirect()->route('administrative.lookup.show', $this->accessToken);
    }

    public function render(LookupService $lookup)
    {
        return view('Administrative::livewire.public.supplement-form', [
            'submission' => $lookup->submissionForAccess($this->accessToken),
        ]);
    }
}
