<?php

namespace Modules\Request\Livewire\Admin;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Request\Application\Services\PublishTypeVersion;
use Modules\Request\Application\Services\SaveTypeDraft;
use Modules\Request\Models\RequestType;

class TypeDesigner extends Component
{
    public string $typePublicId;

    public string $title = '';

    public string $schemaJson = '';

    public string $audiencesJson = '[]';

    public string $stagesJson = '[]';

    public int $lockVersion = 1;

    public function mount(string $typePublicId): void
    {
        $this->typePublicId = $typePublicId;
        $type = $this->type();
        Gate::authorize('update', $type);
        $draft = $type->activeDraft()->with(['audiences', 'stages'])->firstOrFail();
        $this->title = $draft->title;
        $this->schemaJson = json_encode($draft->form_schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->audiencesJson = json_encode($draft->audiences->map->only(['actor_type', 'actor_id', 'capability'])->all(), JSON_PRETTY_PRINT);
        $this->stagesJson = json_encode($draft->stages->map->only(['stage_key', 'name', 'position', 'mode', 'resolver_key', 'resolver_config_json', 'instructions', 'allow_reassignment'])->all(), JSON_PRETTY_PRINT);
        $this->lockVersion = $type->lock_version;
    }

    public function save(SaveTypeDraft $service): void
    {
        $type = $this->type();
        Gate::authorize('update', $type);
        $service->handle($type, ['title' => $this->title, 'form_schema_json' => $this->decode($this->schemaJson, 'schemaJson'), 'audiences' => $this->decode($this->audiencesJson, 'audiencesJson'), 'stages' => $this->decode($this->stagesJson, 'stagesJson')], (int) auth('admin')->id(), $this->lockVersion);
        $this->lockVersion = $type->refresh()->lock_version;
        session()->flash('request_success', __('Request::request.saved'));
    }

    public function publish(PublishTypeVersion $service): void
    {
        $this->save(app(SaveTypeDraft::class));
        $type = $this->type();
        Gate::authorize('publish', $type);
        $service->handle($type, (int) auth('admin')->id(), $this->lockVersion);
        session()->flash('request_success', __('Request::request.published'));
        $this->redirectRoute('request.admin.types.versions', $type->public_id);
    }

    public function render()
    {
        return view('Request::livewire.admin.type-designer', ['type' => $this->type()]);
    }

    private function type(): RequestType
    {
        return RequestType::query()->where('public_id', $this->typePublicId)->firstOrFail();
    }

    private function decode(string $json, string $field): array
    {
        try {
            $value = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([$field => 'invalid_json']);
        }
        if (! is_array($value)) {
            throw ValidationException::withMessages([$field => 'array_required']);
        }

        return $value;
    }
}
