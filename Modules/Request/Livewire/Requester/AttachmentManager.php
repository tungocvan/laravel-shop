<?php

namespace Modules\Request\Livewire\Requester;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Request\Application\Queries\RequestCollaborationQuery;
use Modules\Request\Application\Services\UploadRequestAttachment;
use Modules\Request\Models\RequestAttachment;

class AttachmentManager extends Component
{
    use WithFileUploads;

    public string $requestPublicId;

    public int $requestVersion;

    public ?string $fieldKey = null;

    public $upload = null;

    public string $idempotencyKey;

    public function mount(string $requestPublicId, int $requestVersion, ?string $fieldKey = null): void
    {
        $this->requestPublicId = $requestPublicId;
        $this->requestVersion = $requestVersion;
        $this->fieldKey = $fieldKey;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function store(RequestCollaborationQuery $collaboration, UploadRequestAttachment $service): void
    {
        $validated = $this->validate(['upload' => ['required', 'file', 'max:'.max(1, (int) ceil(config('request.files.max_bytes', 10485760) / 1024))]]);
        $request = $collaboration->findVisible($this->requestPublicId, auth('admin')->user());
        Gate::authorize('upload', [RequestAttachment::class, $request]);
        $attachment = $service->handle($request, $validated['upload'], (int) auth('admin')->id(), $this->requestVersion, $this->idempotencyKey, $this->fieldKey);
        $this->requestVersion = $request->refresh()->lock_version;
        $this->reset('upload');
        $this->idempotencyKey = (string) Str::uuid();
        $this->dispatch('request-version-changed', version: $this->requestVersion);
        if ($this->fieldKey !== null && $attachment->scan_status->value === 'clean') {
            $this->dispatch('request-attachment-created', attachmentPublicId: $attachment->public_id, fieldKey: $this->fieldKey, version: $this->requestVersion);
        }
        session()->flash('request_attachment_success', __('Request::request.attachment_added'));
    }

    #[On('request-version-changed')]
    public function updateRequestVersion(int $version): void
    {
        $this->requestVersion = $version;
    }

    public function render(RequestCollaborationQuery $collaboration)
    {
        $request = $collaboration->findVisible($this->requestPublicId, auth('admin')->user());
        Gate::authorize('view', $request);

        return view('Request::livewire.requester.attachment-manager', ['request' => $request, 'attachments' => collect($collaboration->attachments($request))->filter(fn (RequestAttachment $attachment): bool => $this->fieldKey === null ? $attachment->payload_field_key === null : $attachment->payload_field_key === $this->fieldKey)]);
    }
}
