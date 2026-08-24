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

    public string $description = '';

    public string $requesterGuidance = '';

    public int $schemaVersion = 1;

    public array $schemaExtras = [];

    public array $sections = [];

    public string $audiencesJson = '[]';

    public array $stages = [];

    public int $lockVersion = 1;

    public function mount(string $typePublicId): void
    {
        $this->typePublicId = $typePublicId;
        $type = $this->type();
        Gate::authorize('update', $type);
        $draft = $type->activeDraft()->with(['audiences', 'stages'])->firstOrFail();
        $schema = (array) $draft->form_schema_json;

        $this->title = $draft->title;
        $this->description = (string) ($draft->description ?? '');
        $this->requesterGuidance = (string) ($draft->requester_guidance ?? '');
        $this->schemaVersion = max(1, (int) ($schema['schema_version'] ?? 1));
        $this->sections = array_values((array) ($schema['sections'] ?? []));
        $this->schemaExtras = array_diff_key($schema, array_flip(['schema_version', 'sections']));
        $this->audiencesJson = json_encode($draft->audiences->map->only(['actor_type', 'actor_id', 'capability'])->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->stages = $draft->stages->values()->map(fn ($stage): array => [
            'stage_key' => $stage->stage_key,
            'name' => $stage->name,
            'mode' => $stage->mode->value,
            'resolver_key' => $stage->resolver_key,
            'resolver_config_json' => json_encode($stage->resolver_config_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'instructions' => $stage->instructions ?? '',
            'allow_reassignment' => (bool) $stage->allow_reassignment,
        ])->all();
        $this->lockVersion = $type->lock_version;
    }

    public function addSection(): void
    {
        $number = count($this->sections) + 1;
        $this->sections[] = [
            'key' => 'section_'.$number,
            'label' => 'Section '.$number,
            'fields' => [],
        ];
    }

    public function removeSection(int $section): void
    {
        if (! isset($this->sections[$section])) {
            return;
        }

        array_splice($this->sections, $section, 1);
    }

    public function moveSection(int $section, int $direction): void
    {
        $this->moveItem($this->sections, $section, $direction);
    }

    public function addField(int $section): void
    {
        if (! isset($this->sections[$section])) {
            return;
        }

        $fields = array_values((array) ($this->sections[$section]['fields'] ?? []));
        $number = count($fields) + 1;
        $fields[] = [
            'key' => 'field_'.$number,
            'type' => 'text',
            'label' => 'Field '.$number,
            'required' => false,
            'classification' => 'internal',
            'offline_draft' => true,
        ];
        $this->sections[$section]['fields'] = $fields;
    }

    public function removeField(int $section, int $field): void
    {
        if (! isset($this->sections[$section]['fields'][$field])) {
            return;
        }

        $fields = array_values((array) $this->sections[$section]['fields']);
        array_splice($fields, $field, 1);
        $this->sections[$section]['fields'] = $fields;
    }

    public function moveField(int $section, int $field, int $direction): void
    {
        if (! isset($this->sections[$section])) {
            return;
        }

        $fields = array_values((array) ($this->sections[$section]['fields'] ?? []));
        $this->moveItem($fields, $field, $direction);
        $this->sections[$section]['fields'] = $fields;
    }

    public function addStage(): void
    {
        $number = count($this->stages) + 1;
        $this->stages[] = [
            'stage_key' => 'stage_'.$number,
            'name' => 'Stage '.$number,
            'mode' => 'sequential',
            'resolver_key' => 'fixed_user',
            'resolver_config_json' => "{}",
            'instructions' => '',
            'allow_reassignment' => false,
        ];
    }

    public function removeStage(int $stage): void
    {
        if (! isset($this->stages[$stage])) {
            return;
        }

        array_splice($this->stages, $stage, 1);
    }

    public function moveStage(int $stage, int $direction): void
    {
        $this->moveItem($this->stages, $stage, $direction);
    }

    public function save(SaveTypeDraft $service): void
    {
        $type = $this->type();
        Gate::authorize('update', $type);

        $schema = $this->schemaExtras;
        $schema['schema_version'] = $this->schemaVersion;
        $schema['sections'] = array_values($this->sections);

        $stages = [];
        foreach (array_values($this->stages) as $index => $stage) {
            $stage['position'] = $index + 1;
            $stage['resolver_config_json'] = $this->decode((string) ($stage['resolver_config_json'] ?? '{}'), 'stages.'.($index).'.resolver_config_json');
            $stages[] = $stage;
        }

        $service->handle($type, [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'requester_guidance' => $this->requesterGuidance ?: null,
            'form_schema_json' => $schema,
            'audiences' => $this->decode($this->audiencesJson, 'audiencesJson'),
            'stages' => $stages,
        ], (int) auth('admin')->id(), $this->lockVersion);

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

    private function moveItem(array &$items, int $index, int $direction): void
    {
        $items = array_values($items);
        $target = $index + ($direction < 0 ? -1 : 1);
        if (! isset($items[$index], $items[$target])) {
            return;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
    }
}
