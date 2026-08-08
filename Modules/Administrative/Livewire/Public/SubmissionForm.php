<?php

namespace Modules\Administrative\Livewire\Public;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Administrative\Services\ProcedureService;
use Modules\Administrative\Services\ReceiptService;
use Modules\Administrative\Services\SubmissionService;

class SubmissionForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $procedureId;

    public string $applicant_name = '';

    public string $phone = '';

    public string $email = '';

    public bool $wants_email_receipt = false;

    public string $student_name = '';

    public string $student_code = '';

    public string $date_of_birth = '';

    public string $current_class = '';

    public string $academic_year = '';

    public string $relationship = '';

    public string $relationship_other = '';

    public array $files = [];

    public bool $privacy_consent = false;

    protected ProcedureService $procedureService;

    public function boot(ProcedureService $procedureService): void
    {
        $this->procedureService = $procedureService;
    }

    public function mount(int $procedureId): void
    {
        $procedure = $this->procedureService->findActiveForPublic($procedureId);
        $this->procedureId = $procedure->id;
    }

    public function submit(SubmissionService $service, ReceiptService $receipts)
    {
        $procedure = $this->procedureService->findActiveForPublic($this->procedureId);
        $extensions = implode(',', $procedure->allowed_extensions ?: config('administrative.administrative.allowed_extensions', []));
        $validated = $this->validate([
            'applicant_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{9,10}$/'],
            'email' => ['nullable', 'required_if:wants_email_receipt,true', 'email:rfc', 'max:255'],
            'wants_email_receipt' => ['boolean'],
            'student_name' => ['required', 'string', 'min:2', 'max:255'],
            'student_code' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'current_class' => ['nullable', 'string', 'max:100'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'relationship' => ['nullable', 'string', 'in:father,mother,guardian,student,other'],
            'relationship_other' => ['nullable', 'required_if:relationship,other', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1', 'max:'.$procedure->max_files],
            'files.*' => ['required', 'file', 'mimes:'.$extensions, 'max:'.$procedure->max_file_size_kb],
            'privacy_consent' => ['accepted'],
        ], [
            'phone.regex' => 'Số điện thoại không đúng định dạng Việt Nam.',
            'privacy_consent.accepted' => 'Bạn cần đồng ý việc xử lý thông tin để nộp hồ sơ.',
        ]);

        $result = $service->submit($procedure, $validated, $this->files);
        $receipts->queueEmail($result['submission'], $result['lookup_token']);
        $receipt = bin2hex(random_bytes(24));
        session()->put("administrative.receipts.{$receipt}", $result['submission']->id);
        session()->put("administrative.lookup_tokens.{$receipt}", $result['lookup_token']);

        return redirect()->route('administrative.public.success', $receipt);
    }

    public function render()
    {
        return view('Administrative::livewire.public.submission-form', [
            'procedure' => $this->procedureService->findActiveForPublic($this->procedureId),
        ]);
    }
}
