<?php

namespace Modules\Request\Livewire\Requester;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Request\Application\Queries\RequestCollaborationQuery;
use Modules\Request\Application\Services\UploadRequestAttachment;
use Modules\Request\Authorization\RequestAuthorizationContext;
use Modules\Request\Livewire\Concerns\InteractsWithRequestAuthorization;
use Modules\Request\Models\RequestAttachment;

class AttachmentManager extends Component
{
    use InteractsWithRequestAuthorization;
    use WithFileUploads;

    public string $requestPublicId;

    public int $requestVersion;

    public ?string $fieldKey = null;

    public array $uploads = [];

    public string $idempotencyKey;

    public function mount(string $requestPublicId, int $requestVersion, RequestAuthorizationContext $context, ?string $fieldKey = null): void
    {
        $this->initializeRequestAuthorization($context);
        $this->requestPublicId = $requestPublicId;
        $this->requestVersion = $requestVersion;
        $this->fieldKey = $fieldKey;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function store(RequestCollaborationQuery $collaboration, UploadRequestAttachment $service, RequestAuthorizationContext $context): void
    {
        $user = $this->requestActor($context);
        $maxFiles = max(1, (int) config('request.files.max_count_per_field', 5));
        $maxKilobytes = max(1, (int) ceil(config('request.files.max_bytes', 10485760) / 1024));
        $validated = $this->validate([
            'uploads' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'uploads.*' => ['required', 'file', 'max:'.$maxKilobytes],
        ]);
        $request = $collaboration->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('upload', [RequestAttachment::class, $request]);

        foreach ($validated['uploads'] as $upload) {
            try {
                $attachment = $service->handle($request, $upload, (int) $user->getAuthIdentifier(), $this->requestVersion, $this->idempotencyKey, $this->fieldKey);
            } catch (ValidationException $exception) {
                $this->addError('uploads', $this->attachmentError($exception));
                $this->reset('uploads');

                return;
            }

            $this->requestVersion = $request->refresh()->lock_version;
            $this->idempotencyKey = (string) Str::uuid();
            $this->dispatch('request-version-changed', version: $this->requestVersion);
            if ($this->fieldKey !== null && $attachment->scan_status->value === 'clean') {
                $this->dispatch('request-attachment-created', attachmentPublicId: $attachment->public_id, fieldKey: $this->fieldKey, version: $this->requestVersion);
            }
        }

        $this->reset('uploads');
        session()->flash('request_attachment_success', __('Request::request.attachment_added'));
    }

    private function attachmentError(ValidationException $exception): string
    {
        $code = (string) collect($exception->errors())->flatten()->first();
        $key = 'Request::request.attachment_errors.'.$code;
        $message = __($key);

        return $message === $key ? __('Request::request.attachment_errors.upload_failed') : $message;
    }

    #[On('request-version-changed')]
    public function updateRequestVersion(int $version): void
    {
        $this->requestVersion = $version;
    }

    public function render(RequestCollaborationQuery $collaboration, RequestAuthorizationContext $context)
    {
        $user = $this->requestActor($context);
        $request = $collaboration->findVisible($this->requestPublicId, $user);
        Gate::forUser($user)->authorize('view', $request);

        return view('Request::livewire.requester.attachment-manager', [
            'request' => $request,
            'attachments' => collect($collaboration->attachments($request))->filter(fn (RequestAttachment $attachment): bool => $this->fieldKey === null ? $attachment->payload_field_key === null : $attachment->payload_field_key === $this->fieldKey),
            'downloadRouteName' => $this->requestRouteName('attachments.download'),
        ]);
    }
}
